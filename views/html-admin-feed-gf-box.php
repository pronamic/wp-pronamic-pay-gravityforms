<form method="post" action="">
	<?php

	if ( filter_has_var( INPUT_GET, 'message' ) ) {
		$message = filter_input( INPUT_GET, 'message', FILTER_SANITIZE_STRING );

		// Notice
		$msg   = __( 'There was an error updating this payment feed.', 'pronamic_ideal' );
		$class = 'error';

		if ( '1' === $message ) {
			$class = 'updated';
			$msg   = __( 'Payment feed updated successfully.', 'pronamic_ideal' );
		}

		printf(
			'<div class="%s below-h2"><p>%s</p></div>',
			esc_attr( $class ),
			esc_html( $msg )
		);
	}

	?>

	<div id="titlediv">
		<div id="titlewrap">
			<?php

			$title_placeholder = __( 'Enter title here', 'pronamic_ideal' );

			?>

			<label class="screen-reader-text" id="title-prompt-text" for="title">
				<?php echo esc_html( $title_placeholder ); ?>
			</label>

			<input type="text" name="_pronamic_pay_gf_post_title" size="30" value="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" id="title" spellcheck="true" autocomplete="off"  placeholder="<?php echo esc_attr( $title_placeholder ); ?>" />
		</div>
	</div>

	<?php include dirname( __FILE__ ) . '/html-admin-feed-settings.php'; ?>

	<?php submit_button(); ?>
</form>
