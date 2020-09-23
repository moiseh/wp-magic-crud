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
        $stmt = [];

        foreach ( $fields as $col => $field ) {
            $ref = '';

            switch($field['type']) {
                case 'one_to_many':
                case 'has_many':
                    break;
                case 'belongs_to':
                    $type = 'INTEGER';
                    $entity = wpmc_get_entity( $field['ref_entity'] );
                    $refTable = $entity->tableName;
                    $ref = "REFERENCES {$refTable}(id)";
                break;
                case 'textarea':
                    $type = 'TEXT';
                break;
                case 'integer':
                    $type = 'INTEGER';
                break;
                default:
                    $type = 'VARCHAR(255)';
                break;
            }

            $null = ( !empty($field['required']) && $field['required'] ) ? ' NOT NULL ' : '';
            $stmt[$col] = "`{$col}` {$type}{$null}{$ref},";
        }

        $sql = "CREATE TABLE {$table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            `user_id` INTEGER REFERENCES users(id),
            " . implode("\n", $stmt) . "
            PRIMARY KEY  (id)
        );";

        // var_dump($sql); exit;

        dbDelta($sql);

        do_action('wpmc_db_creating', $table, $fields);
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
}