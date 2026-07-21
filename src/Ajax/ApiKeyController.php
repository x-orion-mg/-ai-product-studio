<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\AI\ProviderFactory;
use AIProductStudio\API\ApiKeyRepository;

/**
 * CRUD AJAX endpoints for AI provider API keys.
 */
final class ApiKeyController extends AbstractController {

	private ApiKeyRepository $repository;

	private ProviderFactory $factory;

	public function __construct( ApiKeyRepository $repository, ProviderFactory $factory ) {
		$this->repository = $repository;
		$this->factory    = $factory;
	}

	public function save(): void {
		$this->guard();

		$provider = $this->post( 'provider' );
		if ( ! array_key_exists( $provider, $this->factory->available() ) ) {
			$this->fail( __( 'Fournisseur IA invalide.', 'ai-product-studio' ) );
		}

		$data = array(
			'provider'  => $provider,
			'label'     => $this->post( 'label' ),
			'api_key'   => isset( $_POST['api_key'] ) ? trim( wp_unslash( (string) $_POST['api_key'] ) ) : '',
			'model'     => $this->post( 'model' ),
			'endpoint'  => esc_url_raw( wp_unslash( (string) ( $_POST['endpoint'] ?? '' ) ) ),
			'priority'  => $this->postInt( 'priority', 10 ),
			'is_active' => $this->postInt( 'is_active' ) === 1,
		);

		$id = $this->postInt( 'id' );

		if ( $id > 0 ) {
			// Do not overwrite the stored key with an empty value on edit.
			if ( $data['api_key'] === '' ) {
				unset( $data['api_key'] );
			}
			$this->repository->update( $id, $data );
		} else {
			$id = $this->repository->create( $data );
		}

		$this->success(
			array(
				'id'      => $id,
				'message' => __( 'Clé API enregistrée.', 'ai-product-studio' ),
			)
		);
	}

	public function delete(): void {
		$this->guard();

		$id = $this->postInt( 'id' );
		if ( $id <= 0 ) {
			$this->fail( __( 'Identifiant invalide.', 'ai-product-studio' ) );
		}

		$this->repository->delete( $id );

		$this->success( array( 'message' => __( 'Clé API supprimée.', 'ai-product-studio' ) ) );
	}

	public function toggle(): void {
		$this->guard();

		$id  = $this->postInt( 'id' );
		$key = $this->repository->find( $id );

		if ( $key === null ) {
			$this->fail( __( 'Clé introuvable.', 'ai-product-studio' ) );
		}

		$this->repository->update( $id, array( 'is_active' => ! $key->isActive ) );

		$this->success( array( 'is_active' => ! $key->isActive ) );
	}
}
