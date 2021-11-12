<?php


namespace src\Api;


use src\Api\Interfaces\AbstractApi;

class ApiFaced
{


    /**
     * @var AbstractApi
     */
    protected $api;

    public function __construct(AbstractApi $api)
    {
        $this->api = $api;
    }


    public function run()
    {

        $response = container()->response;

        $this->api->withResponse($response);

        $this->api->parse();

        $result = $this->api->execute();


        if ($this->api->isError()) {
            return $response->withJson($this->api->errors());
        }

        if (is_object($result)) {

            try {
                $result = (array)$result->toArray();
            } catch (\ErrorException $errorException) {
                return $errorException->getMessage();
            }

        }

        if (!is_array($result)) {
            $result = [$result];
        }

        return $response->withJson($result);
    }

}