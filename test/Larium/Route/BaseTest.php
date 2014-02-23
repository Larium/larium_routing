<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\Request;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchDefault()
    {
        $route = new Base();
        $url = "http://www.example.com";

        $request = new Request($url);

        $this->assertTrue($route->match($request));
        $this->assertEquals('Default', $route->getController());
        $this->assertEquals('index', $route->getAction());
        $this->assertEquals(array(), $route->getParams());
    }

    public function testMatch()
    {
        $route = new Base();

        $url = "http://www.example.com/controller/show/id/1/slug/abcd";
        $request = new Request($url);

        $this->assertTrue($route->match($request));

        $params = array(
            'id' => 1,
            'slug' => 'abcd'
        );

        $this->assertEquals($params, $route->getParams());
        $this->assertEquals('Controller', $route->getController());
        $this->assertEquals('show', $route->getAction());
    }

    public function testGetUrl()
    {
        $route = new Base();

        $url = $route->getUrl(
            array(
                'products',
                'show',
                'id' => 1,
                'slug' => 'test'
            )
        );

        $this->assertEquals("/products/show/id/1/slug/test", $url);
    }
}
