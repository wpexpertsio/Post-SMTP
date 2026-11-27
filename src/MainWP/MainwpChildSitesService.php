<?php
/**
 * Resolves MainWP child sites for dashboard email log filters.
 *
 * @package PostSMTP\MainWP
 */

namespace PostSMTP\MainWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MainwpChildSitesService {

	/**
	 * @return list<array{id:string,name:string}>
	 */
	public function list_for_rest(): array {
		if ( ! MainwpPolicy::isMainwpDashboard() ) {
			return array();
		}

		$map = $this->site_map();
		$out = array();

		foreach ( $map as $id => $name ) {
			$out[] = array(
				'id'   => (string) $id,
				'name' => (string) $name,
			);
		}

		return $out;
	}

	/**
	 * @return array<string,string> site id => display name
	 */
	public function site_map(): array {
		$extension_bootstrap = WP_PLUGIN_DIR . '/post-smtp-for-mainwp/mainwp-post-smtp-extension.php';
		$filter_file         = is_readable( $extension_bootstrap ) ? $extension_bootstrap : __FILE__;

		$child_enabled = apply_filters( 'mainwp_extension_enabled_check', $filter_file );
		if ( ! is_array( $child_enabled ) || empty( $child_enabled['key'] ) ) {
			return array();
		}

		$child_key = $child_enabled['key'];
		$sites     = apply_filters( 'mainwp_getsites', __FILE__, $child_key );
		if ( ! is_array( $sites ) ) {
			$sites = array();
		}

		$map = array();
		foreach ( $sites as $site ) {
			if ( ! is_array( $site ) || empty( $site['id'] ) ) {
				continue;
			}
			$map[ (string) $site['id'] ] = isset( $site['name'] ) ? (string) $site['name'] : (string) $site['id'];
		}

		$main_label = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Main Site', 'post-smtp' );
		$map['main_site'] = $main_label;

		return $map;
	}

	public function label_for_site_id( string $site_id ): string {
		$site_id = trim( $site_id );
		if ( '' === $site_id ) {
			return '';
		}

		$map = $this->site_map();
		if ( isset( $map[ $site_id ] ) ) {
			return $map[ $site_id ];
		}

		return $site_id;
	}
}