<?php
    // get data.
    $data = (!empty($inquiry)) ? $inquiry[0] : null;
    //styling below are from merchant plugin
?>
<div id="contact-us-detail-page" class="wrap">
    <div class="contact-us-detail">
        <section class="contact-us-section">
            <div class="contact-us-card">
                <div class="contact-us-card-header">
                    <h2>Contact Us</h2>
                </div>
                <div class="contact-us-card-body">
                    <p><strong>Full name: </strong><?php echo $data->full_name ?></p>
                    <p><strong>Subject: </strong><?php echo $data->subject ?></p>
                    <p>
                        <strong>Email Address: </strong>
                        <a href="mailto:"<?php echo $data->email_address ?>><?php echo $data->email_address ?></a>
                    </p>
                    <p><strong>Contact Number: </strong><?php echo $data->contact_number ?></p>
                    <p><strong>Order Reference: </strong><?php echo $data->order_reference ?></p>
                    <p><strong>Message: </strong></p>
                    <textarea name="inquiry-message" rows="4" cols="100" readonly><?php echo htmlspecialchars($data->message); ?></textarea>
                </div>
            </div>
        </section>
    </div>
</div>
