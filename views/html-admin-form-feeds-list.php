<div id="gf-pay-feed-list">

	<h3>
		<span>
			<i class="dashicons dashicons-money fa-"></i>
			<?php esc_html_e( 'Pay', 'pronamic_ideal' ) ?>
			<a id="add-new-confirmation" class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'fid' => 0 ) ) ); ?>"><?php esc_html_e( 'Add New', 'pronamic_ideal' ) ?></a>
		</span>
	</h3>

	<?php

	$list = new Pronamic_WP_Pay_Extensions_GravityForms_PayFeed_List_Table( $form_id );

	$list->display();

	?>
</div>

<div id="delete-confirm" title="<?php esc_attr_e( 'Delete pay feed?', 'pronamic_ideal' ); ?>" style="display: none;">
	<?php esc_html_e( 'Are you sure you want to delete the pay feed?', 'pronamic_ideal' ); ?>
</div>
