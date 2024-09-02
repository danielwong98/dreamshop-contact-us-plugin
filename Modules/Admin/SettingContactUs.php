<?php
namespace Modules\Admin;

use Exception;
class SettingContactUs
{
    public function __construct($register_ajax = true)
    {
        // register ajax true to make sure this class is init at the right time.
        if ($register_ajax) {
            // expose ajax handler for storing contact-us inquiries.
            add_action('wp_ajax_update_email_option', array($this, 'updateEmailOption'));
            add_action('wp_ajax_nopriv_update_email_option', array($this, 'updateEmailOption'));
        }
        add_action( 'admin_enqueue_scripts', array($this, 'init_styles_scripts') );
    }

    public function init() {
        // init current module
        $this->renderPage();
    }

    public function renderPage() {
        // get template
        // render current module page.
        echo $this->getPageHTML();
    }

    private function getPageHTML()
    {
        $html = '';

        $page_template = DS_CONTACT_US . 'Templates/ContactUs/setting.php';

        if (file_exists($page_template)) {
            ob_start();
            include($page_template);
            $html = ob_get_clean();
        }

        return $html;
    }


    public function init_styles_scripts() {
        wp_enqueue_script('contact-us-script', plugin_dir_url(__FILE__) . 'Assets/js/contact-us.js', array('jquery'), time(), true);
        wp_localize_script('contact-us-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}