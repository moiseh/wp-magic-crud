<?php
namespace WPMC\UI;

use WPMC\Entity;

class CommonAdmin
{
    public static function adminUrlWithFilters($page, $filters = [])
    {
        $url = 'admin.php?page=' . $page;

        if ( !empty($filters) ) {
            $url .= '&' . http_build_query($filters);
        }

        return get_admin_url(get_current_blog_id(), $url);
    }

    public static function formPageIdentifier(Entity $entity) {
        return $entity->getIdentifier() . '_form';
    }
}