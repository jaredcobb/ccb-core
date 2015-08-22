<?php
/**
 * Everything related to the plugin settings
 *
 * @link       http://jaredcobb.com/ccb-core
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
class CCB_Core_Settings extends CCB_Core_Plugin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function __construct() {

		parent::__construct();

	}

	/**
	 * Validate the settings fields based on the settings config
	 *
	 * @access    public
	 * @since     0.9.0
	 * @param     array    $input
	 * @return    array    $current_settings
	 */
	public function validate_settings( $input ) {

		$current_settings = get_option( $this->plugin_settings_name );
		$validation_hash = $this->generate_validation_hash();

		if ( is_array( $validation_hash ) && ! empty( $validation_hash ) ) {

			foreach ( $validation_hash as $field_id => $validation ) {

				if ( isset( $validation['field_validation'] ) ) {
					switch ( $validation['field_validation'] ) {

						case 'alphanumeric':
							if ( empty( $input[ $field_id ] ) || ctype_alnum( $input[ $field_id ] ) ) {
								$current_settings[ $field_id ] = $input[ $field_id ];
							}
							else {
								add_settings_error( $field_id, $field_id, "Oops! {$validation['field_title']} can only contain letters and numbers." );
							}
							break;

						case 'numeric':
							if ( empty( $input[ $field_id ] ) || ctype_digit( $input[ $field_id ] ) ) {
								$current_settings[ $field_id ] = $input[ $field_id ];
							}
							else {
								add_settings_error( $field_id, $field_id, "Oops! {$validation['field_title']} can only contain numbers." );
							}
							break;

						case 'slug':
								$input[ $field_id ] = strtolower( str_replace( ' ', '_', $input[ $field_id ] ) );
								// continue onto alphanumeric_extended validation

						case 'alphanumeric_extended':
							if ( empty( $input[ $field_id ] ) || ! preg_match( '/[^\w\s-_]/', $input[ $field_id ] ) ) {
								$current_settings[ $field_id ] = $input[ $field_id ];
							}
							else {
								add_settings_error( $field_id, $field_id, "Oops! {$validation['field_title']} can only contain letters, numbers, spaces, dashes, or underscores." );
							}
							break;

						case 'encrypt':

							if ( ! empty( $input[ $field_id ]['password'] ) ) {
								$encrypted_password = $this->encrypt( $input[ $field_id ]['password'] );
								if ( $encrypted_password ) {
									$current_settings[ $field_id ]['password'] = $encrypted_password;
								}
								else {
									add_settings_error( $field_id, $field_id, "Oops! We couldn't encrypt your password." );
								}
							}
							$current_settings[ $field_id ]['username'] = $input[ $field_id ]['username'];

							break;

						case 'switch':

							$current_settings[ $field_id ] = ( isset( $input[ $field_id ] ) && $input[ $field_id ] == '1' ? '1' : '' );
							break;

						default:
							$current_settings[ $field_id ] = $input[ $field_id ];
							break;

					}
				}

			}

		}

		return $current_settings;
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
		$page_id = $_POST['option_page'];
		$settings_definitions = $this->get_settings_definitions();

		if ( is_array( $settings_definitions ) && isset( $settings_definitions[ $page_id ] ) ) {
			if ( isset( $settings_definitions[ $page_id ]['sections'] ) && ! empty( $settings_definitions[ $page_id ]['sections'] ) ) {
				foreach ( $settings_definitions[ $page_id ]['sections'] as $section ) {
					if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
						foreach ( $section['fields'] as $field_id => $field ) {
							if ( isset( $field['field_validation'] ) ) {
								$mapping[ $field_id ] = array(
									'field_title' => $field['field_title'],
									'field_validation' => $field['field_validation'],
								);
							}
							else {
								$mapping[ $field_id ] = false;
							}
						}
					}
				}
			}
		}
		return $mapping;
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
			$this->plugin_settings_name => array(
				'page_title' => 'About',
				'sections' => array(
					'about' => array(
						'section_title' => 'About',
						// no fields needed for the about page
					),
				),
			),
			$this->plugin_settings_name . '_api_settings' => array(
				'page_title' => 'API Settings',
				'sections' => array(
					'api_settings' => array(
						'section_title' => 'API Settings',
						'fields' => array(
							'subdomain' => array(
								'field_title' => 'Software Subdomain',
								'field_render_function' => 'render_text',
								'field_placeholder' => 'subdomain',
								'field_validation' => 'alphanumeric',
								'field_tooltip' => 'We just need the first part of your software URL (<em>without</em> "http://" and <em>without</em> ".ccbchurch.com").',
							),
							'credentials' => array(
								'field_title' => 'API Credentials',
								'field_render_function' => 'render_credentials',
								'field_validation' => 'encrypt',
								'field_tooltip' => 'This is the username and password for the API user in your Church Community Builder software.',
							),
							'test_credentials' => array(
								'field_title' => 'Test Credentials',
								'field_render_function' => 'render_test_credentials',
							),
						),
					),
				),
			),
			$this->plugin_settings_name . '_groups' => array(
				'page_title' => 'Groups',
				'sections' => array(
					'groups' => array(
						'section_title' => 'Groups',
						'fields' => array(
							'groups-enabled' => array(
								'field_title' => 'Enable Groups',
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'groups-name' => array(
								'field_title' => 'Groups Display Name',
								'field_render_function' => 'render_text',
								'field_placeholder' => 'Groups',
								'field_validation' => 'alphanumeric_extended',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => 'This is what you call the groups in your church (i.e. <em>Home Groups, Connections, Life Groups, etc.</em>).',
							),
							'groups-slug' => array(
								'field_title' => 'Groups URL Name',
								'field_render_function' => 'render_text',
								'field_placeholder' => 'groups',
								'field_validation' => 'slug',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => 'This is typically where your theme will display <em>all</em> the groups. WordPress calls this a "slug".',
							),
							'groups-import-images' => array(
								'field_title' => 'Also Import Group Images?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
								'field_tooltip' => "This will download the CCB Group Image and attach it as a Featured Image.<br>If you don't need group images, then disabling this feature will speed up the synchronization.",
							),
							'groups-advanced' => array(
								'field_title' => 'Enable Advanced Settings <em>(Optional)</em>',
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1}' ),
							),
							'groups-exclude-from-search' => array(
								'field_title' => 'Exclude From Search?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-publicly-queryable' => array(
								'field_title' => 'Publicly Queryable?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-show-ui' => array(
								'field_title' => 'Show In Admin UI?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
							'groups-show-in-nav-menus' => array(
								'field_title' => 'Show In Navigation Menus?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"groups-enabled":1,"groups-advanced":1}' ),
							),
						),
					),
				),
			),
			$this->plugin_settings_name . '_calendar' => array(
				'page_title' => 'Public Events',
				'sections' => array(
					'calendar' => array(
						'section_title' => 'Public Events',
						'fields' => array(
							'calendar-enabled' => array(
								'field_title' => 'Enable Events',
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'calendar-name' => array(
								'field_title' => 'Event Display Name',
								'field_render_function' => 'render_text',
								'field_placeholder' => 'Events',
								'field_validation' => 'alphanumeric_extended',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
								'field_tooltip' => 'This is what you call the events in your church (i.e. <em>Meetups, Hangouts, etc.</em>).',
							),
							'calendar-slug' => array(
								'field_title' => 'Events URL Name',
								'field_render_function' => 'render_text',
								'field_placeholder' => 'events',
								'field_validation' => 'slug',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
								'field_tooltip' => 'This is typically where your theme will display <em>all</em> the events. WordPress calls this a "slug".',
							),
							'calendar-advanced' => array(
								'field_title' => 'Enable Advanced Settings <em>(Optional)</em>',
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1}' ),
							),
							'calendar-date-range-type' => array(
								'field_title' => 'Date Range Type',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'relative' => 'Relative Range',
									'specific' => 'Specific Range'
								),
								'field_validation' => '',
								'field_default' => 'relative',
								'field_attributes' => array( 'class' => 'date-range-type', 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
								'field_tooltip' => '<strong>Relative:</strong> For example, always get the events from <em>\'One week ago\'</em>, up to <em>\'Eight weeks from now\'</em>.<br>This is the best setting for most churches.<br><br><strong>Specific:</strong> For example, only get events from <em>\'6/1/2015\'</em> to <em>\'12/1/2015\'</em>.<br>This setting is best if you want to tightly manage the events that get published.',
							),
							'calendar-relative-weeks-past' => array(
								'field_title' => 'How Far Back?',
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
								'field_title' => 'How Into The Future?',
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
								'field_title' => 'Specific Start Date',
								'field_render_function' => 'render_date_picker',
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"specific"}' ),
								'field_tooltip' => 'When synchronizing, we should get events that start <strong>after</strong> this date.<br><em>(Leave empty to always start "today")</em>',
							),
							'calendar-specific-end' => array(
								'field_title' => 'Specific End Date',
								'field_render_function' => 'render_date_picker',
								'field_validation' => '',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1,"calendar-date-range-type":"specific"}' ),
								'field_tooltip' => 'When synchronizing, we should get events that start <strong>before</strong> this date.<br><em>(Setting this too far into the future may cause the API to timeout)</em>',
							),
							'calendar-exclude-from-search' => array(
								'field_title' => 'Exclude From Search?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-publicly-queryable' => array(
								'field_title' => 'Publicly Queryable?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-show-ui' => array(
								'field_title' => 'Show In Admin UI?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'yes',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
							'calendar-show-in-nav-menus' => array(
								'field_title' => 'Show In Navigation Menus?',
								'field_render_function' => 'render_radio',
								'field_options' => array(
									'yes' => 'Yes',
									'no' => 'No'
								),
								'field_validation' => '',
								'field_default' => 'no',
								'field_attributes' => array( 'data-requires' => '{"calendar-enabled":1,"calendar-advanced":1}' ),
							),
						),
					),
				),
			),
			$this->plugin_settings_name . '_sync' => array(
				'page_title' => 'Synchronize',
				'sections' => array(
					'synchronize' => array(
						'section_title' => 'Synchronize',
						'fields' => array(
							'auto-sync' => array(
								'field_title' => 'Enable Auto Sync',
								'field_render_function' => 'render_switch',
								'field_validation' => 'switch',
							),
							'auto-sync-timeout' => array(
								'field_title' => 'Cache Expiration',
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
								'field_title' => 'Manual Sync',
								'field_render_function' => 'render_manual_sync',
							),
							'latest-results' => array(
								'field_title' => 'Latest Sync Results',
								'field_render_function' => 'render_latest_results',
							),
						),
					),
				),
			),
		);
	}
}
