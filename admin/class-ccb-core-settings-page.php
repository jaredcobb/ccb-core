<?php
/**
 * Everything related to the plugin settings pages
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Object to manage the plugin settings pages
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings_Page extends CCB_Core_Plugin {

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
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function __construct( $page_id ) {

		parent::__construct();

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
		if ( ! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', $this->plugin_name ) );
		}
		?>

		<div class="wrap <?php echo $this->plugin_settings_name . '-wrapper ' . $this->page_id; ?>">
			<h2><?php echo $this->plugin_display_name; ?></h2>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">

				<?php settings_fields( $this->page_id ); ?>
				<?php do_settings_sections( $this->page_id ); ?>

				<?php
				if ( $this->page_id != 'ccb_core_settings' ) {
					?>
					<p class="submit">
						<input name="submit" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
					</p>
					<?php
				}
				?>
			</form>
		</div>

		<?php
	}

}
