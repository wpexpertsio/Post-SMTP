<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanWoocommerce' ) ) {
	class PostmanWoocommerce {

		private $options;

		public function __construct() {
			$this->set_vars();
			$this->hooks();
		}

		public function set_vars() {
			$this->options = PostmanOptions::getInstance ();
		}

		public function hooks() {
			add_filter( 'option_woocommerce_email_from_address', array( $this, 'set_postman_from_address' ), 10, 2 );
			add_filter( 'woocommerce_email_from_address', array( $this, 'set_postman_from_address' ), 10, 2 );
			add_filter( 'woocommerce_get_settings_email', array( $this, 'overide_email_settings' ) );
		}

		public function set_postman_from_address( $from_address, $WC_Email ) {
			return $this->options->getMessageSenderEmail();
		}

		public function overide_email_settings( $settings ) {

			$key = $this->find_woocommerce_email_from_address( $settings );

			if ( $key ) {
				$settings[$key] = array(
					'title'             => __( '"From" address', 'post-smtp' ),
					'desc'              => __( 'This is override by the account configured on Post SMTP plugin configuration.', 'post-smtp' ),
					'id'                => 'woocommerce_email_from_address',
					'type'              => 'email',
					'custom_attributes' => array(
						'multiple' => 'multiple',
						'disabled' => 'true',
					),
					'css'               => 'min-width:300px;',
					'default'           => $this->options->getMessageSenderEmail(),
					'autoload'          => false,
					'desc_tip'          => true,
				);
			}

			return $settings;
		}

		private function find_woocommerce_email_from_address($settings) {
			foreach ( $settings as $key => $data ) {
				if ( isset( $data['id'] ) && $data['id'] == 'woocommerce_email_from_address' ) {
					return $key;
				}
			}

			return false;
		}
	}
}