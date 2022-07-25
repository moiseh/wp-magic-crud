<?php
namespace WPMC\UI;

use WPMC\Action\FieldableAction;

class ActionForm
{
    private $ids;

    public function __construct(private FieldableAction $action, private $title)
    {
    }

    public function setContextIds($ids) {
        $this->ids = $ids;
        return $this;
    }

    public function setEchoForm($echo) {
        $this->echoForm = $echo;
        return $this;
    }

    private function getCurrentPageUrl() {
        global $wp;
        $qstring = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
        
        return add_query_arg( $qstring, '', $wp->request );
    }

    private function getRequestIds() {
        $ids = [];

        if ( !empty($_REQUEST['id']) ) {
            $ids = is_array($_REQUEST['id']) ?
                array_map('sanitize_text_field', $_REQUEST['id']) :
                explode(',', sanitize_text_field($_REQUEST['id']));
        }

        return $ids;
    }

    public function renderForm()
    {
        $title = $this->title;
        $action = $this->action;
        $entity = $action->getRootEntity();
        $parameters = $action->getFieldParams();
        $ids = $this->ids ?: $this->getRequestIds();
        $listingUrl = wpmc_entity_admin_url($entity);
        $formAction = !empty($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';
        $backLabel = esc_html__(__('Back to', 'wp-magic-crud') . ' ' . $entity->getMenu()->getPlural());
        $nonce = wp_create_nonce(WPMC_ROOT_DIR);
        $listIds = implode(',', $ids);
        $currPage = $this->getCurrentPageUrl();

        ob_start();

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit">
                <br/>
            </div>
            <?php wpmc_flash_render(); ?>
            <form action="<?php echo $currPage; ?>" method="POST">
                <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
                <input type="hidden" name="action" value="<?php echo $formAction; ?>"/>
                <input type="hidden" name="id" value="<?php echo $listIds; ?>"/>
                <?php if ( !empty($title) ): ?>
                    <h2>
                        <?php echo $title; ?>
                        <a class="add-new-h2" href="<?php echo $listingUrl; ?>">
                            <?php echo $backLabel; ?>
                        </a>
                    </h2>
                <?php endif; ?>
                <div id="post-body-content">
                    <?php foreach ( $parameters as $field ): ?>
                        <?php $field->renderWithLabel(); ?>
                    <?php endforeach; ?>
                </div>
                <?php $this->renderSubmit(__('Execute')); ?>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function renderSubmit($label)
    {
        ?>
        <input type="submit" value="<?php echo $label; ?>" id="submit" class="button-primary" name="submit">
        <?php
    }
}