<?php

namespace CustomTable;

use WP_List_Table;

class AdminCustomTable extends WP_List_Table
{
    public function __construct() {
        parent::__construct( array(
            'singular' => 'contact',
            'plural'   => 'contacts',
            'ajax'     => false
        ) );
    }

    // Define the columns
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',  // Checkbox column
            'full_name' => __('Full Name', 'dreamshop-contact-us'),
            'contact_number' => __('Contact Number', 'dreamshop-contact-us'),
            'email_address' => __('Contact Email', 'dreamshop-contact-us'),
            'order_reference' => __('Order Reference', 'dreamshop-contact-us'),
            'subject' => __('Subject', 'dreamshop-contact-us'),
            'message' => __('Message', 'dreamshop-contact-us'),
            'created_at' => __('Received At', 'dreamshop-contact-us'),
            'status' => __('Status', 'dreamshop-contact-us'),
            'actions' => __('Actions', 'dreamshop-contact-us'),
        );

        return $columns;
    }

    // Prepares the table's data
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dcr_contact_us';

        $this->process_bulk_action();
        $this->appendFilter();

        $per_page = 25;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        // Get the filters from the request
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

        // Build the filter string
        $filter = '';
        if (!empty($status)) {
            $filter .= $wpdb->prepare(" status = %s", $status);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $end_date .= ' 23:59:59';
            if (!empty($filter)) {
                $filter .= ' AND';
            }
            $filter .= $wpdb->prepare(" created_at BETWEEN %s AND %s", $start_date, $end_date);
        } elseif (!empty($start_date)) {
            if (!empty($filter)) {
                $filter .= ' AND';
            }
            $filter .= $wpdb->prepare(" created_at >= %s", $start_date);
        } elseif (!empty($end_date)) {
            // Adjust end_date to include the entire day
            $end_date .= ' 23:59:59';
            if (!empty($filter)) {
                $filter .= ' AND';
            }
            $filter .= $wpdb->prepare(" created_at <= %s", $end_date);
        }

        // Determine sort parameters
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'created_at';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // Run the query based on whether $filter is empty or not
        if (!empty($filter)) {
            $this->items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE $filter ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                ($current_page - 1) * $per_page
            ));
        } else {
            $this->items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                ($current_page - 1) * $per_page
            ));
        }
        // Set pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    // Add the checkbox for each row
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />', $item->id
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'full_name':
            case 'contact_number':
            case 'email_address':
            case 'order_reference':
            case 'subject':
            case 'message':
                return $item->$column_name;
            case 'created_at':
                return $this->format_date($item->created_at);
            case 'status':
                return $this->get_status_html($item->status);
            case 'actions':
                return $this->get_actions_html($item->id);
            default:
                return print_r($item, true);
        }
    }

    private function get_status_html($status) {
        $class = ($status === 'completed') ? 'completed' : 'pending';
        return sprintf('<span class="status-contact-us-%s">%s</span>', $class, esc_html(ucfirst($status)));
    }

    private function get_actions_html($item_id) {
        // Create the URL using admin_url and the item's ID
        $view_details_url = admin_url('admin.php?page=contact-us-detail&id=' . $item_id);

        // Return the button with the correct URL
        return sprintf('<a href="%s" class="button button-primary">View Details</a>', esc_url($view_details_url));
    }

    private function format_date($mysql_date) {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $mysql_date);
        if ($date) {
            return $date->format('F j, Y, g:i a');
        }
        return $mysql_date; // Return original if parsing fails
    }

    protected function get_sortable_columns()
    {
        $sortable_columns = array(
            'created_at'  => array('created_at', true),
            'status'  => array('status', false)
        );
        return $sortable_columns;
    }

    public function get_bulk_actions() {
        $actions = array(
            'mark_as_pending' => __('Mark as pending', 'dreamshop-contact-us'),
            'mark_as_complete' => __('Mark as completed', 'dreamshop-contact-us'),
        );
        return $actions;
    }
    public function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dcr_contact_us';

        // Check if the action and items are set
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $item_ids = isset($_POST['element']) ? $_POST['element'] : array();

        if (empty($item_ids) || !is_array($item_ids)) {
            return;
        }

        // Sanitize item IDs
        $item_ids = array_map('intval', $item_ids);

        // Process bulk actions
        switch ($action) {
            case 'mark_as_pending':
                foreach ($item_ids as $item_id) {
                    $wpdb->update(
                        $table_name,
                        array('status' => 'pending'), // Update the status field to 'pending'
                        array('id' => $item_id), // Where id matches the selected item
                        array('%s'),
                        array('%d')
                    );
                }
                break;

            case 'mark_as_complete':
                foreach ($item_ids as $item_id) {
                    $wpdb->update(
                        $table_name,
                        array('status' => 'completed'), // Update the status field to 'completed'
                        array('id' => $item_id), // Where id matches the selected item
                        array('%s'),
                        array('%d')
                    );
                }
                break;

            default:
                return;
        }
    }
    public function extra_tablenav($which) {
        if ($which == 'top') {
            echo '<div class="filter-wrapper">';

            echo '<div class="alignleft actions" style="display: flex; gap: 10px;">';

            // Status dropdown
            $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
            echo '<select name="status">
                <option value="">Filter by Status</option>
                <option value="">All Status</option>
                <option value="pending"' . selected($status, 'pending', false) . '>Pending</option>
                <option value="complete"' . selected($status, 'complete', false) . '>Complete</option>
            </select>';

            // Date inputs
            $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';

            // Start date input with 'From' label
            echo '<label for="start_date" style="margin-top: auto; margin-bottom: auto">From</label>';
            echo '<input type="date" name="start_date" id="start_date" value="' . esc_attr($start_date) . '" placeholder="Start Date">';

            // End date input with 'To' label, initially hidden
            echo '<label for="end_date" id="end_date_label" style="display:none; margin-top: auto; margin-bottom: auto">To</label>';
            echo '<input type="date" name="end_date" id="end_date" style="display:none;" value="' . esc_attr($end_date) . '" placeholder="End Date">';

            submit_button('Filter', 'action', 'filter_action', false);
            echo '</div>';

            echo '</div>'; // End of tablenav

            // JavaScript to toggle the end date and its label visibility
            ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    var startDateInput = document.getElementById('start_date');
                    var endDateInput = document.getElementById('end_date');
                    var endDateLabel = document.getElementById('end_date_label');

                    // Function to toggle end date and its label visibility
                    function toggleEndDate() {
                        if (startDateInput.value) {
                            endDateInput.style.display = 'inline-block'; // Show end date
                            endDateLabel.style.display = 'inline-block'; // Show 'To' label
                        } else {
                            endDateInput.style.display = 'none'; // Hide end date
                            endDateLabel.style.display = 'none'; // Hide 'To' label
                            endDateInput.value = ''; // Clear end date if start date is cleared
                        }
                    }

                    // Initial check on page load
                    toggleEndDate();

                    // Attach event listener to start date input
                    startDateInput.addEventListener('input', toggleEndDate);
                });
            </script>
            <?php
        }
    }
    private function appendFilter() {
        // Check if the filter button was clicked
        if (!empty($_POST['filter_action'])) {
            // Redirect to the same page with GET parameters
            $redirect_url = add_query_arg(
                array(
                    'page' => $_REQUEST['page'],
                    'status' => $_POST['status'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                ),
                admin_url('admin.php')
            );
            wp_redirect($redirect_url);
            exit;
        }
    }
}
