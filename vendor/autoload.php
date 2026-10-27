<?php
/**
 * Minimal PSR-4 autoloader for Post SMTP 4.x (use `composer install` when available).
 */

spl_autoload_register(
	static function ( $class ) {
		$prefixes = array(
			'PostSMTP\\Kernel\\' => __DIR__ . '/../kernel/',
			'PostSMTP\\'         => __DIR__ . '/../src/',
		);

		foreach ( $prefixes as $prefix => $base_dir ) {
			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				continue;
			}
			$relative = substr( $class, $len );
			$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
				return;
			}
		}
	}
);
