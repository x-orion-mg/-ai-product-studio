<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\Prompt\PromptRepository;

final class PromptsPage extends AbstractPage {

	public function slug(): string {
		return 'prompts';
	}

	public function title(): string {
		return __( 'Prompts — AI Product Studio', 'ai-product-studio' );
	}

	public function menuTitle(): string {
		return __( 'Prompts', 'ai-product-studio' );
	}

	public function render(): void {
		/** @var PromptRepository $repository */
		$repository = $this->container->get( PromptRepository::class );

		$this->view(
			'prompts',
			array(
				'prompts' => $repository->all(),
			)
		);
	}
}
