<?php
/**
 * Admin feed settings.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

use Pronamic\WordPress\Pay\Admin\AdminModule;
use Pronamic\WordPress\Pay\Extensions\GravityForms\Extension;
use Pronamic\WordPress\Pay\Extensions\GravityForms\GravityForms;
use Pronamic\WordPress\Pay\Extensions\GravityForms\Links;
use Pronamic\WordPress\Pay\Extensions\GravityForms\PayFeed;

$payment_addon = $this;

$form_meta = RGFormsModel::get_form_meta( $form_id );

$entry_id_prefix = get_post_meta( $post_id, '_pronamic_pay_gf_entry_id_prefix', true );
$order_id        = get_post_meta( $post_id, '_pronamic_pay_gf_order_id', true );

if ( ! empty( $entry_id_prefix ) ) {
	$order_id = $entry_id_prefix . $order_id;
}

if ( ! GFCommon::has_merge_tag( $order_id ) ) {
	$order_id .= '{entry_id}';
}

$pay_feed = new PayFeed( $post_id );

/**
 * Private helper function for Gravity Forms dropdown input.
 *
 * @param array $form Gravity Forms form array/object.
 * @param array $args Arguments.
 * @return void
 */
function _pronamic_pay_gravityforms_dropdown_input( $form, $args ) {
	$args = \wp_parse_args(
		$args,
		[
			'id'       => '',
			'name'     => '',
			'selected' => '',
			'type'     => '',
			'inputs'   => true,
			'options'  => [],
		]
	);

	$id       = $args['id'];
	$name     = $args['name'];
	$selected = $args['selected'];
	$type     = $args['type'];
	$inputs   = $args['inputs'];
	$options  = $args['options'];

	foreach ( $form['fields'] as $field ) {
		if ( '' !== $type && $type !== $field['type'] ) {
			continue;
		}

		$field_label = empty( $field['adminLabel'] ) ? $field['label'] : $field['adminLabel'];

		if ( empty( $field->inputs ) || false === $inputs ) {
			if ( ! $field->displayOnly ) {
				$options[ $field['id'] ] = $field_label;
			}

			continue;
		}

		if ( \is_array( $field->inputs ) ) {
			foreach ( $field->inputs as $input ) {
				$input_label = empty( $input['adminLabel'] ) ? $input['label'] : $input['adminLabel'];

				$options[ $input['id'] ] = \sprintf(
					'%s (%s)',
					$field_label,
					$input_label
				);
			}
		}
	}

	\printf(
		'<select id="%s" name="%s">',
		\esc_attr( $id ),
		\esc_attr( $name )
	);

	foreach ( $options as $value => $label ) {
		\printf(
			'<option value="%s" %s>%s</option>',
			\esc_attr( $value ),
			\selected( $selected, $value, false ),
			\esc_html( $label )
		);
	}

	echo '</select>';
}

