<?php
/**
 * Admin temporary feeds.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

?>
<script type="text/javascript">
	function dismissMenu() {
		jQuery( '#gf_spinner' ).show();

		jQuery.post( ajaxurl, {
				action : 'gf_dismiss_pronamic_pay_feeds_menu'
			},

			function ( response ) {
				document.location.href = '?page=gf_edit_forms';

				jQuery( '#gf_spinner' ).hide();
			}
		);
	}
</script>

<div class="wrap about-wrap">
	<h1>
		<?php

		echo esc_html(
			sprintf(
				/* translators: %s: Pronamic Pay version number, i.e. 4.7.0 */
				__( 'Pronamic Pay v%s', 'pronamic_ideal' ),
				pronamic_pay_plugin()->get_version()
			)
		);

		?>
	</h1>

	<div class="about-text">
		<?php esc_html_e( 'Thank you for updating! This new version of Pronamic Pay changes how you manage your payment feeds.', 'pronamic_ideal' ); ?>
	</div>

	<hr/>

	<div class="feature-section one-col">
		<h2><?php esc_html_e( 'Manage pay feeds contextually', 'pronamic_ideal' ); ?></h2>

		<p class="lead-description"><?php esc_html_e( 'Pay feeds are now accessed via the Pay sub-menu within the Form Settings.', 'pronamic_ideal' ); ?></p>

		<img src="<?php echo esc_url( plugins_url( 'images/contextual_pay_feeds.png', dirname( __FILE__ ) ) ); ?>" alt="">
	</div>

	<form method="post" id="dismiss_menu_form">
		<label>
			<input type="checkbox" name="dismiss_pronamic_pay_feeds_menu" value="1" onclick="dismissMenu();">

			<?php esc_html_e( 'I understand this change, hide this message.', 'pronamic_ideal' ); ?>
		</label>

		<img id="gf_spinner" src="<?php echo esc_attr( GFCommon::get_base_url() . '/images/spinner.gif' ); ?>" alt="<?php esc_attr_e( 'Please wait…', 'pronamic_ideal' ); ?>" style="display: none;" />
	</form>
</div>
