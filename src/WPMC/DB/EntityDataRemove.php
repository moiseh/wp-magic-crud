<?php

namespace WPMC\DB;

use Exception;
use WPMC\Entity;

class EntityDataRemove
{
    public function __construct(private Entity $entity)
    {
    }

    public function delete($ids) {
        global $wpdb;

        $entity = $this->entity;
        $ids = (array) apply_filters('wpmc_before_delete_ids', $ids, $this);
        $table = $entity->getDatabase()->getTableName();
        $pkey = $entity->getDatabase()->getPrimaryKey();

        foreach ( $ids as $id ) {
            $result = $wpdb->delete($table, [$pkey => $id]);
            psCheckDbError($result);
        }
    }
}