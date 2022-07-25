<?php
namespace WPMC\Action;

trait RESTTableActionTrait
{
    private $expose_as_rest;

    public function getRestMethod()
    {
        return 'GET';
    }

    public function getExposeAsRest()
    {
        return $this->expose_as_rest;
    }

    public function setExposeAsRest($expose_as_rest)
    {
        $this->expose_as_rest = $expose_as_rest;
        return $this;
    }

    protected function resttableToArray()
    {
        $arr = [];

        if ( isset($this->expose_as_rest) ) {
            $arr['expose_as_rest'] = $this->getExposeAsRest();
        }
        
        return $arr;
    }
}