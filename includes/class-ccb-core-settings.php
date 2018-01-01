<?php
/**
 * Everything related to the plugin settings
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Object to manage the plugin settings
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
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
							$input[ $field_id ] = sanitize_key( $input[ $field_id ] );
							// Continue onto alphanumeric_extended validation because these are essentially the same.
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

		// Initialize a settings array with an About page and Credentials page.
		$settings = [
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
								'field_tooltip' => esc_html__( 'We just need the first part of your software URL (without "http://" and without ".ccbchurch.com").', 'ccb-core' ),
							),
							'credentials' => array(
								'field_title' => esc_html__( 'API Credentials', 'ccb-core' ),
								'field_render_function' => 'render_credentials',
								'field_validation' => 'encrypt',
								'field_tooltip' => esc_html__( 'This is the username and password for the API user in your Church Community Builder software.', 'ccb-core' ),
							),
							'test_credentials' => array(
								'field_title' => esc_html__( 'Test Credentials', 'ccb-core' ),
								'field_render_function' => 'render_test_credentials',
							),
						),
					),
				),
			),
		];

		/**
		 * Allow custom post types to have their own settings pages.
		 *
		 * If you implement a custom post type and do not want to
		 * expose a settings page, simply `return $settings` from
		 * within your implementation of `get_post_settings_definitions()`
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $settings The current array of settings definitions.
		 */
		$settings = apply_filters( 'ccb_core_settings_post_definitions', $settings );

		// Add a syncronization settings page.
		$settings['ccb_core_settings_sync'] = array(
			'page_title' => esc_html__( 'Synchronize', 'ccb-core' ),
			'sections' => array(
				'synchronize' => array(
					'section_title' => esc_html__( 'Synchronize', 'ccb-core' ),
					'fields' => array(
						'auto_sync' => array(
							'field_title' => esc_html__( 'Enable Auto Sync', 'ccb-core' ),
							'field_render_function' => 'render_switch',
							'field_validation' => 'switch',
						),
						'auto_sync_timeout' => array(
							'field_title' => esc_html__( 'Cache Expiration', 'ccb-core' ),
							'field_render_function' => 'render_slider',
							'field_options' => array(
								'min' => '10',
								'max' => '180',
								'units' => 'minutes',
							),
							'field_default' => 90,
							'field_validation' => '',
							'field_attributes' => array( 'data-requires' => '{"auto_sync":1}' ),
							'field_tooltip' => sprintf(
								esc_html__(
									'We keep a local copy (cache) of your Church Community Builder data for the best performance.%1$s
									How often (in minutes) should we check for new data?%2$s
									(90 minutes is recommended).',
								'ccb-core' ),
								'<br>',
								'<br>'
							),
						),
						'manual_sync' => array(
							'field_title' => esc_html__( 'Manual Sync', 'ccb-core' ),
							'field_render_function' => 'render_manual_sync',
						),
						'latest_results' => array(
							'field_title' => esc_html__( 'Latest Sync Results', 'ccb-core' ),
							'field_render_function' => 'render_latest_results',
						),
					),
				),
			),
		);

		/**
		 * Allow filtering of the entire settings array.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $settings The current array of settings definitions.
		 */
		return apply_filters( 'ccb_core_settings_definitions', $settings );

	}

	/**
	 * Helper function to create a name/value hash for quick validation
	 *
	 * @access    private
	 * @since     0.9.0
	 * @return    array    $mapping
	 */
	private function generate_validation_hash() {
		// Verify the nonce before processing field data.
		check_admin_referer( 'update_settings', 'ccb_core_nonce' );

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