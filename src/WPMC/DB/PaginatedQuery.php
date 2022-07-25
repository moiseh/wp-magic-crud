<?php

namespace WPMC\DB;

use WPMC\Entity;

class PaginatedQuery
{
    private $searchTerm;
    private $filters = [];
    private $orderBy;
    private $orderMode;
    private $perPage = 10;
    private $pageNumber = 1;
    private $countItems;
    
    public function __construct(private Entity $entity)
    {
    }

    public function fillFromRequest()
    {
        $sortCols = $this->getSortableCols();

        $this->searchTerm = !empty($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $this->filters = $_GET;
        $this->orderBy = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $sortCols)) ? sanitize_text_field($_REQUEST['orderby']) : null;
        $this->orderMode = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field($_REQUEST['order']) : null;
        $this->pageNumber = isset($_REQUEST['paged']) ? intval($_REQUEST['paged']) : 1;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildListingQuery() {
        $entity = $this->entity;
        $entityQuery = new EntityQuery($entity);

        $qb = $entityQuery->buildQueryWithSearch($this->searchTerm, $this->filters);

        return $qb;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function applyQueryPagination(\Illuminate\Database\Query\Builder $qb)
    {
        $perPage = $this->perPage;
        $pageNumber = $this->pageNumber;
        $orderBy = $this->getOrderBy();

        $qb->orderByRaw($orderBy);

        return $qb->forPage($pageNumber, $perPage);

        // return $qb;
    }

    private function getOrderBy()
    {
        $entity = $this->entity;
        $tableName = $entity->getDatabase()->getTableName();
        $entityQuery = new EntityQuery($entity);
        $orderBy = $this->orderBy;
        $orderMode = $this->orderMode;
        
        if ( empty($orderBy) ) {
            $orderBy = $entityQuery->getDefaultOrderCol();
        }

        if ( empty($orderMode) ) {
            $orderMode = $entityQuery->getDefaultOrderMode(); 
        }

        return "{$tableName}.{$orderBy} {$orderMode}";
    }

    private function getPaginatedItems()
    {
        $query = $this->buildListingQuery();
        $pagedQuery = $this->applyQueryPagination($query);
        $items = convertStdToArray($pagedQuery->get());

        return $items;
    }

    public function getFormattedPageItems()
    {
        $entity = $this->entity;
        $fields = $entity->getFieldsObjects();
        $items = $this->getPaginatedItems();

        foreach ( $fields as $field ) {
            $items = $field->formatListTableRows($items);
        }

        return $items;
    }

    private function getSortableCols() {
        $cols = [];
        $fields = $this->entity->fieldsCollection()->getSortableFields();

        foreach ( $fields as $field ) {
            $cols[] = $field->getName();
        }

        return $cols;
    }

    public function getTotalItems() {
        if ( !isset($this->countItems)) {
            $this->countItems = $this->buildListingQuery()->count();
        }

        return $this->countItems;
    }

    public function getTotalPages() {
        return ceil( $this->getTotalItems() / $this->getPerPage() );
    }

    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }
}