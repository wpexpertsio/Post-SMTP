<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Referral URLs for supported mailer providers.
 *
 * @since 4.0.2
 */
class PostmanReferralLinks {

	const LINK_CLASS = 'ps-referral-link';

	const POSTMARK = 'https://www.postmarkapp.com/?via=3baffd';
	const MAILEROO = 'https://maileroo.com/?r=WithinPlugin';
	const SMTPCOM  = 'https://pstk.smtp.com/sp9wc9xviv0a';

	public static function get_postmark_url() {
		return apply_filters( 'post_smtp_postmark_referral_url', self::POSTMARK );
	}

	public static function get_maileroo_url() {
		return apply_filters( 'post_smtp_maileroo_referral_url', self::MAILEROO );
	}

	public static function get_smtpcom_url() {
		return apply_filters( 'post_smtp_smtpcom_referral_url', self::SMTPCOM );
	}

	public static function link( $url, $text ) {
		return sprintf(
			'<a href="%1$s" class="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			esc_url( $url ),
			esc_attr( self::LINK_CLASS ),
			esc_html( $text )
		);
	}
}
