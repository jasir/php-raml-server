<?php


class Example extends MethodsBase
{

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

}
