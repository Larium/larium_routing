<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\RequestInterface;

/**
 *
 */
class Base implements RouteInterface
{
    protected $default_controller = 'default';

    protected $default_action = 'index';

    protected $controller;

    protected $action;

    protected $params = array();

    public function match(RequestInterface $request)
    {
        $array = explode('/', $request->getPath());

        $path = array_filter($array, function($value) {
            if (empty($value)) return false;
            return $value;
        });

        $path  = array_values($path); // reset keys
        $count = count($path);

        switch ($count) {
            case 0:
                $controller = $this->default_controller;
                $action = $this->default_action;
                break;
            case 1:
                $controller = current($path);
                $action = $this->default_action;
                break;
            case 2:
                list($controller, $action) = $path;
                break;
            default:

                $chunk = array_chunk($path, 2);
                foreach ($chunk as $key=>$arr) {
                    if ($key == 0) {
                        $controller = $arr[0];
                        $action = $arr[1];
                        continue;
                    };
                    $value = isset($arr[1]) ? $arr[1] : null;
                    $this->params[$arr[0]] = $value;
                }
                break;
        }

        $this->controller = ucfirst($controller);
        $this->action = $action;

        return true;
    }

    public function getUrl(array $params = array())
    {
        if (empty($params)) {
            return  "/";
        }
        $url = "";
        foreach ($params as $key=>$value) {
            if (!is_numeric($key)) {
                $url .= "/$key/$value";
            } else {
                $url .= "/$value";
            }
        }

        return $url;
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
        return false;
    }

    public function getCallableFunction()
    {
        return false;
    }

    public function call(array $args=array())
    {
        return false;
    }
}
