<?php


namespace Keperis\Api;


use Keperis\Api\Interfaces\AbstractApi;

class Ajax extends AbstractApi
{

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $controller = $this->controller;


        if (!$controller) {
            return $this->errors();
        }

        $data = $this->request->getParsedBody();

        $result = call_user_func($controller, $data);


        return $result;
    }

    /**
     * @inheritDoc
     */
    public function parse()
    {
        if (!$this->request->isXhr()) {
            return $this->setError('Ajax must by with xhr');

        }

        $param = $this->request->getUri()->getParseQuery();

        $controller = $param['class'] === 'Api' ? $this->createControllerByMethod() : $this->createController();

        if (!$controller) {
            return null;
        }

        $this->controller = $controller;
    }
}