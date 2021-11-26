<?php


$_ENV['DB_HOST'] = '127.0.0.1';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'bcclub_crm';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = 'root';
$_ENV['DB_UTC'] = '+2:00';
require_once "vendor/autoload.php";




$builder = \Keperis\Eloquent\DB::getInstance()->getQueryBuilder();

$pagination = $builder
    ->from('bc_user')
    ->select(['*'])
    ->paginate(2);



var_dump($pagination[0]);exit;



use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$route = new \Symfony\Component\Routing\Route('/test', ['_controller' => 'MyController']);

$routes->add('name', $route);

$context = new \Symfony\Component\Routing\RequestContext('/');

$matcher = new \Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);

$parameters = $matcher->match('/foo');

var_dump('test', $routes);
exit;
