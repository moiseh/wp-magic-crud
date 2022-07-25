<?php

namespace WPMC\Entity;


class EntityRest
{
    /**
     * @var bool
     * @required
     */
    private $expose_as_rest = false;

    public function getExposeAsRest()
    {
        return $this->expose_as_rest;
    }

    public function setExposeAsRest(bool $expose_as_rest)
    {
        $this->expose_as_rest = $expose_as_rest;
        return $this;
    }
}