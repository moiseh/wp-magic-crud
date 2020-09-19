<?php

class WPMC_Database {
    private $tableSchema = [];

    public function init_hooks() {
        // add_filter('wpsc_listing_query', array($this, 'listingQueryAlter'), 5, 2);
    }

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
        global $wpdb;
        global $wpsc_entities;
        $stmt = [];

        foreach ( $fields as $col => $field ) {
            $ref = '';

            switch($field['type']) {
                case 'textarea':
                    $type = 'TEXT';
                break;
                case 'integer':
                    $type = 'INTEGER';
                break;
                case 'belongs_to':
                    $type = 'INTEGER';
                    $entity = $wpsc_entities[ $field['ref_entity'] ];
                    $refTable = $entity->tableName;
                    $ref = "REFERENCES {$refTable}(id)";
                break;
                case 'has_many':
                    continue;
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
    }


    public function migrateEntityTables($entities) {
        $versions = (array) get_site_option('wpbc_db_version');

        foreach ( $entities as $key => $entity ) {
            $fieldsHash = md5(serialize($entity->fields));

            if ( empty($versions[$key]) || ( $fieldsHash != $versions[$key] ) ) {
                $versions[$key] = $fieldsHash;
                $entity->install();
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
                throw new Exception(__('Erro ao gravar os dados :(', 'wpbc'), 'error');
            }

            return $wpdb->insert_id;
        }
        else {
            $result = $wpdb->update($tableName, $item, array('id' => $item['id']));

            if (!is_numeric($result)) {
                throw new Exception(__('Erro ao atualizar os dados :(', 'wpbc'));
            }

            return $item['id'];
        }
    }

    public function arrayToSql($sql = array()) {
        $sqlString = '';

        foreach ( array_filter($sql) as $statement => $query ) {
            if ( $statement == 'where' && is_array($query) ) {
                $query = implode(' AND ', $query);
            }

            $sqlString .= strtoupper($statement) . " {$query} ";
        }

        return $sqlString;
    }

    public function buildMainQuery(WPMC_Entity $entity) {

        $qb = qbuilder();
        $qb->select('*');
        $qb->from($entity->tableName);

        if ( !empty($entity->restrictLogged) ) { //  && !$this->is_admin()
            $qb->where("{$entity->tableName}.{$entity->restrictLogged}", '=', get_current_user_id()); 
        }

        return $qb;
    }

    public function buildListingQuery(WPMC_Entity $entity) {
        $perPage = $entity->get_per_page();
        $sortCols = array_keys($entity->get_sortable_columns());
        $sortableFields = array_keys($entity->get_sortable_fields());
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderBy = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $sortCols)) ? $_REQUEST['orderby'] : $entity->defaultOrder;
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
        $search = ( !empty($_REQUEST['s']) && $entity->is_listing() ) ? sanitize_text_field($_REQUEST['s']) : '';

        $qb = $this->buildMainQuery($entity);

        if ( !empty($search) ) {
            $qb->search($sortableFields, $search);
        }

        $qb->orderBy($orderBy, $order);
        $qb->limit($perPage);
        $qb->offset($paged);

        return $qb;
    }
}
