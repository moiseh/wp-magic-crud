<?php

namespace WPMC\DB;

use Illuminate\Database\Query\Builder;

class EloquentDatabase extends \WeDevs\ORM\Eloquent\Database
{
    /**
     * Initializes the Database class
     *
     * @return \WeDevs\ORM\Eloquent\Database
     */
    public static function instance()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function table($table)
    {
        $processor = $this->getPostProcessor();
        $query = new Builder($this, $this->getQueryGrammar(), $processor);

        return $query->from($table);
    }

    public function queryWithoutTable()
    {
        $processor = $this->getPostProcessor();
        $query = new Builder($this, $this->getQueryGrammar(), $processor);

        return $query;
    }
}
