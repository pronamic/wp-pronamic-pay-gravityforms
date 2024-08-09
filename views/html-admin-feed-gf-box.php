<?php
/**
 * Admin feed Gravity Forms box.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

?>
<form method="post" action="">
	<?php

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$message = array_key_exists( 'message', $_GET ) ? filter_var( $_GET['message'], \FILTER_SANITIZE_NUMBER_INT ) : null;

	if ( null !== $message ) {
		// Notice.
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

			$title = empty( $post_id ) ? '' : get_the_title( $post_id );

			$title_placeholder = __( 'Enter title here', 'pronamic_ideal' );

			?>

			<label class="screen-reader-text" id="title-prompt-text" for="title">
				<?php echo esc_html( $title_placeholder ); ?>
			</label>

			<input type="text" name="_pronamic_pay_gf_post_title" size="30" value="<?php echo esc_attr( $title ); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php echo esc_attr( $title_placeholder ); ?>" />
		</div>
	</div>

	<?php require __DIR__ . '/html-admin-feed-settings.php'; ?>

	<?php submit_button(); ?>
</form>
