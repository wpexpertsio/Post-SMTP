jQuery(document).ready(function () {
    let finalStepConfettiShown = false;
    jQuery('.ps-wizard-socket-check:checked').siblings('.ps-wizard-socket-tick-container').css({ 'opacity': 1 });

    jQuery(document).on('click', '.ps-wizard-socket-radio label', function () {

        jQuery('.ps-wizard-socket-tick-container').css({ 'opacity': 0 });
        jQuery(this).find('.ps-wizard-socket-tick-container').css({ 'opacity': 1 });

    })

    /**
     * Step 1 - limit mailer rows and add "See More" toggle
     *
     * - Show only the first two rows of non‑PRO mailers by default.
     * - The last visible non‑PRO card in the second row moves to the
     *   first hidden row so the second row still has a maximum of 4 items.
     * - PRO mailers (ps-pro-extension-outer: Office 365, Amazon SES, Zoho)
     *   are never hidden and are not affected by the See More toggle.
     */
    /**
     * Reflows the mailer sockets grid to ensure proper layout
     * when toggling between "See More" and "See Less".
     * 
     * @param {boolean} isExpanded Whether the list is expanded (See Less) or collapsed (See More)
     */
    const reflowSockets = function (isExpanded) {

        const $step1 = jQuery('.ps-wizard-step-1');
        if (!$step1.length) {
            return;
        }

        // Get all rows that DO NOT contain PRO extensions
        let $rows = $step1.find('.ps-wizard-sockets').not(function () {
            return jQuery(this).find('.ps-pro-extension-outer').length > 0;
        });

        var enabled_gmail = jQuery('.ps-enable-gmail-one-click').is(':checked');
        if (enabled_gmail) {
            jQuery('.gmail_api-outer').addClass('gmail-enabled');
        }

        var enabled_office = jQuery('.ps-enable-office365-one-click').is(':checked');
        if (enabled_office) {
            jQuery('.office365_api-outer').addClass('office-enabled');
        }

        jQuery(document).on('click', 'table tr:last-child .ps-wizard-edit', function (e) {
            e.preventDefault();
            jQuery('.ps-wizard-outer').addClass('ps-wizard-send-email');
        });

        // If we don't have enough rows (e.g. less than 3), we might not need "See More"
        // But we should stick to the logic: if we have more than 2 rows worth of content.
        // Or if we already created the button, we continue.

        // Collect all mailer cards (excluding the See More button container)
        // Collect all socket cards
        let $allCards = $rows.find('.ps-wizard-socket-radio-outer').not('.ps-wizard-see-more-outer').detach();

        // Identify "Special" Active Pro Cards that should be at the bottom
        // defined by specific values/IDs that correspond to Pro mailers
        const specialMailers = ['office365_api', 'aws_ses_api', 'zohomail_api'];

        let $bottomCards = $allCards.filter(function () {
            const val = jQuery(this).find('input').val();
            return specialMailers.includes(val);
        });

        // The rest are standard cards
        let $standardCards = $allCards.not($bottomCards);

        // Handle "See More" button creation/retrieval
        let $seeMoreBtn = $step1.find('.ps-wizard-see-more-outer');
        const seeMoreLabel = (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.seeMoreLabel) ? PostSMTPWizard.seeMoreLabel : 'See More';
        const seeLessLabel = (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.seeLessLabel) ? PostSMTPWizard.seeLessLabel : 'See Less';


        // Update Button State
        const $text = $seeMoreBtn.find('.ps-wizard-see-more-text');
        if (isExpanded) {
            $text.text(seeLessLabel);
            jQuery('.ps-wizard').addClass('ps-see-more-open');
        } else {
            $text.text(seeMoreLabel);
            jQuery('.ps-wizard').removeClass('ps-see-more-open');
        }

        // Clear all rows
        $rows.each(function () {
            // jQuery(this).empty();
        });



        let currentRowIdx = 0;
        let cardIdx = 0;
        const cardsArray = $standardCards.toArray();

        // 5. Apply Visibility
        // Re-query all sockets rows
        const $finalRows = $step1.find('.ps-wizard-sockets');

    
    };

    // Initial Reflow
    // reflowSockets(false);

    // Event Handler for See More
    jQuery(document).on('click', '.ps-wizard-see-more', function (e) {
        e.preventDefault();
        const isOpen = jQuery('.ps-wizard').hasClass('ps-see-more-open');

        reflowSockets(!isOpen);

        if (isOpen) {
            // We just collapsed
            // Clear selection and disable button
            jQuery('.ps-wizard-socket-check').prop('checked', false);
            jQuery('.ps-wizard-socket-tick-container').css({ 'opacity': 0 });
            // $step1ContinueBtn is defined below this block in original code, so we use selector
            jQuery('.ps-wizard-step-1 .ps-wizard-next-btn[data-step="1"]').addClass('ps-disabled').prop('disabled', true);
        }
    });

    // Step 1 "Continue" button - enable if a mailer is already selected.
    const $step1ContinueBtn = jQuery('.ps-wizard-step-1 .ps-wizard-next-btn[data-step="1"]');
    const hasInitialSelection = jQuery('.ps-wizard-socket-check').is(':checked');

    if (hasInitialSelection) {
        $step1ContinueBtn.removeClass('ps-disabled').prop('disabled', false);
    } else {
        $step1ContinueBtn.addClass('ps-disabled').prop('disabled', true);
    }

    // Enable/disable Step 1 button when a mailer is (de)selected
    jQuery(document).on('change', '.ps-wizard-socket-check', function () {

        const hasSelection = jQuery('.ps-wizard-socket-check').is(':checked');

        if (hasSelection) {
            $step1ContinueBtn.removeClass('ps-disabled').prop('disabled', false);
        } else {
            $step1ContinueBtn.addClass('ps-disabled').prop('disabled', true);
        }

    });

    var enabled_gmail = jQuery('.ps-enable-gmail-one-click').is(':checked');
    if (enabled_gmail) {
        jQuery('.gmail_api-outer').addClass('gmail-enabled');
    }

    var enabled_office = jQuery('.ps-enable-office365-one-click').is(':checked');
    if (enabled_office) {
        jQuery('.office365_api-outer').addClass('office-enabled');
    }

    jQuery(document).on('click', 'table tr:last-child .ps-wizard-edit', function (e) {
        e.preventDefault();
        jQuery('.ps-wizard-outer').addClass('ps-wizard-send-email');
    });

    /**
     * Refresh the wizard to show the current step
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const refreshWizard = function () {

        var tableRow = jQuery('.ps-wizard-nav table tr.ps-active-nav');
        var activeTab = jQuery(tableRow).hasClass('ps-active-nav');

        if (activeTab) {

            var stepID = jQuery(tableRow).find('.dashicons-edit').data('step');
            jQuery('.ps-wizard-step').hide();
            jQuery(`.ps-wizard-step-${stepID}`).fadeIn('slow');

            // When we arrive on the final step for the first time,
            // trigger a short confetti animation on the mobile app banner.
            if (stepID === 4 && !finalStepConfettiShown && typeof party !== 'undefined') {

                const target = jQuery('.ps-home-middle-right')[0] || jQuery(`.ps-wizard-step-${stepID}`)[0];

                if (target) {
                    party.confetti(target, {
                        count: party.variation.range(20, 80),
                        size: party.variation.range(0.8, 1.2),
                    });
                    finalStepConfettiShown = true;
                }
            }

        }

        jQuery('.ps-wizard-line').removeClass('ps-email-tester-line');
        jQuery('.ps-dns-results').remove();

    }


    /**
     * Validate the current step
     * 
     * @param {int} stepID Current Step ID
     * @return bool
     * @since 2.0.0
     * @version 1.0.0
     */
    const validateStep = function (stepID) {

        //Validate step 1
        if (stepID == 1) {

            if (jQuery('.ps-wizard-socket-check').is(':checked')) {

                var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
                var _element = jQuery('.ps-wizard-outer').removeClass();
                jQuery(_element).addClass('ps-wizard-outer');
                jQuery(_element).addClass(`${selectedSocket}-outer`);
                //Remove Warning (if has), and contiue to next step
                jQuery('.ps-wizard-error').html('');
                jQuery('.ps-wizard-page-footer').fadeOut();
                renderSocketSettings();
                return true;

            }
            else {

                jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step1E1}`);

            }

        }

        //Validate step 2
        if (stepID == 2) {

            var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
            selectedSocket = jQuery(`.${selectedSocket}`).find('input');
            var validated = true;

            jQuery(selectedSocket).each(function (index, element) {

                var attr = jQuery(element).attr('required');

                if (typeof attr !== 'undefined' && attr !== false && jQuery(element).val() == '') {

                    var error = jQuery(element).data('error');
                    jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${error}`);
                    validated = false;
                    jQuery(element).focus();
                    return false;

                }
                else {

                    //Validate From Name, From Email
                    jQuery('.ps-name-email-settings').find('input').each(function (index, element) {

                        var attr = jQuery(element).attr('required');

                        if (typeof attr !== 'undefined' && attr !== false && jQuery(element).val() == '') {

                            var error = jQuery(element).data('error');
                            jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${error}`);
                            validated = false;
                            jQuery(element).focus();
                            return false;

                        }
                        else {

                            //remove error, since everything is good to go :).
                        }
                    });

                }

            });

            // Validate Email Format for From Email (only check that it contains "@")
            jQuery('.ps-name-email-settings').find('.ps-from-email').each(function (index, element) {

                var email = jQuery(element).val();
                if (email !== '' && email.indexOf('@') === -1) {
                    var error = 'Invalid Email Address';
                    jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${error}`);
                    validated = false;
                    jQuery(element).focus();
                    return false;
                }

            });

            //If everything is good to go, lets save settings.
            if (validated === true) {

                var button = jQuery('.ps-wizard-step-2').find('.ps-wizard-next-btn');
                var buttonHTML = jQuery(button).html();
                jQuery(button).html('Saving...');

                // Step 1: Get selected socket from step 1
                var $step1 = jQuery('.ps-wizard-step-1');
                var selectedSocket = $step1.attr('data-socket');

                if (selectedSocket) {
                    // Convert socket key to match input class (e.g., sendgrid_api -> ps-sendgrid-api-key)
                    var classSelector = '.ps-' + selectedSocket.replace('_', '-') + '-key';
                    var apiKeyInput = jQuery(classSelector);

                    if (apiKeyInput.length > 0) {
                        var apiKey = apiKeyInput.val();
                        $step1.attr('data-apikey', apiKey);
                    }
                }

                //Lets AJAX request.
                jQuery.ajax({

                    url: ajaxurl,
                    type: 'POST',
                    async: true,
                    data: {
                        action: 'ps-save-wizard',
                        FormData: jQuery('#ps-wizard-form').serialize(),
                    },

                    success: function (response) {

                        jQuery('.ps-wizard-error').html('');
                        nextStep(stepID);
                        var _element = jQuery('.ps-wizard-outer').removeClass();
                        jQuery(_element).addClass('ps-wizard-outer ps-wizard-send-email');

                    },
                    error: function (response) {

                        jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step2E3}`);

                    },
                    complete: function (response) {

                        jQuery(button).html(buttonHTML);

                    }

                });

            }

        }

        //Validate step 3 | No validation required, we can skip this step.
        if (stepID == 3) {

            return true;

        }

    }


    /**
     * Switch to next step
     * 
     * @param {int} stepID Current Step ID
     * @return void
     * @since 2.0.0
     * @version 1.0.0 
     */
    const nextStep = function (stepID) {

        var nextStep = stepID + 1;

        jQuery(`*[data-step="${stepID}"]`).closest('tr').removeClass('ps-active-nav ps-in-active-nav');
        jQuery(`*[data-step="${nextStep}"]`).closest('tr').removeClass('ps-active-nav ps-in-active-nav');
        jQuery(`*[data-step="${nextStep}"]`).closest('tr').addClass('ps-active-nav');
        refreshWizard();

    }


    /**
     * Switch to step
     * 
     * @param {int} stepID Step ID to be switched to
     * @return void
     * @since 2.0.0
     * @version 1.0.0 
     */
    const switchStep = function (stepID) {

        jQuery('.ps-wizard-nav table tr.ps-active-nav').addClass('ps-in-active-nav');
        jQuery('.ps-wizard-nav table tr.ps-active-nav').removeClass('ps-active-nav');
        jQuery(`*[data-step="${stepID}"]`).closest('tr').addClass('ps-active-nav');
        jQuery('.ps-wizard-success').html('');
        jQuery('.ps-wizard-error').html('');
        jQuery('.ps-wizard-connectivity-information').remove();
        refreshWizard();

    }


    /**
     * Render Socket Settings
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const renderSocketSettings = function () {

        var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
        jQuery('.ps-wizard-socket').hide();
        jQuery(`.${selectedSocket}`).fadeIn('slow');

    }

    /**
     * Gets URL Parameter
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const getUrlParameter = function getUrlParameter(sParam) {

        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;

    };

    //Switch to next step
    jQuery(document).on('click', '.ps-wizard-next-btn', function (e) {

        e.preventDefault();

        var stepID = jQuery(this).data('step');

        // If this is the Step 1 "Continue" button and it's disabled,
        // do not allow navigation.
        if (stepID == 1 && jQuery(this).hasClass('ps-disabled')) {
            return;
        }

        var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
        var selectedFromEmail = jQuery('.ps-from-email').val();
        jQuery('.ps-wizard-step-1').attr('data-socket', selectedSocket);
        jQuery('.ps-wizard-step-2').attr('data-from-email', selectedFromEmail);

        if (validateStep(stepID) === true) {

            nextStep(stepID);

        }
        var enabled_gmail = jQuery('.ps-enable-gmail-one-click').is(':checked');
        if (enabled_gmail) {
            jQuery('.gmail_api-outer').addClass('gmail-enabled');
        }

        var enabled_office = jQuery('.ps-enable-office365-one-click').is(':checked');
        if (enabled_office) {
            jQuery('.office365_api-outer').addClass('office-enabled');
        }

    });


    //Switch to step | Edit Step
    jQuery(document).on('click', '.ps-wizard-nav table tr td .dashicons-edit, .ps-wizard-back', function (e) {

        e.preventDefault();

        var stepID = jQuery(this).data('step');

        // When attempting to go to Step 2 directly from the sidebar,
        // enforce the same validation as the "Continue" button.
        // If no mailer is selected, stay on Step 1 and show the error.
        if (stepID == 2) {

            var hasSelection = jQuery('.ps-wizard-socket-check').is(':checked');

            if (!hasSelection) {

                jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step1E1}`);
                return;

            }

        }

        if (stepID == 1) {

            jQuery('.ps-wizard-page-footer').fadeIn('slow');

        }

        if (stepID !== 2) {

            var _element = jQuery('.ps-wizard-outer').removeClass();
            jQuery(_element).addClass('ps-wizard-outer');

        }
        if (stepID == 2) {

            var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
            var _element = jQuery('.ps-wizard-outer').removeClass();
            jQuery(_element).addClass('ps-wizard-outer');
            jQuery(_element).addClass(`${selectedSocket}-outer`);

        }

        switchStep(stepID);

        var enabled_gmail = jQuery('.ps-enable-gmail-one-click').is(':checked');
        if (enabled_gmail) {
            jQuery('.gmail_api-outer').addClass('gmail-enabled');
        }

        var enabled_office = jQuery('.ps-enable-office365-one-click').is(':checked');
        if (enabled_office) {
            jQuery('.office365_api-outer').addClass('office-enabled');
        }

    });


    //Send test email
    jQuery(document).on('click', '.ps-wizard-send-test-email', function (e) {

        e.preventDefault();
        jQuery('#ps-dns-results__el_id').empty();
        var sendTo = jQuery('.ps-test-to').val();
        var security = jQuery('#security').val();
        var socket = jQuery('.ps-wizard-step-1').attr('data-socket');
        var apikey = jQuery('.ps-wizard-step-1').attr('data-apikey');
        var fromEmail = jQuery('.ps-wizard-step-2').attr('data-from-email');
        var $btn = jQuery(this);
        $btn.prop('disabled', true);

        if (sendTo == '') {
            jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step3E4}`);
            return;

        }

        jQuery.ajax({

            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'postman_send_test_email',
                email: sendTo,
                security: security,
            },
            success: function (response) {

                jQuery('.ps-wizard-success').html('');

                if (response.success === true) {

                    jQuery('.ps-wizard-success').html(`<span class="dashicons dashicons-yes"></span> ${response.data.message}`);
                    jQuery('.ps-finish-wizard').html(`${PostSMTPWizard.finish} <span class="dashicons dashicons-arrow-right-alt">`);

                    jQuery('.ps-wizard-error').html('');
                    jQuery('.ps-wizard-health-report').html(
                        `<div class="ps-loading-test-report">
                       
                        <p>Please wait, we are checking your email health.</p>
                    </div>`
                    );
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ps-mail-test',
                            email: sendTo,
                            security: security,
                            socket: socket,
                            apikey: apikey,
                            from: fromEmail
                        },
                        success: function (response) {

                            jQuery('.ps-loading-test-report').remove();
                            $btn.prop('disabled', false)
                            if (response.data.message !== undefined && response.data.message === 'test_email_sent') {

                                var title = response.data.data.title;
                                var spf = response.data.data.spf;
                                var dkim = response.data.data.dkim;
                                var dmarc = response.data.data.dmarc;

                                var successIcon = 'dashicons-yes-alt';
                                var warningIcon = 'dashicons-warning';
                                var successClass = 'ps-dns-pass';
                                var warningClass = 'ps-dns-fail';
                                var spfIcon = spf === 'pass' ? `<span class="dashicons ps-dns-status-icon ${successIcon} ${successClass}"></span>` : `<span class="dashicons ps-dns-status-icon ${warningIcon} ${warningClass}"></span>`;
                                var dkimIcon = dkim === 'pass' ? `<span class="dashicons ps-dns-status-icon ${successIcon} ${successClass}"></span>` : `<span class="dashicons ps-dns-status-icon ${warningIcon} ${warningClass}"></span>`;
                                var dmarcIcon = dmarc === 'pass' ? `<span class="dashicons ps-dns-status-icon ${successIcon} ${successClass}"></span>` : `<span class="dashicons ps-dns-status-icon ${warningIcon} ${warningClass}"></span>`;
                                var spfStatus = spf === 'pass' ? 'ps-dns-pass' : 'ps-dns-fail';
                                var dkimStatus = dkim === 'pass' ? 'ps-dns-pass' : 'ps-dns-fail';
                                var dmarcStatus = dmarc === 'pass' ? 'ps-dns-pass' : 'ps-dns-fail';
                                var spfDescription = '';
                                var dkimDescription = '';
                                var dmarcDescription = '';

                                // SPF
                                if (spf === 'pass') {
                                    spfDescription = 'Great! Your SPF is valid.';
                                }
                                if (spf === 'none') {
                                    spfDescription = 'We found an SPF entry on your server but it has still not been propagated.';
                                }
                                if (spf === 'neutral') {
                                    spfDescription = 'SPF: sender does not match SPF record (neutral).';
                                }
                                if (spf === 'softfail') {
                                    spfDescription = 'SPF: sender does not match SPF record (softfail).';
                                }
                                if (spf === 'permerror') {
                                    spfDescription = 'SPF: test of record failed (permerror).';
                                }

                                // DKIM
                                if (dkim === 'pass') {
                                    dkimDescription = 'Your DKIM signature is valid.';
                                }
                                if (dkim === 'none') {
                                    dkimDescription = 'Your message is not signed with DKIM.';
                                }
                                if (dkim === 'fail') {
                                    dkimDescription = 'Your DKIM signature is not valid.';
                                }

                                // DMARC
                                if (dmarc === 'pass') {
                                    dmarcDescription = 'Your message passed the DMARC test.';
                                }
                                if (dmarc === 'missingentry') {
                                    dmarcDescription = 'You do not have a DMARC record.';
                                }
                                if (dmarc === 'alignment') {
                                    dmarcDescription = 'Your domains are not aligned. We can\'t check DMARC.';
                                }
                                if (dmarc === 'dkimmissing') {
                                    dmarcDescription = 'Your message is not signed with DKIM.';
                                }
                                if (dmarc === 'unkown') {
                                    dmarcDescription = 'Your message failed the DMARC verification.';
                                }

                                if (dmarc === 'fail') {
                                    dmarcDescription = 'Your message failed the DMARC verification.';
                                }


                                var ps_dns_results = `<div id="ps-dns-results__el_id" class="ps-dns-results">
                                            <p class="ps-dns-heading">${title}</p>
                                            <div class="ps-dns-record">
                                                ${spfIcon}
                                                <b>SPF record status: <span class="${spfStatus}">${spf}</span></b>
                                                <p>SPF record description: ${spfDescription}</p>
                                            </div>
                                            <div class="ps-dns-record">
                                                ${dkimIcon}
                                                <b>DKIM record status: <span class="${dkimStatus}">${dkim}</span></b>
                                                <p>DKIM record description: ${dkimDescription}</p>
                                            </div>
                                            <div class="ps-dns-record">
                                                ${dmarcIcon}
                                                <b>DMARC record status: <span class="${dmarcStatus}">${dmarc}</span></b>
                                                <p>DMARC record description: ${dmarcDescription}</p>
                                            </div>
                                            <b class="ps-dns-footer">To check and improve your email spam score! <a href="https://postmansmtp.com/domain-health-checker/?utm_source=plugin&utm_medium=test_email_dns_check&utm_campaign=plugin" target="_blank">Click Here</a><span class="dashicons dashicons-external"></span></b>
                                        </div>`;
                                if (jQuery('#ps-dns-results__el_id').length) {
                                    jQuery('#ps-dns-results__el_id').remove();
                                }
                                jQuery('.ps-wizard-success:nth(0)').after(
                                    ps_dns_results
                                );

                                //jQuery( '.ps-wizard-footer-left .ps-in-active-nav .ps-wizard-line:after' ).css( { 'height': '417px' } );
                                jQuery('.ps-wizard-footer-left').find('.ps-in-active-nav').find('.ps-wizard-line').addClass('ps-email-tester-line');

                            } else if (response.data.message !== undefined && response.data.message === 'default_mailer') {
                                if (jQuery('#ps-dns-results__el_id').length) {
                                    jQuery('#ps-dns-results__el_id').remove();
                                }
                            }
                            else {
                                $btn.prop('disabled', false);
                                if (jQuery('#ps-dns-results__el_id').length) {
                                    jQuery('#ps-dns-results__el_id').remove();
                                }

                                jQuery('.ps-wizard-success:nth(0)').after(
                                    `<div id="ps-dns-results__el_id">
                                            <b class="ps-dns-footer" style="padding-left: 12px;">Limit Exceed! To check and improve your email spam score! <a href="https://postmansmtp.com/domain-health-checker/?utm_source=plugin&utm_medium=test_email_dns_check&utm_campaign=plugin" target="_blank">Click Here</a><span class="dashicons dashicons-external"></span></b>
                                        </div>`
                                );

                            }

                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $btn.prop('disabled', false);
                            jQuery('.ps-loading-test-report').remove();
                            if (jqXHR.status === 429) {

                                if (jQuery('#ps-dns-results__el_id').length) {
                                    jQuery('#ps-dns-results__el_id').remove();
                                }
                                jQuery('.ps-wizard-health-report').after(
                                    `<div id="ps-dns-results__el_id">
                                            <b class="ps-dns-footer" style="padding-left: 12px;">Limit Exceed! Please try again later. 
                                            <a href="https://postmansmtp.com/domain-health-checker/?utm_source=plugin&utm_medium=test_email_dns_check&utm_campaign=plugin" target="_blank">Click Here</a>
                                            <span class="dashicons dashicons-external"></span></b>
                                        </div>`
                                );
                            } else {
                                console.log("Error: ", errorThrown);
                            }
                        }
                    });

                }
                if (response.success === false) {
                    $btn.prop('disabled', false);
                    var selectedSocket = jQuery('.ps-wizard-socket-check:checked').val();
                    jQuery('.ps-wizard-error').html(`<span class="dashicons dashicons-warning"></span> ${response.data.message} <br><br>`);
                    // jQuery('.ps-wizard-error').append(`<span class="dashicons dashicons-warning"></span> Test email failed. Please check and correct your SMTP configuration. The Email Health Checker cannot proceed until a test email is successfully sent.`);

                    if (selectedSocket === 'smtp') {

                        jQuery('.ps-wizard-error').after(`<div class="ps-wizard-connectivity-information">${PostSMTPWizard.connectivityTestMsg}</div>`);

                    }

                }

            }

        });

    });


    //Connect to Office 365 | Auth
    jQuery(document).on('click', '#ps-wizard-connect-office365', function (e) {

        e.preventDefault();

        var office365_app_id = jQuery('.ps-office365-client-id').val();
        var office365_app_password = jQuery('.ps-office365-client-secret').val();
        var _button = jQuery(this).html();

        if (office365_app_id == '') {

            jQuery('.ps-office365-client-id').focus();
            return;

        }
        if (office365_app_password == '') {

            jQuery('.ps-office365-client-secret').focus();
            return;

        }

        jQuery(this).html('Redirecting...');
        var tenant = PostSMTPWizard.tenantId || 'common';
        var authURL = `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/authorize?state=${PostSMTPWizard.office365State}&scope=openid profile offline_access Mail.Send Mail.Send.Shared&response_type=code&approval_prompt=auto&redirect_uri=${PostSMTPWizard.adminURL}&client_id=${office365_app_id}`;

        jQuery.ajax({

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },

            success: function (response) {

                window.location.assign(authURL);

            },

        });

    });


    //Connect to Gmail API | Auth
    jQuery(document).on('click', '#ps-wizard-connect-gmail', function (e) {

        e.preventDefault();

        var clientID = jQuery('.ps-gmail-api-client-id').val();
        var clientSecret = jQuery('.ps-gmail-client-secret').val();
        var redirectURI = jQuery(this).attr('href');

        if (clientID == '') {

            jQuery('.ps-gmail-api-client-id').focus();
            return;

        }
        if (clientSecret == '') {

            jQuery('.ps-gmail-client-secret').focus();
            return;

        }

        jQuery(this).html('Redirecting...');

        jQuery.ajax({

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },

            success: function (response) {

                window.location.assign(redirectURI);

            },

        });

    });

    //Connect to Zoho | Auth
    jQuery(document).on('click', '#ps-wizard-connect-zoho', function (e) {

        e.preventDefault();

        var clientID = jQuery('.ps-zoho-client-id').val();
        var clientSecret = jQuery('.ps-zoho-client-secret').val();
        var redirectURI = jQuery(this).attr('href');

        if (clientID == '') {

            jQuery('.ps-zoho-client-id').focus();
            return;

        }
        if (clientSecret == '') {

            jQuery('.ps-zoho-client-secret').focus();
            return;

        }

        jQuery(this).html('Redirecting...');

        jQuery.ajax({

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },

            success: function (response) {

                window.location.assign(redirectURI);

            },

        });

    });

    // Gmail One-Click auth
    jQuery(document).on('click', '.ps-gmail-oneclick-btn', function (e) {

        e.preventDefault();

        var $button = jQuery(this);
        var originalText = $button.text();

        $button.prop('disabled', true).text('Redirecting...');

        // First, save the current wizard settings (From Name/Email, etc.).
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },
            success: function () {
                // Then, request a fresh Gmail One-Click auth URL with a fresh nonce.
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ps_get_gmail_auth_url',
                        nonce: (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.gmail_auth_nonce) ? PostSMTPWizard.gmail_auth_nonce : ''
                    },
                    success: function (response) {
                        if (response.success && response.data && response.data.auth_url) {
                            window.location.assign(response.data.auth_url);
                        } else {
                            $button.prop('disabled', false).text(originalText);
                            if (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.gmailAuthErrorText) {
                                alert(PostSMTPWizard.gmailAuthErrorText);
                            }
                        }
                    },
                    error: function () {
                        $button.prop('disabled', false).text(originalText);
                        if (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.gmailAuthErrorText) {
                            alert(PostSMTPWizard.gmailAuthErrorText);
                        }
                    }
                });
            },
            error: function () {
                $button.prop('disabled', false).text(originalText);
                if (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.gmailAuthErrorText) {
                    alert(PostSMTPWizard.gmailAuthErrorText);
                }
            }
        });

    });

    refreshWizard();

    if (getUrlParameter('socket')) {

        jQuery(document).scrollTop(jQuery(document).height());

    }

    jQuery(document).on('click', '.ps-pro-extension-outer', function () {

        var placeholder = jQuery(this).find('h4').text();
        var imgSrc = jQuery(this).find('img').attr('src');
        var productURL = jQuery(this).data('url');
        imgSrc = imgSrc.replace('.png', '-popup.png');

        console.log(placeholder);
        // placeholder = "Google Mailer Setup?";
        if (placeholder == "Amazon SES") {
            placeholder = 'Amazon SES Mailer?';
        }
        if (placeholder == "Zoho") {
            placeholder = 'Zoho Mailer?';
        }
        if (placeholder == "Microsoft 365") {
            placeholder = 'Microsoft 365 Mailer?';
        }

        jQuery('.ps-pro-for-img').attr('src', imgSrc);
        jQuery('.ps-pro-product-url').attr('href', productURL);
        jQuery('.ps-pro-for').html(placeholder);
        jQuery('.ps-pro-popup-overlay').fadeIn();

    });


    jQuery(document).on('click', '.ps-enable-gmail-one-click', function (e) {

        if (jQuery(this).hasClass('disabled')) {
            e.preventDefault();
            var data = jQuery('#ps-one-click-data').val();
            var parsedData = JSON.parse(data);
            jQuery('.ps-pro-for-img').attr('src', parsedData.url);
            jQuery('.ps-pro-product-url').attr('href', parsedData.product_url);
            jQuery('.ps-pro-for').html(parsedData.transport_name);
            jQuery('.ps-pro-popup-overlay').fadeIn();

            return;
        }
        jQuery(this).prop('disabled', false);
        jQuery(this).removeClass('disabled');

        var enabled = jQuery(this).is(':checked');
        if (enabled) {
            jQuery('.ps-disable-gmail-setup').show();
            jQuery('.gmail_api-outer').addClass('gmail-enabled');
            jQuery('.ps-disable-one-click-setup').hide();
            jQuery('.ps-gmail-api-client-id').removeAttr('required');
            jQuery('.ps-gmail-api-client-secret').removeAttr('required')
            jQuery('#ps-gmail-auth-buttons').show();
        } else {
            jQuery('.gmail_api-outer').removeClass('gmail-enabled');
            jQuery('.ps-disable-one-click-setup').show();
            jQuery('.ps-disable-gmail-setup').hide();
            jQuery('.ps-gmail-api-client-id').attr('required', 'required');
            jQuery('#ps-gmail-auth-buttons').hide();
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'update_post_smtp_pro_option',
                enabled: enabled ? 'gmail-oneclick' : '',
                _wpnonce: (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.pro_option_nonce) ? PostSMTPWizard.pro_option_nonce : ''
            },
            success: function (response) {
                if (response.success) {
                    console.log('Option updated successfully!');
                } else {
                    console.log('Failed to update option.');
                }
            }
        });
    });

    jQuery(document).on('click', '.ps-pro-close-popup', function (e) {

        e.preventDefault();
        jQuery('.ps-pro-popup-overlay').fadeOut();

    });

    jQuery('.ps-click-to-copy').click(function () {

        // Get the coupon code text
        var couponCode = jQuery('.ps-pro-coupon-code').text();

        // Create a temporary textarea element to copy the text
        var $temp = jQuery('<textarea>');
        jQuery('body').append($temp);
        $temp.val(couponCode).select();
        document.execCommand('copy');
        $temp.remove();

        // Show the notification
        jQuery('#ps-pro-code-copy-notification').fadeIn();

        // Hide the notification after 1 second
        setTimeout(function () {
            jQuery('#ps-pro-code-copy-notification').fadeOut();
        }, 2000);

    });

    const gmail_icon = PostSMTPWizard.gmail_icon;
    const office365_icon = PostSMTPWizard.office365_icon;

    const css = `
      .ps-gmail-btn::before {
          background-image: url(${gmail_icon});
      }
      .ps-office365-btn::before {
          background-image: url(${office365_icon});
      }
    `;

    const style = jQuery('<style>').text(css);
    jQuery('head').append(style);
});


jQuery(document).on('click', '.ps-enable-office365-one-click', function (e) {

    // Check if this is a disabled checkbox due to business plan requirement
    if (jQuery(this).is(':disabled') || jQuery(this).closest('.ps-office365-upgrade-required').length > 0) {
        e.preventDefault();
        return false;
    }

    var enabled = jQuery(this).is(':checked');
    if (enabled) {
        jQuery('.ps-disable-one-click-setup').hide();
        jQuery('.office365_api-outer').addClass('office-enabled');
        jQuery('.ps-disable-office365-setup').show();
        jQuery('.ps-office365-client-id').removeAttr('required');
        jQuery('.ps-office365-client-secret').removeAttr('required')
    } else {
        jQuery('.office365_api-outer').removeClass('office-enabled');
        jQuery('.ps-disable-office365-setup').hide();
        jQuery('.ps-disable-one-click-setup').show();
        jQuery('.ps-office365-client-id').attr('required', 'required');
        jQuery('.ps-office365-client-secret').attr('required', 'required');
    }

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        async: true,
        data: {
            action: 'update_post_smtp_pro_option_office365',
            enabled: enabled ? 'microsoft-one-click' : '',
            _wpnonce: (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.pro_option_nonce) ? PostSMTPWizard.pro_option_nonce : ''
        },
        success: function (response) {
            if (response.success) {
                console.log('Option updated successfully!');

                // Handle office_365-require field based on access token and email availability
                if (response.data && (typeof response.data.has_access_token !== 'undefined' || typeof response.data.has_email !== 'undefined')) {
                    var hasAccessToken = !!response.data.has_access_token;
                    var hasEmail = !!response.data.has_email;
                    var office365RequireField = jQuery('.office_365-require');

                    // If one-click is enabled, require authentication unless both token and email are present
                    if (enabled) {
                        if (hasAccessToken && hasEmail) {
                            // One-click enabled and fully authenticated (token + email) - no validation required
                            office365RequireField.removeAttr('required');
                            office365RequireField.removeAttr('data-error');
                            office365RequireField.val('1');
                        } else {
                            // One-click enabled but missing token or email - require authentication
                            office365RequireField.attr('required', 'required');
                            office365RequireField.attr('data-error', 'Please authenticate by clicking Connect to Office 365 API');
                            office365RequireField.val('');
                        }
                    } else {
                        // One-click disabled (normal setup) - always require authentication.
                        // Even if a stored email/token exists, user must explicitly authenticate when one-click is off.
                        office365RequireField.attr('required', 'required');
                        office365RequireField.attr('data-error', 'Please authenticate by clicking Connect to Office 365');
                        office365RequireField.val('');
                    }
                }
            } else {
                console.log('Failed to update option.');
            }
        }
    });

});

// Handle Office 365 upgrade required click
jQuery(document).on('click', '.ps-office365-upgrade-required', function (e) {
    e.preventDefault();

    // Check if this is a version warning or business plan requirement
    if (jQuery('.ps-version-warning-notice').length > 0) {
        // Prefer the Step 2 wizard error container (the one shown for mailer settings)
        var $errorSection = jQuery('.ps-wizard-step-2 .ps-wizard-error');

        // Fallback to the first visible wizard error container if Step 2 one is not found
        if (!$errorSection.length) {
            $errorSection = jQuery('.ps-wizard-error:visible').first();
        }

        // As a last resort, fall back to any wizard error container
        if (!$errorSection.length) {
            $errorSection = jQuery('.ps-wizard-error').first();
        }

        if ($errorSection.length) {
            // Show version update message in the resolved error area instead of popup
            $errorSection.html('<span class="dashicons dashicons-warning"></span> <strong>Post SMTP Pro Update Required!</strong><br> Office 365 One-Click Setup requires the latest version of Post SMTP Pro (v1.5.0 or higher).<br>Please update your Post SMTP Pro plugin to use this feature.');

            // Scroll to the resolved error message container
            jQuery('html, body').animate({
                scrollTop: $errorSection.offset().top - 100
            }, 500);
        }

        return false;
    }

    // Get data for the popup (business plan upgrade case)
    var data = jQuery('#ps-one-click-data-office365').val();
    var parsedData = JSON.parse(data);

    // Set popup content for Office 365
    var office365Img = parsedData.url;
    // Convert to popup image (replace .png with -popup.png)
    office365Img = office365Img.replace('.png', '-popup.png');

    var upgradeUrl = parsedData.product_url;
    var serviceName = parsedData.transport_name;

    jQuery('.ps-pro-for-img').attr('src', office365Img);
    jQuery('.ps-pro-product-url').attr('href', upgradeUrl);
    jQuery('.ps-pro-for').html(serviceName);
    jQuery('.ps-pro-popup-overlay').fadeIn();

    return false;
});


jQuery(document).ready(function ($) {
    const toggleFields = () => {
        const isChecked = $('.ps-enable-gmail-one-click').is(':checked');

        // Show/Hide Gmail Authorization button
        jQuery('#ps-wizard-connect-gmail').closest('tr').toggle(isChecked);

        // Show/Hide Client ID and Client Secret fields
        jQuery('#oauth_client_id, #oauth_client_secret, #input_oauth_callback_domain, #input_oauth_redirect_url')
            .closest('tr')
            .toggle(!isChecked);
    };

    // Initialize Office 365 validation based on current state
    const initOffice365Validation = () => {
        const office365OneClickEnabled = $('.ps-enable-office365-one-click').is(':checked');
        const office365RequireField = jQuery('.office_365-require');

        // Don't override the PHP-set validation state on page load
        // The PHP already sets the correct 'required' attribute based on access token existence
        // Only set validation if one-click is currently enabled but we need to handle dynamic changes

        // If one-click is enabled, we might need to check access token status via AJAX
        // For normal setup, trust the PHP-set validation state
    };

    // Initialize visibility on page load
    toggleFields();
    initOffice365Validation();

    // Listen for changes on the checkbox
    jQuery('.ps-enable-gmail-one-click').on('change', toggleFields);

});

jQuery(document).ready(function ($) {
    $(".ps-form-control p, .ps-form-ui p, .ps-wizard-socket p").each(function () {
        var $p = $(this);
        var words = $p.html().trim().split(/\s+/);

        if (words.length > 15) {
            var fullText = $p.html();
            var shortText = words.slice(0, 15).join(" ") + "...";

            // Save original in data attributes
            $p.data("full-text", fullText);
            $p.data("short-text", shortText);

            // Start with short text
            // $p.html(shortText + ' <a href="#" class="ps-toggle-text">Show More</a>');
        }
    });

    // Toggle handler
    $(document).on("click", ".ps-toggle-text", function (e) {
        e.preventDefault();
        var $link = $(this);
        var $p = $link.closest("p");

        if ($link.text() === "Show More") {
            $p.html($p.data("full-text") + ' <a href="#" class="ps-toggle-text">Show Less</a>');
        } else {
            $p.html($p.data("short-text") + ' <a href="#" class="ps-toggle-text">Show More</a>');
        }
    });
    jQuery(document).on('click', '.ps-gmail-btn', function (e) {
        e.preventDefault();
        var redirectURI = jQuery(this).attr('href');
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },
            success: function (response) {
                window.location.assign(redirectURI);

            },

        });
    });

    jQuery(document).on('click', '.ps-office365-btn', function (e) {
        e.preventDefault();
        var redirectURI = jQuery(this).attr('href');
        var $button = jQuery(this);
        var originalText = $button.text();

        $button.prop('disabled', true).text('Redirecting...');

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery('#ps-wizard-form').serialize(),
            },
            success: function (response) {
                // Then, request a fresh Office One-Click auth URL with a fresh nonce.
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ps_get_office365_auth_url',
                        nonce: (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.office365_auth_nonce) ? PostSMTPWizard.office365_auth_nonce : ''
                    },
                    success: function (response) {
                        if (response.success && response.data && response.data.auth_url) {
                            window.location.assign(response.data.auth_url);
                        } else {
                            $button.prop('disabled', false).text(originalText);
                            if (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.office365AuthErrorText) {
                                alert(PostSMTPWizard.office365AuthErrorText);
                            }
                        }
                    },
                    error: function () {
                        $button.prop('disabled', false).text(originalText);
                        if (typeof PostSMTPWizard !== 'undefined' && PostSMTPWizard.office365AuthErrorText) {
                            alert(PostSMTPWizard.office365AuthErrorText);
                        }
                    }
                });

            },

        });
    });

    jQuery(function ($) {
        jQuery(".ps-form-ui.ps-wizard-socket.gmail_api h3")
            .first()
            .text("Google Workspace (Gmail) API");

        jQuery(".ps-form-ui.ps-wizard-socket.mandrill_api h3")
            .first()
            .text("Mandrill (Mailchimp Transactional) API");

        jQuery(".ps-form-ui.ps-wizard-socket.office365_api h3")
            .first()
            .text("Microsoft Office 365 API");

        jQuery(".ps-form-ui.ps-wizard-socket.aws_ses_api h3")
            .first()
            .text("Amazon (AWS) SES API");

        jQuery(".ps-form-ui.ps-wizard-socket.postmark_api h3")
            .first()
            .text("PostMark API");

        jQuery(".ps-form-ui.ps-wizard-socket.mailtrap_api h3")
            .first()
            .text("Mailtrap API ");

        jQuery(".ps-form-ui.ps-wizard-socket.sweego_api h3")
            .first()
            .text("Sweego API");

        jQuery(".ps-form-ui.ps-wizard-socket.elasticemail_api h3")
            .first()
            .text("Elastic Email API");

        jQuery(".ps-form-ui.ps-wizard-socket.smtp2go_api h3")
            .first()
            .text("SMTP2GO API");

        jQuery(".ps-form-ui.ps-wizard-socket.mailjet_api h3")
            .first()
            .text("Mailjet API");

        jQuery(".ps-form-ui.ps-wizard-socket.sparkpost_api h3")
            .first()
            .text("SparkPost API");

        jQuery(".ps-form-ui.ps-wizard-socket.sendpulse_api h3")
            .first()
            .text("SendPulse API ");
    });

});