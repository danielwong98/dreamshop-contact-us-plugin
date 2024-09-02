<?php
    if (isset($_POST['update_contact_us_email_setting'])) {
        // Sanitize and get the email input from the form
        $new_contact_us_email = sanitize_email($_POST['email']);

        // Update the option in the database
        update_option('dcr_contact_us_email', $new_contact_us_email);

        // Optional: Add a success message or redirect
        echo '<div class="notice notice-success is-dismissible">
                <p>Email address updated successfully!</p>
              </div>';
    }

    // Retrieve the current value of the option
    $contact_us_email = (get_option('dcr_contact_us_email')) ?: get_option('admin_email');
?>
<div id="contact-us-email-setting-page" class="wrap">
    <div class="contact-us-email-setting-wrapper contact-us-card">
        <div class="contact-us-card-header">
            Email Setting
        </div>
        <div class="contact-us-card-body">
            <form id="email-setting-form" class="email-setting-form-wrapper contact-us-email-setting" method="post" action="">
                <div class="form-group">
                    <p>
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $contact_us_email; ?>">
                    </p>
                    <span class="hint-text">* All inquiries from the contact us form will be sent to email above.</span>
                </div>
                <div class="update-email-btn-wrapper">
                    <button type="submit" class="button button-primary" name="update_contact_us_email_setting">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>