<?php

declare(strict_types=1);

namespace AIProductStudio\Admin;

use AIProductStudio\Admin\Pages\ApiPage;
use AIProductStudio\Admin\Pages\DashboardPage;
use AIProductStudio\Admin\Pages\GeneratePage;
use AIProductStudio\Admin\Pages\HistoryPage;
use AIProductStudio\Admin\Pages\LogsPage;
use AIProductStudio\Admin\Pages\PromptsPage;
use AIProductStudio\Admin\Pages\SettingsPage;
use AIProductStudio\Core\Container;

/**
 * Builds the "AI Product Studio" admin menu and its sub-pages.
 */
final class AdminMenu {

	private const CAPABILITY = 'manage_woocommerce';
	private const PARENT     = 'ai-product-studio';

	private Container $container;

	/** @var array<int, AbstractPage> */
	private array $pages;

	public function __construct( Container $container ) {
		$this->container = $container;

		$this->pages = array(
			new DashboardPage( $container ),
			new GeneratePage( $container ),
			new SettingsPage( $container ),
			new PromptsPage( $container ),
			new ApiPage( $container ),
			new HistoryPage( $container ),
			new LogsPage( $container ),
		);
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'buildMenu' ) );
		add_action( 'admin_init', array( $this, 'handleFormPosts' ) );
	}

	public function buildMenu(): void {
		add_menu_page(
			__( 'AI Product Studio', 'ai-product-studio' ),
			__( 'AI Product Studio', 'ai-product-studio' ),
			self::CAPABILITY,
			self::PARENT,
			array( $this->pages[0], 'render' ),
			'dashicons-superhero',
			56
		);

		foreach ( $this->pages as $index => $page ) {
			$slug = $index === 0 ? self::PARENT : self::PARENT . '-' . $page->slug();

			add_submenu_page(
				self::PARENT,
				$page->title(),
				$page->menuTitle(),
				self::CAPABILITY,
				$slug,
				array( $page, 'render' )
			);
		}
	}

	/**
	 * Handle non-AJAX settings form submissions (Configuration page).
	 */
	public function handleFormPosts(): void {
		foreach ( $this->pages as $page ) {
			if ( $page instanceof SettingsPage ) {
				$page->maybeHandleSubmit();
			}
		}
	}
}
