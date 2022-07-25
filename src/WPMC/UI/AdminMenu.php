<?php
namespace WPMC\UI;

use WPMC\Entity;

class AdminMenu {
    private $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function loadAdminMenu() {    
        $entity = $this->entity;    

        if ( !$entity->getMenu()->getDisplayMenu() ) {
            return;
        }

        $identifier = $entity->getIdentifier();
        $capability = 'activate_plugins';
        $addLabel = $this->getFormPageTitle();
        $listingPage = array($this, 'listingPageHandler');
        $formPage = array($this, 'formPageHandler');
        $plural = $entity->getMenu()->getPlural();
        $parentMenu = $entity->getMenu()->getParentMenu();
        $menuIcon = $entity->getMenu()->getMenuIcon();

        if ( !empty($parentMenu) ) {
            add_submenu_page($parentMenu, $plural, $plural, $capability, $identifier, $listingPage);    
        }
        else {
            add_menu_page($plural, $plural, $capability, $identifier, $listingPage, $menuIcon);
            add_submenu_page($identifier, $plural, $plural, $capability, $identifier, $listingPage);
        }

        if ( $entity->fieldsCollection()->canCreate() ) {
            $formId = CommonAdmin::formPageIdentifier($entity);

            if ( !empty($parentMenu) ) {
                add_submenu_page($identifier, $addLabel, $addLabel, $capability, $formId, $formPage);
            }
            else {
                add_submenu_page($identifier, $addLabel, $addLabel, $capability, $formId, $formPage);
            }
        }
    }

    private function getFormPageTitle()
    {
        $entity = $this->entity;

        if ( !empty($_REQUEST['id']) ) {
            return __('Manage', 'wp-magic-crud') . ' ' . $entity->getMenu()->getSingular();
        }
        else {
            return __('Create', 'wp-magic-crud') . ' ' . $entity->getMenu()->getSingular();
        }
    }

    public function listingPageHandler()
    {
        $entity = $this->entity;

        do_action("wpmc_before_entity", $entity);
        do_action("wpmc_before_list", $entity);
    
        $table = new ListTable($entity);
        $table->execute_page_handler();
    }

    public function formPageHandler()
    {
        $entity = $this->entity;

        do_action("wpmc_before_entity", $entity);
        do_action("wpmc_before_form", $entity);

        $form = new Form($entity);
        $form->execute_page_handler();
    }
}