<?php
$postman_dismiss_donation = get_option('postman_dismiss_donation');

if ( empty( $postman_dismiss_donation ) || isset( $in_wizard ) ) : ?>
    <div class="updated settings-error notice is-dismissible post-smtp-donation">
        <p style="font-size: 1.1em;">It is hard to continue development and support for this free plugin without contributions from users like you.<br>
            If you enjoy using <strong>Post SMTP</strong> and find it useful, please consider making a donation.<br>
            Your donation will help encourage and support the plugin's continued development and better user support.</p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick" />
            <input type="hidden" name="hosted_button_id" value="4B3PJANHBA7MG" />
            <input type="image" src="https://www.paypalobjects.com/en_US/IL/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
            <img alt="" border="0" src="https://www.paypal.com/en_IL/i/scr/pixel.gif" width="1" height="1" />
        </form>
        <button style="z-index: 100;" data-security="<?php echo wp_create_nonce('postsmtp'); ?>" type="button" class="notice-dismiss donation-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
    </div>
<?php endif; ?>