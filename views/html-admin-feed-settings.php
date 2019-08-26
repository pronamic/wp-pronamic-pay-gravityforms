<?php
/**
 * Admin feed settings.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Pay\Admin\AdminModule;
use Pronamic\WordPress\Pay\Extensions\GravityForms\Extension;
use Pronamic\WordPress\Pay\Extensions\GravityForms\GravityForms;
use Pronamic\WordPress\Pay\Extensions\GravityForms\Links;

$form_meta = RGFormsModel::get_form_meta( $form_id );

$condition_enabled                  = get_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
$condition_field_id                 = get_post_meta( $post_id, '_pronamic_pay_gf_condition_field_id', true );
$condition_operator                 = get_post_meta( $post_id, '_pronamic_pay_gf_condition_operator', true );
$condition_value                    = get_post_meta( $post_id, '_pronamic_pay_gf_condition_value', true );
$delay_notification_ids             = get_post_meta( $post_id, '_pronamic_pay_gf_delay_notification_ids', true );
$links                              = get_post_meta( $post_id, '_pronamic_pay_gf_links', true );
$subscription_amount_type           = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_amount_type', true );
$subscription_amount_field          = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_amount_field', true );
$subscription_interval_type         = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_type', true );
$subscription_interval              = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval', true );
$subscription_interval_period       = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_period', true );
$subscription_interval_date_type    = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_type', true );
$subscription_interval_date         = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date', true );
$subscription_interval_date_day     = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_day', true );
$subscription_interval_date_month   = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_month', true );
$subscription_interval_date_prorate = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_prorate', true );
$subscription_interval_field        = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_field', true );
$subscription_frequency_type        = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency_type', true );
$subscription_frequency             = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency', true );
$subscription_frequency_field       = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency_field', true );

$entry_id_prefix = get_post_meta( $post_id, '_pronamic_pay_gf_entry_id_prefix', true );
$order_id        = get_post_meta( $post_id, '_pronamic_pay_gf_order_id', true );

if ( ! empty( $entry_id_prefix ) ) {
	$order_id = $entry_id_prefix . $order_id;
}

if ( ! GFCommon::has_merge_tag( $order_id ) ) {
	$order_id .= '{entry_id}';
}

$feed                             = new stdClass();
$feed->conditionEnabled           = $condition_enabled;
$feed->conditionFieldId           = $condition_field_id;
$feed->conditionOperator          = $condition_operator;
$feed->conditionValue             = $condition_value;
$feed->delayNotificationIds       = $delay_notification_ids;
$feed->fields                     = get_post_meta( $post_id, '_pronamic_pay_gf_fields', true );
$feed->userRoleFieldId            = get_post_meta( $post_id, '_pronamic_pay_gf_user_role_field_id', true );
$feed->links                      = $links;
$feed->subscriptionAmountType     = $subscription_amount_type;
$feed->subscriptionAmountField    = $subscription_amount_field;
$feed->subscriptionIntervalType   = $subscription_interval_type;
$feed->subscriptionInterval       = $subscription_interval;
$feed->subscriptionIntervalPeriod = $subscription_interval_period;
$feed->subscriptionIntervalField  = $subscription_interval_field;
$feed->subscriptionFrequencyType  = $subscription_frequency_type;
$feed->subscriptionFrequency      = $subscription_frequency;
$feed->subscriptionFrequencyField = $subscription_frequency_field;

?>
<div id="gf-pay-feed-editor">
	<?php wp_nonce_field( 'pronamic_pay_save_pay_gf', 'pronamic_pay_nonce' ); ?>

	<input id="gf_ideal_gravity_form" name="gf_ideal_gravity_form" value="<?php echo esc_attr( wp_json_encode( $form_meta ) ); ?>" type="hidden" />
	<input id="gf_ideal_feed_id" name="gf_ideal_feed_id" value="<?php echo esc_attr( $post_id ); ?>" type="hidden" />
	<input id="gf_ideal_feed" name="gf_ideal_feed" value="<?php echo esc_attr( wp_json_encode( $feed ) ); ?>" type="hidden" />

	<?php if ( filter_has_var( INPUT_GET, 'fid' ) ) : ?>

		<input id="_pronamic_pay_gf_form_id" name="_pronamic_pay_gf_form_id" value="<?php echo esc_attr( $form_id ); ?>" type="hidden" />

	<?php endif; ?>

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

				<?php if ( ! filter_has_var( INPUT_GET, 'fid' ) ) : ?>

					<tr>
						<th scope="row">
							<label for="_pronamic_pay_gf_form_id">
								<?php esc_html_e( 'Form', 'pronamic_ideal' ); ?>
							</label>

							<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'The Gravity Forms form to process payments for.', 'pronamic_ideal' ); ?>"></span>
						</th>
						<td>
							<select id="_pronamic_pay_gf_form_id" name="_pronamic_pay_gf_form_id">
								<option value=""><?php esc_html_e( '— Select a form —', 'pronamic_ideal' ); ?></option>

								<?php foreach ( RGFormsModel::get_forms() as $form ) : ?>

									<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id, $form->id ); ?>>
										<?php echo esc_html( $form->title ); ?>
									</option>

								<?php endforeach; ?>
							</select>
						</td>
					</tr>

				<?php endif; ?>

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
							array(
								'name'     => '_pronamic_pay_gf_config_id',
								'selected' => $config_id,
							)
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
								array(
									'code' => array(),
								)
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
								array(
									'code' => array(),
								)
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
								array(
									'code' => array(),
								)
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
								array(
									'code' => array(),
								)
							);

							?>

							<br />

							<?php esc_html_e( 'A description which uses tags and results in more than 32 characters will be truncated.', 'pronamic_ideal' ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Send Notifications Delay', 'pronamic_ideal' ); ?>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" title="<?php esc_attr_e( 'Notifications for which sending will be delayed until the payment has been received.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<p>
							<?php esc_html_e( 'Delay sending notifications until payment has been received.', 'pronamic_ideal' ); ?>
						</p>

						<?php

						if ( ! is_array( $delay_notification_ids ) ) {
							$delay_notification_ids = array();
						}

						$delay_admin_notification = get_post_meta( $post_id, '_pronamic_pay_gf_delay_admin_notification', true );
						$delay_user_notification  = get_post_meta( $post_id, '_pronamic_pay_gf_delay_user_notification', true );

						if ( version_compare( GFCommon::$version, '1.7', '>=' ) ) :

							$notifications = array();
							if ( isset( $form_meta['notifications'] ) && is_array( $form_meta['notifications'] ) ) {
								$notifications = $form_meta['notifications'];
							}

							printf( '<ul id="gf_ideal_delay_notifications">' );

							if ( ! empty( $notifications ) ) {
								foreach ( $notifications as $notification ) {
									if ( 'form_submission' !== $notification['event'] ) {
										continue;
									}

									$id = $notification['id'];

									printf( '<li>' );

									printf(
										'<input id="%s" type="checkbox" value="%s" name="_pronamic_pay_gf_delay_notification_ids[]" %s />',
										esc_attr( 'pronamic-pay-gf-notification-' . $id ),
										esc_attr( $id ),
										checked( in_array( $id, $delay_notification_ids, true ), true, false )
									);

									printf( ' ' );

									printf(
										'<label class="inline" for="%s">%s</label>',
										esc_attr( 'pronamic-pay-gf-notification-' . $id ),
										esc_html( $notification['name'] )
									);

									printf( '</li>' );
								}
							}

							printf( '</ul>' );

						else :

							?>

							<ul>
								<li id="gf_ideal_delay_admin_notification_item">
									<input type="checkbox" name="_pronamic_pay_gf_delay_admin_notification" id="gf_ideal_delay_admin_notification" value="true" <?php checked( $delay_admin_notification ); ?> />

									<label for="gf_ideal_delay_admin_notification">
										<?php esc_html_e( 'Admin notification', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li id="gf_ideal_delay_user_notification_item">
									<input type="checkbox" name="_pronamic_pay_gf_delay_user_notification" id="gf_ideal_delay_user_notification" value="true" <?php checked( $delay_user_notification ); ?> />

									<label for="gf_ideal_delay_user_notification">
										<?php esc_html_e( 'User notification', 'pronamic_ideal' ); ?>
									</label>
								</li>
								<li id="gf_ideal_delay_post_creation_item">

								</li>
							</ul>

						<?php endif; ?>

					</td>
				</tr>
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
							function( $action ) {
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

				$fields = array(
					Links::SUCCESS => __( 'Success', 'pronamic_ideal' ),
					Links::CANCEL  => __( 'Cancelled', 'pronamic_ideal' ),
					Links::EXPIRED => __( 'Expired', 'pronamic_ideal' ),
					Links::ERROR   => __( 'Error', 'pronamic_ideal' ),
					Links::OPEN    => __( 'Open', 'pronamic_ideal' ),
				);

				foreach ( $fields as $name => $label ) :

					?>

					<tr>
						<?php

						$type    = null;
						$page_id = null;
						$url     = null;

						if ( is_array( $links ) && isset( $links[ $name ] ) ) {
							$link = $links[ $name ];

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

								<label>
									<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_confirmation" value="confirmation" <?php checked( $type, 'confirmation' ); ?> />
									<?php esc_html_e( 'Confirmation:', 'pronamic_ideal' ); ?>
								</label>

								<?php

								printf(
									'<select id="gf_ideal_link_%s_confirmation_id" name="_pronamic_pay_gf_links[%1$s][confirmation_id]" class="gf_ideal_confirmation_select" data-pronamic-link-name="%1$s"></select>',
									esc_attr( $name )
								);

								?>

								<br />

								<label>
									<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_page" value="page" <?php checked( $type, 'page' ); ?> />
									<?php esc_html_e( 'Page:', 'pronamic_ideal' ); ?>
								</label>

								<?php

								wp_dropdown_pages(
									array(
										'selected'         => esc_attr( $page_id ),
										'name'             => esc_attr( '_pronamic_pay_gf_links[' . $name . '][page_id]' ),
										'show_option_none' => esc_html__( '— Select —', 'pronamic_ideal' ),
									)
								);

								?>

								<br />

								<label>
									<input type="radio" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][type]" id="gf_ideal_link_<?php echo esc_attr( $name ); ?>_url" value="url" <?php checked( $type, 'url' ); ?> />
									<?php esc_html_e( 'URL:', 'pronamic_ideal' ); ?>
								</label> <input type="text" name="_pronamic_pay_gf_links[<?php echo esc_attr( $name ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" class="regular-text" />
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

							<label>
								<input id="pronamic_pay_gf_subscription_amount_type_none" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="" <?php checked( $subscription_amount_type, '' ); ?> />
								<?php esc_html_e( 'None', 'pronamic_ideal' ); ?>
							</label>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_amount_type_total" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="total" <?php checked( $subscription_amount_type, 'total' ); ?> />
								<?php esc_html_e( 'Form total', 'pronamic_ideal' ); ?>
							</label>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_amount_type_field" name="_pronamic_pay_gf_subscription_amount_type" type="radio" value="field" <?php checked( $subscription_amount_type, 'field' ); ?> />
								<?php esc_html_e( 'Form field', 'pronamic_ideal' ); ?>
							</label>

							<div class="pronamic-pay-gf-subscription-amount-settings amount-field">
								<select id="pronamic_pay_gf_subscription_amount_field" name="_pronamic_pay_gf_subscription_amount_field"></select>
							</div>

							<br />
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

							<label>
								<input id="pronamic_pay_gf_subscription_interval_type_fixed" name="_pronamic_pay_gf_subscription_interval_type" type="radio" value="fixed" <?php checked( $subscription_interval_type, 'fixed' ); ?> />
								<?php esc_html_e( 'Fixed', 'pronamic_ideal' ); ?>
							</label>

							<div class="pronamic-pay-gf-subscription-interval-settings interval-fixed">
								<?php echo esc_html( _x( 'Every', 'Recurring payment', 'pronamic_ideal' ) ); ?>

								<input id="pronamic_pay_gf_subscription_interval" name="_pronamic_pay_gf_subscription_interval" type="text" size="4" value="<?php echo esc_attr( $subscription_interval ); ?>" />

								<select id="pronamic_pay_gf_subscription_interval_period" name="_pronamic_pay_gf_subscription_interval_period">
									<option value="D" <?php selected( $subscription_interval_period, 'D' ); ?>><?php esc_html_e( 'day(s)', 'pronamic_ideal' ); ?></option>
									<option value="W" <?php selected( $subscription_interval_period, 'W' ); ?>><?php esc_html_e( 'week(s)', 'pronamic_ideal' ); ?></option>
									<option value="M" <?php selected( $subscription_interval_period, 'M' ); ?>><?php esc_html_e( 'month(s)', 'pronamic_ideal' ); ?></option>
									<option value="Y" <?php selected( $subscription_interval_period, 'Y' ); ?>><?php esc_html_e( 'year(s)', 'pronamic_ideal' ); ?></option>
								</select>
							</div>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_interval_type_field" name="_pronamic_pay_gf_subscription_interval_type" type="radio" value="field" <?php checked( $subscription_interval_type, 'field' ); ?> />
								<?php esc_html_e( 'Form field', 'pronamic_ideal' ); ?>
							</label>

							<div class="pronamic-pay-gf-subscription-interval-settings interval-field">
								<select id="pronamic_pay_gf_subscription_interval_field" name="_pronamic_pay_gf_subscription_interval_field"></select>

								<?php esc_html_e( 'days', 'pronamic_ideal' ); ?>

								<br />

								<span class="description pronamic-pay-description">
									<?php

									esc_html_e( 'Use a field value of 0 days for one-time payments.', 'pronamic_ideal' );

									?>
								</span>
							</div>

							<br />
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Frequency', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Frequency', 'pronamic_ideal' ); ?></span>
							</legend>

							<label>
								<input id="pronamic_pay_gf_subscription_frequency_type_unlimited" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="unlimited" <?php checked( $subscription_frequency_type, 'unlimited' ); ?> /> <?php echo esc_html_x( 'Unlimited', 'Recurring payment', 'pronamic_ideal' ); ?>
							</label>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_frequency_type_fixed" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="fixed" <?php checked( $subscription_frequency_type, 'fixed' ); ?> /> <?php esc_html_e( 'Fixed', 'pronamic_ideal' ); ?>
							</label>

							<div class="pronamic-pay-gf-subscription-frequency-settings frequency-fixed">
								<input id="pronamic_pay_gf_subscription_frequency" name="_pronamic_pay_gf_subscription_frequency" type="text" size="4" value="<?php echo esc_attr( $subscription_frequency ); ?>" />

								<?php echo esc_html( _x( 'times', 'Recurring payment', 'pronamic_ideal' ) ); ?>
							</div>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_frequency_type_field" name="_pronamic_pay_gf_subscription_frequency_type" type="radio" value="field" <?php checked( $subscription_frequency_type, 'field' ); ?> /> <?php esc_html_e( 'Form field', 'pronamic_ideal' ); ?>
							</label>

							<div class="pronamic-pay-gf-subscription-frequency-settings frequency-field">
								<select id="pronamic_pay_gf_subscription_frequency_field" name="_pronamic_pay_gf_subscription_frequency_field"></select>

								<?php echo esc_html( _x( 'times', 'Recurring payment', 'pronamic_ideal' ) ); ?>
							</div>

							<br />
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Synchronized payment date', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Synchronized payment date', 'pronamic_ideal' ); ?></span>
							</legend>

							<p>
								<?php esc_html_e( 'Synchronized payment date allows recurring payments on a pre-defined day or date, instead of on expiration of the first period.', 'pronamic_ideal' ); ?>
							</p>

							<br />

							<?php

							if ( empty( $subscription_interval_date_type ) ) :

								$subscription_interval_date_type = 'payment_date';

							endif;

							?>

							<label>
								<input id="pronamic_pay_gf_subscription_interval_date_type_payment_date" name="_pronamic_pay_gf_subscription_interval_date_type" type="radio" value="payment_date" <?php checked( $subscription_interval_date_type, 'payment_date' ); ?> />
								<?php esc_html_e( 'Follow interval starting from first payment date', 'pronamic_ideal' ); ?>
							</label>

							<br />

							<label>
								<input id="pronamic_pay_gf_subscription_interval_date_type_field" name="_pronamic_pay_gf_subscription_interval_date_type" type="radio" value="sync" <?php checked( $subscription_interval_date_type, 'sync' ); ?> />

								<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-D">
									<?php esc_html_e( 'Synchronized payment date', 'pronamic_ideal' ); ?>
								</span>

								<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-W interval-Y">
									<?php echo esc_html( _x( 'On', 'Recurring payment', 'pronamic_ideal' ) ); ?>
								</span>

								<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-M">
									<?php echo esc_html( _x( 'On the', 'Recurring payment', 'pronamic_ideal' ) ); ?>
								</span>

								<select class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-W" id="pronamic_pay_gf_subscription_interval_date_day" name="_pronamic_pay_gf_subscription_interval_date_day">
									<?php for ( $day = 0; $day <= 6; $day++ ) : ?>

										<?php

										$day_date = DateTime::create_from_format( 'U', strtotime( 'next Monday + ' . $day . ' days' ) );

										$day_value = ( $day + 1 );

										?>

										<option value="<?php echo esc_html( $day_value ); ?>" <?php selected( $subscription_interval_date_day, $day_value ); ?>><?php echo esc_html( $day_date->format_i18n( 'l' ) ); ?></option>

									<?php endfor; ?>
								</select>

								<select class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-M interval-Y" id="pronamic_pay_gf_subscription_interval_date" name="_pronamic_pay_gf_subscription_interval_date">
									<?php for ( $date = 1; $date <= 27; $date++ ) : ?>

										<?php

										$interval_date = DateTime::create_from_format( 'j', $date );

										?>

										<option value="<?php echo esc_html( $date ); ?>" <?php selected( $subscription_interval_date, $date ); ?>><?php echo esc_html( $interval_date->format_i18n( 'j' ) ); ?></option>

									<?php endfor; ?>

									<option value="last" <?php selected( $subscription_interval_date, 'last' ); ?>><?php esc_html_e( 'last', 'pronamic_ideal' ); ?></option>
								</select>

								<span class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-M">
									<?php echo wp_kses( __( '<sup>th</sup> day of the month', 'pronamic_ideal' ), array( 'sup' => array() ) ); ?>
								</span>

								<select class="pronamic-pay-gf-subscription-interval-date-sync-settings interval-Y" id="pronamic_pay_gf_subscription_interval_date_month" name="_pronamic_pay_gf_subscription_interval_date_month">
									<?php for ( $month = 1; $month <= 12; $month++ ) : ?>

										<?php $month_date = DateTime::create_from_format( '!m', $month ); ?>

										<option value="<?php echo esc_html( $month ); ?>" <?php selected( $subscription_interval_date_month, $month ); ?>><?php echo esc_html( $month_date->format_i18n( 'F' ) ); ?></option>

									<?php endfor; ?>
								</select>
							</label>

							<div class="pronamic-pay-gf-subscription-interval-date-settings interval-date-sync">
								<input type="checkbox" name="_pronamic_pay_gf_subscription_interval_date_prorate" id="pronamic_pay_gf_subscription_interval_date_prorate" value="true" <?php checked( $subscription_interval_date_prorate ); ?> />

								<label for="pronamic_pay_gf_subscription_interval_date_prorate">
									<?php esc_html_e( 'Prorate first payment amount', 'pronamic_ideal' ); ?>
								</label>
							</div>

							<br />
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

			$fields = array(
				'prefix_name'      => __( 'Prefix Name', 'pronamic_ideal' ),
				'first_name'       => __( 'First Name', 'pronamic_ideal' ),
				'middle_name'      => __( 'Middle Name', 'pronamic_ideal' ),
				'last_name'        => __( 'Last Name', 'pronamic_ideal' ),
				'suffix_name'      => __( 'Suffix Name', 'pronamic_ideal' ),
				'address1'         => __( 'Address', 'pronamic_ideal' ),
				'address2'         => __( 'Address 2', 'pronamic_ideal' ),
				'zip'              => __( 'Zip', 'pronamic_ideal' ),
				'city'             => __( 'City', 'pronamic_ideal' ),
				'state'            => __( 'State', 'pronamic_ideal' ),
				'country'          => __( 'Country', 'pronamic_ideal' ),
				'telephone_number' => __( 'Telephone Nnumber', 'pronamic_ideal' ),
				'email'            => __( 'Email', 'pronamic_ideal' ),
			);

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

							printf(
								'<select id="%s" name="%s" data-gateway-field-name="%s" class="field-select"><select>',
								esc_attr( 'gf_ideal_fields_' . $name ),
								esc_attr( '_pronamic_pay_gf_fields[' . $name . ']' ),
								esc_attr( $name )
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
							<?php esc_html_e( 'Condition', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<div id="gf_ideal_condition_config">
							<?php

							// Select field.
							$select_field = '<select id="gf_ideal_condition_field_id" name="_pronamic_pay_gf_condition_field_id"></select>';

							// Select operator.
							$select_operator = '<select id="gf_ideal_condition_operator" name="_pronamic_pay_gf_condition_operator">';

							$operators = array();

							if ( false === $condition_operator ) {
								$operators[] = '';
							}

							$operators[ GravityForms::OPERATOR_IS ]     = __( 'is', 'pronamic_ideal' );
							$operators[ GravityForms::OPERATOR_IS_NOT ] = __( 'is not', 'pronamic_ideal' );

							foreach ( $operators as $value => $label ) {
								$select_operator .= sprintf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $value ),
									selected( $condition_operator, $value, false ),
									esc_html( $label )
								);
							}

							$select_operator .= '</select>';

							// Select value.
							$select_value = '<select id="gf_ideal_condition_value" name="_pronamic_pay_gf_condition_value"></select>';

							// Print select.
							// @codingStandardsIgnoreStart
							printf(
								'%s %s %s',
								$select_field,
								$select_operator,
								$select_value
							);
							// @codingStandardsIgnoreEnd

							?>
						</div>

						<input id="gf_ideal_condition_enabled" name="_pronamic_pay_gf_condition_enabled" type="hidden" value="<?php echo esc_attr( $condition_enabled ); ?>" />

						<span class="description pronamic-pay-description">
							<?php esc_html_e( 'Set a condition to only use the gateway if the entry matches the condition.', 'pronamic_ideal' ); ?>

							<span id="gf_ideal_condition_message" class="description pronamic-pay-description"><?php esc_html_e( 'To create a condition, your form must contain a drop down, checkbox or multiple choice field.', 'pronamic_ideal' ); ?></span>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gf_ideal_user_role_field_id">
							<?php esc_html_e( 'Update user role', 'pronamic_ideal' ); ?>
						</label>
					</th>
					<td>
						<select id="gf_ideal_user_role_field_id" name="_pronamic_pay_gf_user_role_field_id"></select>
					</td>
				</tr>
			</table>
		</div>

		<?php

		if ( version_compare( GFCommon::$version, '1.7', '<' ) ) :

			$js_form = GFFormsModel::get_form_meta( $form_id );

			if ( $form_id && null !== $js_form ) :

				if ( ! isset( $js_form['fields'] ) || ! is_array( $js_form['fields'] ) ) {
					$js_form['fields'] = array();
				}

				$_GET['id'] = $form_id;

				printf(
					'<script type="text/javascript">
					var form = %s;
					%s
					</script>',
					wp_json_encode( $js_form ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					GFCommon::gf_vars( false )
				);

			endif;

		endif;

		?>

	</div>
</div>
