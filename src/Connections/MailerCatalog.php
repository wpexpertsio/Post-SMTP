<?php
/**
 * Mailer selection grid metadata (Figma Choose Mailer).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MailerCatalog {

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function all(): array {
		$items = array(
			array(
				'slug'             => 'gmail_api',
				'label'            => __( 'Gmail', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => true,
				'icon'             => 'gmail-pro-feature.svg',
				'sort'             => 40,
			),
			array(
				'slug'             => 'office365_api',
				'label'            => __( 'Microsoft 365', 'post-smtp' ),
				'requires_pro'     => true,
				'initial_visible'  => true,
				'icon'             => 'office-pro-feature.svg',
				'sort'             => 20,
			),
			array(
				'slug'             => 'amazon_ses_api',
				'label'            => __( 'Amazon SES', 'post-smtp' ),
				'requires_pro'     => true,
				'initial_visible'  => true,
				'icon'             => 'aws-pro-feature.svg',
				'sort'             => 30,
			),
			array(
				'slug'             => 'sendgrid_api',
				'label'            => __( 'SendGrid', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => true,
				'icon'             => 'email.svg',
				'sort'             => 40,
			),
			array(
				'slug'             => 'mailgun_api',
				'label'            => __( 'Mailgun', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => true,
				'icon'             => 'email.svg',
				'sort'             => 50,
			),
			array(
				'slug'             => 'smtp',
				'label'            => __( 'Other SMTP', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => true,
				'icon'             => 'connectivity.svg',
				'sort'             => 60,
			),
			array(
				'slug'             => 'zohomail_api',
				'label'            => __( 'Zoho Mail', 'post-smtp' ),
				'requires_pro'     => true,
				'initial_visible'  => false,
				'icon'             => 'zoho-pro-feature.svg',
				'sort'             => 70,
			),
			array(
				'slug'             => 'postmark_api',
				'label'            => __( 'Postmark', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 80,
			),
			array(
				'slug'             => 'sendinblue_api',
				'label'            => __( 'Brevo', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 90,
			),
			array(
				'slug'             => 'sendpulse_api',
				'label'            => __( 'SendPulse', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 100,
			),
			array(
				'slug'             => 'mailersend_api',
				'label'            => __( 'MailerSend', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 110,
			),
			array(
				'slug'             => 'smtp2go_api',
				'label'            => __( 'SMTP2GO', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 120,
			),
			array(
				'slug'             => 'elasticemail_api',
				'label'            => __( 'Elastic Email', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 130,
			),
			array(
				'slug'             => 'sparkpost_api',
				'label'            => __( 'SparkPost', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 140,
			),
			array(
				'slug'             => 'smtp_com_api',
				'label'            => __( 'SMTP.com', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 150,
			),
			array(
				'slug'             => 'maileroo_api',
				'label'            => __( 'Maileroo', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 160,
			),
			array(
				'slug'             => 'emailit_api',
				'label'            => __( 'Emailit', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 170,
			),
			array(
				'slug'             => 'mailtrap_api',
				'label'            => __( 'Mailtrap', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 180,
			),
			array(
				'slug'             => 'sweego_api',
				'label'            => __( 'Sweego', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 190,
			),
			array(
				'slug'             => 'resend_api',
				'label'            => __( 'Resend', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 200,
			),
			array(
				'slug'             => 'mandrill_api',
				'label'            => __( 'Mandrill', 'post-smtp' ),
				'requires_pro'     => false,
				'initial_visible'  => false,
				'icon'             => 'email.svg',
				'sort'             => 210,
			),
		);

		$items = apply_filters( 'post_smtp_mailer_catalog', $items );

		usort(
			$items,
			static function ( $a, $b ) {
				return (int) ( $a['sort'] ?? 0 ) <=> (int) ( $b['sort'] ?? 0 );
			}
		);

		$is_pro = ConnectionLimits::isPro();
		foreach ( $items as &$item ) {
			$item['configurable'] = ! ( ! empty( $item['requires_pro'] ) && ! $is_pro );
		}
		unset( $item );

		return $items;
	}
}
