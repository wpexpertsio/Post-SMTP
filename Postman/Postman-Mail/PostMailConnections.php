<?php

class PostmanMailConnections {
	/**
	 * Constructor
	 *
	 * Initializes the class.
	 *
	 * @since 1.0
	 */
	public function __construct() {
	}

	/**
	 * Get Mail Connection Details by Transport Type
	 *
	 * @param string $transport_type The transport type (e.g., 'smtp', 'elastic_email').
	 * @return array|null The connection details or null if not found.
	 * @since 3.0.1
	 */
	public function get_mail_connection_details( $transport_type ) {
		// Fetch all connection settings from the 'postman_connections' option.
		$connections = get_option( 'postman_connections', array() );

		// Iterate through the connections to find the matching transport type.
		foreach ( $connections as $connection ) {
			if ( isset( $connection['provider'] ) && $connection['provider'] === $transport_type ) {
				return $connection; // Return the connection details if found.
			}
		}

		// Return null if the transport type is not found.
		return null;
	}
}
