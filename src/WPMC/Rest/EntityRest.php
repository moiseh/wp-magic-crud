<?php

namespace WPMC\Rest;

use Exception;
use WPMC\DB\EntityQuery;
use WPMC\Entity;

class EntityRest
{
    public function __construct(private Entity $entity)
    {
    }

    public function registerPaginationRest()
    {
        $entity = $this->entity;
        $identifier = $entity->getIdentifier();

        register_rest_route('crud', $identifier, array(
            'methods'  => 'GET',
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array('required' => false, 'type' => 'integer', 'default' => 1),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 15,
                    'validate_callback' => functioN($val){ return ($val <= 50); },
                ),
            ),
            'callback' => function(\WP_REST_Request $request) use($entity){
                try {
                    $page = $request->get_param('page');
                    $limit = $request->get_param('limit');
                    $search = $request->get_param('search');

                    $entityQuery = new EntityQuery($entity);
                    $qb = $entityQuery->buildQueryWithSearch($search);
                    $rows = $entityQuery->paginateData($qb, $limit, $page);

                    return new \WP_REST_Response(['data' => $rows]);
                }
                catch (Exception $e ) {
                    $code = $e->getCode() ?: 400;
                    return new \WP_Error($code, $e->getMessage(), array('status' => $code));
                }
            }
        ));
    }

    public function registerGetRest()
    {
        $entity = $this->entity;
        $identifier = $entity->getIdentifier();

        register_rest_route('crud', "{$identifier}/(?P<id>\d+)", array(
            'methods'  => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function(\WP_REST_Request $req) use($entity){
                try {
                    $id = $req->get_param('id');
                    $row = $entity->findById($id, true);

                    return new \WP_REST_Response($row);
                }
                catch (Exception $e ) {
                    $code = $e->getCode() ?: 400;
                    return new \WP_Error($code, $e->getMessage(), array('status' => $code));
                }
            }
        ));
    }

    public function registerPostRest()
    {
        $entity = $this->entity;
        $identifier = $entity->getIdentifier();

        register_rest_route('crud', $identifier, array(
            'methods'  => 'POST',
            'permission_callback' => '__return_true',
            'callback' => function(\WP_REST_Request $req) use($entity){
                try {
                    $body = $req->get_json_params();
                    $newId = $entity->saveDbData($body);
                    $row = $entity->findById($newId);

                    return new \WP_REST_Response($row);
                }
                catch (Exception $e ) {
                    $code = $e->getCode() ?: 400;
                    return new \WP_Error($code, $e->getMessage(), array('status' => $code));
                }
            }
        ));
    }

    public function registerPutRest()
    {
        $entity = $this->entity;
        $identifier = $entity->getIdentifier();

        register_rest_route('crud', "{$identifier}/(?P<id>\d+)", array(
            'methods'  => ['PUT', 'PATH'],
            'permission_callback' => '__return_true',
            'callback' => function(\WP_REST_Request $req) use($entity){
                try {
                    $id = $req->get_param('id');
                    $body = $req->get_json_params();

                    $entity->saveDbData($body, $id);
                    $row = $entity->findById($id, true);

                    return new \WP_REST_Response($row);
                }
                catch (Exception $e ) {
                    $code = $e->getCode() ?: 400;
                    return new \WP_Error($code, $e->getMessage(), array('status' => $code));
                }
            }
        ));
    }

    public function registerActionsRest()
    {
        $entity = $this->entity;
        $identifier = $entity->getIdentifier();
        $restActions = $entity->actionsCollection()->getResttableActions();

        foreach ( $restActions as $action ) {
            $alias = $action->getAlias();
            $method = $action->getRestMethod();

            register_rest_route('crud', "{$identifier}/action/{$alias}/(?P<id>\d+)", array(
                'methods'  => $method,
                'permission_callback' => '__return_true',
                'callback' => function(\WP_REST_Request $req) use($entity, $action){
                    try {
                        $id = $req->get_param('id');
                        $runner = $action->getRunner();
                        $params = $req->get_json_params();
                        $row = $entity->findById($id, true); // safe check ID exists

                        $json = [
                            'description' => $action->getLabel(),
                            'action' => $action->getAlias(),
                            'type' => $action->getType(),
                            'id' => $id,
                            'message' => $runner->getRestMessage(),
                            'result' => $runner->executeForRest($id, $params),
                        ];

                        return new \WP_REST_Response($json);
                    }
                    catch (Exception $e ) {
                        $code = $e->getCode() ?: 400;
                        return new \WP_Error($code, $e->getMessage(), array('status' => $code));
                    }
                }
            )); 
        }
    }
}