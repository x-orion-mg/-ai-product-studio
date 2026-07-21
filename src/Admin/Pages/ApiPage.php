<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\AI\ProviderFactory;
use AIProductStudio\API\ApiKeyRepository;

final class ApiPage extends AbstractPage {

	public function slug(): string {
		return 'api';
	}

	public function title(): string {
		return __( 'API — AI Product Studio', 'ai-product-studio' );
	}

	public function menuTitle(): string {
		return __( 'API', 'ai-product-studio' );
	}

	public function render(): void {
		/** @var ApiKeyRepository $repository */
		$repository = $this->container->get( ApiKeyRepository::class );
		/** @var ProviderFactory $factory */
		$factory = $this->container->get( ProviderFactory::class );

		$this->view(
			'api',
			array(
				'keys'      => $repository->all(),
				'providers' => $factory->available(),
			)
		);
	}
}
