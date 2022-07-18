<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (! class_exists ( 'PostmanAbstractAjaxHandler' )) {
	
	require_once ('PostmanPreRequisitesCheck.php');
	require_once ('Postman-Mail/PostmanMessage.php');
	
	/**
	 *
	 * @author jasonhendriks
	 */
	abstract class PostmanAbstractAjaxHandler {
		protected $logger;
		function __construct() {
			$this->logger = new PostmanLogger ( get_class ( $this ) );
		}
		/**
		 *
		 * @param mixed $actionName        	
		 * @param mixed $callbackName        	
		 */
		protected function registerAjaxHandler($actionName, $class, $callbackName) {
			if (is_admin ()) {
				$fullname = 'wp_ajax_' . $actionName;
				// $this->logger->debug ( 'Registering ' . 'wp_ajax_' . $fullname . ' Ajax handler' );
				add_action ( $fullname, array (
						$class,
						$callbackName 
				) );
			}
		}
		
		/**
		 *
		 * @param mixed $parameterName        	
		 * @return mixed
		 */
		protected function getBooleanRequestParameter($parameterName) {
			return filter_var ( $this->getRequestParameter ( $parameterName ), FILTER_VALIDATE_BOOLEAN );
		}
		
		/**
		 *
		 * @param mixed $parameterName        	
		 * @return mixed
		 */
		protected function getRequestParameter($parameterName) {
			if (isset ( $_POST [$parameterName] )) {
			    if ( is_array($_POST [$parameterName] ) ) {
                    array_walk_recursive( $_POST [$parameterName], 'sanitize_text_field' );
                    $value = $_POST [$parameterName];
                } else {
                    $value = sanitize_text_field($_POST[$parameterName]);
                }

				$this->logger->trace ( sprintf ( 'Found parameter "%s"', $parameterName ) );
				$this->logger->trace ( $value );

				return $value;
			}
		}
	}
}

require_once ('Postman-Controller/PostmanManageConfigurationAjaxHandler.php');
