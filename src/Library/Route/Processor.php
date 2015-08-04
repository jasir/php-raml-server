<?php

// namespace Library\Route;

class Processor
{

    public static function process($route, $request)
    {
        $pathinfo = pathinfo($route['path']);
        $method = $pathinfo['basename'];

        return self::$method($route, $request);
    }

    private static function send_query($query)
    {
        return false;
    }

    private static function hello($route, $request) {
        $response = new stdClass();
        $response->message = "hello";
        $response->test = $request->params('test');
        return json_encode($response);
    }



}
