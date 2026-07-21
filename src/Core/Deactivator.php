<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

/**
 * Runs when the plugin is deactivated. Kept intentionally light: destructive
 * cleanup (tables, options) lives in uninstall.php instead.
 */
final class Deactivator {

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'aips_rotate_logs' );
		flush_rewrite_rules();
	}
}
