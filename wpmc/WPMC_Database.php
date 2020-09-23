<?php
class WPMC_Database {
    private $tableSchema = [];

    public function getTableColumns($table) {
        global $wpdb;

        if ( empty($this->tableSchema[$table]) ) {
            $rows = $wpdb->get_results(  "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table}'", ARRAY_A  );
            foreach ( $rows as $row ) {
                $this->tableSchema[$table][ $row['COLUMN_NAME'] ] = $row;
            }
        }

        return $this->tableSchema[$table];
    }

    public function getTableColumn($table, $column) {
        $cols = $this->getTableColumns($table);
        return $cols[$column];
    }

    public function tableHasColumn($table, $column) {
        $cols = array_keys($this->getTableColumns($table));
        return in_array($column, $cols);
    }

    public function doCreateTable($table, $fields) {
        $fields = apply_filters('wpmc_db_creating_fields', $fields, $table);
        $stmt = [];

        foreach ( $fields as $col => $field ) {
            $dbType = !empty($field['db_type']) ? $field['db_type'] : 'VARCHAR(255)';
            $ref = !empty($field['db_references']) ? $field['db_references'] : '';
            $null = ( !empty($field['required']) && $field['required'] ) ? ' NOT NULL ' : '';
            $stmt[$col] = "`{$col}` {$dbType}{$null}{$ref},";
        }

        $sql = "CREATE TABLE {$table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            `user_id` INTEGER REFERENCES users(id),
            " . implode("\n", $stmt) . "
            PRIMARY KEY  (id)
        );";

        dbDelta($sql);
        do_action('wpmc_db_table_created', $table, $fields);
    }


    public function migrateEntityTables($entities) {
        $versions = (array) get_site_option('wpbc_db_version');

        foreach ( $entities as $key => $entity ) {
            if ( $entity instanceof WPMC_Entity ) {
                $fieldsHash = md5(serialize($entity->fields));

                if ( empty($versions[$key]) || ( $fieldsHash != $versions[$key] ) ) {
                    $versions[$key] = $fieldsHash;
    
                    $this->doCreateTable($entity->tableName, $entity->fields);
                }
            }
        }

        // update db version
        update_option('wpbc_db_version', $versions); 
    }

    public function saveData($tableName, $item) {
        global $wpdb;

        if (empty($item['id'])) {
            $result = $wpdb->insert($tableName, $item);

            if (!is_numeric($result)) {
                throw new Exception(__('Erro ao gravar os dados :(', 'wp-magic-crud'), 'error');
            }

            return $wpdb->insert_id;
        }
        else {
            $result = $wpdb->update($tableName, $item, array('id' => $item['id']));

            if (!is_numeric($result)) {
                throw new Exception(__('Erro ao atualizar os dados :(', 'wp-magic-crud'));
            }

            return $item['id'];
        }
    }

    public function saveEntityData(WPMC_Entity $entity, $item) {
        $item = apply_filters('wpmc_process_save_data', $item, $entity);

        $db = new WPMC_Database();
        $id = $db->saveData($entity->tableName, $item);

        $item['id'] = $id;
        do_action('wpmc_data_saved', $entity, $item);

        return $id;
    }

    public function findByEntityId(WPMC_Entity $entity, $id) {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT * FROM {$entity->tableName} WHERE id = %d", $id);
        $row = $wpdb->get_row($sql, ARRAY_A);

        return apply_filters('wpmc_entity_find', $row, $entity);
    }

    public function buildMainQuery(WPMC_Entity $entity) {

        $qb = wpmc_query();
        $qb->from($entity->tableName);

        $selects = ['id' => "{$entity->tableName}.id"];

        foreach ( $entity->fields as $name => $field ) {
            switch($field['type']) {
                case 'has_many':
                case 'one_to_many':
                break;
                default:
                    $selects[$name] = "{$entity->tableName}.{$name}";
                break;
            }
        }

        $qb->select( apply_filters('wpmc_query_selects', $selects, $qb, $entity) );

        return apply_filters('wpmc_entity_query', $qb, $entity);
    }

    public function buildEntityOptionsList(WPMC_Entity $entity, $ids = array()) {
        global $wpdb;

        $sql = " SELECT id, {$entity->displayField} FROM {$entity->tableName} ";
        if ( !empty($ids) ) {
            $sql .= " WHERE id IN (" . implode(',', $ids) . ")";
        }
        $sql .= " ORDER BY {$entity->defaultOrder }";

        $rows = $wpdb->get_results( $sql, ARRAY_A  );
        $opts = [];
        
        foreach ( $rows as $row ) {
            $opts[ $row['id'] ] = $row[$entity->displayField];
        }

        return $opts;
    }
}