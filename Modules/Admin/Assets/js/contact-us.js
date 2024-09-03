jQuery(document).ready(function($) {
    $('#save-status-button').on('click', function() {
        // Capture the selected status value
        var status = $('select[name="status"]').val();
        var contactUsId = $('#contact-us-id').val();

        // AJAX POST request to update the status
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_contact_us_status',
                status: status,
                id: contactUsId
            },
            success: function(response) {
                if (response.success) {
                    alert('Status updated successfully.');
                    window.history.back();
                } else {
                    alert('Failed to update status.');
                    location.reload();
                }
            },
            error: function(response) {
                alert('Error updating status.');
                location.reload();
            }
        });
    });

    // look for merchant listing table.
    const isContactUsPage = $('#contact-us-listing-page').length > 0;
    if (isContactUsPage) {
        // ajax call to fetch merchants.
        fetchContactUs();
        // Function declaration.
        function fetchContactUs() {
            $.ajax({
                url: ajax_object.ajax_url + '?action=fetch_all_contact_us_ajax_handler',
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    // show loader.
                },
                success: function (response) {
                    console.log(response);
                    // hide loader.
                    // render merchants.
                    if (response && response.success && response.data && response.data.length > 0) {
                        renderContactUsTable(response.data)
                    }

                },
                error: function (response) {
                    // hide loader.
                    // show error message.
                }
            })
        }

        function renderContactUsTable(inquiries) {
            $.each(inquiries, function (index, inquiry) {
                $('#the-list').append(
                    '<tr>' +
                    '<td class="column-full-name">' + inquiry.full_name + '</td>' +
                    '<td class="column-contact-number">' + inquiry.contact_number + '</td>' +
                    '<td class="column-email">' + inquiry.email_address + '</td>' +
                    '<td class="column-order-reference">' + inquiry.order_reference + '</td>' +
                    '<td class="column-subject">' + inquiry.subject + '</td>' +
                    '<td class="column-message">' + inquiry.message + '</td>' +
                    '<td class="column-created-at">' + inquiry.created_at_formatted + '</td>' +
                    '<td class="column-status">' +
                        '<div class= "' + (inquiry.status === 'pending' ? 'status-pending' : 'status-complete') + '">' +
                            inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1).toLowerCase() +
                        '</div>' +
                    '</td>' +
                    '<td class="column-actions">' +
                    '<a href="'+ inquiry.view_detail_url +'" class="button button-primary">View Details</a>' +
                    '</td>' +
                    '</tr>'
                )
            })
        }
    }
});