/**
 * Post SMTP Conflict Notice Dismissal Handler
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        $(document).on('click', '.postman-smtp-conflict-notice .notice-dismiss', function(e) {
            e.preventDefault();
            
            var $notice = $(this).closest('.postman-smtp-conflict-notice');
            var noticeId = $notice.data('notice-id');
            
            if (!noticeId) {
                return;
            }
            
            // Check if localized data is available
            if (typeof postmanSmtpConflict === 'undefined' || !postmanSmtpConflict.nonce) {
                console.error('Post SMTP Conflict Notice: Localized data not available');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_smtp_conflict_notice',
                    nonce: postmanSmtpConflict.nonce,
                    notice_id: noticeId
                },
                success: function(response) {
                    if (response.success) {
                        $notice.fadeOut('fast', function() {
                            $(this).remove();
                        });
                    } else {
                        console.error('Failed to dismiss notice:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        });
    });
})(jQuery);

