<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\Route;

use Larium\Http\RequestInterface;

interface RouteInterface
{
    /**
     * Checks if current Route match the request.
     *
     * @param RequestInterface $request
     *
     * @access public
     * @return void
     */
    public function match(RequestInterface $request);

    /**
     * Generates a url path based on current Route and user params.
     *
     * @param array $params
     *
     * @access public
     * @return void
     */
    public function getUrl();

    public function getParams();

    public function getController();

    public function getAction();

    public function isCallable();

    public function getCallableFunction();

    public function call(array $args=array());
}
