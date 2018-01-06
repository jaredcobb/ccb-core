<?php
/**
 * Everything related to the plugin settings fields
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Object to manage the plugin settings fields
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings_Field {

	/**
	 * The key for the field in the settings array
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $field_id
	 */
	protected $field_id;

	/**
	 * An array of field settings
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $field
	 */
	protected $field;

	/**
	 * The existing settings currently stored
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $existing_settings
	 */
	protected $existing_settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $field_id Slug of the field.
	 * @param   array  $field Array of field settings.
	 * @return void
	 */
	public function __construct( $field_id, $field ) {

		$this->field_id = $field_id;
		$this->field = $field;
		$this->existing_settings = CCB_Core_Helpers::instance()->get_options();

	}

	/**
	 * General method that calls correct field render method based on config
	 *
	 * @access   public
	 * @since    0.9.0
	 * @return   void
	 */
	public function render_field() {
		if ( isset( $this->field['field_render_function'] ) && is_callable( [ $this, $this->field['field_render_function'] ] ) ) {
			call_user_func( [ $this, $this->field['field_render_function'] ] );
			if ( isset( $this->field['field_tooltip'] ) ) {
				echo '<span class="ccb-core-tooltip dashicons dashicons-editor-help" data-tip="' .
					esc_html( $this->field['field_tooltip'] ) .
					'"></span>';
			}
		}
	}

	/**
	 * Render a textfield
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_text() {
		$value = '';

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo sprintf(
			'<input type="text" placeholder="%1$s" name="ccb_core_settings[%2$s]" value="%3$s" ',
			esc_attr( $this->field['field_placeholder'] ),
			esc_attr( $this->field_id ),
			esc_attr( $value )
		);

		$this->output_attributes();

		echo '/>';
	}

	/**
	 * Render a switch button (checkbox)
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_switch() {
		$value = '';

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo sprintf(
			'<input type="checkbox" class="js-switch" name="ccb_core_settings[%1$s]" value="1" %2$s ',
			esc_attr( $this->field_id ),
			checked( $value, '1', false )
		);

		$this->output_attributes();

		echo '/>';
	}

	/**
	 * Render a slider widget (textfield)
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_slider() {
		$value = $this->field['field_default'];

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo sprintf(
			'<div class="slider-wrapper">
				<input type="text" class="js-range" name="ccb_core_settings[%1$s]" value="%2$s"
				data-sibling="%1$s-readonly" data-min="%3$s" data-max="%4$s" ',
			esc_attr( $this->field_id ),
			esc_attr( $value ),
			esc_attr( $this->field['field_options']['min'] ),
			esc_attr( $this->field['field_options']['max'] )
		);

		$this->output_attributes();

		echo ' /></div>';

		echo sprintf(
			'<span><input type="text" readonly class="%1$s-readonly small-text" />%2$s</span>',
			esc_attr( $this->field_id ),
			esc_html( $this->field['field_options']['units'] )
		);
	}

	/**
	 * Render a jQuery date picker
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_date_picker() {
		$value = '';

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo sprintf(
			'<div class="date-picker-wrapper">
				<input type="text" class="datepicker" name="ccb_core_settings[%1$s]" data-value="%2$s" ',
			esc_attr( $this->field_id ),
			esc_attr( $value )
		);

		$this->output_attributes();

		echo '/></div>';
	}

	/**
	 * Render radio buttons
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_radio() {

		$value = $this->field['field_default'];

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo '<fieldset>';
		foreach ( (array) $this->field['field_options'] as $option_value => $option_label ) {
			echo sprintf(
				'<label><input type="radio" name="ccb_core_settings[%1$s]" value="%2$s" %3$s ',
				esc_attr( $this->field_id ),
				esc_attr( $option_value ),
				checked( $value, $option_value, false )
			);

			$this->output_attributes();

			echo sprintf(
				'/><span>%s</span></label><br>',
				esc_attr( $option_label )
			);
		}
		echo '</fieldset>';

	}

	/**
	 * Render a username and password field
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_credentials() {
		$value = [];
		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value['username'] = $this->existing_settings[ $this->field_id ]['username'];
			$value['password'] = CCB_Core_Helpers::instance()->decrypt( $this->existing_settings[ $this->field_id ]['password'] );
		} else {
			$value['username'] = '';
			$value['password'] = '';
		}

		echo sprintf(
			'<input type="text" placeholder="%1$s" name="ccb_core_settings[%2$s][username]" value="%3$s" />',
			esc_attr__( 'Username', 'ccb-core' ),
			esc_attr( $this->field_id ),
			esc_attr( $value['username'] )
		);
		echo sprintf(
			'<input type="password" placeholder="%1$s" name="ccb_core_settings[%2$s][password]" value="%3$s" />',
			esc_attr__( 'Password', 'ccb-core' ),
			esc_attr( $this->field_id ),
			esc_attr( $value['password'] )
		);
	}

	/**
	 * Render a test credentials button
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_test_credentials() {
		if ( empty( $this->existing_settings['credentials']['username'] ) || empty( $this->existing_settings['credentials']['password'] ) ) {
			echo sprintf(
				'<p>%s</p>',
				esc_html__( 'Please enter your API Credentials' )
			);
		} elseif ( empty( $this->existing_settings['subdomain'] ) ) {
			echo sprintf(
				'<p>%s</p>',
				esc_html__( 'Please enter your Church Community Builder subdomain.' )
			);
		} else {
			echo sprintf(
				'<div class="test-credentials-wrapper">
					<input type="button" name="test_credentials" id="test-credentials" class="button" value="%s" />
				</div>',
				esc_attr__( 'Test Credentials', 'ccb-core' )
			);
		}
	}

	/**
	 * Render a manual sync button
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_manual_sync() {
		if ( CCB_Core_API::instance()->initialized ) {
			echo sprintf(
				'<div class="sync-wrapper">
					<input type="button" name="manual_sync" id="manual_sync" class="button" value="%s" />
				</div>',
				esc_attr__( 'Synchronize', 'ccb-core' )
			);
		} else {
			echo '<p>' . esc_html__( 'Please enter your credentials under the API Settings page', 'ccb-core' ) . '</p>';
		}
	}

	/**
	 * Render an area to display the latest sync results
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_latest_results() {
		echo '<div class="ajax-message ccb-core-latest-results">
				<span class="spinner is-active"></span>
			</div>';
	}

	/**
	 * Helper method to echo HTML attributes from the config
	 *
	 * @access   protected
	 * @since    0.9.0
	 * @return   void
	 */
	protected function output_attributes() {
		$attributes = '';
		if ( ! empty( $this->field['field_attributes'] ) ) {
			foreach ( $this->field['field_attributes'] as $attr_name => $attr_value ) {
				echo sprintf(
					'%1$s="%2$s" ',
					esc_attr( $attr_name ),
					esc_attr( $attr_value )
				);
			}
		}
	}
}
