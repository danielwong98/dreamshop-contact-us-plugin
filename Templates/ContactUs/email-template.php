<?php
 $inquiry_data = (!empty($data)) ? $data : [];
?>
<html lang="en">
    <body>
        <p>New inquiry received: </p>
        <?php
            if (count($inquiry_data) > 0) {
        ?>
            <p><strong>Full name: </strong><?php echo $inquiry_data['full_name'] ?></p>
            <p><strong>Subject: </strong><?php echo $inquiry_data['subject'] ?></p>
            <p>
                <strong>Email Address: </strong>
                <a href="mailto:"<?php echo $inquiry_data['email_address'] ?>><?php echo $inquiry_data['email_address'] ?></a>
            </p>
            <p><strong>Contact Number: </strong><?php echo $inquiry_data['contact_number'] ?></p>
            <p><strong>Order Reference: </strong><?php echo $inquiry_data['order_reference'] ?></p>
            <p><strong>Message: </strong></p>
            <textarea name="inquiry-message" rows="4" cols="100" readonly><?php echo htmlspecialchars($inquiry_data['message']); ?></textarea>
        <?php
            }
        ?>
    </body>
</html>