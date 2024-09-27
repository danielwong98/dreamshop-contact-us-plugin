<?php

namespace GraphQL;

use Exception;
use GraphQL\Error\UserError;
use WC_Product_Simple;
use WP_User;
use WC_Customer;
use DSHelper;

class ContactUsGraphQL
{
    private $contact_us_form;
    public function __construct()
    {
        $this->contact_us_form = include_once DS_CONTACT_US . 'GraphQL/Forms/ContactUs/ContactUsForm.php';
    }

    public function init()
    {
        // Registration mutations
       add_action('graphql_register_types', array($this, 'register_custom_contact_us_mutation'));
    }

    /***
     *
     * Custom Billing Address Starts here **
     *
     */
    public function register_custom_contact_us_mutation() {
        register_graphql_mutation('addContactUs', [
            'inputFields' => $this->contact_us_form,
            'outputFields' => [
                'success' => [
                    'type' => 'Boolean',
                    'description' => 'Returns true if the address is added successfully.',
                    'resolve' => function($payload, $args, $context, $info) {
                        return $payload['success'];
                    }
                ],
                'message' => [
                    'type' => 'String',
                    'description' => 'Response Message'
                ]
            ],
            'mutateAndGetPayload' => function($input, $context, $info) {
                $status = $this->storeContactUsInquiry($input, $context, $info);
                return $status;
            }
        ]);
    }

    public function storeContactUsInquiry($input, $context, $info) {
        global $wpdb;

        $hasErrors = $this->contactUsValidation($input);
        $inquiry_email = (get_option('dcr_contact_us_email')) ?: get_option('admin_email');

        if (!empty($hasErrors)) {
            // concat hasError messages.
            $error_message = implode(' <br/>', $hasErrors);

            throw new \GraphQL\Error\UserError(__($error_message));
        }

        // if no errors, we sanitize all the text fields.
        $sanitized_data = array_map('sanitize_text_field', $input);

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
            return [
                'success' => true,
                'message' => $wpdb->last_error
            ];
        }

        // if not fail, send mail to admin.
        $this->sendInquiryEmail($data, $inquiry_email);
        // if success, then return success message.
        return [
            'success' => true,
            'message' => 'Submitted successfully.'
        ];
    }

    public function contactUsValidation($input) {
        // validate contact us form.
        $errors = array();

        if (empty($input['subject'])) {
            $errors['subject'] = 'Subject is required.';
        }

        // Validate Full Name
        if (empty($input['full_name'])) {
            $errors['full_name'] = 'Full Name is required.';
        }

        // Validate Email Address
        if (empty($input['email_address'])) {
            $errors['email_address'] = 'Email Address is required.';
        } elseif (!filter_var($input['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_address'] = 'Invalid Email Address.';
        }

        // Validate Contact Number
        if (empty($input['contact_number'])) {
            $errors['contact_number'] = 'Contact Number is required.';
        }

        // Validate Order Reference (optional)
        if (!empty($input['order_reference']) && strlen($input['order_reference']) > 50) {
            $errors['order_reference'] = 'Order Reference must not exceed 50 characters.';
        }

        // Validate Message
        if (empty($input['message'])) {
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