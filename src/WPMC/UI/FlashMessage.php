<?php
namespace WPMC\UI;

class FlashMessage {
    public static function queue_flash_message($message, $class = '') {

        $default_allowed_classes = array('error', 'updated');
        $allowed_classes = apply_filters('flash_messages_allowed_classes', $default_allowed_classes);
        $default_class = apply_filters('flash_messages_default_class', 'updated');

        if(!in_array($class, $allowed_classes)) $class = $default_class;

        $flash_messages = maybe_unserialize(get_option('wp_flash_messages', array()));
        $flash_messages[$class][] = $message;

        update_option('wp_flash_messages', $flash_messages);
    }
    
    public static function show_flash_messages() {
        $flash_messages = maybe_unserialize(get_option('wp_flash_messages', ''));

        if(is_array($flash_messages)) {
            foreach($flash_messages as $class => $messages) {
                foreach($messages as $message) {
                    ?>
                    <div class="<?php echo esc_html__($class); ?>">
                        <p><?php echo $message; ?></p>
                    </div>
                    <?php
                }
            }
        }

        //clear flash messages
        delete_option('wp_flash_messages');
    }
}