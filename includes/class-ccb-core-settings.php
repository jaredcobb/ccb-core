<?php
/**
 * Everything related to the plugin settings
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Object to manage the plugin settings
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings {

	/**
	 * Validate the settings fields based on the settings config
	 *
	 * @access   public
	 * @since    0.9.0
	 * @param    array $input An array of fields to sanitize.
	 * @return   array $current_options
	 */
	public function validate_settings( $input ) {

		$current_options = CCB_Core_Helpers::instance()->get_options();
		$validation_hash = $this->generate_validation_hash();

		foreach ( $validation_hash as $field_id => $validation ) {

			if ( isset( $validation['field_validation'] ) ) {

				switch ( $validation['field_validation'] ) {

					case 'alphanumeric':

						if ( empty( $input[ $field_id ] ) || ctype_alnum( $input[ $field_id ] ) ) {
							$current_options[ $field_id ] = $input[ $field_id ];
						} else {
							add_settings_error(
								$field_id,
								$field_id,
								sprintf(
									esc_html__(
										'Oops! %s can only contain letters and numbers',
										'ccb-core'
									),
									esc_html( $validation['field_title'] )
								)
							);
						}
						break;

					case 'numeric':

						if ( empty( $input[ $field_id ] ) || ctype_digit( $input[ $field_id ] ) ) {
							$current_options[ $field_id ] = $input[ $field_id ];
						} else {
							add_settings_error(
								$field_id,
								$field_id,
								sprintf(
									esc_html__(
										'Oops! %s can only contain numbers',
										'ccb-core'
									),
									esc_html( $validation['field_title'] )
								)
							);
						}
						break;

					case 'slug':

							// Continue onto alphanumeric_extended validation.
							$input[ $field_id ] = sanitize_key( $input[ $field_id ] );

					case 'alphanumeric_extended':

						if ( empty( $input[ $field_id ] ) || ! preg_match( '/[^\w\s-_]/', $input[ $field_id ] ) ) {
							$current_options[ $field_id ] = $input[ $field_id ];
						} else {
							add_settings_error(
								$field_id,
								$field_id,
								sprintf(
									esc_html__(
										'Oops! %s can only contain letters, numbers, spaces, dashes, or underscores.',
										'ccb-core'
									),
									esc_html( $validation['field_title'] )
								)
							);
						}
						break;

					case 'encrypt':

						if ( ! empty( $input[ $field_id ]['password'] ) ) {
							$encrypted_password = CCB_Core_Helpers::instance()->encrypt( $input[ $field_id ]['password'] );
							if ( $encrypted_password ) {
								$current_options[ $field_id ]['password'] = $encrypted_password;
							} else {
								add_settings_error(
									$field_id,
									$field_id,
									'Oops! We couldn\'t encrypt your password.'
								);
							}
						}
						$current_options[ $field_id ]['username'] = $input[ $field_id ]['username'];

						break;

					case 'switch':

						$current_options[ $field_id ] = ( isset( $input[ $field_id ] ) && '1' === $input[ $field_id ] ? '1' : '' );
						break;

					default:
						$current_options[ $field_id ] = $input[ $field_id ];
						break;

				}
			}

		}

		return $current_options;
	}

	/**
	 * The whopper config used to create all the settings
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    array
	 */
	public function get_settings_definitions() {
		return array(
			'ccb_core_settings' => array(
				'page_title' => esc_html__( 'About', 'ccb-core' ),
				'sections' => array(
					'about' => array(
						'section_title' => esc_html__( 'About', 'ccb-core' ),
						// No fields needed for the about page.
					),
				),
			),
			'ccb_core_settings_api_settings' => array(
				'page_title' => esc_html__( 'API Settings', 'ccb-core' ),
				'sections' => array(
					'api_settings' => array(
						'section_title' => esc_html__( 'API Settings', 'ccb-core' ),
						'fields' => array(
							'subdomain' => array(
								'field_title' => esc_html__( 'Software Subdomain', 'ccb-core' ),
								'field_render_function' => 'render_text',
								'field_placeholder' => 'subdomain',
								'field_validation' => 'alphanumeric',
								'field_tooltip' => 'We just need the first part of your software URL (<em>without</em> "http://" and <em>without</em> ".ccbchurch.com").',
							),
							'credentials' => array(
								'field_title' => esc_html__( 'API Credentials', 'ccb-core' ),
								'field_render_function' => 'render_credentials',
								'field_validation' => 'encrypt',
								'field_tooltip' => 'This is the username and password for the API user in your Church Community Builder software.',
							),
							'test_credentials' => array(
								'field_title' => esc_html__( 'Test Credentials', 'ccb-core' ),
								'field_render_function' => 'render_test_credentials',
							),
						),
					),
				),
			),
			'ccb_core_settings_groups' => array(
				'page_title' => esc_html__( 'Groups', 'ccb-core' ),
				'sections' => array(
					'groups' => array(
						'section_title' => esc_html__( 'Groups', 'ccb-core' ),
						'fields' => array(
							'groups-enabled' => array(
								'field_title' => esc_html__( 'Enable Groups', 'ccb-core' ),
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'groups-name' => array(
								'field_title' => esc_html__( 'Groups Display Name', 'ccb-core' ),
								'field_render_function' => 'render_text',
								'field_placeholder' => esc_html__( 'Groups', 'ccb-core' ),
								'field_validation' => 'alphanumeric_extended',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => 'This is what you call the groups in your church (i.e. <em>Home Groups, Connections, Life Groups, etc.</em>).',
							),
							'groups-slug' => array(
								'field_title' => esc_html__( 'Groups URL Name', 'ccb-core' ),
								'field_render_function' => 'render_text',
								'field_placeholder' => 'groups',
								'field_validation' => 'slug',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => 'This is typically where your theme will display <em>all</em> the groups. WordPress calls this a "slug".',
							),
							'groups-import-images' => array(
								'field_title' => esc_html__( 'Also Import Group Images?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => 'This will download the CCB Group Image and attach it as a Featured Image.<br>If you don\'t need group images, then disabling this feature will speed up the synchronization.',
							),
							'groups-advanced' => array(
								'field_title' => esc_html__( 'Enable Advanced Settings (Optional)', 'ccb-core' ),
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
							),
							'groups-exclude-from-search' => array(
								'field_title' => esc_html__( 'Exclude From Search?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-publicly-queryable' => array(
								'field_title' => esc_html__( 'Publicly Queryable?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-show-ui' => array(
								'field_title' => esc_html__( 'Show In Admin UI?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-show-in-nav-menus' => array(
								'field_title' => esc_html__( 'Show In Navigation Menus?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
						),
					),
				),
			),
			'ccb_core_settings_calendar' => array(
				'page_title' => esc_html__( 'Public Events', 'ccb-core' ),
				'sections' => array(
					'calendar' => array(
						'section_title' => esc_html__( 'Public Events', 'ccb-core' ),
						'fields' => array(
							'calendar-enabled' => array(
								'field_title' => esc_html__( 'Enable Events', 'ccb-core' ),
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'calendar-name' => array(
								'field_title' => esc_html__( 'Event Display Name', 'ccb-core' ),
								'field_render_function' => 'render_text',
								'field_placeholder' => esc_html__( 'Events', 'ccb-core' ),
								'field_validation' => 'alphanumeric_extended',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
								'field_tooltip' => 'This is what you call the events in your church (i.e. <em>Meetups, Hangouts, etc.</em>).',
							),
							'calendar-slug' => array(
								'field_title' => esc_html__( 'Events URL Name', 'ccb-core' ),
								'field_render_function' => 'render_text',
								'field_placeholder' => 'events',
								'field_validation' => 'slug',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
								'field_tooltip' => 'This is typically where your theme will display <em>all</em> the events. WordPress calls this a "slug".',
							),
							'calendar-advanced' => array(
								'field_title' => esc_html__( 'Enable Advanced Settings (Optional)', 'ccb-core' ),
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
							),
							'calendar-date-range-type' => array(
								'field_title' => esc_html__( 'Date Range Type', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'relative' => esc_html__( 'Relative Range', 'ccb-core' ),
									'specific' => esc_html__( 'Specific Range', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'relative',
								'field_attributes' => array( 'class' => 'date-range-type', 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
								'field_tooltip' => '<strong>Relative:</strong> For example, always get the events from <em>\'One week ago\'</em>, up to <em>\'Eight weeks from now\'</em>.<br>This is the best setting for most churches.<br><br><strong>Specific:</strong> For example, only get events from <em>\'6/1/2015\'</em> to <em>\'12/1/2015\'</em>.<br>This setting is best if you want to tightly manage the events that get published.',
							),
							'calendar-relative-weeks-past' => array(
								'field_title' => esc_html__( 'How Far Back?', 'ccb-core' ),
								'field_render_function' => 'render_slider',
								'field_options' => array(
									'min' => '0',
									'max' => '26',
									'units' => 'weeks',
								),
								'field_default' => 1,
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"relative"}' ),
								'field_tooltip' => 'Every time we synchronize, how many <strong>weeks</strong> in the past should we look?<em>(0 would be "today")</em>',
							),
							'calendar-relative-weeks-future' => array(
								'field_title' => esc_html__( 'How Into The Future?', 'ccb-core' ),
								'field_render_function' => 'render_slider',
								'field_options' => array(
									'min' => '1',
									'max' => '52',
									'units' => 'weeks',
								),
								'field_default' => 16,
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"relative"}' ),
								'field_tooltip' => 'Every time we synchronize, how many <strong>weeks</strong> in the future should we look?',
							),
							'calendar-specific-start' => array(
								'field_title' => esc_html__( 'Specific Start Date', 'ccb-core' ),
								'field_render_function' => 'render_date_picker',
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"specific"}' ),
								'field_tooltip' => 'When synchronizing, we should get events that start <strong>after</strong> this date.<br><em>(Leave empty to always start "today")</em>',
							),
							'calendar-specific-end' => array(
								'field_title' => esc_html__( 'Specific End Date', 'ccb-core' ),
								'field_render_function' => 'render_date_picker',
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"specific"}' ),
								'field_tooltip' => 'When synchronizing, we should get events that start <strong>before</strong> this date.<br><em>(Setting this too far into the future may cause the API to timeout)</em>',
							),
							'calendar-exclude-from-search' => array(
								'field_title' => esc_html__( 'Exclude From Search?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-publicly-queryable' => array(
								'field_title' => esc_html__( 'Publicly Queryable?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-show-ui' => array(
								'field_title' => esc_html__( 'Show In Admin UI?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-show-in-nav-menus' => array(
								'field_title' => esc_html__( 'Show In Navigation Menus?', 'ccb-core' ),
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => esc_html__( 'Yes', 'ccb-core' ),
									'no' => esc_html__( 'No', 'ccb-core' ),
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
						),
					),
				),
			),
			'ccb_core_settings_sync' => array(
				'page_title' => esc_html__( 'Synchronize', 'ccb-core' ),
				'sections' => array(
					'synchronize' => array(
						'section_title' => esc_html__( 'Synchronize', 'ccb-core' ),
						'fields' => array(
							'auto-sync' => array(
								'field_title' => esc_html__( 'Enable Auto Sync', 'ccb-core' ),
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'auto-sync-timeout' => array(
								'field_title' => esc_html__( 'Cache Expiration', 'ccb-core' ),
								'field_render_function' => 'render_slider',
								'field_options' => array(
									'min' => '10',
									'max' => '180',
									'units' => 'minutes',
								),
								'field_default' => 90,
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"auto-sync":1}' ),
								'field_tooltip' => 'We keep a local copy (cache) of your Church Community Builder data for the best performance.<br>How often (in minutes) should we check for new data?<br><em>90 minutes is recommended.</em>',
							),
							'manual-sync' => array(
								'field_title' => esc_html__( 'Manual Sync', 'ccb-core' ),
								'field_render_function' => 'render_manual_sync',
							),
							'latest-results' => array(
								'field_title' => esc_html__( 'Latest Sync Results', 'ccb-core' ),
								'field_render_function' => 'render_latest_results',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Helper function to create a name/value hash for quick validation
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    array    $mapping
	 */
	protected function generate_validation_hash() {
		$mapping = array();
		$page_id = isset( $_POST['option_page'] ) ? sanitize_text_field( wp_unslash( $_POST['option_page'] ) ) : false; // Input var okay.
		$settings_definitions = $this->get_settings_definitions();

		foreach ( $settings_definitions[ $page_id ]['sections'] as $section ) {
			if ( ! empty( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field_id => $field ) {
					if ( isset( $field['field_validation'] ) ) {
						$mapping[ $field_id ] = array(
							'field_title' => $field['field_title'],
							'field_validation' => $field['field_validation'],
						);
					} else {
						$mapping[ $field_id ] = false;
					}
				}
			}
		}
		return $mapping;
	}

}
