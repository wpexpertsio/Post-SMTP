<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostmanBirdSettings {
	public static function get() {
		$settings = get_option( 'bird_post_smtp', [] );
		return is_array( $settings ) ? $settings : [];
	}

	public static function reset() {
		delete_option( 'bird_post_smtp' );
	}

	public static function endpoint( $key ) {
		if ( ! is_string( $key ) || ! preg_match( '/\Abk_(eu1|us1)_[A-Za-z0-9_-]+\z/', $key, $matches ) ) {
			throw new RuntimeException( esc_html__( 'Enter a Bird API key beginning with bk_eu1_ or bk_us1_.', 'post-smtp' ) );
		}
		return 'https://' . $matches[1] . '.platform.bird.com';
	}

	public static function register() {
		if ( ! class_exists( 'PostmanAdminController' ) ) {
			return;
		}
		register_setting( PostmanAdminController::SETTINGS_GROUP_NAME, 'bird_post_smtp', [
			'type' => 'array',
			'sanitize_callback' => [self::class, 'sanitize'],
			'show_in_rest' => false,
		] );
		add_settings_field( 'bird_email_category', __( 'Email category', 'post-smtp' ), [self::class, 'renderCategory'], PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION, [
			'label_for' => 'bird_email_category',
			'class' => 'bird_advanced_setting',
		] );
	}

	public static function sanitize( $input ) {
		$saved = self::get();
		if ( ! is_array( $input ) ) {
			return $saved;
		}
		try {
			$settings = self::validated( $input );
			$transport = $_POST['postman_options']['transport_type'] ?? PostmanOptions::getInstance()->getTransportType();
			if ( $transport === 'bird_api' ) {
				self::endpoint( $settings['api_key'] );
			}
			return $settings;
		} catch ( RuntimeException $error ) {
			self::error( 'invalid_settings', $error->getMessage() );
			return $saved;
		}
	}

	public static function saveWizard( $input ) {
		if ( ! is_array( $input ) ) {
			throw new RuntimeException( __( 'Enter your Bird API key.', 'post-smtp' ) );
		}
		$settings = self::validated( $input );
		self::endpoint( $settings['api_key'] );
		update_option( 'bird_post_smtp', $settings );
	}

	private static function validated( array $input ) {
		$saved = self::get();
		if ( isset( $input['api_key'] ) && ! is_string( $input['api_key'] ) ) {
			throw new RuntimeException( __( 'Enter a Bird API key as text.', 'post-smtp' ) );
		}
		$key = isset( $input['api_key'] ) && is_string( $input['api_key'] ) ? trim( $input['api_key'] ) : '';
		if ( $key === '' ) {
			$key = $saved['api_key'] ?? '';
		} else {
			self::endpoint( $key );
		}
		$category = $input['category'] ?? ( $saved['category'] ?? 'transactional' );
		if ( ! in_array( $category, ['transactional', 'marketing'], true ) ) {
			throw new RuntimeException( __( 'Choose a valid email category.', 'post-smtp' ) );
		}
		return ['api_key' => $key, 'category' => $category];
	}

	private static function error( $code, $message ) {
		add_settings_error( 'bird_post_smtp', $code, $message );
		// Post SMTP redirects successful saves before WordPress can show Settings API errors.
		if ( class_exists( 'PostmanMessageHandler' ) && class_exists( 'PostmanInputSanitizer' ) ) {
			( new PostmanMessageHandler() )->addError( esc_html( $message ) );
			PostmanSession::getInstance()->setAction( PostmanInputSanitizer::VALIDATION_FAILED );
		}
	}

	public static function render() {
		echo '<div id="bird_settings" class="authentication_setting non-basic non-oauth2" hidden>';
		echo '<h2>' . esc_html( __( 'Authentication', 'post-smtp' ) ) . '</h2>';
		echo self::fields();
		echo '</div>';
	}

	public static function fields( $wizard = false ) {
		$settings = self::get();
		$placeholder = empty( $settings['api_key'] ) ? __( 'Enter your Bird API key', 'post-smtp' ) : __( 'Key saved. Leave blank to keep it.', 'post-smtp' );
		ob_start();
		echo '<p>' . sprintf(
			esc_html( __( 'Create an account at %1$s and enter %2$s below.', 'post-smtp' ) ),
			'<a href="' . esc_url( 'https://bird.com' ) . '" target="_blank" rel="noopener noreferrer">Bird</a>',
			'<a href="' . esc_url( 'https://bird.com/dashboard/w/api-keys' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( __( 'an API Key', 'post-smtp' ) ) . '</a>'
		) . '</p>';
		echo '<div class="ps-form-control"><label for="bird_api_key">' . esc_html( __( 'API Key', 'post-smtp' ) ) . '</label>';
		echo '<input id="bird_api_key" name="bird_post_smtp[api_key]" type="password" autocomplete="new-password" value="" size="60" placeholder="' . esc_attr( $placeholder ) . '" data-error="' . esc_attr( __( 'Enter your Bird API key.', 'post-smtp' ) ) . '"' . ( $wizard && empty( $settings['api_key'] ) ? ' required' : '' ) . '></div>';
		return ob_get_clean();
	}

	public static function renderCategory() {
		$category = self::get()['category'] ?? 'transactional';
		echo '<select id="bird_email_category" name="bird_post_smtp[category]">';
		foreach ( ['transactional' => __( 'Transactional', 'post-smtp' ), 'marketing' => __( 'Marketing', 'post-smtp' )] as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . ( $value === $category ? ' selected' : '' ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select><p>' . esc_html( __( 'Choose transactional for receipts and password resets. Choose marketing for promotional mail. Open and click tracking are disabled.', 'post-smtp' ) ) . '</p>';
	}
}
