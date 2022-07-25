<?php

namespace WPMC\Entity;

use Exception;
use JsonMapper;
use WPMC\Entity;

class EntityLoader
{
    public function __construct()
    {
    }

    public function loadEntityObjects()
    {
        $cacheTime = apply_filters('wpmc_entity_cache_time', 0);
        $entityObjects = [];

        // try read from objects cache
        if ( $cacheTime > 0 ) {
            $entityObjects = get_transient('wpmc_cache_entities') ?: [];
        }

        if ( empty($entityObjects) ) {
            $entitiesData = $this->loadEntitiesData();

            foreach ( $entitiesData as $identifier => $options ) {
                $entityObjects[$identifier] = $this->mapEntity($identifier, $options);
            }

            // write temporary entities cache
            if ( $cacheTime > 0 ) {
                set_transient('wpmc_cache_entities', $entityObjects, $cacheTime);
            }
        }

        return $entityObjects;
    }

    private function mapEntity($identifier, $options)
    {
        $options['identifier'] = $identifier;
        $entity = new Entity($options);

        $jm = new JsonMapper();
        $jm->bExceptionOnMissingData = true;
        $jm->bExceptionOnUndefinedProperty = true;
        $jm->bEnforceMapType = false;
        $jm->bStrictNullTypes = false;
        $jm->map($options, $entity);

        return $entity;
    }

    private function loadEntitiesData()
    {
        // do_action('wpmc_entity_registers');
        // $entities = self::$entityDefinitions;

        $entitiesData = apply_filters('wpmc_load_entities', []);
        $entities = [];

        foreach ( $entitiesData as $identifier => $data ) {
            $entities[$identifier] = $this->loadEntityData($identifier, $data);
        }

        return $entities;
    }

    /**
     * @return array
     */
    private function loadEntityData($identifier, $jsonOrArray)
    {
        if ( is_array($jsonOrArray) ) {
            $jsonOrArray['identifier'] = $identifier;

            return $jsonOrArray;
        }
        else if ( is_file($jsonOrArray) ) {
            $entity = $this->readJson($jsonOrArray);
            $entity['json_file'] = $jsonOrArray;
            $entity['identifier'] = $identifier;

            return $entity;
        }
        else {
            throw new Exception('Unrecognized entity data: ' . $identifier);
        }
    }

    private function readJson($file) {
        $json = file_get_contents($file);
        return json_decode($json, true);
    }
}