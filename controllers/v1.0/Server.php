<?php

namespace App\Api;

use RamlServer\Controller;

class Server extends Controller
{
    /**
     * A test endpoint
     * /hello?test={test}
     * @return object
     */
    public function getHello ()
    {
        $response = new \stdClass();
        $response->message = "hello";
        $response->test = $this->request->params('test');
        return $response;
    }

    // Begin API methods
    public function getCorrection ()
    {
        $this->response->setStatus(501);
        $response = new \stdClass();
        $response->message = 'Not implemented (but called)';
        return $response;
    }


}
