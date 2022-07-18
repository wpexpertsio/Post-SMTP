<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (! interface_exists ( "PostmanMailEngine" )) {

	interface PostmanMailEngine {
		public function getTranscript();
		public function send(PostmanMessage $message);
	}

}

