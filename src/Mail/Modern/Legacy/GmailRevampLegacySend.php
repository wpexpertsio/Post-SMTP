<?php
/**
 * Gmail API delivery for revamp (delegates to legacy Gmail transport/engine).
 *
 * @package PostSMTP\Mail\Modern\Legacy
 */

namespace PostSMTP\Mail\Modern\Legacy;

use PostSMTP\Connections\GmailConnectionTokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GmailRevampLegacySend {

	/**
	 * @param array<string,mixed> $atts           wp_mail attributes.
	 * @param int|null            $log_id         Email log ID.
	 * @param array<string,mixed> $connection     Active connection.
	 * @param array<string,mixed> $processed_data Normalized email data.
	 * @return array{success:bool,message:string,send:bool,transcript?:string}
	 */
	public static function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		if ( class_exists( GmailConnectionTokens::class, false ) ) {
			return GmailConnectionTokens::sendViaLegacyTransport( $atts, $log_id, $connection, $processed_data );
		}

		return RevampLegacyPostmanSend::send(
			$atts,
			__( 'The email could not be sent. Check your Gmail connection and OAuth grant.', 'post-smtp' )
		);
	}
}