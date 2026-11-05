<?php
/**
 * Builds test email bodies for Tools → Send Test Email.
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TestEmailMessageBuilder {

	private const TEMPLATE_BODY     = 'send-test-email-body.html';
	private const TEMPLATE_DOCUMENT = 'send-test-email-document.html';
	private const TEMPLATE_PLAIN    = 'send-test-email-plain.txt';

	/**
	 * HTML email for wp_mail (modern pipeline).
	 * Empty optional message → branded template file; otherwise custom text only.
	 */
	public static function buildHtmlEmail( string $custom_message = '' ): string {
		if ( self::hasCustomMessage( $custom_message ) ) {
			return self::wrapSimpleHtmlDocument(
				'<p style="font-family:Inter,sans-serif;color:#2d3340;font-size:14px;line-height:1.5;">'
				. nl2br( esc_html( trim( $custom_message ) ) )
				. '</p>'
			);
		}

		return self::legacyBrandedHtmlDocument();
	}

	/**
	 * @return string Multipart/alternative raw message body (legacy Postman transport format).
	 */
	public static function buildMultipartBody( string $custom_message = '' ): string {
		$plain = self::plainBody( $custom_message );
		$html  = self::buildHtmlEmail( $custom_message );

		$message_array = array(
			'Content-Type: text/plain; charset = "UTF-8"',
			'Content-Transfer-Encoding: 8bit',
			'',
			$plain,
			'',
			'Content-Type: text/html; charset=UTF-8',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			$html,
		);

		$eol = class_exists( '\PostmanMessage' ) ? \PostmanMessage::EOL : "\r\n";

		return implode( $eol, $message_array );
	}

	/**
	 * @return array{plain:string,html:string,subject:string}
	 */
	public static function previewParts( string $subject, string $custom_message = '' ): array {
		return array(
			'subject' => $subject,
			'plain'   => self::plainBody( $custom_message ),
			'html'    => self::buildHtmlEmail( $custom_message ),
		);
	}

	public static function defaultSubject(): string {
		$server = class_exists( '\PostmanUtils' ) ? \PostmanUtils::postmanGetServerName() : wp_parse_url( home_url(), PHP_URL_HOST );
		/* translators: %s: site hostname */
		return sprintf( _x( 'Postman SMTP Test (%s)', 'Test Email Subject', 'post-smtp' ), (string) $server );
	}

	private static function hasCustomMessage( string $custom_message ): bool {
		return '' !== trim( $custom_message );
	}

	private static function plainBody( string $custom_message ): string {
		if ( self::hasCustomMessage( $custom_message ) ) {
			return trim( $custom_message );
		}

		$plugin_data = apply_filters( 'postman_get_plugin_metadata', null );
		$version     = is_array( $plugin_data ) && isset( $plugin_data['version'] ) ? $plugin_data['version'] : ( defined( 'POST_SMTP_VER' ) ? POST_SMTP_VER : '5.0.0' );
		$sent_by     = sprintf( _x( 'Sent by Postman %s', 'Test Email Tagline', 'post-smtp' ), $version );
		$sent_line   = $sent_by . ' - https://wordpress.org/plugins/post-smtp/';

		$rendered = self::renderTemplate(
			self::TEMPLATE_PLAIN,
			array(
				'home_url'     => home_url(),
				'sent_by_line' => $sent_line,
			)
		);

		if ( '' !== $rendered ) {
			return $rendered;
		}

		return "Hello!\r\n\r\nThis is a test email from Post SMTP on " . home_url() . ".\r\n\r\n{$sent_line}";
	}

	private static function legacyBrandedHtmlDocument(): string {
		$inner = self::renderBrandedBodyFragment();
		$html  = self::renderTemplate(
			self::TEMPLATE_DOCUMENT,
			array(
				'body_content' => $inner,
			)
		);

		if ( '' === $html ) {
			return self::fallbackBrandedHtmlDocument( $inner );
		}

		/**
		 * Filter the default Send Test Email HTML (branded template).
		 *
		 * @param string $html Full HTML document.
		 */
		return (string) apply_filters( 'post_smtp_test_email_html', $html );
	}

	private static function renderBrandedBodyFragment(): string {
		$vars = self::brandedTemplateVariables();
		$html = self::renderTemplate( self::TEMPLATE_BODY, $vars );

		if ( '' !== $html ) {
			return $html;
		}

		return self::fallbackBrandedBodyFragment( $vars );
	}

	/**
	 * @return array<string,string>
	 */
	private static function brandedTemplateVariables(): array {
		$assets = defined( 'POST_SMTP_ASSETS' ) ? POST_SMTP_ASSETS : '';
		if ( '' === $assets && defined( 'POST_SMTP_URL' ) ) {
			$assets = POST_SMTP_URL . '/assets/';
		}

		return array(
			'logo_url'   => esc_url( $assets . 'images/logos/postman-logo-email.png' ),
			'banner_url' => esc_url( $assets . 'images/logos/post-man-banner.png' ),
			'home_url'   => esc_url( home_url() ),
			'site_name'  => esc_html( get_bloginfo( 'name' ) ),
		);
	}

	/**
	 * @param array<string,string> $variables Placeholder values (already escaped where needed).
	 */
	private static function renderTemplate( string $filename, array $variables ): string {
		$path = self::templatePath( $filename );
		if ( ! is_readable( $path ) ) {
			return '';
		}

		$content = file_get_contents( $path );
		if ( false === $content || '' === $content ) {
			return '';
		}

		$search  = array();
		$replace = array();
		foreach ( $variables as $key => $value ) {
			$search[]  = '{{' . $key . '}}';
			$replace[] = $value;
		}

		return str_replace( $search, $replace, $content );
	}

	private static function templatePath( string $filename ): string {
		$base = defined( 'POST_SMTP_PATH' ) ? POST_SMTP_PATH : dirname( __DIR__, 2 );
		return $base . '/assets/email-templates/' . $filename;
	}

	/**
	 * @param array<string,string> $vars Variables.
	 */
	private static function fallbackBrandedBodyFragment( array $vars ): string {
		return str_replace(
			array( '{{logo_url}}', '{{banner_url}}', '{{home_url}}', '{{site_name}}' ),
			array( $vars['logo_url'], $vars['banner_url'], $vars['home_url'], $vars['site_name'] ),
			'<table><tr><td><img src="{{logo_url}}" /></td></tr></table>'
		);
	}

	private static function fallbackBrandedHtmlDocument( string $inner ): string {
		$eol = class_exists( '\PostmanMessage' ) ? \PostmanMessage::EOL : "\r\n";

		return implode(
			$eol,
			array(
				'<!DOCTYPE html><html><head><meta charset="utf-8" /></head>',
				'<body class="wporg-notification"><div style="width:600px;margin:0 auto;">',
				$inner,
				'</div></body></html>',
			)
		);
	}

	private static function wrapSimpleHtmlDocument( string $body ): string {
		return '<!DOCTYPE html><html><head><meta charset="utf-8" /></head><body>' . $body . '</body></html>';
	}
}
