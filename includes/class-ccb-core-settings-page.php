<?php
/**
 * Everything related to the plugin settings pages
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Object to manage the plugin settings pages
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings_Page {

	/**
	 * The key for the page in the settings array
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $section_id
	 */
	protected $page_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param    string $page_id The slug of the page.
	 * @return   void
	 */
	public function __construct( $page_id ) {
		$this->page_id = $page_id;
	}

	/**
	 * Render the settings page template (used for all pages)
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function render_page() {

		/**
		 * Defines the capability that is required for the user
		 * to access the settings page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $capability The capability required to access the page.
		 */
		if ( ! current_user_can( apply_filters( 'ccb_core_settings_capability', 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ccb-core' ) );
		}
		?>

		<div class="wrap ccb_core_settings-wrapper <?php echo esc_attr( $this->page_id ); ?>">
			<h2><?php echo esc_html__( 'Church Community Builder Core API' ); ?></h2>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">

				<?php settings_fields( $this->page_id ); ?>
				<?php do_settings_sections( $this->page_id ); ?>
				<?php wp_nonce_field( 'update_settings', 'ccb_core_nonce' ); ?>

				<?php
				if ( 'ccb_core_settings' !== $this->page_id ) {
					?>
					<p class="submit">
						<input name="submit" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save Changes', 'ccb-core' ); ?>" />
					</p>
					<?php
				}
				?>
			</form>
		</div>

		<?php
	}

}
