<?php


namespace Keperis\Api\Interfaces;


use App\Api\CreateApiFactory;
use Keperis\Controller\Api\ApiController;
use Keperis\Http\Request;
use Keperis\Interfaces\ResponseInterface;

abstract class AbstractApi
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array|null
     */
    protected $errors;

    /**
     * @var ResponseInterface
     */
    protected $response;


    protected $controller;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function withResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return mixed
     */
    public abstract function execute();

    /**
     * @return mixed
     * Parse request and create controller
     */
    public abstract function parse();

    /**
     * @return bool
     */
    public function isError()
    {
        if (!is_null($this->errors)) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return ['error' => $this->errors];
    }

    protected function createControllerByMethod()
    {
        $param = $this->request->getUri()->getParseQuery();

        if (!array_key_exists('method', $param)) {
            $this->setError("Undefined method in uri");
            return null;
        }

        $method = $param['method'];

        $api = include_once API_STRUCTURE_DIR;

        if (!is_array($api)) {
            $this->setError("Empty api file in config");
            return null;
        }

        $controller = null;
        foreach ($api as $class => $value) {
            if(in_array($method, $value)){
                $controller = $class;
                break;
            }
        }



        if (is_null($controller)) {
            $this->setError("Cant find method {$method} in api array");
            return null;
        }


        $controller = new $controller;

        if (!method_exists($controller, $method)) {
            $method = $method . 'Api';
        }

        $run = function ($data) use ($method, $controller) {
            return call_user_func([$controller, $method], $data);
        };
        return $run;


    }

    /**
     * @param $value
     * @param string|null $key
     * @return null
     */
    protected function setError($value, string $key = null)
    {

        error_log($value);

        if (!$key) {
            $this->errors[] = $value;
            return null;
        }
        $this->errors[$key] = $value;
        return null;
    }

    /**
     * @return \Closure
     */
    protected function createController()
    {
        $param = $this->request->getUri()->getParseQuery();

        if (!array_key_exists('class', $param) || !array_key_exists('method', $param)) {
            $this->setError("Cant read class or method from uri");
            return null;
        }
        $controller = CreateApiFactory::make($param['class']);
        $method = $param['method'];


        if (!($controller instanceof ApiController)) {
            $this->setError('Controller not instance ApiController');
            return null;
        }


        if (!method_exists($controller, $method)) {
            if (!method_exists($controller, $method . 'Api')) {
                $this->setError("Cant find method $method in " . get_class($controller));
                return null;

            }

            $method = $method . 'Api';
        }

        $run = function ($data) use ($method, $controller) {
            return call_user_func([$controller, $method], $data);
        };
        return $run;

    }
}
