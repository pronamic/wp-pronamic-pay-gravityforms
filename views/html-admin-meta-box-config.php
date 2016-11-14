<?php

$post_id = get_the_ID();

$form_id = get_post_meta( $post_id, '_pronamic_pay_gf_form_id', true );

$form_meta = RGFormsModel::get_form_meta( $form_id );

$condition_enabled      = get_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
$condition_field_id     = get_post_meta( $post_id, '_pronamic_pay_gf_condition_field_id', true );
$condition_operator     = get_post_meta( $post_id, '_pronamic_pay_gf_condition_operator', true );
$condition_value        = get_post_meta( $post_id, '_pronamic_pay_gf_condition_value', true );
$delay_notification_ids = get_post_meta( $post_id, '_pronamic_pay_gf_delay_notification_ids', true );
$links                  = get_post_meta( $post_id, '_pronamic_pay_gf_links', true );

$feed = new stdClass();
$feed->conditionEnabled       = $condition_enabled;
$feed->conditionFieldId       = $condition_field_id;
$feed->conditionOperator      = $condition_operator;
$feed->conditionValue         = $condition_value;
$feed->delayNotificationIds   = $delay_notification_ids;
$feed->fields                 = get_post_meta( $post_id, '_pronamic_pay_gf_fields', true );
$feed->userRoleFieldId        = get_post_meta( $post_id, '_pronamic_pay_gf_user_role_field_id', true );
$feed->links                  = $links;

?>

