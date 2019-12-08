<?php
/**
 * Manage persistent and dismissable notices in the admin.
 *
 * @link       https://www.wpccb.com
 * @since      1.0.8
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Manage persistent and dismissable notices in the admin.
 *
 * @since      1.0.8
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Notices {

	/**
	 * A collection of notices to display.
	 *
	 * @var array
	 */
	private $notices = [];

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		$ccb_core_notices = get_transient( 'ccb_core_notices' );
		$this->notices    = ! empty( $ccb_core_notices ) ? $ccb_core_notices : [];
		$this->register_hooks();
	}

	/**
	 * Register hooks for notices.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_notices', [ $this, 'display_notices' ] );
		add_action( 'wp_ajax_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
	}

	/**
	 * Handle AJAX event for dismissible notices.
	 *
	 * @access    public
	 * @since     1.0.8
	 * @return    void
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'ccb_core_nonce', 'nonce' );
		$notice_id = ! empty( $_POST['noticeId'] ) ? absint( wp_unslash( $_POST['noticeId'] ) ) : false;
		if ( ! empty( $notice_id ) ) {
			$this->delete_notice( $notice_id );
		}
	}

	/**
	 * Add a notice to the collection.
	 *
	 * @param   string $message The message to display.
	 * @param   string $type The type of notice: success, warning, error.
	 *
	 * @return  void
	 */
	public function save_notice( $message = '', $type = 'warning' ) {
		$this->notices[ time() ] = [
			'message' => $message,
			'type'    => $type,
		];
		set_transient( 'ccb_core_notices', $this->notices );
	}

	/**
	 * Delete a notice from the collection.
	 *
	 * @param    int $timestamp The notice ID.
	 *
	 * @return   void
	 */
	public function delete_notice( $timestamp ) {
		unset( $this->notices[ $timestamp ] );
		set_transient( 'ccb_core_notices', $this->notices );
	}

	/**
	 * Display the notices in the admin.
	 *
	 * @return   void
	 */
	public function display_notices() {
		if ( ! empty( $this->notices ) ) {
			foreach ( $this->notices as $notice_id => $notice ) {
				echo sprintf(
					'<div data-notice-id="%3$s" class="ccb-dismissible-notice notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( $notice['type'] ),
					wp_kses_post( $notice['message'] ),
					esc_attr( $notice_id )
				);
			}
		}
	}
}

return new CCB_Core_Notices();
