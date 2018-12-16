<?php
/**
 * AJAX callbacks for the dashboard functionality
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * AJAX callbacks for the dashboard functionality
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Admin_AJAX {

	/**
	 * Initialize the class and register hooks
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_sync', [ $this, 'ajax_sync' ] );
		add_action( 'wp_ajax_poll_sync', [ $this, 'ajax_poll_sync' ] );
		add_action( 'wp_ajax_get_latest_sync', [ $this, 'ajax_get_latest_sync' ] );
		add_action( 'wp_ajax_test_credentials', [ $this, 'ajax_test_credentials' ] );
	}

	/**
	 * Launches a synchronization from an ajax hook and will respond
	 * with a non-blocking ajax response
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );

		// Tell the user to move along and go about their business...
		CCB_Core_Helpers::instance()->send_non_blocking_json_success();
		$result = CCB_Core_Synchronizer::instance()->synchronize();

	}

	/**
	 * Checks for an active synchronization from an ajax hook
	 * and responds with the transient value
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_poll_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );
		$sync_in_progress = get_transient( CCB_Core_Helpers::SYNC_STATUS_KEY );
		wp_send_json_success( $sync_in_progress );

	}

	/**
	 * Gets the latest synchronization results from an ajax hook
	 * and encodes / echoes a standardized result array.
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_get_latest_sync() {

		check_ajax_referer( 'ccb_core_nonce', 'nonce' );

		$message = '';
		$result = [];

		// Latest sync results are always stored as an option after a sync takes place.
		$latest_sync = get_option( 'ccb_core_latest_sync_result' );

		if ( ! empty( $latest_sync ) ) {
			// Set the success result to the same result as the latest sync.
			$result['success'] = $latest_sync['success'];

			if ( true === $latest_sync['success'] ) {

				$message .= esc_html(
					sprintf(
						// Translators: A formatted date/time.
						__( 'The latest synchronization was successful on %s.', 'ccb-core' ),
						get_date_from_gmt(
							date( 'Y-m-d H:i:s', $latest_sync['timestamp'] ),
							get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' )
						)
					)
				) . '<br>';

				// Send detailed results for each service that has information.
				if ( ! empty( $latest_sync['services'] ) ) {
					foreach ( $latest_sync['services'] as $service => $service_result ) {

						$message .= esc_html(
							sprintf(
								// Translators: The service name.
								__( 'Results from the %s service: ', 'ccb-core' ),
								$service
							)
						);

						if ( isset( $service_result['insert_update']['processed'] ) ) {
							$message .= esc_html(
								sprintf(
									// Translators: The number of records processed.
									__( '%s records inserted / updated. ', 'ccb-core' ),
									absint( $service_result['insert_update']['processed'] )
								)
							);
						}

						if ( isset( $service_result['delete']['processed'] ) ) {
							$message .= esc_html(
								sprintf(
									// Translators: The number of records processed.
									__( '%s records deleted. ', 'ccb-core' ),
									absint( $service_result['delete']['processed'] )
								)
							);
						}

						$message .= '<br>';
					}
				}

			} else {
				$message .= esc_html(
					sprintf(
						__( '%1$s on %2$s', 'ccb-core' ),
						$latest_sync['message'],
						get_date_from_gmt(
							date( 'Y-m-d H:i:s', $latest_sync['timestamp'] ),
							get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' )
						)
					)
				) . '<br>';
				if ( ! empty( $latest_sync['services'] ) ) {
					foreach ( $latest_sync['services'] as $service => $service_result ) {
						if ( ! empty( $service_result['insert_update']['message'] ) ) {
							$message .= $service_result['insert_update']['message'] . '<br>';
						}
						if ( ! empty( $service_result['delete']['message'] ) ) {
							$message .= $service_result['delete']['message'] . '<br>';
						}
					}
				}
			}
		} else {
			$message .= esc_html__( 'We do not have any recent synchronizations', 'ccb-core' );
		}

		/**
		 * Filters the message that gets output to the user
		 * after a synchronization is finished.
		 *
		 * @since 1.0.0
		 *
		 * @param   string $message The message with the results.
		 * @param   array  $latest_sync The latest synchronization results.
		 */
		$result['message'] = apply_filters( 'ccb_core_ajax_results_message', $message, $latest_sync );
		wp_send_json_success( $result );

	}

	/**
	 * Checks the CCB API credentials for a user from an ajax hook
	 *
	 * @access    public
	 * @since     1.0.0
	 * @return    void
	 */
	public function ajax_test_credentials() {
		check_ajax_referer( 'ccb_core_nonce', 'nonce' );
		$response = CCB_Core_API::instance()->get( 'api_status' );

		if ( 'SUCCESS' === $response['status'] ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( $response['error'] );
		}
	}

}

new CCB_Core_Admin_AJAX();
