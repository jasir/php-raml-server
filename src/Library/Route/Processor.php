<?php

// namespace Library\Route;

class Processor
{


    public static function process($route, $request)
    {
        $pathinfo = pathinfo ($route['path']);

        //trim the leading slash
        $dirname = ltrim ($pathinfo['dirname'], '/');
        //replace slashes with underscores and append basename
        $method = $dirname ? str_replace("/", "_", $dirname) . "_" . $pathinfo['basename'] : $pathinfo['basename'];

        // execute the method
        return self::$method($route, $request);

    }

    private static function returnExample($route, $request)
    {
        $responses = $route['method']->getResponses();
        foreach ($responses as $response) {
            try {
                return $response->getBodyByType('application/json')->getExample();
            } catch (Exception $e) {
                
            }
        }
    }


    // Begin API methods

    private static function correction ($route, $request)
    {
        return self::returnExample($route, $request);
    }

    private static function hello ($route, $request) 
    {
        $response = new stdClass();
        $response->message = "hello";
        $response->test = $request->params('test');
        return json_encode($response);
    }



}