?>
<div id="gf-pay-feed-editor">
	<?php wp_nonce_field( 'pronamic_pay_save_pay_gf', 'pronamic_pay_nonce' ); ?>

	<input id="gf_ideal_feed_id" name="gf_ideal_feed_id" value="<?php echo esc_attr( $post_id ); ?>" type="hidden" />

	<input id="_pronamic_pay_gf_form_id" name="_pronamic_pay_gf_form_id" value="<?php echo esc_attr( $form_id ); ?>" type="hidden" />

	<div class="pronamic-pay-tabs">

		<ul class="pronamic-pay-tabs-items">
			<li><?php esc_html_e( 'General', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Status pages', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Subscription', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Fields', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Advanced', 'pronamic_ideal' ); ?></li>
		</ul>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">

			</div>

			<table class="pronamic-pay-table-striped form-table">
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_config_id">
							<?php esc_html_e( 'Configuration', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Gateway configuration, created via <strong>Pay » Configurations</strong>.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<?php

						$config_id = get_post_meta( $post_id, '_pronamic_pay_gf_config_id', true );

						if ( '' === $config_id ) {
							$config_id = get_option( 'pronamic_pay_config_id' );
						}

						AdminModule::dropdown_configs(
							[
								'name'     => '_pronamic_pay_gf_config_id',
								'selected' => $config_id,
							]
						);

						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_order_id">
							<?php esc_html_e( 'Order ID', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Order ID.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<input name="_pronamic_pay_gf_entry_id_prefix" value="" type="hidden" />

						<input id="_pronamic_pay_gf_order_id" name="_pronamic_pay_gf_order_id" value="<?php echo esc_attr( $order_id ); ?>" placeholder="{entry_id}" type="text" class="input-text regular-input merge-tag-support mt-position-right mt-hide_all_fields" />

						<span class="description pronamic-pay-description">
							<?php

							echo wp_kses(
								sprintf(
									'%s %s',
									__( 'Default:', 'pronamic_ideal' ),
									sprintf(
										'<code>%s</code>',
										'{entry_id}'
									)
								),
								[
									'code' => [],
								]
							);

							?>

							<br />

							<?php

							echo wp_kses(
								sprintf(
									'%s %s',
									__( 'Available tags:', 'pronamic_ideal' ),
									sprintf(
										'<code>%s</code> <code>%s</code> <code>%s</code>',
										'{entry_id}',
										'{form_id}',
										'{form_title}'
									)
								),
								[
									'code' => [],
								]
							);

							?>

							<br />

							<?php esc_html_e( 'An order ID makes it easier to match payments from transaction overviews to a form and is required by some payment providers.', 'pronamic_ideal' ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_transaction_description">
							<?php esc_html_e( 'Transaction Description', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Transaction description that will be send with the payment. Use tags to customize the description.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<?php

						$transaction_description = get_post_meta( $post_id, '_pronamic_pay_gf_transaction_description', true );

						?>
						<input id="_pronamic_pay_gf_transaction_description" name="_pronamic_pay_gf_transaction_description" value="<?php echo esc_attr( $transaction_description ); ?>" placeholder="{entry_id}" type="text" class="regular-text merge-tag-support mt-position-right mt-hide_all_fields" />

						<span class="description pronamic-pay-description">
							<?php

							echo wp_kses(
								sprintf(
									'%s %s',
									__( 'Default:', 'pronamic_ideal' ),
									sprintf(
										'<code>%s</code>',
										'{entry_id}'
									)
								),
								[
									'code' => [],
								]
							);

							?>

							<br />

							<?php

							echo wp_kses(
								sprintf(
									'%s %s',
									__( 'Available tags:', 'pronamic_ideal' ),
									sprintf(
										'<code>%s</code> <code>%s</code> <code>%s</code>',
										'{entry_id}',
										'{form_id}',
										'{form_title}'
									)
								),
								[
									'code' => [],
								]
							);

							?>

							<br />

							<?php esc_html_e( 'A description which uses tags and results in more than 32 characters will be truncated.', 'pronamic_ideal' ); ?>
						</span>
					</td>
				</tr>

				<?php

				$notifications = [];

				if ( isset( $form_meta['notifications'] ) && is_array( $form_meta['notifications'] ) ) {
					$notifications = $form_meta['notifications'];
				}

				$notifications = \array_filter(
					$notifications,
					function ( $notification ) {
						return 'form_submission' === $notification['event'];
					}
				);

				if ( count( $notifications ) > 0 ) :
					?>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Send Notifications Delay', 'pronamic_ideal' ); ?>

							<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Notifications for which sending will be delayed until the payment has been received.', 'pronamic_ideal' ); ?>"></span>
						</th>
						<td>
							<p>
								<?php esc_html_e( 'Delay sending notifications until payment has been received.', 'pronamic_ideal' ); ?>
							</p>

							<ul id="gf_ideal_delay_notifications">

								<?php foreach ( $notifications as $notification ) : ?>

									<li>
										<?php

										$id = $notification['id'];

										printf(
											'<input id="%s" type="checkbox" value="%s" name="_pronamic_pay_gf_delay_notification_ids[]" %s />',
											esc_attr( 'pronamic-pay-gf-notification-' . $id ),
											esc_attr( $id ),
											checked( in_array( $id, $pay_feed->delay_notification_ids, true ), true, false )
										);

										printf( ' ' );

										printf(
											'<label class="inline" for="%s">%s</label>',
											esc_attr( 'pronamic-pay-gf-notification-' . $id ),
											esc_html( $notification['name'] )
										);

										?>
									</li>

								<?php endforeach; ?>

							</ul>
						</td>
					</tr>

				<?php endif; ?>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Delay actions', 'pronamic_ideal' ); ?>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Actions for which execution will be delayed until the payment has been received.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<p>
							<?php esc_html_e( 'Delay actions until payment has been received.', 'pronamic_ideal' ); ?>
						</p>

						<?php

						$delay_post_creation = get_post_meta( $post_id, '_pronamic_pay_gf_delay_post_creation', true );

						$delay_actions = Extension::get_delay_actions();

						$delay_actions = array_filter(
							$delay_actions,
							function ( $action ) {
								return $action['active'];
							}
						);

						foreach ( $delay_actions as $slug => $data ) {
							$delay_actions[ $slug ]['delay'] = ( '1' === get_post_meta( $post_id, $delay_actions[ $slug ]['meta_key'], true ) );
						}

						?>
						<ul>
							<li>
								<input type="checkbox" name="_pronamic_pay_gf_delay_post_creation" id="_pronamic_pay_gf_delay_post_creation" value="true" <?php checked( $delay_post_creation ); ?> />

								<label for="_pronamic_pay_gf_delay_post_creation">
									<?php esc_html_e( 'Creating a post', 'pronamic_ideal' ); ?>
								</label>
							</li>

							<?php foreach ( $delay_actions as $slug => $action ) : ?>

								<li>
									<input type="checkbox" name="<?php echo esc_attr( $action['meta_key'] ); ?>" id="<?php echo esc_attr( $action['meta_key'] ); ?>" value="true" <?php checked( $action['delay'] ); ?> />

									<label for="<?php echo esc_attr( $action['meta_key'] ); ?>">
										<?php echo esc_html( $action['label'] ); ?>
									</label>
								</li>

							<?php endforeach; ?>

						</ul>
					</td>
				</tr>
			</table>
		</div>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">
				<?php esc_html_e( 'Set Gravity Forms confirmations, pages or URLs to redirect to after a payment with the mentioned status.', 'pronamic_ideal' ); ?>
			</div>

			<table class="pronamic-pay-table-striped form-table pronamic-gf-links-tab">
				<?php

				$fields = [
					Links::SUCCESS => __( 'Success', 'pronamic_ideal' ),
					Links::CANCEL  => __( 'Cancelled', 'pronamic_ideal' ),
					Links::EXPIRED => __( 'Expired', 'pronamic_ideal' ),
					Links::ERROR   => __( 'Error', 'pronamic_ideal' ),
					Links::OPEN    => __( 'Open', 'pronamic_ideal' ),
				];

				foreach ( $fields as $name => $label ) :

					?>

					<tr>
						<?php

						$type            = null;
						$confirmation_id = null;
						$page_id         = null;
						$url             = null;

						if ( isset( $pay_feed->links[ $name ] ) ) {
							$link = $pay_feed->links[ $name ];

							$type            = isset( $link['type'] ) ? $link['type'] : null;
							$confirmation_id = isset( $link['confirmation_id'] ) ? $link['confirmation_id'] : null;
							$page_id         = isset( $link['page_id'] ) ? $link['page_id'] : null;
							$url             = isset( $link['url'] ) ? $link['url'] : null;
						}

						?>
						<th scope="row">
							<label for="gf_ideal_link_<?php echo esc_attr( $name ); ?>_confirmation">
								<?php echo esc_html( $label ); ?>
							</label>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo esc_html( $label ); ?></span>
								</legend>

								<ul style="margin: 0;">
									<li style="margin: 0;">
										<label>
											<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_confirmation" value="confirmation" <?php checked( $type, 'confirmation' ); ?> />
											<?php esc_html_e( 'Confirmation:', 'pronamic_ideal' ); ?>
										</label>

										<?php

										$confirmations = \GFFormsModel::get_form_confirmations( $form_id );

										printf(
											'<select id="gf_ideal_link_%s_confirmation_id" name="_pronamic_pay_gf_links[%1$s][confirmation_id]" data-pronamic-link-name="%1$s">',
											esc_attr( $name )
										);

										printf(
											'<option value="%s" %s>%s</option>',
											\esc_attr( '' ),
											\selected( null === $confirmation_id, true, false ),
											\esc_html__( '— Select Confirmation —', 'pronamic_ideal' )
										);

										foreach ( $confirmations as $confirmation ) {
											printf(
												'<option value="%s" %s>%s</option>',
												\esc_attr( $confirmation['id'] ),
												\selected( $confirmation['id'], $confirmation_id, false ),
												\esc_html( $confirmation['name'] )
											);
										}

										echo '</select>';

										?>
									</li>
									<li style="margin: 0;">
										<label>
											<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_page" value="page" <?php checked( $type, 'page' ); ?> />
											<?php esc_html_e( 'Page:', 'pronamic_ideal' ); ?>
										</label>

										<?php

										wp_dropdown_pages(
											[
												'selected' => esc_attr( $page_id ),
												'name'     => esc_attr( '_pronamic_pay_gf_links[' . $name . '][page_id]' ),
												'show_option_none' => esc_html__( '— Select Page —', 'pronamic_ideal' ),
											]
										);

										?>
									</li>
									<li style="margin: 0;">
										<label>
											<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_url" value="url" <?php checked( $type, 'url' ); ?> />
											<?php esc_html_e( 'URL:', 'pronamic_ideal' ); ?>
										</label>

										<input type="text" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" class="regular-text" />
									</li>
								</ul>
							</fieldset>
						<td>
					</tr>

				<?php endforeach; ?>
			</table>
		</div>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">
				<?php esc_html_e( 'Set the subscription details for recurring payments.', 'pronamic_ideal' ); ?>
			</div>

			<table class="pronamic-pay-table-striped form-table pronamic-gf-subscription-tab">
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Recurring amount', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Recurring amount', 'pronamic_ideal' ); ?></span>
							</legend>

							<ul class="pronamic-pay-gf-form-choice-list">
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_amount_type_none" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="" <?php checked( $pay_feed->subscription_amount_type, '' ); ?> />

									<label for="pronamic_pay_gf_subscription_amount_type_none">
										<?php esc_html_e( 'None', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_amount_type_total" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="total" <?php checked( $pay_feed->subscription_amount_type, 'total' ); ?> />

									<label for="pronamic_pay_gf_subscription_amount_type_total">
										<?php esc_html_e( 'Total Amount', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_amount_type_field" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="field" <?php checked( $pay_feed->subscription_amount_type, 'field' ); ?> />

									<label for="pronamic_pay_gf_subscription_amount_type_field">
										<?php esc_html_e( 'Form Field', 'pronamic_ideal' ); ?>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<?php

										_pronamic_pay_gravityforms_dropdown_input(
											$form_meta,
											[
												'id'       => 'pronamic_pay_gf_subscription_amount_field',
												'name'     => '_pronamic_pay_gf_subscription_amount_field',
												'selected' => $pay_feed->subscription_amount_field,
												'type'     => 'product',
												'inputs'   => false,
												'options'  => [
													'' => '',
												],
											]
										);

										?>
									</div>
								</li>
							</ul>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Interval', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Interval', 'pronamic_ideal' ); ?></span>
							</legend>

							<ul class="pronamic-pay-gf-form-choice-list">
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_interval_type_fixed" name="_pronamic_pay_gf_subscription_interval_type" type="radio" value="fixed" <?php checked( $pay_feed->subscription_interval_type, 'fixed' ); ?> />

									<label for="pronamic_pay_gf_subscription_interval_type_fixed">
										<?php esc_html_e( 'Fixed', 'pronamic_ideal' ); ?>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<?php echo esc_html( _x( 'Every', 'Recurring payment', 'pronamic_ideal' ) ); ?>

										<input id="pronamic_pay_gf_subscription_interval" name="_pronamic_pay_gf_subscription_interval" type="text" size="4" value="<?php echo esc_attr( $pay_feed->subscription_interval ); ?>" />

										<select id="pronamic_pay_gf_subscription_interval_period" name="_pronamic_pay_gf_subscription_interval_period">
											<option value="D" <?php selected( $pay_feed->subscription_interval_period, 'D' ); ?>><?php esc_html_e( 'day(s)', 'pronamic_ideal' ); ?></option>
											<option value="W" <?php selected( $pay_feed->subscription_interval_period, 'W' ); ?>><?php esc_html_e( 'week(s)', 'pronamic_ideal' ); ?></option>
											<option value="M" <?php selected( $pay_feed->subscription_interval_period, 'M' ); ?>><?php esc_html_e( 'month(s)', 'pronamic_ideal' ); ?></option>
											<option value="Y" <?php selected( $pay_feed->subscription_interval_period, 'Y' ); ?>><?php esc_html_e( 'year(s)', 'pronamic_ideal' ); ?></option>
										</select>
									</div>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_interval_type_field" name="_pronamic_pay_gf_subscription_interval_type" type="radio" value="field" <?php checked( $pay_feed->subscription_interval_type, 'field' ); ?> />

									<label for="pronamic_pay_gf_subscription_interval_type_field">
										<?php esc_html_e( 'Form field', 'pronamic_ideal' ); ?>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<?php

										_pronamic_pay_gravityforms_dropdown_input(
											$form_meta,
											[
												'id'       => 'pronamic_pay_gf_subscription_interval_field',
												'name'     => '_pronamic_pay_gf_subscription_interval_field',
												'selected' => $pay_feed->subscription_interval_field,
												'options'  => [
													'' => '',
												],
											]
										);

										echo ' ';

										esc_html_e( 'days', 'pronamic_ideal' );

										?>

										<br />

										<p class="description pronamic-pay-description">
											<?php

											esc_html_e( 'Use a field value of 0 days for one-time payments.', 'pronamic_ideal' );

											?>
										</p>
									</div>
								</li>
							</ul>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Number of Periods', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Number of Periods', 'pronamic_ideal' ); ?></span>
							</legend>

							<ul class="pronamic-pay-gf-form-choice-list">
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_frequency_type_unlimited" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="unlimited" <?php checked( $pay_feed->subscription_frequency_type, 'unlimited' ); ?> />

									<label for="pronamic_pay_gf_subscription_frequency_type_unlimited">
										<?php echo esc_html_x( 'Unlimited', 'Recurring payment', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_frequency_type_fixed" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="fixed" <?php checked( $pay_feed->subscription_frequency_type, 'fixed' ); ?> />

									<label for="pronamic_pay_gf_subscription_frequency_type_fixed">
										<?php esc_html_e( 'Fixed', 'pronamic_ideal' ); ?>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<input id="pronamic_pay_gf_subscription_number_periods" name="_pronamic_pay_gf_subscription_number_periods" type="text" size="4" value="<?php echo esc_attr( $pay_feed->subscription_number_periods ); ?>" />

										<?php echo esc_html( _x( 'times', 'Recurring payment', 'pronamic_ideal' ) ); ?>
									</div>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_frequency_type_field" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="field" <?php checked( $pay_feed->subscription_frequency_type, 'field' ); ?> />

									<label for="pronamic_pay_gf_subscription_frequency_type_field">
										<?php esc_html_e( 'Form Field', 'pronamic_ideal' ); ?>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<?php

										_pronamic_pay_gravityforms_dropdown_input(
											$form_meta,
											[
												'id'       => 'pronamic_pay_gf_subscription_frequency_field',
												'name'     => '_pronamic_pay_gf_subscription_frequency_field',
												'selected' => $pay_feed->subscription_frequency_field,
												'options'  => [
													'' => '',
												],
											]
										);

										echo ' ';

										echo esc_html( _x( 'times', 'Recurring payment', 'pronamic_ideal' ) );

										?>
									</div>
								</li>
							</ul>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Trial Period', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<?php

						$trial = $pay_feed->get_subscription_trial();

						?>

						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Trial Period', 'pronamic_ideal' ); ?></span>
							</legend>

							<div class="pronamic-pay-gf-form-choice">
								<input id="pronamic_pay_gf_subscription_trial_enabled" name="_pronamic_pay_gf_subscription_trial_enabled" type="checkbox" value="1" <?php checked( $trial->enabled ); ?> />

								<label for="pronamic_pay_gf_subscription_trial_enabled">
									<?php esc_html_e( 'Enable trial period', 'pronamic_ideal' ); ?>
								</label>

								<div class="pronamic-pay-gf-form-choice-checked">
									<label for="pronamic_pay_gf_subscription_trial_length">
										<?php esc_html_e( 'Length', 'pronamic_ideal' ); ?>
									</label>

									<input id="pronamic_pay_gf_subscription_trial_length" name="_pronamic_pay_gf_subscription_trial_length" type="number" step="1" min="1" value="<?php echo esc_attr( $trial->length ); ?>" />

									<select id="pronamic_pay_gf_subscription_trial_length_unit" name="_pronamic_pay_gf_subscription_trial_length_unit">
										<option value="D" <?php selected( $trial->length_unit, 'D' ); ?>><?php esc_html_e( 'day(s)', 'pronamic_ideal' ); ?></option>
										<option value="W" <?php selected( $trial->length_unit, 'W' ); ?>><?php esc_html_e( 'week(s)', 'pronamic_ideal' ); ?></option>
										<option value="M" <?php selected( $trial->length_unit, 'M' ); ?>><?php esc_html_e( 'month(s)', 'pronamic_ideal' ); ?></option>
										<option value="Y" <?php selected( $trial->length_unit, 'Y' ); ?>><?php esc_html_e( 'year(s)', 'pronamic_ideal' ); ?></option>
									</select>

									<br />

									<p class="description pronamic-pay-description">
										<?php

										\esc_html_e(
											'The trial period uses the total amount of the form. You can set the recurring amount separately and add product fields with negative amounts for a discounted trial period.',
											'pronamic_ideal'
										);

										?>
									</p>
								</div>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Payment Date Alignment', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Payment Date Alignment', 'pronamic_ideal' ); ?></span>
							</legend>

							<p>
								<?php

								esc_html_e( 'The payment date can be aligned to a fixed day of the week, month, or year. An additional subscription phase is added at the beginning of the subscription to achieve this, resulting in one extra period being added to the configured number of periods. Optionally, the amount of this alignment phase can be prorated.', 'pronamic_ideal' );

								?>
							</p>

							<ul class="pronamic-pay-gf-form-choice-list">
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_interval_date_type_payment_date" name="_pronamic_pay_gf_subscription_interval_date_type" type="radio" value="payment_date" <?php checked( $pay_feed->subscription_interval_date_type, 'payment_date' ); ?> />

									<label for="pronamic_pay_gf_subscription_interval_date_type_payment_date">
										<?php esc_html_e( 'Entry Date', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li class="pronamic-pay-gf-form-choice">
									<input id="pronamic_pay_gf_subscription_interval_date_type_field" name="_pronamic_pay_gf_subscription_interval_date_type" type="radio" value="sync" <?php checked( $pay_feed->subscription_interval_date_type, 'sync' ); ?> />

									<label for="pronamic_pay_gf_subscription_interval_date_type_field">
										<?php

										$allowed_html = [
											'select' => [
												'class' => true,
												'id'    => true,
												'name'  => true,
											],
											'option' => [
												'value'    => true,
												'selected' => true,
											],
											'sup'    => [],
										];


										/**
										 * Locale.
										 *
										 * @link https://developer.wordpress.org/reference/classes/wp_locale/get_weekday/
										 * @link https://github.com/WordPress/WordPress/blob/5.2/wp-includes/class-wp-locale.php#L121-L128
										 */
										global $wp_locale;

										// Weekday options.
										$weekdays = [
											1 => $wp_locale->get_weekday( 1 ),
											2 => $wp_locale->get_weekday( 2 ),
											3 => $wp_locale->get_weekday( 3 ),
											4 => $wp_locale->get_weekday( 4 ),
											5 => $wp_locale->get_weekday( 5 ),
											6 => $wp_locale->get_weekday( 6 ),
											7 => $wp_locale->get_weekday( 0 ),
										];

										$weekday_options_html = '';

										foreach ( $weekdays as $day_value => $label ) {
											$weekday_options_html .= sprintf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $day_value ),
												selected( $pay_feed->subscription_interval_date_day, $day_value, false ),
												esc_html( $label )
											);
										}

										// Monthday options.
										$monthdays = range( 1, 28 );

										$monthday_options_html = '';

										foreach ( $monthdays as $value ) {
											$monthday_options_html .= sprintf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $value ),
												selected( $pay_feed->subscription_interval_date, $value, false ),
												esc_html( $value )
											);
										}

										// Month options.
										$month_options_html = '';

										foreach ( range( 1, 12 ) as $month_number ) {
											$month_options_html .= sprintf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $month_number ),
												selected( $pay_feed->subscription_interval_date_month, $month_number, false ),
												esc_html( $wp_locale->get_month( $month_number ) )
											);
										}

										?>

										<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-D">
											<?php esc_html_e( 'Not Available', 'pronamic_ideal' ); ?>
										</span>

										<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-W">
											<?php

											$select = sprintf(
												'<select id="%s" name="%s">%s</select>',
												esc_attr( 'pronamic_pay_gf_subscription_interval_date_day' ),
												esc_attr( '_pronamic_pay_gf_subscription_interval_date_day' ),
												$weekday_options_html
											);

											echo wp_kses(
												sprintf(
													/* translators: %s: input HTML */
													__( 'On %s', 'pronamic_ideal' ),
													$select
												),
												$allowed_html
											);

											?>
											<br />
										</span>

										<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-M">
											<?php

											$select = sprintf(
												'<select id="%s" name="%s">%s</select>',
												esc_attr( 'pronamic_pay_gf_subscription_interval_date' ),
												esc_attr( '_pronamic_pay_gf_subscription_interval_m_date' ),
												$monthday_options_html
											);

											echo wp_kses(
												sprintf(
													/* translators: %s: <select> Monthday (1-27). */
													__( 'On the %s <sup>th</sup> day of the month', 'pronamic_ideal' ),
													$select
												),
												$allowed_html
											);

											?>
											<br />
										</span>

										<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-Y">
											<?php

											$select_monthday = sprintf(
												'<select id="%s" name="%s">%s</select>',
												esc_attr( 'pronamic_pay_gf_subscription_interval_date' ),
												esc_attr( '_pronamic_pay_gf_subscription_interval_y_date' ),
												$monthday_options_html
											);

											$select_month = sprintf(
												'<select id="%s" name="%s">%s</select>',
												esc_attr( 'pronamic_pay_gf_subscription_interval_date_month' ),
												esc_attr( '_pronamic_pay_gf_subscription_interval_date_month' ),
												$month_options_html
											);

											echo wp_kses(
												sprintf(
													/* translators: 1: <select> Monthday (1-27), 2: <select> Month (Jan-Dec). */
													__( 'On %1$s %2$s', 'pronamic_ideal' ),
													$select_monthday,
													$select_month
												),
												$allowed_html
											);

											?>
										</span>
									</label>

									<div class="pronamic-pay-gf-form-choice-checked">
										<input type="checkbox" name="_pronamic_pay_gf_subscription_interval_date_prorate" id="pronamic_pay_gf_subscription_interval_date_prorate" value="true" <?php checked( $pay_feed->subscription_interval_date_prorate ); ?> />

										<label for="pronamic_pay_gf_subscription_interval_date_prorate">
											<?php

											/* translators: nl: Bereken bedrag uitlijingsperiode pro rata. */
											esc_html_e( 'Prorate the amount of the alignment period.', 'pronamic_ideal' );

											?>
										</label>
									</div>
								</li>
							</ul>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">
				<?php esc_html_e( 'Set corresponding form fields to include user data in the payment data with some payment providers.', 'pronamic_ideal' ); ?>
			</div>

			<?php

			$fields = [
				'prefix_name'                => __( 'Prefix Name', 'pronamic_ideal' ),
				'first_name'                 => __( 'First Name', 'pronamic_ideal' ),
				'middle_name'                => __( 'Middle Name', 'pronamic_ideal' ),
				'last_name'                  => __( 'Last Name', 'pronamic_ideal' ),
				'suffix_name'                => __( 'Suffix Name', 'pronamic_ideal' ),
				'address1'                   => __( 'Address', 'pronamic_ideal' ),
				'address2'                   => __( 'Address 2', 'pronamic_ideal' ),
				'zip'                        => __( 'Zip', 'pronamic_ideal' ),
				'city'                       => __( 'City', 'pronamic_ideal' ),
				'state'                      => __( 'State', 'pronamic_ideal' ),
				'country'                    => __( 'Country', 'pronamic_ideal' ),
				'telephone_number'           => __( 'Telephone Number', 'pronamic_ideal' ),
				'email'                      => __( 'Email', 'pronamic_ideal' ),
				'consumer_bank_details_name' => __( 'Account Holder Name', 'pronamic_ideal' ),
				'consumer_bank_details_iban' => __( 'Account IBAN', 'pronamic_ideal' ),
				'company_name'               => __( 'Company Name', 'pronamic_ideal' ),
				'vat_number'                 => __( 'VAT Number', 'pronamic_ideal' ),
			];

			$meta_fields = \get_post_meta( $post_id, '_pronamic_pay_gf_fields', true );
			$meta_fields = \is_array( $meta_fields ) ? $meta_fields : [];

			?>

			<table class="pronamic-pay-table-striped form-table">

				<?php foreach ( $fields as $name => $label ) : ?>

					<tr>
						<th scope="row">
							<label for="gf_ideal_fields_<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $label ); ?>
							</label>
						</th>
						<td>
							<?php

							$auto_option_label = '';

							if ( in_array( $name, [ 'prefix_name', 'first_name', 'middle_name', 'last_name', 'suffix_name' ], true ) ) {
								$auto_option_label = __( '— From first name field —', 'pronamic_ideal' );
							} elseif ( in_array( $name, [ 'address1', 'address2', 'zip', 'city', 'state', 'country' ], true ) ) {
								$auto_option_label = __( '— From first address field —', 'pronamic_ideal' );
							} elseif ( 'telephone_number' === $name ) {
								$auto_option_label = __( '— First phone field —', 'pronamic_ideal' );
							} elseif ( 'email' === $name ) {
								$auto_option_label = __( '— First email address field —', 'pronamic_ideal' );
							}

							$current = 'auto';

							if ( \array_key_exists( $name, $meta_fields ) ) {
								$current = $meta_fields[ $name ];
							}

							$options = [];

							if ( '' !== $auto_option_label ) {
								$options['auto'] = $auto_option_label;
							}

							$options[''] = '';

							_pronamic_pay_gravityforms_dropdown_input(
								$form_meta,
								[
									'id'       => 'gf_ideal_fields_' . $name,
									'name'     => '_pronamic_pay_gf_fields[' . $name . ']',
									'selected' => $current,
									'options'  => $options,
								]
							);

							?>
						</td>
					</tr>

				<?php endforeach; ?>

			</table>
		</div>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">
				<?php esc_html_e( 'Optional settings for advanced usage only.', 'pronamic_ideal' ); ?>
			</div>

			<table class="pronamic-pay-table-striped form-table">
				<tr>
					<th scope="row">
						<label for="gf_ideal_condition_field_id">
							<?php esc_html_e( 'Conditional Logic', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<div id="gf_ideal_condition_config">
							<?php

							if ( GravityForms::version_compare( '2.5-rc', '>=' ) ) {
								/**
								 * We are executing the `gform_admin_pre_render` filter here, instead of within the script below,
								 * as the filter can also result in output breaking our script.
								 *
								 * @link https://docs.gravityforms.com/gform_admin_pre_render/
								 */
								$form = \gf_apply_filters( [ 'gform_admin_pre_render', $form_id ], GFFormsModel::get_form_meta( $form_id ) );

								?>

								<script type="text/javascript">
								var form = <?php echo \wp_json_encode( $form ); ?>;
								</script>

								<?php
							}

							?>

							<script type="text/javascript">
								function GetConditionalLogicFields () {
									<?php

									$conditional_logic_fields = [];

									foreach ( \GF_Fields::get_all() as $gf_field ) {
										if ( ! $gf_field->is_conditional_logic_supported() ) {
											continue;
										}

										$conditional_logic_fields[] = $gf_field->type;
									}

									printf(
										'return %s;',
										\wp_json_encode( $conditional_logic_fields )
									);

									?>
								}
							</script>

							<?php

							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$post_id = array_key_exists( 'fid', $_GET ) ? \sanitize_text_field( \wp_unslash( $_GET['fid'] ) ) : null;

							if ( null !== $post_id && method_exists( $payment_addon, 'get_settings_renderer' ) ) {
								if ( false === $payment_addon->get_settings_renderer() && class_exists( '\Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
									$payment_addon->set_settings_renderer( new \Gravity_Forms\Gravity_Forms\Settings\Settings() );
								}

								$feed = new PayFeed( $post_id );

								$payment_addon->get_settings_renderer()->set_values(
									[
										'feed_condition_conditional_logic'        => $feed->condition_enabled,
										'feed_condition_conditional_logic_object' => $feed->conditional_logic_object,
									]
								);
							}

							$field = [
								'name'  => 'conditionalLogic',
								'label' => __( 'Conditional Logic', 'pronamic_ideal' ),
								'type'  => 'feed_condition',
							];

							$payment_addon->settings_feed_condition( $field );

							?>
						</div>

						<input id="gf_ideal_condition_enabled" name="_pronamic_pay_gf_condition_enabled" type="hidden"
							value="<?php echo esc_attr( $pay_feed->condition_enabled ); ?>"/>

						<p class="description pronamic-pay-description">
							<?php esc_html_e( 'Set conditional logic to only use this gateway if the entry matches the condition(s).', 'pronamic_ideal' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gf_ideal_user_role_field_id">
							<?php esc_html_e( 'Update user role', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<?php

						_pronamic_pay_gravityforms_dropdown_input(
							$form_meta,
							[
								'id'       => 'gf_ideal_user_role_field_id',
								'name'     => '_pronamic_pay_gf_user_role_field_id',
								'selected' => \get_post_meta( $post_id, '_pronamic_pay_gf_user_role_field_id', true ),
								'options'  => [
									'' => \__( '— Select Field —', 'pronamic_ideal' ),
								],
							]
						);

						?>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
