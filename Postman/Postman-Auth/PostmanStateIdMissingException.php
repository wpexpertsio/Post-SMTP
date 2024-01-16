<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (! class_exists ( 'PostmanStateIdMissingException' )) {
	class PostmanStateIdMissingException extends Exception {
	}
}