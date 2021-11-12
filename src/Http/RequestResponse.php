<?php


namespace src\Http;


use src\Controller\Controller;

class RequestResponse
{


    /**
     * @param Controller| \Closure $controller
     * @param $method
     * @param Request $request
     * @param Response $response
     * @param $routeArgument
     * @return mixed
     */
    public function __invoke($controller, $method, Request &$request, Response $response, $routeArgument)
    {
        if ($routeArgument) {
            foreach ($routeArgument as $k => $v) {
                $request = $k === 2 && is_array($v) ? $request->withAttribute('argc', $v) : $request->withAttribute($k,
                    $v);
            }
        }
        $routeArgument = $routeArgument[2];
        if ($controller instanceof \Closure) {
            return call_user_func($controller, $request, $response, $routeArgument);
        }

        if (method_exists($controller,
                'getRole') && $controller->getRole() !== null && $controller->getRole() !== true) {
            $role = $controller->getRole() === null || role_check($controller->getRole()) === true;
            if(!$role){
                return $response->withRedirect('/404');
            }
        }

        $callback = call_user_func([
            $controller,
            $method,
        ], $request, $response, $routeArgument);
        return $callback;


    }
}