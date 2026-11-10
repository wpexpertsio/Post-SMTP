<?php
/**
 * Resolve stored email log attachments from disk + post_smtp_logmeta.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogAttachmentResolver {

	public const META_KEY = 'attachments';

	public static function isFeatureEnabled(): bool {
		if ( ! defined( 'POST_SMTP_PRO_VERSION' ) ) {
			return false;
		}

		/**
		 * @param bool $enabled Whether saving/displaying log attachments is enabled.
		 */
		return (bool) apply_filters( 'post_smtp_log_save_attachments_enabled', true );
	}

	public static function uploadBaseDir(): string {
		return trailingslashit( WP_CONTENT_DIR ) . 'uploads/post-smtp';
	}

	public static function uploadBaseUrl(): string {
		return trailingslashit( content_url( 'uploads/post-smtp' ) );
	}

	/**
	 * @param int $log_id Log ID.
	 * @return list<array{name:string,url:string}>
	 */
	public static function listForLog( int $log_id ): array {
		if ( $log_id <= 0 || ! self::isFeatureEnabled() ) {
			return array();
		}

		$folder = self::folderIdForLog( $log_id );
		if ( '' === $folder ) {
			return array();
		}

		return self::listFilesInFolder( $folder );
	}

	/**
	 * @param int $log_id Log ID.
	 * @return list<string> Absolute file paths for wp_mail resend.
	 */
	public static function pathsForResend( int $log_id ): array {
		if ( $log_id <= 0 ) {
			return array();
		}

		$folder = self::folderIdForLog( $log_id );
		if ( '' === $folder ) {
			return array();
		}

		$dir = self::uploadBaseDir() . '/' . $folder;
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$paths = array();
		foreach ( self::scandirFiles( $dir ) as $name ) {
			$path = $dir . '/' . $name;
			if ( is_file( $path ) && is_readable( $path ) ) {
				$paths[] = $path;
			}
		}

		return $paths;
	}

	/**
	 * @param list<int> $log_ids Log IDs on the current page.
	 * @return array<int,bool> log_id => has attachments
	 */
	public static function batchHasAttachments( array $log_ids ): array {
		$log_ids = array_values( array_filter( array_map( 'intval', $log_ids ) ) );
		if ( empty( $log_ids ) || ! self::isFeatureEnabled() ) {
			return array();
		}

		if ( ! function_exists( 'postman_get_log_meta' ) && defined( 'POST_SMTP_PATH' ) ) {
			require_once POST_SMTP_PATH . '/includes/postman-functions.php';
		}

		global $wpdb;
		$meta_table = $wpdb->prefix . 'post_smtp_logmeta';
		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$sql = $wpdb->prepare(
			"SELECT log_id, meta_value FROM `{$meta_table}` WHERE meta_key = %s AND log_id IN ({$placeholders})",
			self::META_KEY,
			...$log_ids
		);

		$rows = $wpdb->get_results( $sql );
		$out  = array_fill_keys( $log_ids, false );
		if ( ! is_array( $rows ) ) {
			return $out;
		}

		foreach ( $rows as $row ) {
			$id = isset( $row->log_id ) ? (int) $row->log_id : 0;
			if ( $id <= 0 ) {
				continue;
			}
			$folder = isset( $row->meta_value ) ? (string) $row->meta_value : '';
			$out[ $id ] = '' !== $folder && is_dir( self::uploadBaseDir() . '/' . $folder );
		}

		return $out;
	}

	/**
	 * @param list<int|float|string> $log_ids Deleted log IDs, or -1 for wipe all attachment dirs.
	 */
	public static function deleteStoredAttachments( array $log_ids ): void {
		if ( empty( $log_ids ) ) {
			return;
		}

		foreach ( $log_ids as $log_id ) {
			if ( -1 === (int) $log_id || '-1' === $log_id ) {
				self::deleteAllAttachmentDirs();
				return;
			}
		}

		foreach ( $log_ids as $log_id ) {
			$log_id = (int) $log_id;
			if ( $log_id <= 0 ) {
				continue;
			}
			self::deleteFolderForLog( $log_id );
		}
	}

	private static function folderIdForLog( int $log_id ): string {
		if ( ! function_exists( 'postman_get_log_meta' ) && defined( 'POST_SMTP_PATH' ) ) {
			require_once POST_SMTP_PATH . '/includes/postman-functions.php';
		}

		if ( ! function_exists( 'postman_get_log_meta' ) ) {
			return '';
		}

		return sanitize_file_name( (string) postman_get_log_meta( $log_id, self::META_KEY ) );
	}

	/**
	 * @return list<array{name:string,url:string}>
	 */
	private static function listFilesInFolder( string $folder ): array {
		$folder = sanitize_file_name( $folder );
		if ( '' === $folder ) {
			return array();
		}

		$dir = self::uploadBaseDir() . '/' . $folder;
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$base_url = trailingslashit( content_url( 'uploads/post-smtp/' . $folder ) );
		$out      = array();
		foreach ( self::scandirFiles( $dir ) as $name ) {
			$out[] = array(
				'name' => $name,
				'url'  => $base_url . rawurlencode( $name ),
			);
		}

		return $out;
	}

	/**
	 * @return list<string> Filenames only.
	 */
	private static function scandirFiles( string $dir ): array {
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$names = array_diff( scandir( $dir ), array( '.', '..' ) );
		$files = array();
		foreach ( $names as $name ) {
			if ( is_file( $dir . '/' . $name ) ) {
				$files[] = $name;
			}
		}

		return $files;
	}

	private static function deleteFolderForLog( int $log_id ): void {
		$folder = self::folderIdForLog( $log_id );
		if ( '' === $folder ) {
			return;
		}

		self::deleteDirectory( self::uploadBaseDir() . '/' . $folder );

		if ( function_exists( 'postman_delete_log_meta' ) ) {
			postman_delete_log_meta( $log_id, self::META_KEY );
		}
	}

	private static function deleteAllAttachmentDirs(): void {
		$base = self::uploadBaseDir();
		if ( ! is_dir( $base ) ) {
			return;
		}

		foreach ( array_diff( scandir( $base ), array( '.', '..' ) ) as $folder ) {
			$path = $base . '/' . $folder;
			if ( is_dir( $path ) ) {
				self::deleteDirectory( $path );
			}
		}
	}

	private static function deleteDirectory( string $path ): void {
		if ( ! is_dir( $path ) ) {
			return;
		}

		foreach ( self::scandirFiles( $path ) as $name ) {
			wp_delete_file( $path . '/' . $name );
		}

		foreach ( array_diff( scandir( $path ), array( '.', '..' ) ) as $child ) {
			$child_path = $path . '/' . $child;
			if ( is_dir( $child_path ) ) {
				self::deleteDirectory( $child_path );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $path );
	}
}
