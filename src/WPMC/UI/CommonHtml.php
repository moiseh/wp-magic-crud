<?php
namespace WPMC\UI;

class CommonHtml
{
    public static function buildHtmlList($list = [])
    {
        $html = '<ul>';
        
        foreach ( $list as $label ) {
            $html .= "<li>- {$label}</li>";
        }

        $html .= '</ul>';

        return $html;
    }

    public static function htmlLink($url, $text) {
        return '<a href="' . $url . '">' . $text . '</a>';
    }
}