<?php
namespace WPMC;

use Exception;

class EntityExport
{
    public function __construct(private Entity $entity)
    {
    }

    public function toJsonFile() {
        $entity = $this->entity;

        if ( $entity->hasJsonFile() ) {
            $json = json_encode($entity->toArray(), \JSON_PRETTY_PRINT);
            $file = $entity->getJsonFile();

            if ( !file_put_contents($file, $json) ) {
                throw new Exception('Fail to save JSON: ' . $file);
            }
            // else {
            //     chmod($file, 0777);
            // }
        }
    }
}