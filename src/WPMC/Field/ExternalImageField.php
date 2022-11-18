<?php
namespace WPMC\Field;

class ExternalImageField extends TextField
{
    public function formatValue($value, $item)
    {
        if ( !empty($value) ) {
            return "<a href=\"{$value}\" target=\"_new\"><img src=\"{$value}\" width=\"150\" height=\"150\"></a>";
        }
    
        return $value;
    }
}