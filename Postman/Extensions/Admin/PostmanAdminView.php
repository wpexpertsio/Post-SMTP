<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style>
    .form-table .row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .form-table .row .flex > *:not(:last-child) {
        margin-right: 5px;
    }

    .form-table .label {
        align-self: center;
        font-weight: bold;
    }

    .form-table .flex {
        display: flex;
    }

    .form-table .flex input {
        border-radius: 3px;
        height: 30px;
        margin: 0;
        margin-left: 5px;
    }

    .form-table .flex button {
        box-shadow: none;
        height: 100%;
    }
</style>

<div class="wrap">
    <h1>Post SMTP Installed Extensions</h1>
    <form action="" method="post">
        <div class="form-table">
            <?php
            $PostmanLicenseManager = PostmanLicenseManager::get_instance();
            $extensions = $PostmanLicenseManager->get_extensions();

            foreach ( $extensions as $slug => $extension) :
                $short_name = $extension['license_manager']->get_slug( $extension['plugin_data']['Name'] );
                $nonce = $short_name . '_license_key-nonce';

                $license_data = get_option( $short_name . '_license_active' );
                $license_key = get_option( $short_name . '_license_key' );

                $license_valid = is_object( $license_data ) && $license_data->license === 'valid';
                $license_field_class  = $license_valid ? 'readonly' : '';
                $license_field_value  = $license_valid ? base64_encode($license_key) : '';

                wp_nonce_field( $nonce, $nonce );
                ?>

                <div class="row">
                    <div class="label">
                        <?php echo esc_html( $extension['plugin_data']['Name'] ); ?>
                    </div>

                    <div class="flex">
                        <div class="input">
                            <input <?php echo $license_field_class; ?>
                                    type="password"
                                    name="post_smtp_extension[<?php echo $short_name . '_license_key'; ?>]"
                                    class="regular-text"
                                    value="<?php echo $license_field_value; ?>"
                                    placeholder="Serial Key">
                        </div>

                        <div class="buttons">
                            <?php if ( ! $license_valid ) :?>
                                <button type="submit" name="post_smtp_extension[<?php echo $short_name; ?>_activate]" class="button button-primary">Activate</button>
                            <?php endif; ?>

                            <?php if ( $license_data->license === 'expired' ) : ?>
                                <a href="<?php echo $license_data->renew_url; ?>" target="_blank" class="button button-primary">Renew License</a>
                            <?php endif; ?>

                            <button type="submit" name="post_smtp_extension[<?php echo $short_name; ?>_deactivate]" class="button button-secondary">Deactivate</button>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>
    </form>
</div>
