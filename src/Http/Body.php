<?php


namespace Keperis\Http;


class Body extends Stream
{

    /**
     * @return static
     */
    public static function getAsRequestBody()
    {
        $stream = fopen('php://temp', 'r+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);

        return new static($stream);
    }

    public static function getAsResponseBody(){
        $stream = fopen('php://output', 'r+');

        return new static($stream);
    }
}