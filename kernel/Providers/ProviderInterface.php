<?php
/**
 * Mail provider contract (SureMail-inspired).
 *
 * @package PostSMTP\Kernel\Providers
 */

namespace PostSMTP\Kernel\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ProviderInterface {

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array{success:bool,message:string,error_code?:int}
	 */
	public function authenticate( array $connection ): array;

	/**
	 * @param array<string,mixed> $atts           wp_mail attributes.
	 * @param int|null            $log_id         Email log ID.
	 * @param array<string,mixed> $connection     Active connection.
	 * @param array<string,mixed> $processed_data Normalized email data.
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string}
	 */
	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array;

	/**
	 * Form schema for React FormGenerator.
	 *
	 * @return array<string,mixed>
	 */
	public static function getOptions(): array;

	/**
	 * Provider slug used in connection rows.
	 */
	public static function slug(): string;
}
