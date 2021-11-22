<?php

require_once "vendor/autoload.php";


use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$route = new \Symfony\Component\Routing\Route('/test', ['_controller' => 'MyController']);

$routes->add('name', $route);

$context = new \Symfony\Component\Routing\RequestContext('/');

$matcher = new \Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);

$parameters = $matcher->match('/foo');

var_dump('test', $routes);
exit;