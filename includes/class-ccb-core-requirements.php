<?php
/**
 * A class that validates plugin requirements before the plugin gets loaded.
 *
 * @link          https://www.wpccb.com
 * @package       CCB_Core
 * @subpackage    CCB_Core/includes
 */

/**
 * A class that validates plugin requirements before the plugin gets loaded.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Requirements {

	/**
	 * Whether or not the requirements are met
	 *
	 * @var bool
	 */
	public $requirements_met = true;

	/**
	 * The minimum required version of PHP
	 *
	 * @var string
	 */
	private $required_php = '5.6.0';

	/**
	 * The minimum required version of WordPress
	 *
	 * @var string
	 */
	private $required_wordpress = '4.6.0';

	/**
	 * Required global constants defined in wp-config.php
	 *
	 * @var array
	 */
	private $required_keys = [ 'AUTH_KEY', 'AUTH_SALT' ];

	/**
	 * Required PHP modules
	 *
	 * @var array
	 */
	private $required_modules = [ 'OpenSSL' => 'openssl' ];

	/**
	 * Any applicable error messages
	 *
	 * @var string
	 */
	private $error_message = '';

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		$this->validate_versions();
		$this->validate_modules();
		$this->validate_writable_files();
	}

	/**
	 * Setup hooks to disable the plugin and show a notice
	 *
	 * @return void
	 */
	public function register_disable_plugin() {
		add_action( 'admin_init', array( $this, 'disable_plugin' ) );
		add_action( 'admin_notices', array( $this, 'send_error' ) );
	}

	/**
	 * Deactivate the plugin
	 *
	 * @return void
	 */
	public function disable_plugin() {
		deactivate_plugins( CCB_CORE_BASENAME );
	}

	/**
	 * Display an admin notice if the server has failed minimum requirements.
	 *
	 * @return   void
	 */
	public function send_error() {
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $this->error_message ) . '</p></div>';
	}

	/**
	 * Ensure that the infrastructure supports the minimum
	 * requirements for PHP and WordPress versions
	 *
	 * @return void
	 */
	private function validate_versions() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, $this->required_php, '<' ) ) {
			$issue   = 'PHP';
			$version = $this->required_php;
		} elseif ( version_compare( $wp_version, $this->required_wordpress, '<' ) ) {
			$issue   = 'WordPress';
			$version = $this->required_wordpress;
		} else {
			return;
		}

		$this->requirements_met = false;
		$this->error_message    = sprintf(
			'Church Community Builder Core API requires %1$s version %2$s or greater.',
			$issue,
			$version
		);
		$this->register_disable_plugin();
	}

	/**
	 * Ensure the site has the required PHP modules installed.
	 *
	 * @return void
	 */
	private function validate_modules() {
		foreach ( $this->required_modules as $module_name => $module_slug ) {
			if ( ! extension_loaded( $module_slug ) ) {
				$this->requirements_met = false;
				$this->error_message    = sprintf(
					'Church Community Builder Core API requires that you have the ' .
					'%s PHP module installed. Please contact your hosting provider ' .
					'and ask them if they can install or enable that module for your site.',
					$module_name
				);
				$this->register_disable_plugin();
				return;
			}
		}
	}

	/**
	 * Ensure we can write to the config file.
	 *
	 * @return void
	 */
	private function validate_writable_files() {
		if ( ! wp_is_writable( CCB_CORE_PATH ) ) {
			$this->requirements_met = false;
			$this->error_message    = 'Church Community Builder Core API requires that ' .
				'its plugin folder and files are writable by your server. ' .
				'Please <a target="_blank" href="https://wordpress.org/support/article/changing-file-permissions/">see this article</a>' .
				' and contact your hosting provider for help.';
			$this->register_disable_plugin();
			return;
		}
	}
}
