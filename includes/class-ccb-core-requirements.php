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
		$this->validate_keys();
		$this->validate_encryption_methods();
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
		echo '<div class="notice notice-error"><p>' . esc_html( $this->error_message ) . '</p></div>';
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
			$issue = 'PHP';
			$version = $this->required_php;
		} elseif ( version_compare( $wp_version, $this->required_wordpress, '<' ) ) {
			$issue = 'WordPress';
			$version = $this->required_wordpress;
		} else {
			return;
		}

		$this->requirements_met = false;
		$this->error_message = sprintf(
			'Church Community Builder Core API requires %1$s version %2$s or greater.',
			$issue,
			$version
		);
		$this->register_disable_plugin();
	}

	/**
	 * Ensure the site has configured the required keys.
	 *
	 * @return void
	 */
	private function validate_keys() {
		foreach ( $this->required_keys as $key ) {
			if ( ! defined( $key ) || 32 > strlen( constant( $key ) ) ) {
				$this->requirements_met = false;
				$this->error_message = sprintf(
					'Church Community Builder Core API requires that you configure a random ' .
					'value for the %s constant that is at least 32 characters long. See ' .
					'https://codex.wordpress.org/Editing_wp-config.php#Security_Keys ' .
					'for more information.',
					$key
				);
				$this->register_disable_plugin();
				return;
			}
		}
	}

	/**
	 * Ensure the site has an encryption module installed.
	 *
	 * @return void
	 */
	private function validate_encryption_methods() {
		if (
			! function_exists( 'sodium_crypto_secretbox' )
			&& ! function_exists( 'sodium_crypto_secretbox_open' )
			&& ! function_exists( 'mcrypt_encrypt' )
			&& ! function_exists( 'mcrypt_decrypt' )
		) {
			$this->requirements_met = false;
			$this->error_message = 'Church Community Builder Core API requires that you ' .
				'have an encryption library installed on your system. By default, ' .
				'you should have the mcrypt module installed for PHP versions less than 7.2 ' .
				'or the sodium module installed for PHP versions 7.2 or later. ' .
				'Please contact your hosting provider for more information.';
			$this->register_disable_plugin();
			return;
		}
	}
}
