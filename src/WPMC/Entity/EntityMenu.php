<?php

namespace WPMC\Entity;

use Exception;

class EntityMenu
{
    /**
     * @var string
     * @required
     */
    private $menu_icon;

    /**
     * @var string
     * @required
     */
    private $parent_menu;

    /**
     * @var bool
     * @required
     */
    private $display_menu;

    /**
     * @var string
     * @required
     */
    private $singular;

    /**
     * @var string
     * @required
     */
    private $plural;

    public function getMenuIcon()
    {
        return $this->menu_icon;
    }

    public function setMenuIcon(string $menu_icon)
    {
        $this->menu_icon = $menu_icon;
        return $this;
    }

    public function getParentMenu()
    {
        return $this->parent_menu;
    }

    public function setParentMenu(string $parent_menu)
    {
        $this->parent_menu = $parent_menu;
        return $this;
    }

    public function getDisplayMenu()
    {
        return $this->display_menu;
    }

    public function setDisplayMenu(bool $display_menu)
    {
        $this->display_menu = $display_menu;
        return $this;
    }

    public function getSingular()
    {
        return $this->singular;
    }

    public function setSingular(string $singular)
    {
        $this->singular = $singular;
        return $this;
    }

    public function getPlural()
    {
        return $this->plural;
    }

    public function setPlural(string $plural)
    {
        $this->plural = $plural;
        return $this;
    }

    public function validateDefinitions()
    {
        $parentMenu = $this->getParentMenu();

        if ( !empty($parentMenu) ) {
            $menuUrl = menu_page_url( $parentMenu, false );

            if ( empty($menuUrl) ) {
                throw new Exception('Parent menu not found: ' . $parentMenu);
            }
        }
    }

    public function toArray()
    {
        $arr = [];
        $arr['menu']['menu_icon'] = $this->getMenuIcon();
        $arr['menu']['parent_menu'] = $this->getParentMenu();
        $arr['menu']['display_menu'] = $this->getDisplayMenu();
        $arr['menu']['singular'] = $this->getSingular();
        $arr['menu']['plural'] = $this->getPlural();

        return $arr;
    }
}