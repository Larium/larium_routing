<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\RequestInterface;
use Larium\Http\Request;

/**
 *
 */
class Route implements RouteInterface
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var array
     */
    protected $route;

    protected $map;

    protected $method;

    protected $prefix;

    protected $controller;

    protected $action;

    private $matches;

    /**
     *
     * @param string $pattern The pattern to match.
     * @param mixed  $route   An array with controller, action keys, a Closure
     *                        or a string in format 'controllerClass::actionName'
     * @param array  $map     An array of mapped values when a regular
     *                               expression pattern used
     * @param string $prefix  A namespace to use for controller class
     *                               when ':controller' used in pattern.
     * @param string $method  The request method to match.
     *
     * @access public
     * @return void
     */
    public function __construct(
        $pattern,
        $route,
        array $map = array(),
        $prefix = null,
        $method = Request::GET_METHOD
    ) {
        $this->pattern  = $pattern;
        $this->route    = $route;
        $this->map      = $map;
        $this->prefix   = $prefix ? rtrim($prefix, '\\') . '\\' : null;
        $this->method   = $method;
    }

    public function match(RequestInterface $request)
    {
        if ($this->method !== $request->getMethod()) {

            return false;
        }

        $regex_pattern = $this->create_regex_pattern();

        if (preg_match("/^" . $regex_pattern . "$/", $request->getPath(), $this->matches)) {

            $this->route($request);

            return true;
        }

        return false;
    }

    public function getUrl(array $params = array())
    {
        if (empty($this->map)) {

            if (empty($params)) {

                return $this->pattern;
            }

            //format :controller value for params
            if (array_key_exists(':controller', $params)) {

                // converts php namespaces to url path
                $params[':controller'] = strtolower(str_replace('\\','/',$params[':controller']));
                // pops last element as the controller base name.
                $a = explode('/', $params[':controller']);
                $params[':controller'] = array_pop($a);
            }

            // checks if url pattern has any placeholder(:) for controller or
            // action
            preg_match_all('/:[\w]+/', $this->pattern, $m);
            $want = $m[0];


            // if has placeholders(:) then intersect them with user params to
            // find which params should be appended as query string.
            $q = null;
            if (!empty($want)) {
                $extra = array_flip(array_diff(array_keys($params), $want));
                $query = array_intersect_key($params, $extra);
                if (!empty($query)) {
                    $q = '?' . http_build_query($query);
                }

                $wants = (array_intersect_key($params, array_flip($want)));

                return str_replace(array_keys($wants), $wants, $this->pattern) . $q;
            } else {
                $q = '?' . http_build_query($params);

                return str_replace(array_keys($params), $params, $this->pattern) . $q;
            }


        } else {

            $patterns = array_filter(explode('/', $this->pattern));

            $map = $this->map;

            array_walk($patterns, function(&$value, $key) use (&$map, &$params) {
                if (preg_match("/[\/\(\)\{\}\[\]\\\\]+/", $value)) {
                    $key = array_shift($map);
                    $value = $params[$key];
                    unset($params[$key]);
                }
            });

            $q = null;
            if (!empty($params)) {
                $q = "?" . http_build_query($params);
            }
            return "/" . implode('/', $patterns) .$q;
        }
    }

    public function getPrefix()
    {
        return rtrim($this->prefix, '\\');
    }

    public function route($request)
    {
        $path = array_values(array_filter(explode('/', $request->getPath()), 'strlen'));

        list($controller, $action) = $this->prepare_controller_action();

        // Mapped url routing
        if (!empty($this->route) && empty($this->map)) {

            $pattern = array_values(
                array_filter(explode('/', $this->pattern), 'strlen')
            );

            // Map ":value" to corresponding values from request url path.
            array_walk($pattern, function(&$val){
                if (  strpos($val, ":") === 0
                    && !in_array($val, array(':controller', ':action'))
                ) {
                    $val = substr($val, 1);
                }
            });

            $combine = array_combine($pattern, $path);
            $params = array_filter($combine, function($value) use (&$combine) {
                $return = true;
                if (key($combine) == $value) {
                    $return = false;
                }
                next($combine);

                return $return;
            });

            // Set controller from ":controller" if exists
            if (array_key_exists(':controller', $params)) {
                $controller = $params[':controller'];
                unset($params[':controller']);
            }

            // Set action from ":action" if exists
            if (array_key_exists(':action', $params)) {
                $action = $params[':action'];
                unset($params[':action']);
            }

            // Last check for controller and action if they are null.
            // Set the first part of path as controller and the second path as
            // action.
            if (null === $controller) {
                $controller = count($path) > 0
                    ? $path[0]
                    : null;
            }

            if (null === $action) {
                $action = count($path) > 1
                    ? $path[1]
                    : null;
            }

            $this->params = $params;

            // RegExp url routing
        } elseif (!empty($this->map)) {

            if (empty($this->route)) {
                throw new \InvalidArgumentException("Router\Route::route argument is not defined.");
            }

            array_shift($this->matches);

            $this->params = array_combine($this->map, $this->matches);
        }

        if (!($this->route instanceof \Closure)) {
            if (null === $controller) {
                throw new \InvalidArgumentException(
                    "Controller not defined in Route"
                );
            }

            if (null === $action) {
                throw new \InvalidArgumentException(
                    "Action not defined in Route"
                );
            }

            $this->controller = $this->prefix . ucfirst($controller);
            $this->action = $action;
        }
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getParam($name)
    {
        return array_key_exists($name, $this->params)
            ? $this->params[$name]
            : null;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function isCallable()
    {
        return $this->route instanceof \Closure;
    }

    public function getCallableFunction()
    {
        if ($this->isCallable()) {
            return $this->route;
        }
    }

    public function call(array $args=array())
    {
        $function = new \ReflectionFunction($this->route);

        return $function->invokeArgs($args);
    }

    private function prepare_controller_action()
    {
        if (is_string($this->route)) {
            list($controller, $action) = explode('::', $this->route);
            $this->route = array('controller'=>$controller, 'action'=>$action);
        }
        // Set controller for route array
        $controller = array_key_exists('controller', $this->route)
            ? $this->route['controller']
            : null;

        // Set action for route array
        $action = array_key_exists('action', $this->route)
            ? $this->route['action']
            : null;

        return array($controller, $action);
    }

    private function create_regex_pattern()
    {
        //if (empty($this->map)) {
            $regex_pattern = preg_replace("/:[a-z]+/", "([^/]+)", $this->pattern);
        //} else {
            //$regex_pattern = $this->pattern;
        //}

        $regex_pattern =  str_replace('/','\/', $regex_pattern);
        $regex_pattern =  str_replace('.','\.', $regex_pattern);

        return $regex_pattern;
    }
}
