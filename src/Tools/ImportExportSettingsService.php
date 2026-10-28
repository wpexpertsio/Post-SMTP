<?php
/**
 * Import / Export plugin settings (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostmanOptions;
use PostmanPreRequisitesCheck;
use PostmanTransportRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ImportExportSettingsService {

	private const EXPORT_FORMAT = 'post-smtp-settings';

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		$options = PostmanOptions::getInstance();

		return array(
			'can_export'   => PostmanPreRequisitesCheck::checkZlibEncode(),
			'has_settings' => ! $options->isNew(),
			'copy'         => array(
				'title'              => __( 'Plugin settings', 'post-smtp' ),
				'lead'               => __( 'Download or upload your settings.', 'post-smtp' ),
				'export_label'       => __( 'Export settings', 'post-smtp' ),
				'export_hint'        => __( 'Downloads your Post SMTP configuration as a JSON file.', 'post-smtp' ),
				'export_button'      => __( 'Export Settings (JSON)', 'post-smtp' ),
				'import_label'       => __( 'Import settings', 'post-smtp' ),
				'import_hint'        => __( 'Importing overwrites every current Post SMTP setting, including your Primary Connection.', 'post-smtp' ),
				'choose_file'        => __( 'Choose File', 'post-smtp' ),
				'import_button'      => __( 'Import', 'post-smtp' ),
				'export_success'     => __( 'Settings exported.', 'post-smtp' ),
				'import_success'     => __( 'Settings imported successfully.', 'post-smtp' ),
				'import_error'       => __( 'There was an error importing the data.', 'post-smtp' ),
				'export_unavailable' => __( 'Export is unavailable because zlib is not enabled on this server.', 'post-smtp' ),
				'no_file'            => __( 'Choose a settings file before importing.', 'post-smtp' ),
				'invalid_file'       => __( 'The selected file could not be read.', 'post-smtp' ),
			),
			'oauth_warning' => $this->getOAuthWarning(),
		);
	}

	/**
	 * @return array{success:bool,message?:string,filename?:string,content?:string}
	 */
	public function exportSettings(): array {
		if ( ! PostmanPreRequisitesCheck::checkZlibEncode() ) {
			return array(
				'success' => false,
				'message' => __( 'Export is unavailable because zlib is not enabled on this server.', 'post-smtp' ),
			);
		}

		$options = PostmanOptions::getInstance();
		if ( $options->isNew() ) {
			return array(
				'success' => false,
				'message' => __( 'There are no settings to export yet.', 'post-smtp' ),
			);
		}

		$payload = $options->export();
		if ( empty( $payload ) ) {
			return array(
				'success' => false,
				'message' => __( 'Could not build an export file.', 'post-smtp' ),
			);
		}

		$document = array(
			'format'      => self::EXPORT_FORMAT,
			'version'     => 2,
			'exported_at' => gmdate( 'c' ),
			'settings'    => $payload,
		);

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			$connections = get_option( ConnectionSchema::OPTION_CONNECTIONS, array() );
			$control     = get_option( ConnectionSchema::OPTION_OPTIONS, array() );
			if ( is_array( $connections ) && ! empty( $connections ) ) {
				$document['connections'] = $connections;
			}
			if ( is_array( $control ) && ! empty( $control ) ) {
				$document['options'] = $control;
			}
		}

		$filename = 'post-smtp-settings-' . gmdate( 'Y-m-d' ) . '.json';

		return array(
			'success'  => true,
			'filename' => $filename,
			'content'  => wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
		);
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array{success:bool,message:string}
	 */
	public function importSettings( array $payload ): array {
		if ( ! PostmanPreRequisitesCheck::checkZlibEncode() ) {
			return array(
				'success' => false,
				'message' => __( 'Import is unavailable because zlib is not enabled on this server.', 'post-smtp' ),
			);
		}

		$raw = isset( $payload['content'] ) ? (string) $payload['content'] : '';
		$raw = trim( $raw );

		if ( '' === $raw ) {
			return array(
				'success' => false,
				'message' => __( 'Choose a settings file before importing.', 'post-smtp' ),
			);
		}

		$settings_blob = $raw;
		$connections     = null;
		$control_options = null;

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			if ( ! empty( $decoded['settings'] ) && is_string( $decoded['settings'] ) ) {
				$settings_blob = $decoded['settings'];
			} elseif ( ! empty( $decoded['payload'] ) && is_string( $decoded['payload'] ) ) {
				$settings_blob = $decoded['payload'];
			}

			if ( ! empty( $decoded['connections'] ) && is_array( $decoded['connections'] ) ) {
				$connections = $decoded['connections'];
			}
			if ( ! empty( $decoded['options'] ) && is_array( $decoded['options'] ) ) {
				$control_options = $decoded['options'];
			}
		}

		$settings_blob = sanitize_textarea_field( $settings_blob );
		$imported      = PostmanOptions::getInstance()->import( $settings_blob );

		if ( ! $imported ) {
			return array(
				'success' => false,
				'message' => __( 'There was an error importing the data.', 'post-smtp' ),
			);
		}

		if ( is_array( $connections ) ) {
			update_option( ConnectionSchema::OPTION_CONNECTIONS, $connections, false );
		}

		if ( is_array( $control_options ) ) {
			update_option( ConnectionSchema::OPTION_OPTIONS, $control_options, false );
		}

		return array(
			'success' => true,
			'message' => __( 'Settings imported successfully.', 'post-smtp' ),
		);
	}

	/**
	 * @return string
	 */
	private function getOAuthWarning(): string {
		if ( ! class_exists( '\PostmanTransportRegistry' ) ) {
			return '';
		}

		$transport = \PostmanTransportRegistry::getInstance()->getSelectedTransport();
		if ( ! $transport || ! method_exists( $transport, 'isOAuthUsed' ) ) {
			return '';
		}

		if ( ! $transport->isOAuthUsed( PostmanOptions::getInstance()->getAuthenticationType() ) ) {
			return '';
		}

		return __(
			'Using the same OAuth 2.0 Client ID and Client Secret from this site at the same time as another site will cause failures.',
			'post-smtp'
		);
	}
}
