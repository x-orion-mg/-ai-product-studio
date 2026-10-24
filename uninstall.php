<?php
/**
 * Fired when the plugin is uninstalled. Removes the plugin's tables, options
 * and stored files.
 *
 * @package AIProductStudio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'aips_prompts',
	$wpdb->prefix . 'aips_api_keys',
	$wpdb->prefix . 'aips_history',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted internal table name built from $wpdb->prefix.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove options.
delete_option( 'aips_settings' );
delete_option( 'aips_version' );

// Remove stored log/cache files.
$storage = plugin_dir_path( __FILE__ ) . 'storage/';
if ( is_dir( $storage ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $storage, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			@rmdir( $file->getPathname() );
		} else {
			@unlink( $file->getPathname() );
		}
	}
}
