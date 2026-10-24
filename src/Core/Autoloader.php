<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

/**
 * Minimal PSR-4 autoloader used as a fallback when Composer's autoloader is
 * not available (e.g. the plugin is installed without running `composer install`).
 */
final class Autoloader {

	private string $prefix;

	private string $baseDir;

	public function __construct( string $prefix, string $baseDir ) {
		$this->prefix  = $prefix;
		$this->baseDir = rtrim( $baseDir, '/\\' ) . '/';
	}

	/**
	 * Register the autoloader with the SPL stack.
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'loadClass' ) );
	}

	/**
	 * Resolve a fully qualified class name to a file and require it.
	 */
	public function loadClass( string $class ): void {
		$len = strlen( $this->prefix );

		if ( strncmp( $this->prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relativeClass = substr( $class, $len );
		$file          = $this->baseDir . str_replace( '\\', '/', $relativeClass ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
