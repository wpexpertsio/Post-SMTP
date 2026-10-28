<?php
/**
 * Builds multipart test email bodies for Tools → Send Test Email.
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TestEmailMessageBuilder {

	/**
	 * @return string Multipart/alternative raw message body (legacy Postman format).
	 */
	public static function buildMultipartBody( string $custom_message = '' ): string {
		$plain = self::plainBody( $custom_message );
		$html  = self::htmlBody( $custom_message );

		$message_array = array(
			'Content-Type: text/plain; charset = "UTF-8"',
			'Content-Transfer-Encoding: 8bit',
			'',
			$plain,
			'',
			'Content-Type: text/html; charset=UTF-8',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
			'<html xmlns="http://www.w3.org/1999/xhtml">',
			'<head>',
			'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',
			'</head>',
			'<body class="wporg-notification">',
			'<div style="width: 600px; margin: 0 auto;">',
			$html,
			'</div>',
			'</body>',
			'</html>',
		);

		if ( ! class_exists( '\PostmanMessage' ) ) {
			return implode( "\r\n", $message_array );
		}

		return implode( \PostmanMessage::EOL, $message_array );
	}

	/**
	 * @return array{plain:string,html:string,subject:string}
	 */
	public static function previewParts( string $subject, string $custom_message = '' ): array {
		return array(
			'subject' => $subject,
			'plain'   => self::plainBody( $custom_message ),
			'html'    => self::wrapHtmlDocument( self::htmlBody( $custom_message ) ),
		);
	}

	public static function defaultSubject(): string {
		$server = class_exists( '\PostmanUtils' ) ? \PostmanUtils::postmanGetServerName() : wp_parse_url( home_url(), PHP_URL_HOST );
		/* translators: %s: site hostname */
		return sprintf( _x( 'Postman SMTP Test (%s)', 'Test Email Subject', 'post-smtp' ), (string) $server );
	}

	private static function plainBody( string $custom_message ): string {
		if ( '' !== trim( $custom_message ) ) {
			return trim( $custom_message );
		}

		$plugin_data = apply_filters( 'postman_get_plugin_metadata', null );
		$version     = is_array( $plugin_data ) && isset( $plugin_data['version'] ) ? $plugin_data['version'] : '4';
		$sent_by     = sprintf( _x( 'Sent by Postman %s', 'Test Email Tagline', 'post-smtp' ), $version );

		return "Hello!\r\n\r\nThis is a test email from Post SMTP on " . home_url() . ".\r\n\r\n{$sent_by} - https://wordpress.org/plugins/post-smtp/";
	}

	private static function htmlBody( string $custom_message ): string {
		if ( '' !== trim( $custom_message ) ) {
			return '<p style="font-family:Inter,sans-serif;color:#2d3340;font-size:14px;line-height:1.5;">' . nl2br( esc_html( trim( $custom_message ) ) ) . '</p>';
		}

		$assets = defined( 'POST_SMTP_ASSETS' ) ? POST_SMTP_ASSETS : '';
		$logo   = esc_url( $assets . '/images/logos/postman-logo-email.png' );
		$banner = esc_url( $assets . '/images/logos/post-man-banner.png' );
		$site   = esc_html( get_bloginfo( 'name' ) );
		$home   = esc_url( home_url() );

		return '
		<table style="width: 600px;margin:50px auto;color:#7D98B2;font-size:12px;padding:30px 0;">
        <thead>
            <tr>
                <th>
                    <a href="https://postmansmtp.com/"><img style="width: 160px;" src="' . $logo . '"/></a>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0 20px;">Hey there 👋,</td>
            </tr>
            <tr>
               <td style="padding: 0 20px;"><h3 style="color:#214A72;font-size:16px;font-weight:600;display: inline-block;margin:0; font-size: 20px;">Congrats! Your test email was sent successfully</h3></td>
            </tr>
            <tr>
                <td style="padding:0 20px;line-height: 20px;">Thank you for using Post SMTP. Our mission is to enhance your email deliverability.</td>
            </tr>
            <tr>
                <td>
					<a href="https://postmansmtp.com/post-smtp-mobile-app/" target="_blank">
						<img style="margin: 20px 0; width:100%" src="' . $banner . '"/>
					</a>
				</td>
            </tr>
            <tr>
                <td style="padding:0 20px;font-size: 12px;text-align: center;">
                    This email was sent from your website <a href="' . $home . '" target="_blank"><strong>' . $site . '</strong></a> to test your email functionality.
                </td>
            </tr>
        </tbody>
        </table>';
	}

	private static function wrapHtmlDocument( string $body ): string {
		return '<!DOCTYPE html><html><head><meta charset="utf-8" /></head><body>' . $body . '</body></html>';
	}
}
