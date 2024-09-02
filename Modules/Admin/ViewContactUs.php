<?php
namespace Modules\Admin;
class ViewContactUs
{
    public function __construct($register_ajax = true)
    {
        // register ajax true to make sure this class is init at the right time.
        if ($register_ajax) {
            // register ajax handler.
            add_action('wp_ajax_fetch_contact_us_inquiry', array($this, 'fetchContactUsInquiry'));
            add_action('wp_ajax_nopriv_fetch_contact_us_inquiry', array($this, 'fetchContactUsInquiry'));
        }
    }

    public function fetchContactUsInquiry()
    {
        // contact us detail.
    }

    public function init() {
        // init current module
        $this->renderPage();
    }

    public function renderPage() {
        // get template
        // render current module page.
        echo $this->handler_inquiry_detail_view();
    }

    private function handler_inquiry_detail_view() {
        global $wpdb;
        if (isset($_GET['page']) && $_GET['page'] == 'contact-us-detail' && isset($_GET['id'])) {
            $html = '';

            $page_template = DS_CONTACT_US . 'Templates/ContactUs/view.php';

            if (file_exists($page_template)) {
                $contact_us_id = intval($_GET['id']); // get contact us id.

                $table_name = $wpdb->prefix . 'dcr_contact_us';

                $query = "
                    SELECT *
                    FROM $table_name
                    WHERE id = %d
                ";

                $query = $wpdb->prepare($query, $contact_us_id);

                $inquiry = $wpdb->get_results($query);

                ob_start();

                include($page_template);

                return ob_get_clean();
            }
        }
        // if id not set, redirect them to merchant listing page
        wp_redirect(admin_url('admin.php?page=contact-us'));
    }
}