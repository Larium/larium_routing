<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

//Dependecy
require_once 'larium_http/autoload.php';

require_once 'ClassMap.php';

$classes = array(
    'Larium\\Route\\RouterInterface' => 'Larium/Route/RouterInterface.php',
    'Larium\\Route\\Router' => 'Larium/Route/Router.php',
    'Larium\\Route\\RouterInterface' => 'Larium/Route/RouterInterface.php',
    'Larium\\Route\\RouteInterface' => 'Larium/Route/RouteInterface.php',
    'Larium\\Route\\Route' => 'Larium/Route/Route.php',
    'Larium\\Route\\Base' => 'Larium/Route/Base.php',
);

ClassMap::load(__DIR__ . "/src/", $classes)->register();
