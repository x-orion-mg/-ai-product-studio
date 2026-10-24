<?php
/**
 * History view.
 *
 * @var array<int, \AIProductStudio\History\HistoryEntry> $entries
 * @var int                                               $page
 * @var int                                               $perPage
 * @var int                                               $total
 * @var int                                               $pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap aips-wrap">
	<h1><?php esc_html_e( 'Historique', 'ai-product-studio' ); ?></h1>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Statut', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Fournisseur', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Prompt', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Durée', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Produit', 'ai-product-studio' ); ?></th>
				<th><?php esc_html_e( 'Message', 'ai-product-studio' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $entries === array() ) : ?>
			<tr><td colspan="7"><?php esc_html_e( 'Aucune entrée.', 'ai-product-studio' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $entries as $entry ) : ?>
				<tr>
					<td><?php echo esc_html( $entry->createdAt ); ?></td>
					<td><span class="aips-badge aips-badge--<?php echo esc_attr( $entry->status ); ?>"><?php echo esc_html( $entry->status ); ?></span></td>
					<td><?php echo esc_html( $entry->provider ); ?></td>
					<td><?php echo esc_html( (string) $entry->promptId ); ?></td>
					<td><?php echo esc_html( (string) $entry->duration ); ?>s</td>
					<td>
						<?php if ( $entry->productId > 0 ) : ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $entry->productId ) ); ?>">#<?php echo esc_html( (string) $entry->productId ); ?></a>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $entry->message ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $page,
						'total'     => $pages,
						'prev_text' => '‹',
						'next_text' => '›',
					)
				) ?? ''
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
