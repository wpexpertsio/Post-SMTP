<?php
/**
 * Email log queries and actions for the revamp admin.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogService {

	/** @var bool|null */
	private static $has_event_type_column = null;

	public static function init(): void {
		add_filter( 'post_smtp_get_logs_query_cols', array( self::class, 'filter_list_query_columns' ), 10, 2 );
	}

	/**
	 * @param string              $columns SELECT column list.
	 * @param array<string,mixed> $args    Query args.
	 */
	public static function filter_list_query_columns( string $columns, array $args ): string {
		if ( empty( $args['include_stored_body_flag'] ) ) {
			return $columns;
		}

		return $columns . ', (CASE WHEN pl.`original_message` IS NOT NULL AND pl.`original_message` <> \'\' THEN 1 ELSE 0 END) AS has_stored_body';
	}

	/**
	 * @param array<string,mixed> $params Request params.
	 * @return array<string,mixed>
	 */
	public function list_logs( array $params ): array {
		$page     = max( 1, (int) ( $params['page'] ?? 1 ) );
		$per_page = max( 1, min( 200, (int) ( $params['per_page'] ?? 50 ) ) );
		$search   = isset( $params['search'] ) ? sanitize_text_field( (string) $params['search'] ) : '';
		$status   = isset( $params['status'] ) ? sanitize_text_field( (string) $params['status'] ) : 'all';
		$mailer   = isset( $params['mailer'] ) ? sanitize_text_field( (string) $params['mailer'] ) : 'all';
		$form     = isset( $params['form'] ) ? sanitize_text_field( (string) $params['form'] ) : 'all';
		$date     = isset( $params['date'] ) ? sanitize_text_field( (string) $params['date'] ) : 'all';

		$query_args = array(
			'start'                    => ( $page - 1 ) * $per_page,
			'end'                      => $per_page,
			'search'                   => $search,
			'order'                    => 'desc',
			'order_by'                 => 'id',
			'columns'                  => $this->list_columns(),
			'include_stored_body_flag' => true,
		);

		if ( 'all' !== $status ) {
			$query_args['status'] = $status;
		}

		if ( 'all' !== $mailer ) {
			$query_args['mailer'] = $mailer;
		}

		if ( 'all' !== $form && $this->has_event_type_column() ) {
			$query_args['form'] = $form;
		}

		$range = EmailLogNormalizer::date_preset_to_range( $date );
		if ( ! empty( $range['from'] ) ) {
			$query_args['from'] = $range['from'];
		}
		if ( ! empty( $range['to'] ) ) {
			$query_args['to'] = $range['to'];
		}

		$logs_query = new \PostmanEmailQueryLog();
		$rows       = $logs_query->get_logs( $query_args );
		$filtered   = $logs_query->get_filtered_rows_count();
		$total      = $logs_query->get_total_row_count();

		$filtered_count = 0;
		if ( is_array( $filtered ) && ! empty( $filtered[0] ) && isset( $filtered[0]->count ) ) {
			$filtered_count = (int) $filtered[0]->count;
		}

		$total_count = 0;
		if ( is_array( $total ) && ! empty( $total[0] ) && isset( $total[0]->count ) ) {
			$total_count = (int) $total[0]->count;
		}

		$rows = $this->attachOpenTrackingEvents( $rows );

		$items = array();
		foreach ( $rows as $row ) {
			$row     = apply_filters( 'ps_email_logs_row', $row );
			$items[] = EmailLogNormalizer::normalize_list_row( $row );
		}

		return array(
			'items'    => $items,
			'total'    => $filtered_count,
			'total_all'=> $total_count,
			'page'     => $page,
			'per_page' => $per_page,
			'facets'   => $this->get_facets(),
		);
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_log( int $id ) {
		$query = new \PostmanEmailQueryLog();
		$log   = $query->get_log( $id );
		if ( empty( $log ) ) {
			return null;
		}

		/**
		 * @param array<string,mixed> $log    Raw log row.
		 * @param int                 $log_id Log ID.
		 */
		$log = apply_filters( 'post_smtp_email_log_detail_row', $log, $id );
		$log = apply_filters( 'ps_email_logs_row', (object) $log );
		if ( is_object( $log ) ) {
			$log = (array) $log;
		}

		return EmailLogNormalizer::normalize_detail( $log );
	}

	/**
	 * @param list<int> $ids Log IDs.
	 */
	public function delete_logs( array $ids ): array {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'No log records selected.', 'post-smtp' ),
			);
		}

		$query  = new \PostmanEmailQueryLog();
		$delete = $query->delete_logs( $ids );
		if ( $delete ) {
			do_action( 'postman_delete_logs_successfully', $ids );
			return array(
				'success' => true,
				'message' => __( 'Logs deleted successfully.', 'post-smtp' ),
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Could not delete logs.', 'post-smtp' ),
		);
	}

	/**
	 * @param array<string,mixed> $params Filters or ids.
	 * @return array{success:bool,message?:string,content?:string,filename?:string,mime?:string}
	 */
	public function export_logs( array $params ): array {
		$format = isset( $params['format'] ) ? sanitize_text_field( (string) $params['format'] ) : 'csv';
		$ids    = isset( $params['ids'] ) && is_array( $params['ids'] ) ? array_map( 'intval', $params['ids'] ) : array();

		if ( ! empty( $ids ) ) {
			$query = new \PostmanEmailQueryLog();
			$rows  = $query->get_all_logs( $ids );
		} else {
			$list  = $this->list_logs(
				array_merge(
					$params,
					array(
						'page'     => 1,
						'per_page' => 10000,
					)
				)
			);
			$ids   = array_map(
				static function ( $item ) {
					return (int) $item['id'];
				},
				$list['items']
			);
			$query = new \PostmanEmailQueryLog();
			$rows  = ! empty( $ids ) ? $query->get_all_logs( $ids ) : array();
		}

		if ( 'json' === $format ) {
			$normalized = array();
			foreach ( $rows as $row ) {
				$normalized[] = EmailLogNormalizer::normalize_list_row( $row );
			}
			return array(
				'success'  => true,
				'content'  => EmailLogExporter::to_json( $normalized ),
				'filename' => 'email-logs.json',
				'mime'     => 'application/json',
			);
		}

		return array(
			'success'  => true,
			'content'  => EmailLogExporter::to_csv( $rows ),
			'filename' => 'email-logs.csv',
			'mime'     => 'text/csv',
		);
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	public function resend( int $id ): array {
		$query = new \PostmanEmailQueryLog();
		$log   = $query->get_log( $id );
		if ( empty( $log ) ) {
			return array(
				'success' => false,
				'message' => __( 'Log not found.', 'post-smtp' ),
			);
		}

		if ( empty( $log['original_message'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Message body was not saved for this email.', 'post-smtp' ),
			);
		}

		$to = ! empty( $log['original_to'] ) ? $log['original_to'] : '';
		if ( empty( $to ) && ! empty( $log['to_header'] ) ) {
			$to = $log['to_header'];
		}

		$headers = EmailLogResendHeaders::for_wp_mail( $log['original_headers'] ?? '' );

		$attachments = apply_filters( 'post_smtp_resend_attachments', array(), $id );

		try {
			$success = wp_mail( $to, $log['original_subject'], $log['original_message'], $headers, $attachments );
		} catch ( \Throwable $exception ) {
			return array(
				'success' => false,
				'message' => $exception->getMessage(),
			);
		}

		$result = apply_filters( 'postman_wp_mail_result', null );

		if ( $success ) {
			return array(
				'success' => true,
				'message' => __( 'Email was successfully re-sent.', 'post-smtp' ),
			);
		}

		$message = __( 'Email could not be re-sent.', 'post-smtp' );
		if ( is_array( $result ) && isset( $result['exception'] ) && is_object( $result['exception'] ) ) {
			$message = $result['exception']->getMessage();
		}

		return array(
			'success' => false,
			'message' => $message,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		$logging_enabled = true;
		if ( class_exists( '\PostmanOptions' ) ) {
			$opts            = \PostmanOptions::getInstance();
			$logging_enabled = $opts->isMailLoggingEnabled();
		}

		$open_tracking = self::openTrackingActive();

		return array(
			'logging_enabled'          => $logging_enabled,
			'is_pro'                   => (bool) $has_pro,
			'show_opened_column'       => $open_tracking,
			'open_tracking_active'     => $open_tracking,
			'show_attachments'         => (bool) $has_pro,
			'show_queued'              => (bool) $has_pro,
			'form_filter_available'    => (bool) $has_pro && $this->has_event_type_column(),
			'advanced_filters_enabled' => (bool) get_option( 'postman_advanced_log_filter', false ),
		);
	}

	public static function openTrackingActive(): bool {
		$active = self::shouldShowOpenTrackingColumn();

		/**
		 * @param bool $active Whether the email log UI should show open-tracking (Opened column, drawer row).
		 */
		return (bool) apply_filters( 'post_smtp_open_tracking_active', $active );
	}

	/**
	 * Opened column / row: licensed Pro + Settings → Email Reporting → Track email opens enabled.
	 */
	private static function shouldShowOpenTrackingColumn(): bool {
		if ( ! defined( 'POST_SMTP_PRO_VERSION' ) ) {
			return false;
		}

		if ( ! function_exists( 'pspro_fs' ) || ! pspro_fs()->can_use_premium_code() ) {
			return false;
		}

		return (bool) apply_filters( 'post_smtp_track_email_opens', false );
	}

	/**
	 * @return array{mailers:list<string>,forms:list<string>}
	 */
	private function get_facets(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'post_smtp_logs';
		$uris  = $wpdb->get_col( "SELECT DISTINCT transport_uri FROM {$table} WHERE transport_uri != '' LIMIT 50" );
		$mailers = array();
		foreach ( $uris as $uri ) {
			$mailers[] = (string) $uri;
		}

		$forms = array();
		if ( $this->has_event_type_column() ) {
			$forms = $wpdb->get_col( "SELECT DISTINCT event_type FROM {$table} WHERE event_type != '' LIMIT 50" );
		}

		return array(
			'mailers' => array_values( array_filter( $mailers ) ),
			'forms'   => array_values( array_filter( array_map( 'strval', $forms ) ) ),
		);
	}

	/**
	 * @return list<string>
	 */
	private function list_columns(): array {
		$columns = array(
			'id',
			'original_subject',
			'to_header',
			'success',
			'time',
			'original_to',
			'transport_uri',
			'from_header',
		);
		if ( $this->has_event_type_column() ) {
			$columns[] = 'event_type';
		}
		return $columns;
	}

	/**
	 * @param list<object|array<string,mixed>> $rows Log rows from the query.
	 * @return list<object|array<string,mixed>>
	 */
	private function attachOpenTrackingEvents( array $rows ): array {
		if ( ! self::openTrackingActive() || ! class_exists( 'Post_SMTP_RAT_DB', false ) || empty( $rows ) ) {
			return $rows;
		}

		$ids = array();
		foreach ( $rows as $row ) {
			$row = (array) $row;
			if ( ! empty( $row['id'] ) ) {
				$ids[] = (int) $row['id'];
			}
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( empty( $ids ) ) {
			return $rows;
		}

		global $wpdb;
		$table        = ( new \Post_SMTP_RAT_DB() )->get_table_name();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only.
		$query   = $wpdb->prepare(
			"SELECT email_id, event_type FROM {$table} WHERE event_type = 'open-email' AND email_id IN ({$placeholders})",
			...$ids
		);
		$matches = $wpdb->get_results( $query, OBJECT_K );
		if ( empty( $matches ) ) {
			return $rows;
		}

		$open_by_log = array();
		foreach ( $matches as $email_id => $record ) {
			$open_by_log[ (int) $email_id ] = 'open-email';
		}

		foreach ( $rows as $index => $row ) {
			if ( is_object( $row ) ) {
				$id = (int) ( $row->id ?? 0 );
			} else {
				$row_arr = (array) $row;
				$id      = (int) ( $row_arr['id'] ?? 0 );
			}
			if ( $id > 0 && isset( $open_by_log[ $id ] ) ) {
				if ( is_object( $row ) ) {
					$row->event_type = $open_by_log[ $id ];
				} else {
					$row['event_type'] = $open_by_log[ $id ];
				}
				$rows[ $index ] = $row;
			}
		}

		return $rows;
	}

	private function has_event_type_column(): bool {
		if ( null !== self::$has_event_type_column ) {
			return self::$has_event_type_column;
		}
		global $wpdb;
		$table                         = $wpdb->prefix . 'post_smtp_logs';
		$column                        = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE 'event_type'" );
		self::$has_event_type_column = ! empty( $column );
		return self::$has_event_type_column;
	}
}
