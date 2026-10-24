<?php

declare(strict_types=1);

namespace AIProductStudio\History;

/**
 * Data-access layer for the generation history table.
 */
final class HistoryRepository {

	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR   = 'error';

	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . 'aips_history';
	}

	/**
	 * Record a generation attempt.
	 *
	 * @param array<string, mixed> $payload
	 */
	public function record(
		int $productId,
		string $provider,
		int $promptId,
		string $status,
		float $duration,
		string $message = '',
		array $payload = array()
	): int {
		global $wpdb;

		$wpdb->insert(
			self::tableName(),
			array(
				'product_id' => $productId,
				'provider'   => $provider,
				'prompt_id'  => $promptId,
				'status'     => $status,
				'duration'   => $duration,
				'message'    => $message,
				'payload'    => (string) wp_json_encode( $payload ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%s', '%f', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * @return array<int, HistoryEntry>
	 */
	public function paginate( int $page = 1, int $perPage = 20 ): array {
		global $wpdb;

		$page    = max( 1, $page );
		$perPage = max( 1, $perPage );
		$offset  = ( $page - 1 ) * $perPage;

		$table = self::tableName();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted internal table name built from $wpdb->prefix.
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$perPage,
				$offset
			),
			ARRAY_A
		) ?: array();

		return array_map( array( HistoryEntry::class, 'fromRow' ), $rows );
	}

	public function count(): int {
		global $wpdb;

		$table = self::tableName();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted internal table name built from $wpdb->prefix.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public function clear(): void {
		global $wpdb;

		$table = self::tableName();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a trusted internal table name built from $wpdb->prefix.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
