<?php


$_ENV['DB_HOST'] = '127.0.0.1';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'bcclub_crm';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = 'root';
$_ENV['DB_UTC'] = '+2:00';
require_once "vendor/autoload.php";


$builder = \Keperis\Eloquent\DB::getInstance()->getQueryBuilder();


$paginator = new \Keperis\Page\Paginator\Paginator($builder
    ->from('bc_user')
    ->select(['*']));


$elements = $paginator->paginate()->getCollection();

$row = array_map(function ($el) {
    return (array)$el;
}, $elements->toArray());

$table = new \Keperis\Page\Table\Table(['bc_user_id' => [
    'text' => 'id'
]], $row, []);

var_dump($table->render(),$row);exit;


use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$route = new \Symfony\Component\Routing\Route('/test', ['_controller' => 'MyController']);

$routes->add('name', $route);

$context = new \Symfony\Component\Routing\RequestContext('/');

$matcher = new \Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);

$parameters = $matcher->match('/foo');

var_dump('test', $routes);
exit;
