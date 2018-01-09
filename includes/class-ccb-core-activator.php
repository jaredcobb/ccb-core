<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.wpccb.com
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
class CCB_Core_Activator {

	/**
	 * Activation code
	 *
	 * @since    0.9.0
	 */
	public static function activate() {
	}

	/**
	 * Deactivation code
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Ensure we do not have a scheduled hook.
		$timestamp = wp_next_scheduled( 'ccb_core_auto_sync_hook' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ccb_core_auto_sync_hook' );
		}
	}

}
