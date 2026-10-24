<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\Prompt\PromptRepository;

/**
 * CRUD AJAX endpoints for prompts.
 */
final class PromptController extends AbstractController {

	private PromptRepository $repository;

	public function __construct( PromptRepository $repository ) {
		$this->repository = $repository;
	}

	public function save(): void {
		$this->guard();

		// Prompt content may legitimately contain markup/braces, keep it raw but unslashed.
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( (string) $_POST['content'] ) ) : '';

		$data = array(
			'name'        => $this->post( 'name' ),
			'description' => $this->postTextarea( 'description' ),
			'content'     => $content,
			'is_active'   => $this->postInt( 'is_active' ) === 1,
		);

		if ( $data['name'] === '' ) {
			$this->fail( __( 'Le nom du prompt est obligatoire.', 'ai-product-studio' ) );
		}

		$id = $this->postInt( 'id' );

		if ( $id > 0 ) {
			$this->repository->update( $id, $data );
		} else {
			$id = $this->repository->create( $data );
		}

		$this->success(
			array(
				'id'      => $id,
				'message' => __( 'Prompt enregistré.', 'ai-product-studio' ),
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

		$this->success( array( 'message' => __( 'Prompt supprimé.', 'ai-product-studio' ) ) );
	}

	public function toggle(): void {
		$this->guard();

		$id     = $this->postInt( 'id' );
		$prompt = $this->repository->find( $id );

		if ( $prompt === null ) {
			$this->fail( __( 'Prompt introuvable.', 'ai-product-studio' ) );
		}

		$this->repository->update( $id, array( 'is_active' => ! $prompt->isActive ) );

		$this->success( array( 'is_active' => ! $prompt->isActive ) );
	}
}
