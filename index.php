<?php
/**
 * Plugin Name: Dreamshop Contact Us plugin
 * Description: This plugin is used to store any contact us details at backend.
 * Version: 1.0.0
 * Author: Daniel Wong
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DS_CONTACT_US', plugin_dir_path(__FILE__));

/**
 *  Include modules here
 **/
include_once(DS_CONTACT_US . 'Modules/Admin/ContactUs.php');
include_once(DS_CONTACT_US . 'Modules/Admin/ViewContactUs.php');
include_once(DS_CONTACT_US . 'Modules/Admin/SettingContactUs.php');

use Modules\Admin\ContactUs;
use Modules\Admin\ViewContactUs;
use Modules\Admin\SettingContactUs;

class AdminPlugin
{
    private $contact_us;
    private $contact_us_detail;
    private $contact_us_setting;
    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'initialize_contact_us_module_menu'));
        add_action('init', array($this, 'initialize_contact_us'));
        add_action('init', array($this, 'initialize_contact_us_view'));
        add_action('init', array($this, 'initialize_contact_us_setting_page'));
        add_action('admin_enqueue_scripts', array($this, 'init_styles'));
    }

    public function activate() {
        // Activation logic here
        // register table here.
        $this->init_contact_us_table();
    }

    public function deactivate() {
        // Deactivation logic here
    }
    /**
     * Init admin menu.
     **/
    public function initialize_contact_us_module_menu() {
        add_menu_page(
            'Contact Us | Listing',
            'Contact Us',
            'manage_options',
            'contact-us',
            array($this, 'initialize_contact_us_page'),
            'dashicons-sos',
            999
        );
        add_submenu_page(
            null,
            'Contact Us | View',
            'View Inquiry',
            'manage_options',
            'contact-us-detail',
            array($this, 'initialize_contact_us_detail_page')
        );
        add_submenu_page(
            'contact-us',
            'Contact Us | Setting',
            'Setting',
            'manage_options',
            'contact-us-setting',
            array($this, 'initialize_setting_page')
        );
    }

    /**
     * Initialize the module.
     */
    public function initialize_contact_us_view() {
        $this->contact_us_detail = new ViewContactUs();
    }
    public function initialize_contact_us_detail_page() {
        if (!$this->contact_us_detail) {
            $this->contact_us_detail = new ViewContactUs(false);
        }
        // fire init when load this page.
        $this->contact_us_detail->init();
    }
    public function initialize_contact_us() {
        $this->contact_us = new ContactUs();
    }

    /**
     * This is for render the page when user navigate to the page at admin panel.
     * */
    public function initialize_contact_us_page() {
        // admin menu init
        if (!$this->contact_us) {
            $this->contact_us = new ContactUs(false);
        }
        $this->contact_us->init();
    }

    public function initialize_contact_us_setting() {
        $this->contact_us_detail = new ViewContactUs();
    }

    public function initialize_contact_us_setting_page() {
        if (!$this->contact_us_setting) {
            $this->contact_us_setting = new SettingContactUs();
        }
    }

    public function initialize_setting_page() {
        // admin menu init
        if (!$this->contact_us_setting) {
            $this->contact_us_setting = new SettingContactUs(false);
        }
        $this->contact_us_setting->init();
    }

    private function init_contact_us_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcr_contact_us';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                subject varchar(255) NOT NULL,
                full_name varchar(100) NOT NULL,
                email_address varchar(100) NOT NULL,
                contact_number varchar(20) NOT NULL,
                order_reference varchar(50) DEFAULT NULL,
                message text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function init_styles() {
        wp_enqueue_style( 'main-style', plugin_dir_url(__FILE__) . 'Assets/css/main.css');
    }
}

new AdminPlugin(); // Initialize the plugin