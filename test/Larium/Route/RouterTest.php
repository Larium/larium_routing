<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\Request;
use Larium\Http\Response;

class RouterTest extends \PHPUnit_Framework_TestCase
{

    protected $routes;

    public function setUp()
    {
        $this->routes = array(
            new Route(
                '/products/:slug',
                array(
                    'action' => 'show'
                )
            ),
            new Route(
                '/products/:id/edit',
                array(
                    'action' => 'edit'
                )
            ),
            new Route(
                '/products',
                array(
                    'action' => 'index'
                )
            ),
            new Route(
                '/admin/:controller/:id/edit',
                array(
                    'action' => 'edit'
                ),
                array(),
                'Admin\\'
            ),
            new Route(
                '/admin/panel/:controller/:id/edit',
                array(
                    'action' => 'edit'
                ),
                array(),
                'Admin\\Panel'
            )
        );
    }

    public function testCreateRouter()
    {
        $router = new Router($this->routes);

        $this->assertEquals(6, count($router->getRoutes()));
    }

    public function testRouteRouter()
    {

        $router = new Router($this->routes);
        $request = new Request('http://www.example.com/products/t-shirt');
        $response = new Response();

        $router->route($request, $response);

        $this->assertEquals($this->routes[0], $router->getMatchRoute());
        $this->assertEquals(200, $response->getStatus());

        // url that does not match with any given route, fallbacks to Route\Base;
        $router = new Router($this->routes);
        $request = new Request('http://www.example.com/products/apply/t-shirt');
        $response = new Response();

        $match = $router->route($request, $response);

        $this->assertInstanceOf('Larium\\Route\\Base', $router->getMatchRoute());
        $this->assertEquals('Products', $match->getController());
        $this->assertEquals('apply', $match->getAction());

    }

    public function testPriorityRoutes()
    {
        $route_1 = new Route(
            '/products/:id',
            array(
                'action' => 'index'
            )
        );

        $route_2 = new Route(
            '/products/:slug',
            array(
                'action' => 'show'
            )
        );

        $router = new Router();
        $request = new Request('http://www.example.com/products/t-shirt');
        $response = new Response();

        $router->registerRoute($route_1, 1);
        $router->registerRoute($route_2, 2); // Highest priority match first;

        $match = $router->route($request, $response);

        $this->assertEquals($route_2, $match);
    }

    public function testLoadFromYaml()
    {
        $router = Router::loadFromYaml(__DIR__ . '/../../routing.yml');

        $request = new Request('http://www.example.com/products/t-shirt');
        $response = new Response();

        $router->route($request, $response);

        $this->assertEquals(200, $response->getStatus());

        // url that does not match with any given route, fallbacks to Route\Base;
        $router = Router::loadFromYaml(__DIR__ . '/../../routing.yml');
        $request = new Request('http://www.example.com/products/apply/t-shirt');
        $response = new Response();

        $match = $router->route($request, $response);

        $this->assertInstanceOf('Larium\\Route\\Base', $router->getMatchRoute());
        $this->assertEquals('Products', $match->getController());
        $this->assertEquals('apply', $match->getAction());
    }
}
