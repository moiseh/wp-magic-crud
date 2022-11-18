<?php
namespace WPMC\Field;

class LocalFileField extends TextField
{
    public function formatValue($value, $item)
    {
        if ( !empty($value)  ) {
            $fileLink = get_site_url() . '/wp-content/' . str_replace(WP_CONTENT_DIR, '', $value);
            return "<a href=\"{$fileLink}\" target=\"_new\">" . basename($value) . "</a>";
        }

        return $value;
    }
}