<?php

use Slim\Helper\Set;

class Methods
{

    private $appContainer;
    private $route;

    public function __construct(Set $appContainer, array $route)
    {
        $this->appContainer = $appContainer;
        $this->route = $route;
    }

    // Begin API methods
    public function get_correction ()
    {
        $this->appContainer['response']->setStatus(501);
    }

    public function patch_correction ()
    {
        $this->appContainer['response']->setStatus(501);
    }

    public function post_correction ()
    {
        $this->appContainer['response']->setStatus(501);
    }

    public function get_correction_details ()
    {
        $this->appContainer['response']->setStatus(501);
    }
    public function post_correction_details ()
    {
        $this->appContainer['response']->setStatus(501);
    }

    public function get_hello () 
    {
        $response = new stdClass();
        $response->message = "hello";
        $response->test = $this->appContainer['request']->params('test');
        return $response;
    }



}
