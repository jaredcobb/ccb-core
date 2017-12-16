<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link          https://www.wpccb.com
 * @package       CCB_Core
 * @subpackage    CCB_Core/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    0.9.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  0.9.0
	 * @access private
	 */
	private function load_dependencies() {

		// Encryption class to provide better security and ease of use.
		require_once CCB_CORE_PATH . 'lib/class-ccb-core-vendor-encryption.php';

		// A generic helper class with commonly used mehtods.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-helpers.php';

		// The classes that define options and settings for the plugin.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-page.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-section.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-field.php';

		// The class that handles data synchronization between CCB and the local cache.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-sync.php';

		// Custom Post Type classes.
		require_once CCB_CORE_PATH . 'includes/post-types/class-ccb-core-cpt.php';
		require_once CCB_CORE_PATH . 'includes/post-types/class-ccb-core-group.php';
		require_once CCB_CORE_PATH . 'includes/post-types/class-ccb-core-calendar.php';

		// Custom Taxonomy classes.
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-taxonomy.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-calendar-event-type.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-calendar-group-name.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-calendar-grouping-name.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-area.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-day.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-department.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-tag.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-time.php';
		require_once CCB_CORE_PATH . 'includes/taxonomies/class-ccb-core-group-type.php';

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    0.9.0
	 * @access   private
	 */
	private function define_hooks() {

		// Internationalization.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Plugin settings, menus, options.
		add_filter( 'plugin_action_links_' . CCB_CORE_BASENAME, array( $this, 'add_settings_link' ) );

		// Setup settings pages.
		add_action( 'admin_menu', array( $this, 'initialize_settings_menu' ) );
		add_action( 'admin_init', array( $this, 'initialize_settings' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/*// Cron related hooks.
		$this->loader->add_action( 'schedule_auto_refresh', $plugin_admin, 'auto_sync' );
		$this->loader->add_action( 'wp_loaded', $plugin_admin, 'check_auto_refresh' );

		// User initiated actions.
		$this->loader->add_action( 'pre_update_option_' . $this->plugin_settings_name, $plugin_admin, 'update_settings_callback', 10, 2 );
		$this->loader->add_action( 'schedule_flush_rewrite_rules', $plugin_admin, 'flush_rewrite_rules_event' );

		// All backend ajax hooks.
		$this->loader->add_action( 'wp_ajax_sync', $plugin_admin, 'ajax_sync' );
		$this->loader->add_action( 'wp_ajax_poll_sync', $plugin_admin, 'ajax_poll_sync' );
		$this->loader->add_action( 'wp_ajax_test_credentials', $plugin_admin, 'ajax_test_credentials' );
		$this->loader->add_action( 'wp_ajax_get_latest_sync', $plugin_admin, 'ajax_get_latest_sync' );*/

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since   0.9.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ccb-core',
			false,
			dirname( CCB_CORE_BASENAME ) . '/languages/'
		);

	}

	/**
	 * Create a helpful settings link on the plugin page
	 *
	 * @param    array $links An array of links.
	 * @access   public
	 * @since    0.9.0
	 * @return   array
	 */
	public function add_settings_link( $links ) {
		$links[] = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=ccb_core_settings' ) ) . '">' . esc_html__( 'Settings', 'ccb-core' ) . '</a>';
		return $links;
	}

	/**
	 * Initialize the Settings Menu and Page
	 *
	 * @access   public
	 * @since    0.9.0
	 * @return   void
	 */
	public function initialize_settings_menu() {

		$settings = new CCB_Core_Settings();
		$settings_page = new CCB_Core_Settings_Page( 'ccb_core_settings' );

		add_menu_page(
			__( 'Church Community Builder Core API', 'ccb-core' ),
			__( 'CCB Core API', 'ccb-core' ),
			'manage_options',
			'ccb_core_settings',
			'__return_null',
			'dashicons-update',
			'80.9'
		);

		foreach ( $settings->get_settings_definitions() as $page_id => $page ) {
			$settings_page = new CCB_Core_Settings_Page( $page_id );
			add_submenu_page(
				'ccb_core_settings',
				$page['page_title'],
				$page['page_title'],
				'manage_options',
				$page_id,
				array(
					$settings_page,
					'render_page',
				)
			);
		}
	}

	/**
	 * Initialize the Settings
	 *
	 * @access   public
	 * @since    0.9.0
	 * @return   void
	 */
	public function initialize_settings() {

		$settings = new CCB_Core_Settings();

		foreach ( $settings->get_settings_definitions() as $page_id => $page ) {

			register_setting( $page_id, 'ccb_core_settings', array( $settings, 'validate_settings' ) );

			foreach ( $page['sections'] as $section_id => $section ) {

				$settings_section = new CCB_Core_Settings_Section( $section_id, $section );
				add_settings_section(
					$section_id,
					$section['section_title'],
					array(
						$settings_section,
						'render_section',
					),
					$page_id
				);

				if ( ! empty( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field_id => $field ) {

						$settings_field = new CCB_Core_Settings_Field( $field_id, $field );
						add_settings_field(
							$field_id,
							$field['field_title'],
							array(
								$settings_field,
								'render_field',
							),
							$page_id,
							$section_id
						);

					}
				}

			}

		}

	}

	/**
	 * Register the stylesheets for the dashboard.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_styles( $hook ) {

		if ( false !== stristr( $hook, 'ccb_core_settings' ) ) {
			wp_enqueue_style( 'ccb-core', CCB_CORE_URL . 'css/ccb-core-admin.css', array(), CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'switchery', CCB_CORE_URL . 'css/vendor/switchery.min.css', array(), CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'powerange', CCB_CORE_URL . 'css/vendor/powerange.min.css', array(), CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'picker', CCB_CORE_URL . 'css/vendor/default.css', array(), CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'picker-date', CCB_CORE_URL . 'css/vendor/default.date.css', array(), CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'tipr', CCB_CORE_URL . 'css/vendor/tipr.css', array(), CCB_CORE_VERSION, 'all' );
		}

	}

	/**
	 * Register the scripts for the dashboard.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {

		if ( false !== stristr( $hook, 'ccb_core_settings' ) ) {
			wp_enqueue_script( 'ccb-core', CCB_CORE_URL . 'js/ccb-core-admin.js', array( 'jquery' ), CCB_CORE_VERSION, false );
			wp_enqueue_script( 'switchery', CCB_CORE_URL . 'js/vendor/switchery.min.js', array( 'jquery' ), CCB_CORE_VERSION, false );
			wp_enqueue_script( 'powerange', CCB_CORE_URL . 'js/vendor/powerange.min.js', array( 'jquery' ), CCB_CORE_VERSION, false );
			wp_enqueue_script( 'picker', CCB_CORE_URL . 'js/vendor/picker.js', array( 'jquery' ), CCB_CORE_VERSION, false );
			wp_enqueue_script( 'picker-date', CCB_CORE_URL . 'js/vendor/picker.date.js', array( 'picker' ), CCB_CORE_VERSION, false );
			wp_enqueue_script( 'tipr', CCB_CORE_URL . 'js/vendor/tipr.min.js', array( 'jquery' ), CCB_CORE_VERSION, false );
			wp_localize_script( 'ccb-core', 'CCB_CORE_SETTINGS', array(
					'nextNonce' => wp_create_nonce( 'ccb-core-nonce' ),
				)
			);
		}

	}


}
