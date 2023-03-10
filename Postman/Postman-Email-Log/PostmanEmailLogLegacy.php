<?php
// Create an instance of our package class...
$testListTable = new PostmanEmailLogView();
wp_enqueue_script( 'postman-email-logs-script' );
// Fetch, prepare, sort, and filter our data...
$testListTable->prepare_items();
?>
<div class="wrap">

	<div id="icon-users" class="icon32">
		<br />
	</div>
	<h2><?php
	/* Translators where (%s) is the name of the plugin */
		echo sprintf( __( '%s Email Log', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) )?></h2>

    <?php //include_once POST_SMTP_PATH . '/Postman/extra/donation.php'; ?>

	<div class="ps-config-bar">
		<p><?php

		echo __( 'This is a record of deliveries made to the mail server. It does not neccessarily indicate sucessful delivery to the recipient.', 'post-smtp' )?></p>
	</div>

	<?php
	$from_date = isset( $_GET['from_date'] ) ? sanitize_text_field( $_GET['from_date'] ) : '';
	$to_date = isset( $_GET['to_date'] ) ? sanitize_text_field( $_GET['to_date'] ) : '';
	$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
	$page_records = apply_filters( 'postman_log_per_page', array( 10, 15, 25, 50, 75, 100 ) );
	$postman_page_records = isset( $_GET['postman_page_records'] ) ? absint( $_GET['postman_page_records'] ) : '';
	?>

	<form id="postman-email-log-filter" action="<?php echo admin_url( PostmanUtils::POSTMAN_EMAIL_LOG_PAGE_RELATIVE_URL ); ?>" method="get">
        <input type="hidden" name="page" value="postman_email_log">
        <input type="hidden" name="post-smtp-filter" value="1">
        <?php wp_nonce_field('post-smtp', 'post-smtp-log-nonce'); ?>

		<div id="email-log-filter" class="postman-log-row">
			<div class="form-control">
				<label for="from_date"><?php _e( 'From Date', 'post-smtp' ); ?></label>
				<input id="from_date" class="email-log-date" value="<?php echo esc_attr($from_date); ?>" type="text" name="from_date" placeholder="<?php _e( 'From Date', 'post-smtp' ); ?>">
			</div>
			<div class="form-control">
				<label for="to_date"><?php _e( 'To Date', 'post-smtp' ); ?></label>
				<input id="to_date" class="email-log-date" value="<?php echo esc_attr($to_date); ?>" type="text" name="to_date" placeholder="<?php _e( 'To Date', 'post-smtp' ); ?>">
			</div>
			<div class="form-control">
				<label for="search"><?php _e( 'Search', 'post-smtp' ); ?></label>
				<input id="search" type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e( 'Search', 'post-smtp' ); ?>">
			</div>
			<div class="form-control">
				<label id="postman_page_records"><?php _e( 'Records per page', 'post-smtp' ); ?></label>
				<select id="postman_page_records" name="postman_page_records">
					<?php
					foreach ( $page_records as $value ) {
						$selected = selected( $postman_page_records, $value, false );
						echo '<option value="' . $value . '"' . $selected . '>' . $value . '</option>';
					}
					?>
				</select>
			</div>

            <div class="form-control" style="padding: 0 5px 0 5px;">
                <button type="submit" name="filter" class="ps-btn-orange"><?php _e( 'Filter/Search', 'post-smtp' ); ?></button>
            </div>

            <div class="form-control" style="padding: 0 5px 0 0px;">
                <button type="submit" id="postman_export_csv" name="postman_export_csv" class="ps-btn-orange"><?php _e( 'Export To CSV', 'post-smtp' ); ?></button>
            </div>

			<div class="form-control">
				<button type="submit" id="postman_trash_all" name="postman_trash_all" class="ps-btn-red"><?php _e( 'Trash All', 'post-smtp' ); ?></button>
			</div>

        </div>
		<div class="error">Please notice: when you select a date for example 11/20/2017, behind the scene the query select <b>11/20/2017 00:00:00</b>.<br>So if you searching for an email arrived that day at any hour you need to select 11/20/2017 as the <b>From Date</b> and 11/21/2017 as the <b>To Date</b>.</div>
	</form>

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="movies-filter" method="get">
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<input type="hidden" name="page"
			value="<?php echo filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ); ?>" />

		<!-- Now we can render the completed list table -->
			<?php $testListTable->display()?>
		</form>

		<?php add_thickbox(); ?>

</div>