<div id="gf-ideal-feed-editor">
	<?php wp_nonce_field( 'pronamic_pay_save_pay_gf', 'pronamic_pay_nonce' ); ?>

	<input id="gf_ideal_gravity_form" name="gf_ideal_gravity_form" value="<?php echo esc_attr( json_encode( $form_meta ) ); ?>" type="hidden" />
	<input id="gf_ideal_feed" name="gf_ideal_feed" value="<?php echo esc_attr( json_encode( $feed ) ); ?>" type="hidden" />

	<div class="pronamic-pay-tabs">

		<ul class="pronamic-pay-tabs-items">
			<li><?php esc_html_e( 'General', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Status pages', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Fields', 'pronamic_ideal' ); ?></li>
			<li><?php esc_html_e( 'Advanced', 'pronamic_ideal' ); ?></li>
		</ul>

		<div class="pronamic-pay-tab">
			<div class="pronamic-pay-tab-block">

			</div>

			<table class="pronamic-pay-table-striped form-table">
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_form_id">
							<?php esc_html_e( 'Form', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'The Gravity Forms form to process payments for.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<select id="_pronamic_pay_gf_form_id" name="_pronamic_pay_gf_form_id">
							<option value=""><?php esc_html_e( '— Select a form —', 'pronamic_ideal' ); ?></option>

							<?php foreach ( RGFormsModel::get_forms() as $form ) : ?>

								<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id, $form->id ); ?>>
									<?php echo esc_html( $form->title ); ?>
								</option>

								<?php

								if ( $form_id && $form_id === $form->id ) {
									$js_form = GFFormsModel::get_form_meta( $form_id );

									if ( ! isset( $js_form['fields'] ) || ! is_array( $js_form['fields'] ) ) {
										$js_form['fields'] = array();
									}

									$js_form = gf_apply_filters( array( 'gform_admin_pre_render', $form_id ), $js_form );
								}

								?>

							<?php endforeach; ?>
						</select>

						<?php

						if ( isset( $js_form ) ) {
							$_GET['id'] = $form_id;

							printf( //xss ok
								'<script type="text/javascript">
									var form = %s;
									%s
								</script>',
								json_encode( $js_form ),
								GFCommon::gf_vars( false )
							);
						}

						?>

					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_config_id">
							<?php esc_html_e( 'Configuration', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'Gateway configuration, created via <strong>iDEAL » Configurations</strong>.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<?php

						$config_id = get_post_meta( $post_id, '_pronamic_pay_gf_config_id', true );

						if ( '' === $config_id ) {
							$config_id = get_option( 'pronamic_pay_config_id' );
						}

						Pronamic_WP_Pay_Admin::dropdown_configs( array(
							'name'     => '_pronamic_pay_gf_config_id',
							'selected' => $config_id,
						) );

						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_order_id">
							<?php esc_html_e( 'Entry ID Prefix', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'Prefix for the entry ID.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<?php

						$entry_id_prefix = get_post_meta( $post_id, '_pronamic_pay_gf_entry_id_prefix', true );

						?>
						<input id="_pronamic_pay_gf_entry_id_prefix" name="_pronamic_pay_gf_entry_id_prefix" value="<?php echo esc_attr( $entry_id_prefix ); ?>" type="text" class="input-text regular-input" maxlength="8" />

						<p class="pronamic-pay-description description">
							<?php esc_html_e( 'A prefix makes it easier to match payments from transaction overviews to a form and is required by some payment providers.', 'pronamic_ideal' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_pronamic_pay_gf_transaction_description">
							<?php esc_html_e( 'Transaction Description', 'pronamic_ideal' ); ?>
						</label>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'Transaction description that will be send with the payment. Use tags to customize the description.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<?php

						$transaction_description = get_post_meta( $post_id, '_pronamic_pay_gf_transaction_description', true );

						?>
						<input id="_pronamic_pay_gf_transaction_description" name="_pronamic_pay_gf_transaction_description" value="<?php echo esc_attr( $transaction_description ); ?>" type="text" class="regular-text merge-tag-support mt-position-right mt-hide_all_fields" />

						<p class="pronamic-pay-description description">
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
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Send Notifications Delay', 'pronamic_ideal' ); ?>

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'Notifications for which sending will be delayed until the payment has been received.', 'pronamic_ideal' ); ?>"></span>
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
						$delay_user_notification = get_post_meta( $post_id, '_pronamic_pay_gf_delay_user_notification', true );

						if ( version_compare( GFCommon::$version, '1.7', '>=' ) ) :

							$notifications = array();
							if ( isset( $form_meta['notifications'] ) && is_array( $form_meta['notifications'] ) ) {
								$notifications = $form_meta['notifications'];
							}

							printf( '<ul id="gf_ideal_delay_notifications">' );

							if ( ! empty( $notifications ) ) {
								foreach ( $notifications as $notification ) {
									$id = $notification['id'];

									printf( '<li>' );

									printf(
										'<input id="%s" type="checkbox" value="%s" name="_pronamic_pay_gf_delay_notification_ids[]" %s />',
										esc_attr( 'pronamic-pay-gf-notification-' . $id ),
										esc_attr( $id ),
										checked( in_array( $id, $delay_notification_ids ), true, false )
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

						<span class="dashicons dashicons-editor-help pronamic-pay-tip" data-tip="<?php esc_attr_e( 'Actions for which execution will be delayed until the payment has been received.', 'pronamic_ideal' ); ?>"></span>
					</th>
					<td>
						<p>
							<?php esc_html_e( 'Delay actions until payment has been received.', 'pronamic_ideal' ); ?>
						</p>

						<?php

						$delay_post_creation                = get_post_meta( $post_id, '_pronamic_pay_gf_delay_post_creation', true );
						$delay_aweber_subscription          = get_post_meta( $post_id, '_pronamic_pay_gf_delay_aweber_subscription', true );
						$delay_campaignmonitor_subscription = get_post_meta( $post_id, '_pronamic_pay_gf_delay_campaignmonitor_subscription', true );
						$delay_mailchimp_subscription       = get_post_meta( $post_id, '_pronamic_pay_gf_delay_mailchimp_subscription', true );
						$delay_zapier                       = get_post_meta( $post_id, '_pronamic_pay_gf_delay_zapier', true );
						$delay_user_registration            = get_post_meta( $post_id, '_pronamic_pay_gf_delay_user_registration', true );

						?>

						<ul>
							<li>
								<input type="checkbox" name="_pronamic_pay_gf_delay_post_creation" id="_pronamic_pay_gf_delay_post_creation" value="true" <?php checked( $delay_post_creation ); ?> />

								<label for="_pronamic_pay_gf_delay_post_creation">
									<?php esc_html_e( 'Creating a post', 'pronamic_ideal' ); ?>
								</label>
							</li>

							<?php if ( class_exists( 'GFUser' ) ) : ?>

								<li>
									<input type="checkbox" name="_pronamic_pay_gf_delay_user_registration" id="_pronamic_pay_gf_delay_user_registration" value="true" <?php checked( $delay_user_registration ); ?> />

									<label for="_pronamic_pay_gf_delay_user_registration">
										<?php esc_html_e( 'Registering the user', 'pronamic_ideal' ); ?>
									</label>
								</li>

							<?php endif; ?>

							<?php if ( class_exists( 'GFZapier' ) ) : ?>

								<li>
									<input type="checkbox" name="_pronamic_pay_gf_delay_zapier" id="_pronamic_pay_gf_delay_zapier" value="true" <?php checked( $delay_zapier ); ?> />

									<label for="_pronamic_pay_gf_delay_zapier">
										<?php esc_html_e( 'Sending data to Zapier', 'pronamic_ideal' ); ?>
									</label>
								</li>

							<?php endif; ?>

							<?php if ( class_exists( 'GFAWeber' ) ) : ?>

								<li>
									<input type="checkbox" name="_pronamic_pay_gf_delay_aweber_subscription" id="_pronamic_pay_gf_delay_aweber_subscription" value="true" <?php checked( $delay_aweber_subscription ); ?> />

									<label for="_pronamic_pay_gf_delay_aweber_subscription">
										<?php esc_html_e( 'Subscribing the user to AWeber', 'pronamic_ideal' ); ?>
									</label>
								</li>

							<?php endif; ?>

							<?php if ( class_exists( 'GFCampaignMonitor' ) ) : ?>

								<li>
									<input type="checkbox" name="_pronamic_pay_gf_delay_campaignmonitor_subscription" id="_pronamic_pay_gf_delay_campaignmonitor_subscription" value="true" <?php checked( $delay_campaignmonitor_subscription ); ?> />

									<label for="_pronamic_pay_gf_delay_campaignmonitor_subscription">
										<?php esc_html_e( 'Subscribing the user to Campaign Monitor', 'pronamic_ideal' ); ?>
									</label>
								</li>

							<?php endif; ?>

							<?php if ( class_exists( 'GFMailChimp' ) ) : ?>

								<li>
									<input type="checkbox" name="_pronamic_pay_gf_delay_mailchimp_subscription" id="_pronamic_pay_gf_delay_mailchimp_subscription" value="true" <?php checked( $delay_mailchimp_subscription ); ?> />

									<label for="_pronamic_pay_gf_delay_mailchimp_subscription">
										<?php esc_html_e( 'Subscribing the user to MailChimp', 'pronamic_ideal' ); ?>
									</label>
								</li>

							<?php endif; ?>
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
					Pronamic_WP_Pay_Extensions_GravityForms_Links::SUCCESS => __( 'Success', 'pronamic_ideal' ),
					Pronamic_WP_Pay_Extensions_GravityForms_Links::CANCEL  => __( 'Cancelled', 'pronamic_ideal' ),
					Pronamic_WP_Pay_Extensions_GravityForms_Links::EXPIRED => __( 'Expired', 'pronamic_ideal' ),
					Pronamic_WP_Pay_Extensions_GravityForms_Links::ERROR   => __( 'Error', 'pronamic_ideal' ),
					Pronamic_WP_Pay_Extensions_GravityForms_Links::OPEN    => __( 'Open', 'pronamic_ideal' ),
				);

				foreach ( $fields as $name => $label ) : ?>

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

								wp_dropdown_pages( array(
									'selected'         => esc_attr( $page_id ),
									'name'             => esc_attr( '_pronamic_pay_gf_links[' . $name . '][page_id]' ),
									'show_option_none' => esc_html__( '— Select —', 'pronamic_ideal' ),
								) );

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
				<?php esc_html_e( 'Set corresponding form fields to include user data in the payment data with some payment providers.', 'pronamic_ideal' ); ?>
			</div>

			<?php

			$fields = array(
				'first_name'       => __( 'First Name', 'pronamic_ideal' ),
				'last_name'        => __( 'Last Name', 'pronamic_ideal' ),
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

							// Select field
							$select_field = '<select id="gf_ideal_condition_field_id" name="_pronamic_pay_gf_condition_field_id"></select>';

							// Select operator
							$select_operator = '<select id="gf_ideal_condition_operator" name="_pronamic_pay_gf_condition_operator">';

							$operators = array();

							if ( false === $condition_operator ) {
								$operators[] = '';
							}

							$operators[ Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS ]     = __( 'is', 'pronamic_ideal' );
							$operators[ Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS_NOT ] = __( 'is not', 'pronamic_ideal' );

							foreach ( $operators as $value => $label ) {
								$select_operator .= sprintf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $value ),
									selected( $condition_operator, $value, false ),
									esc_html( $label )
								);
							}

							$select_operator .= '</select>';

							// Select value
							$select_value = '<select id="gf_ideal_condition_value" name="_pronamic_pay_gf_condition_value"></select>';

							// Print
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

						<p class="description">
							<?php esc_html_e( 'Set a condition to only use the gateway if the entry matches the condition.', 'pronamic_ideal' ); ?>

							<span id="gf_ideal_condition_message" class="description"><?php esc_html_e( 'To create a condition, your form must contain a drop down, checkbox or multiple choice field.', 'pronamic_ideal' ); ?></span>
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
						<select id="gf_ideal_user_role_field_id" name="_pronamic_pay_gf_user_role_field_id"></select>
					</td>
				</tr>
			</table>
		</div>
	</div>

</div>
