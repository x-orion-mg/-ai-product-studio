<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

use AIProductStudio\Exceptions\ContainerException;

/**
 * A tiny dependency-injection container.
 *
 * Services are registered as lazy factories and resolved (and cached) on demand.
 * This keeps the plugin decoupled: consumers depend on the container, not on the
 * concrete construction of each service.
 */
final class Container {

	/** @var array<string, callable> */
	private array $factories = array();

	/** @var array<string, mixed> */
	private array $instances = array();

	/**
	 * Register a lazy service factory.
	 *
	 * @param callable(Container):mixed $factory
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Store an already-created instance.
	 */
	public function instance( string $id, mixed $instance ): void {
		$this->instances[ $id ] = $instance;
	}

	/**
	 * Resolve a service, building it once and caching the result.
	 */
	public function get( string $id ): mixed {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new ContainerException(
				sprintf( 'Aucun service enregistré pour l\'identifiant « %s ».', $id )
			);
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || array_key_exists( $id, $this->instances );
	}
}
