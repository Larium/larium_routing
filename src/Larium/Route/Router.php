<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\RequestInterface;
use Larium\Http\ResponseInterface;
use Larium\Route\Base;

class Router implements RouterInterface
{
    protected $routes;

    protected $named_routes = array();

    protected $match_route;

    public function __construct(array $routes=array())
    {
        $this->routes = new \SplPriorityQueue();
        $this->routes->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);

        // Register Route\Base by default
        $this->registerRoute(new Base(), -1, 'default');
        $this->setRoutes($routes);
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoute(RouteInterface $route, $priority=null, $name=null)
    {
        if ($name) {
            $this->named_routes[$name] = $route;
        }
        $priority = $priority ?: $this->routes->count();
        $this->routes->insert($route, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        $routes = array();
        $this->routes->top();

        foreach ($this->routes as $route) {
            $routes[] = $route['data'];
        }

        return $routes;
    }

    public function setRoutes(array $routes)
    {
        foreach ($routes as $priority => $route) {
            $this->registerRoute($route, $priority);
        }
    }

    public function getMatchRoute()
    {
        return $this->match_route;
    }

    /**
     * {@inheritdoc}
     */
    public function route(RequestInterface $request)
    {
        $this->routes->top();

        while($this->routes->valid()) {
            $current = $this->routes->current();
            $route = $current['data'];
            if ($route->match($request)) {
                $this->match_route = $route;
                return $route;
                break;
            }
            $this->routes->next();
        }

        return false;
    }

    public function createUrl($name, array $params = array())
    {
        if (array_key_exists($name, $this->named_routes)) {
            return $this->named_routes[$name]->getUrl($params);
        }
    }

    static public function loadFromYaml($filepath)
    {
        $routes = yaml_parse_file($filepath);

        return static::load($routes);

    }

    static public function loadFromArray($filepath)
    {
        $routes = include($filepath);

        return static::load($routes);

    }

    static private function load(array $routes)
    {
        $defaults = array(
            'pattern'   => null,
            'route'     => array(),
            'map'       => array(),
            'prefix'    => null,
            'method'    => RequestInterface::GET_METHOD
        );

        $route_instances = array();

        foreach ($routes as $name => $options) {
            $params = array_merge($defaults, $options);
            $route = new Route(
                $params['pattern'],
                $params['route'],
                $params['map'],
                $params['prefix'],
                $params['method']
            );

            $route_instances[] = $route;
        }

        return new self($route_instances);
    }
}
