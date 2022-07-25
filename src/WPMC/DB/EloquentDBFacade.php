<?php

namespace WPMC\DB;

/**
 * @see \Illuminate\Database\DatabaseManager
 * @see \Illuminate\Database\Connection
 */
class EloquentDBFacade extends \WeDevs\ORM\Eloquent\Facades\DB
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EloquentDatabase::instance();
    }
}