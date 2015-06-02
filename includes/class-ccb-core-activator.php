<?php
/**
 * Fired during plugin activation
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.9.0
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Activator extends CCB_Core_Plugin {

	/**
	 * Activation code
	 *
	 * @since    0.9.0
	 */
	public static function activate() {

		$redux_installed_version = get_option( 'redux_version_upgraded_from' );

		if ( $redux_installed_version != static::$redux_bundled_version ) {
			update_option( 'redux_version_upgraded_from', static::$redux_bundled_version );
		}
	}

}
