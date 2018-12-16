<?php
/**
 * Manage the cron events for auto sync
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Manage the cron events for auto sync
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Cron {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Setup a custom cron schedule based on the user preferences.
		// phpcs:ignore
		add_filter( 'cron_schedules', [ $this, 'custom_cron_schedule' ] );
		// Setup the action and callback for the hook.
		add_action( 'ccb_core_auto_sync_hook', [ $this, 'auto_sync_callback' ] );
		// When the cron settings are changed, configure the events.
		add_action( 'update_option_ccb_core_settings', [ $this, 'cron_settings_changed' ], 10, 2 );
	}

	/**
	 * Create a custom cron schedule based on the
	 * timeout interval set by the user.
	 *
	 * @param    array $schedules An array of cron schedules.
	 * @return   array
	 */
	public function custom_cron_schedule( $schedules ) {
		$settings = CCB_Core_Helpers::instance()->get_options();
		if ( ! empty( $settings['auto_sync_timeout'] ) ) {
			$schedules['ccb_core_schedule'] = [
				'interval' => MINUTE_IN_SECONDS * absint( $settings['auto_sync_timeout'] ),
				'display' => esc_html(
					sprintf(
						__( 'Every %s Minutes' ),
						absint( $settings['auto_sync_timeout'] )
					)
				),
			];
		}
		return $schedules;
	}

	/**
	 * Callback method to detect when the settings have changed.
	 *
	 * We check for whether or not the auto sync was turned on / off and
	 * whether or not the user changed the timeout interval. This is
	 * how we register cron events and clean up invalid events.
	 *
	 * @param    array $old_value The old settings array.
	 * @param    array $new_value The new settings array.
	 * @return   void
	 */
	public function cron_settings_changed( $old_value, $new_value ) {
		// If the cron was enabled OR the timeout was changed.
		if (
			( empty( $old_value['auto_sync'] ) && ! empty( $new_value['auto_sync'] ) )
			|| (
				! empty( $old_value['auto_sync_timeout'] )
				&& ! empty( $new_value['auto_sync_timeout'] )
				&& $old_value['auto_sync_timeout'] !== $new_value['auto_sync_timeout']
			)
		) {
			$this->remove_existing_cron_events();
			wp_schedule_event( time(), 'ccb_core_schedule', 'ccb_core_auto_sync_hook' );
		} elseif ( ! empty( $old_value['auto_sync'] ) && empty( $new_value['auto_sync'] ) ) {
			$this->remove_existing_cron_events();
		}
	}

	/**
	 * Removes all CCB Core cron events.
	 *
	 * @return   void
	 */
	private function remove_existing_cron_events() {
		$timestamp = wp_next_scheduled( 'ccb_core_auto_sync_hook' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ccb_core_auto_sync_hook' );
		}
	}

	/**
	 * The callback method of the cron event that kicks off a synchronization
	 *
	 * @return   void
	 */
	public function auto_sync_callback() {
		CCB_Core_Synchronizer::instance()->synchronize();
	}

}

new CCB_Core_Cron();
