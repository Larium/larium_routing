<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\Request;

class RouteTest extends \PHPUnit_Framework_TestCase
{

    public function testMatchWithRoute()
    {
        $route = new Route(
            '/:controller/:id',
            array(
                'action' => 'show'
            )
        );

        $url = "http://example.com/products/2";
        $request = new Request($url);
        $route->match($request);

        $this->assertEquals('show', $route->getAction());

        $this->assertEquals('Products', $route->getController());


        $this->assertEquals('2', $route->getParam('id'));

        $params = array(
            ':controller' => 'products',
            ':id' => 2
        );

        $this->assertEquals('/products/2', $route->getUrl($params));
    }

    public function testMatchWithRouteAndMap()
    {
        $route = new Route(
            '/posts/([0-9]{4})/([0-9]{2})/([0-9]{2})/([a-zA-z-_0-9]+)',
            array(
                'controller' => 'posts',
                'action' => 'show'
            ),
            array(
                'year',
                'month',
                'day',
                'slug'
            )
        );

        $url = "http://example.com/posts/2012/08/15/test-post";
        $request = new Request($url);
        $match = $route->match($request);

        $this->assertTrue($match);
        $this->assertEquals('Posts', $route->getController());
        $this->assertEquals('show', $route->getAction());

        $this->assertEquals(2012, $route->getParam('year'));
        $this->assertEquals('08', $route->getParam('month'));
        $this->assertEquals(15, $route->getParam('day'));
        $this->assertEquals('test-post', $route->getParam('slug'));

        $params = array(
            'year'=> 2012,
            'month' => '09',
            'day' => '12',
            'slug' => 'test'
        );

        $this->assertEquals('/posts/2012/09/12/test', $route->getUrl($params));
    }

    public function testMatchRegExGetUrl()
    {
        $route = new Route(
            '/posts/([0-9]{4})/edit/(\d+)',
            array(
                'controller' => 'posts',
                'action' => 'edit'
            ),
            array(
                'year',
                'id',
            )
        );

        $url = "http://example.com/posts/2012/edit/15";
        $request = new Request($url);
        $match = $route->match($request);

        $this->assertTrue($match);

        $params = array(
            'year'=> 2012,
            'id' => '12',
        );
        $this->assertEquals('/posts/2012/edit/12', $route->getUrl($params));
    }

    public function testGetRegExUrlWithQuery()
    {
        $route = new Route(
            '/posts/([0-9]{4})/edit/(\d+)',
            array(
                'controller' => 'posts',
                'action' => 'edit'
            ),
            array(
                'year',
                'id',
            )
        );

        $params = array(
            'year'=> 2012,
            'id'  => '12',
            'foo' => 'bar'
        );

        $this->assertEquals('/posts/2012/edit/12?foo=bar', $route->getUrl($params));
    }

    public function testGetRouteUrlWithQuery()
    {

        $route = new Route(
            '/:controller/edit/:id',
            array(
                'action' => 'show'
            )
        );

        $params = array(
            ':controller'=> 'products',
            ':id'  => '12',
            'foo' => 'bar'
        );

        $this->assertEquals('/products/edit/12?foo=bar', $route->getUrl($params));
    }

    public function testGetRouteUrlWithQueryWithoutParams()
    {

        $route = new Route(
            '/edit',
            array(
                'action' => 'show'
            )
        );

        $params = array(
            'foo' => 'bar'
        );

        $this->assertEquals('/edit?foo=bar', $route->getUrl($params));
    }

    public function testMatchWithPrefix()
    {
        $route = new Route(
            '/admin/:controller/edit/:id',
            array(
                'action' => 'show'
            ),
            array(),
            'Admin'
        );

        $request = new Request('http://www.example.com/admin/products/edit/12');

        $match = $route->match($request);

        $this->assertTrue($match);

        $this->assertEquals('Admin\\Products', $route->getController());

        $route = new Route(
            '/admin/panel/:controller/edit/:id',
            array(
                'action' => 'show'
            ),
            array(),
            'Admin\\Panel'
        );

        $request = new Request('http://www.example.com/admin/panel/products/edit/12');

        $match = $route->match($request);

        $this->assertTrue($match);

        $this->assertEquals('Admin\\Panel\\Products', $route->getController());
    }

    public function testGetUrlWithPrefix()
    {

        $route = new Route(
            '/admin/:controller/edit/:id',
            array(
                'action' => 'show'
            ),
            array(),
            'Admin'
        );

        $params = array(
            ':controller'=> 'Admin\\Products',
            ':id' => 12
        );

        $route = new Route(
            '/admin/panel/:controller/edit/:id',
            array(
                'action' => 'show'
            ),
            array(),
            'Admin\\Panel'
        );

        $params = array(
            ':controller'=> 'Admin\\Panel\\Products',
            ':id' => 12
        );

        $this->assertEquals('/admin/panel/products/edit/12', $route->getUrl($params));
    }

    public function testCallableRoute()
    {
        $route = new Route(
            '/admin/products/edit/:id',
            function($id, $request) {
                $buffer = get_class($request);
                $buffer .= "_$id";
                return $buffer;
            }
        );


        $request = new Request('http://www.example.com/admin/products/edit/12');

        $route->route($request);

        $args = array_merge($route->getParams(), array($request));

        $this->assertEquals('Larium\Http\Request_12', $route->call($args));

    }

    public function testStringRoute()
    {
        $route = new Route(
            '/products/:slug',
            'ProductsController::showAction'
        );

        $request = new Request('http://www.example.com/products/t-shirt');

        $match = $route->match($request);

        $this->assertTrue($match);

        $this->assertEquals('ProductsController', $route->getController());
        $this->assertEquals('showAction', $route->getAction());

    }

    public function testRouteActionOnly()
    {
        $route = new Route(
            '/products/:slug',
            array(
                'action' => 'Action\productShowAction'
            )
        );

        $request = new Request('http://www.example.com/products/t-shirt');

        $match = $route->match($request);

        $this->assertTrue($match);

        $this->assertEquals('Action\productShowAction', $route->getAction());
    }
}
