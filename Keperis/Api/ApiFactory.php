<?php


namespace Keperis\Api;


use Keperis\Http\Request;

class ApiFactory
{

    public static function makeByRequest(Request $request)
    {
        if ($request->isXhr()) {
            static::ajax($request);
        }
        return static::api($request);
    }

    public static function ajax(Request $request)
    {
        return new ApiFaced(new Ajax($request));
    }

    public static function api(Request $request = null)
    {
        return new ApiFaced(new Api($request ?: container()->request));
    }
}