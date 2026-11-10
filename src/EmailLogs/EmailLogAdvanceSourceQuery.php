<?php
/**
 * Advance log filter — query by wp_mail initiator (alf_source meta).
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

use PostSMTP\Settings\SettingsEmailLogsPanelHooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogAdvanceSourceQuery {

	public const META_SOURCE = 'alf_source';

	public static function init(): void {
		add_filter( 'post_smtp_get_logs_query_after_table', array( self::class, 'filterQueryBySource' ), 10, 2 );
	}

	public static function isEnabled(): bool {
		return (bool) get_option( SettingsEmailLogsPanelHooks::ADVANCED_LOG_FILTER_OPTION, 0 );
	}

	/**
	 * @param string              $query SQL fragment.
	 * @param array<string,mixed> $args  Query args.
	 */
	public static function filterQueryBySource( string $query, array $args ): string {
		if ( ! self::isEnabled() ) {
			return $query;
		}

		if ( empty( $args['filter_by'] ) || 'all' === $args['filter_by'] ) {
			return $query;
		}

		global $wpdb;
		$meta_table = $wpdb->prefix . 'post_smtp_logmeta';
		$source     = sanitize_text_field( (string) $args['filter_by'] );

		$clause = $wpdb->prepare(
			"pl.`id` IN ( SELECT lm.`log_id` FROM `{$meta_table}` AS lm WHERE lm.`meta_key` = %s AND lm.`meta_value` = %s )",
			self::META_SOURCE,
			$source
		);

		if ( stripos( $query, 'WHERE' ) !== false ) {
			$query .= ' AND ' . $clause;
		} else {
			$query .= ' WHERE ' . $clause;
		}

		return $query;
	}

	/**
	 * @return list<array{source:string,count:int}>
	 */
	public static function getSourceFacets(): array {
		if ( ! self::isEnabled() ) {
			return array();
		}

		global $wpdb;
		$meta_table = $wpdb->prefix . 'post_smtp_logmeta';
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS source, COUNT(*) AS count
				FROM `{$meta_table}`
				WHERE meta_key = %s
				GROUP BY meta_value
				ORDER BY count DESC
				LIMIT 50",
				self::META_SOURCE
			)
		);

		$out = array();
		if ( ! is_array( $rows ) ) {
			return $out;
		}

		foreach ( $rows as $row ) {
			$source = isset( $row->source ) ? sanitize_text_field( (string) $row->source ) : '';
			if ( '' === $source ) {
				continue;
			}
			$out[] = array(
				'source' => $source,
				'count'  => isset( $row->count ) ? (int) $row->count : 0,
			);
		}

		return $out;
	}
}
