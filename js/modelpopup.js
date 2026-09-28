require(['jquery'], function($) {

    /*
     * Open reminder details modal.
     */
    $(document).on('click', '.view-reminder-details', function(e) {

        e.preventDefault();

        var userid = $(this).data('userid');
        var courseid = $(this).data('courseid');

        $.ajax({

            url: M.cfg.wwwroot +
                '/local/courseremainder_mails/ajax.php',

            type: 'POST',

            data: {
                userid: userid,
                courseid: courseid
            },

            success: function(response) {

                $('#course-modal-label')
                    .text('Reminder Details');

                $('#course-modal-body')
                    .html(response);

                $('#course-modal')
                    .modal('show');
            },

            error: function() {

                $('#course-modal-label')
                    .text('Reminder Details');

                $('#course-modal-body')
                    .html(
                        '<p class="text-danger">' +
                        'Unable to load reminder details.' +
                        '</p>'
                    );

                $('#course-modal')
                    .modal('show');
            }
        });
    });


    /*
     * Close reminder details modal.
     *
     * Works with the custom close button:
     * .reminder-modal-close
     */
    $(document).on(
        'click',
        '.reminder-modal-close',
        function(e) {

            e.preventDefault();

            $('#course-modal').modal('hide');
        }
    );


    /*
     * Select / deselect all checkboxes.
     */
    $(document).on(
        'change',
        '#selectall',
        function() {

            $('.selectitem').prop(
                'checked',
                $(this).prop('checked')
            );
        }
    );

});