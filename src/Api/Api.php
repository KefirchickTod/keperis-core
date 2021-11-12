<?php


namespace src\Api;


use src\Api\Interfaces\AbstractApi;

class Api extends AbstractApi
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
        if ($this->request->isXhr()) {
            return $this->setError('Get Xhr header in request for api system');

        }


        $controller = $this->createController();

        if (!$controller) {
            return null;
        }

        $this->controller = $controller;


    }


}