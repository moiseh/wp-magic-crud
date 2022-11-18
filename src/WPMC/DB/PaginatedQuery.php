<?php

namespace WPMC\DB;

use WPMC\Entity;

class PaginatedQuery
{
    private $searchTerm;
    private $filters = [];
    private $orderBy;
    private $orderMode;
    private $perPage = 15;
    private $pageNumber = 1;
    private $countItems;
    
    public function __construct(private Entity $entity)
    {
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildListingQuery() {
        $entity = $this->entity;
        $entityQuery = new EntityQuery($entity);
        $search = $this->getSearchTerm();
        $filters = $this->getFilters();

        $qb = $entityQuery->buildQueryWithSearch($search, $filters);

        return $qb;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function applyQueryPagination(\Illuminate\Database\Query\Builder $qb)
    {
        $perPage = $this->getPerPage();
        $pageNumber = $this->getPageNumber();
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
        $orderMode = $this->getOrderMode();
        $sortCols = $this->getSortableCols();

        if (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $sortCols)) {
            $orderBy = sanitize_text_field($_REQUEST['orderby']);
        }

        if ( empty($orderBy) ) {
            $orderBy = $entityQuery->getDefaultOrderWithoutMode();
        }

        if ( empty($orderMode) ) {
            $orderMode = $entityQuery->getDefaultOrderMode(); 
        }

        return "{$tableName}.{$orderBy} {$orderMode}";
    }

    private function getPaginatedItems()
    {
        $query = $this->buildListingQuery();
        // $query->whereRaw('YEAR(date) = 2022');

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
        if ( isset($_REQUEST['perpage']) ) {
            return min( intval($_REQUEST['perpage']), 500 );
        }

        return $this->perPage;
    }

    public function getPageNumber()
    {
        if ( isset($_REQUEST['paged']) ) {
            return intval($_REQUEST['paged']);
        }

        return $this->pageNumber;
    }

    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    public function getSearchTerm()
    {
        if ( !empty($_REQUEST['s']) ) {
            return sanitize_text_field($_REQUEST['s']);
        }

        return $this->searchTerm;
    }

    public function getOrderMode()
    {
        if ( isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc')) ) {
            return sanitize_text_field($_REQUEST['order']);
        }

        return $this->orderMode;
    }

    public function getFilters()
    {
        if ( empty($this->filters) ) {
            $this->filters = $_GET;
        }

        return $this->filters;
    }
}