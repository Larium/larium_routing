<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\RequestInterface;

interface RouterInterface
{
    /**
     * Register a route
     *
     * @param RouteInterface $route
     *
     * @return void
     */
    public function registerRoute(RouteInterface $route, $priority);

    /**
     * Returns an array of registered routes
     *
     * @return array
     */
    public function getRoutes();

    /**
     * Iterates through register routes and returns the matching route for the
     * given request object.
     *
     * @param RequestInterface $request
     * @param Responsenterface $response
     *
     * @return RouteInterface
     */
    public function route(RequestInterface $request);
}
