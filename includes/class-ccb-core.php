<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
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
 * @since      0.9.0
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core extends CCB_Core_Plugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      CCB_Core_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * A helper for getting the plugin_basename
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      CCB_Core_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $plugin_basename;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    0.9.0
	 */
	public function __construct( $plugin_basename ) {

		parent::__construct();
		$this->plugin_basename = $plugin_basename;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Also create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.9.0
	 * @access   private
	 */
	private function load_dependencies() {

		// encryption class to provide better security and ease of use
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/Encryption/Encryption.php';

		// the class responsible for orchestrating the actions and filters of the core plugin.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ccb-core-loader.php';

		// the class responsible for defining internationalization functionality of the plugin.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ccb-core-i18n.php';

		// the class that defines options and settings for the plugin
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-settings-page.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-settings-section.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-settings-field.php';

		// the class responsible for defining all actions that occur in the Dashboard.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-admin.php';

		// the class that handles data synchronization between CCB and the local cache
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-sync.php';

		// the class that handles data synchronization between CCB and the local cache
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ccb-core-cpts.php';

		// instantiate the loader
		$this->loader = new CCB_Core_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CCB_Core_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.9.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new CCB_Core_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    0.9.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new CCB_Core_Admin();

		$this->loader->add_action( 'init', $plugin_admin, 'initialize_custom_post_types' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'initialize_settings_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'initialize_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_filter( 'plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'add_settings_link' );
		$this->loader->add_action( 'schedule_auto_refresh', $plugin_admin, 'auto_sync' );
		$this->loader->add_action( 'wp_loaded', $plugin_admin, 'check_auto_refresh' );
		$this->loader->add_action( 'pre_update_option_' . $this->plugin_settings_name, $plugin_admin, 'update_settings_callback', 10, 2 );
		$this->loader->add_action( 'schedule_flush_rewrite_rules', $plugin_admin, 'flush_rewrite_rules_event' );

		// all backend ajax hooks
		$this->loader->add_action( 'wp_ajax_sync', $plugin_admin, 'ajax_sync' );
		$this->loader->add_action( 'wp_ajax_poll_sync', $plugin_admin, 'ajax_poll_sync' );
		$this->loader->add_action( 'wp_ajax_test_credentials', $plugin_admin, 'ajax_test_credentials' );
		$this->loader->add_action( 'wp_ajax_get_latest_sync', $plugin_admin, 'ajax_get_latest_sync' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.9.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.9.0
	 * @return    CCB_Core_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

}
