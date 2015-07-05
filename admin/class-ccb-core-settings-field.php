<?php
/**
 * Everything related to the plugin settings fields
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Object to manage the plugin settings fields
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings_Field extends CCB_Core_Plugin {

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
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function __construct( $field_id, $field ) {

		parent::__construct();

		$this->field_id = $field_id;
		$this->field = $field;
		$this->existing_settings = get_option( $this->plugin_settings_name );

	}

	/**
	 * General method that calls correct field render method based on config
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function render_field() {
		if ( isset( $this->field['field_render_function'] ) && is_callable( array( $this, $this->field['field_render_function'] ) ) ) {
			call_user_func( array( $this, $this->field['field_render_function'] ) );
			if ( isset( $this->field['field_tooltip'] ) ) {
				echo '<span class="ccb-core-tooltip dashicons dashicons-editor-help" data-tip="' . esc_html( $this->field['field_tooltip'] ) . '"></span>';
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
		$attributes = $this->build_attributes_string();

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo "<input type=\"text\" placeholder=\"{$this->field['field_placeholder']}\" name=\"{$this->plugin_settings_name}[{$this->field_id}]\" value=\"{$value}\" {$attributes} />";
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
		$attributes = $this->build_attributes_string();

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo "<input type=\"checkbox\" class=\"js-switch\" name=\"{$this->plugin_settings_name}[{$this->field_id}]\" value=\"1\" " . checked( $value, '1', false ) . "{$attributes} />";
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
		$attributes = $this->build_attributes_string();

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo "<div class=\"slider-wrapper\"><input type=\"text\" class=\"js-range\" name=\"{$this->plugin_settings_name}[{$this->field_id}]\" value=\"{$value}\"{$attributes} data-sibling=\"{$this->field_id}-readonly\" data-min=\"{$this->field['field_options']['min']}\" data-max=\"{$this->field['field_options']['max']}\" /></div>";
		echo '<span><input type="text" readonly class="' . $this->field_id . '-readonly small-text" /> ' . $this->field['field_options']['units'] . '</span>';
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
		$attributes = $this->build_attributes_string();

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo "<div class=\"date-picker-wrapper\"><input type=\"text\" class=\"datepicker\" name=\"{$this->plugin_settings_name}[{$this->field_id}]\" data-value=\"{$value}\" {$attributes} /></div>";
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
		$attributes = $this->build_attributes_string();

		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value = $this->existing_settings[ $this->field_id ];
		}

		echo '<fieldset>';
		foreach ( (array) $this->field['field_options'] as $option_value => $option_label ) {
			echo "<label><input type=\"radio\" name=\"{$this->plugin_settings_name}[{$this->field_id}]\" value=\"{$option_value}\" " . checked( $value, $option_value, false ) . "{$attributes} /><span>{$option_label}</span></label><br>";
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
		$value = array();
		if ( isset( $this->existing_settings[ $this->field_id ] ) ) {
			$value['username'] = $this->existing_settings[ $this->field_id ]['username'];
			$value['password'] = $this->decrypt( $this->existing_settings[ $this->field_id ]['password'] );
		}
		else {
			$value['username'] = '';
			$value['password'] = '';
		}
		echo <<<HTML
		<input type="text" placeholder="Username" name="{$this->plugin_settings_name}[{$this->field_id}][username]" value="{$value['username']}" />
		<input type="password" placeholder="Password" name="{$this->plugin_settings_name}[{$this->field_id}][password]" value="{$value['password']}" />
HTML;
	}

	/**
	 * Render a test credentials button
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_test_credentials() {
		if ( ! isset( $this->existing_settings['credentials']['username'] ) || empty( $this->existing_settings['credentials']['username'] ) || ! isset( $this->existing_settings['credentials']['password'] ) || empty( $this->existing_settings['credentials']['password'] ) ) {
			echo '<p>Please enter your API Credentials</p>';
		}
		elseif ( ! isset( $this->existing_settings['subdomain'] ) || empty( $this->existing_settings['subdomain'] ) ) {
			echo '<p>Please enter your Church Community Builder subdomain.</p>';
		}
		else {
			echo <<<HTML
			<div class="test-login-wrapper">
				<input type="button" name="test_login" id="test-login" class="button" value="Test Credentials" />
				<div class="spinner"></div>
			</div>
HTML;
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
		$sync_in_progress = get_transient( $this->plugin_name . '-sync-in-progress' );
		$sync_message = '';
		$button_disabled = '';
		$spinner_active = '';

		if ( $sync_in_progress ) {
			$sync_message = '<div class="in-progress-message ajax-message updated">Syncronization in progress... You can safely navigate away from this page while we work hard in the background. (It should be just a moment).</div>';
			$button_disabled = 'disabled';
			$spinner_active = 'is-active';
		}
		echo <<<HTML
			<div class="sync-wrapper">
				<input type="button" name="manual_sync" id="manual-sync" class="button {$button_disabled}" value="Synchronize" />
				<div class="spinner {$spinner_active}"></div>
				{$sync_message}
			</div>
HTML;
	}

	/**
	 * Render an area to display the latest sync results
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function render_latest_results() {
		echo <<<HTML
			<div class="ajax-message ccb-core-latest-results">
				<div class="spinner is-active" style="float:left;margin-top:-8px;"></div>
			</div>
HTML;
	}

	/**
	 * Helper method to build HTML attributes from the config
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    string
	 */
	protected function build_attributes_string() {
		$attributes = '';
		if ( isset( $this->field['field_attributes'] ) && ! empty( $this->field['field_attributes'] ) ) {
			foreach ( $this->field['field_attributes'] as $attr_name => $attr_value ) {
				$attributes .= " {$attr_name}='{$attr_value}'";
			}
		}
		return $attributes;
	}
}
