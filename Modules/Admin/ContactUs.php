<?php
namespace Modules\Admin;

include_once(DS_CONTACT_US . 'Templates/CustomTable/AdminCustomTable.php');

use CustomTable\AdminCustomTable;
use Exception;
class ContactUs
{
    public function __construct($register_ajax = true)
    {
        // register ajax true to make sure this class is init at the right time.
        if ($register_ajax) {
            // init rest api.
            //add_action( 'rest_api_init', array($this, 'register_contact_us_rest_routes') );
            // expose ajax handler for storing contact-us inquiries.
            add_action('wp_ajax_fetch_all_contact_us_ajax_handler', array($this, 'fetchAllContactUs'));
            add_action('wp_ajax_nopriv_fetch_all_contact_us_ajax_handler', array($this, 'fetchAllContactUs'));
        }
        add_action( 'admin_enqueue_scripts', array($this, 'init_styles_scripts') );
    }

    public function init() {
        // init current module
        $this->renderPage();
    }

    public function renderPage() {
        $table = new AdminCustomTable();
        $table->prepare_items();

        echo '<div class="wrap">';
        echo '<h2>Contact Us</h2>';
        echo '<form method="post">';
        $table->display();
        echo '</form>';
        echo '</div>';
    }

    private function getPageHTML()
    {
        $html = '';

        $page_template = DS_CONTACT_US . 'Templates/ContactUs/index.php';

        if (file_exists($page_template)) {
            ob_start();
            include($page_template);
            $html = ob_get_clean();
        }

        return $html;
    }

//    public function register_contact_us_rest_routes() {
//        $namespace = 'dcr/v1';
//        register_rest_route(
//            $namespace,
//            '/contact-us',
//            array(
//                'methods' => 'POST',
//                'callback' => array($this, 'storeContactUsInquiry'),
//                'permission_callback' => function () {
//                    return true; // public link
//                }
//        ));
//    }
    /**
     * @throws Exception
     */
    public function storeContactUsInquiry() {
        global $wpdb;

        $hasErrors = $this->contactUsValidation();
        $inquiry_email = (get_option('dcr_contact_us_email')) ?: get_option('admin_email');

        if (!empty($hasErrors)) {
            // if have errors, then 422
            wp_send_json_error(array(
                'message' => 'Validation failed.',
                'errors' => $hasErrors
            ), 422);
        }

        // if no errors, we sanitize all the text fields.
        $sanitized_data = array_map('sanitize_text_field', $_POST);

        $table_name = $wpdb->prefix . 'dcr_contact_us';

        $data = array(
            'subject' => $sanitized_data['subject'],
            'full_name' => $sanitized_data['full_name'],
            'email_address' => sanitize_email($sanitized_data['email_address']), // sanitize email after sanitized texts.
            'contact_number' => $sanitized_data['contact_number'],
            'order_reference' => $sanitized_data['order_reference'],
            'message' => $sanitized_data['message'],
            'created_at' => current_time('mysql', 1),
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            throw new Exception($wpdb->last_error);
        }

        // if not fail, send mail to admin.
        $this->sendInquiryEmail($data, $inquiry_email);
        // if success, then return success message.
        wp_send_json_success(array(
            'message' => 'Submitted successfully.'
        ));
    }

    public function fetchAllContactUs()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcr_contact_us';

        $query = "SELECT * FROM $table_name ORDER BY created_at DESC";

        $contact_us = $wpdb->get_results($query);

        foreach($contact_us as $inquiry) {
            $inquiry->view_detail_url = admin_url('admin.php?page=contact-us-detail&id=' . $inquiry->id);
            $created_at_timestamp = strtotime($inquiry->created_at);
            $inquiry->created_at_formatted = date('F j, Y, g:i a', $created_at_timestamp);
        }

        wp_send_json([
            'message' => 'Successful',
            'success' => true,
            'data' => $contact_us
        ]);
    }

    public function contactUsValidation() {
        // validate contact us form.
        $errors = array();

        if (empty($_POST['subject'])) {
            $errors['subject'] = 'Subject is required.';
        }

        // Validate Full Name
        if (empty($_POST['full_name'])) {
            $errors['full_name'] = 'Full Name is required.';
        }

        // Validate Email Address
        if (empty($_POST['email_address'])) {
            $errors['email_address'] = 'Email Address is required.';
        } elseif (!filter_var($_POST['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_address'] = 'Invalid Email Address.';
        }

        // Validate Contact Number
        if (empty($_POST['contact_number'])) {
            $errors['contact_number'] = 'Contact Number is required.';
        }

        // Validate Order Reference (optional)
        if (!empty($_POST['order_reference']) && strlen($_POST['order_reference']) > 50) {
            $errors['order_reference'] = 'Order Reference must not exceed 50 characters.';
        }

        // Validate Message
        if (empty($_POST['message'])) {
            $errors['message'] = 'Message is required.';
        }

        // Validate File Attachment (if implemented)
//        if (!empty($_FILES['file_attachment']['name'])) {
//            $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']; // Example allowed types
//            $file_ext = strtolower(pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION));
//
//            if (!in_array($file_ext, $allowed_types)) {
//                $errors['file_attachment'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
//            }
//        }

        return $errors;
    }

    public function handleFileUploads() {
        $file = $_FILES['file_attachment'];
        $upload_dir = wp_upload_dir();
        // store publicly?
        $file_name = wp_unique_filename($upload_dir['path'], $file['name']);

        if (move_uploaded_file($file['tmp_name'], $upload_dir['path'] . '/' . $file_name)) {
            // Build the URL of the uploaded image
            return $upload_dir['url'] . '/' . $file_name; // return url.
        }
        return ''; // if failed, then return empty string.
    }

    public function init_styles_scripts() {
        wp_enqueue_script('contact-us-script', plugin_dir_url(__FILE__) . 'Assets/js/contact-us.js', array('jquery'), time(), true);
        wp_localize_script('contact-us-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    private function sendInquiryEmail($data, $inquiry_email) {
        $subject = 'New Contact Us Inquiry';

        $message = $this->getEmailTemplate($data);

        $headers = array('Content-Type: text/html; charset=UTF-8');

        $email_sent = wp_mail($inquiry_email, $subject, $message, $headers);

        if (!$email_sent) {
            error_log('Error sending email:' . error_get_last()['message']);
            return false;
        }
        return true;
    }

    private function getEmailTemplate($data) {
        $page_template = DS_CONTACT_US . 'Templates/ContactUs/email-template.php';

        if (file_exists($page_template)) {
            ob_start();
            include($page_template);
            return ob_get_clean();
        }

        return '';
    }
}