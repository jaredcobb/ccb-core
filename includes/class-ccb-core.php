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

		// For environments that do not support Sodium (usually PHP < 7.2) use a legacy class.
		if ( ! function_exists( 'sodium_crypto_secretbox' ) || ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			// Encryption class to provide better security and ease of use.
			require_once CCB_CORE_PATH . 'lib/class-ccb-core-vendor-encryption.php';
		}

		// A generic helper class with commonly used mehtods.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-helpers.php';

		// The classes that define options and settings for the plugin.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-page.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-section.php';
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-settings-field.php';

		// The class that handles communication with the CCB API.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-api.php';

		// The class that handles synchronization logic.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-synchronizer.php';

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

		// Admin AJAX methods.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-admin-ajax.php';

		// Cron Management.
		require_once CCB_CORE_PATH . 'includes/class-ccb-core-cron.php';

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
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );

		// Check the plugin / database version and run any required upgrades.
		add_action( 'plugins_loaded', [ $this, 'check_version' ] );

		// Plugin settings, menus, options.
		add_filter( 'plugin_action_links_' . CCB_CORE_BASENAME, [ $this, 'add_settings_link' ] );

		// Setup settings pages.
		add_action( 'admin_menu', [ $this, 'initialize_settings_menu' ] );
		add_action( 'admin_init', [ $this, 'initialize_settings' ] );

		// Callback for after the options are saved.
		add_action( 'update_option_ccb_core_settings', [ $this, 'updated_options' ], 10, 2 );

		// Determine if the rewrite rules need to be flushed.
		add_action( 'init', [ $this, 'check_rewrite_rules' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

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
	 * Check the current plugin version and kick off any applicable upgrades.
	 *
	 * @return void
	 */
	public function check_version() {
		$current_version = get_option( 'ccb_core_version' );

		// We are currently up to date.
		if ( version_compare( $current_version, CCB_CORE_VERSION, '>=' ) ) {
			return;
		}

		// Upgrade to version 1.0.0.
		if ( version_compare( $current_version, '1.0.0', '<' ) ) {
			$this->upgrade_to_1_0_0();
		}

		// Upgrade to version 1.0.7.
		if ( version_compare( $current_version, '1.0.7', '<' ) ) {
			$this->upgrade_to_1_0_7();
		}

		// Update the DB version.
		update_option( 'ccb_core_version', CCB_CORE_VERSION );
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
			/**
			 * Defines the capability that is required for the user
			 * to access the settings page.
			 *
			 * @since 1.0.0
			 *
			 * @param string $capability The capability required to access the page.
			 */
			apply_filters( 'ccb_core_settings_capability', 'manage_options' ),
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
				apply_filters( 'ccb_core_settings_capability', 'manage_options' ),
				$page_id,
				[
					$settings_page,
					'render_page',
				]
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

			register_setting( $page_id, 'ccb_core_settings', [ $settings, 'validate_settings' ] );

			foreach ( $page['sections'] as $section_id => $section ) {

				$settings_section = new CCB_Core_Settings_Section( $section_id, $section );
				add_settings_section(
					$section_id,
					$section['section_title'],
					[
						$settings_section,
						'render_section',
					],
					$page_id
				);

				if ( ! empty( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field_id => $field ) {

						$settings_field = new CCB_Core_Settings_Field( $field_id, $field );
						add_settings_field(
							$field_id,
							$field['field_title'],
							[
								$settings_field,
								'render_field',
							],
							$page_id,
							$section_id
						);

					}
				}

			}

		}

	}

	/**
	 * After the options are saved, check to see if we
	 * should flush the rewrite rules.
	 *
	 * @param    array $old_value The previous option value.
	 * @param    array $value The new option value.
	 * @access   public
	 * @since    1.0.0
	 * @return   void
	 */
	public function updated_options( $old_value, $value ) {

		// Create a collection of settings that, if they change, should
		// trigger a flush_rewrite_rules event.
		$setting_array = [
			'groups_enabled',
			'groups_slug',
			'calendar_enabled',
			'calendar_slug',
		];

		foreach ( $setting_array as $setting ) {
			if ( isset( $value[ $setting ] ) ) {
				if ( ! isset( $old_value[ $setting ] ) || $value[ $setting ] !== $old_value[ $setting ] ) {
					// At least one option requires a flush, so set the transient and return.
					set_transient( 'ccb_core_flush_rewrite_rules', true );
					return;
				}
			}
		}

	}

	/**
	 * Checks for a flag that may have been previously
	 * set in order to flush the rewrite rules.
	 *
	 * @return void
	 */
	public function check_rewrite_rules() {
		if ( get_transient( 'ccb_core_flush_rewrite_rules' ) ) {
			delete_transient( 'ccb_core_flush_rewrite_rules' );
			flush_rewrite_rules();
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
			wp_enqueue_style( 'ccb-core', CCB_CORE_URL . 'css/ccb-core-admin.css', [], CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'switchery', CCB_CORE_URL . 'css/vendor/switchery.min.css', [], CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'powerange', CCB_CORE_URL . 'css/vendor/powerange.min.css', [], CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'picker', CCB_CORE_URL . 'css/vendor/default.css', [], CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'picker-date', CCB_CORE_URL . 'css/vendor/default.date.css', [], CCB_CORE_VERSION, 'all' );
			wp_enqueue_style( 'tipr', CCB_CORE_URL . 'css/vendor/tipr.css', [], CCB_CORE_VERSION, 'all' );
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
			wp_enqueue_script( 'ccb-core', CCB_CORE_URL . 'js/ccb-core-admin.js', [ 'jquery' ], CCB_CORE_VERSION, false );
			wp_enqueue_script( 'switchery', CCB_CORE_URL . 'js/vendor/switchery.min.js', [ 'jquery' ], CCB_CORE_VERSION, false );
			wp_enqueue_script( 'powerange', CCB_CORE_URL . 'js/vendor/powerange.min.js', [ 'jquery' ], CCB_CORE_VERSION, false );
			wp_enqueue_script( 'picker', CCB_CORE_URL . 'js/vendor/picker.js', [ 'jquery' ], CCB_CORE_VERSION, false );
			wp_enqueue_script( 'picker-date', CCB_CORE_URL . 'js/vendor/picker.date.js', [ 'picker' ], CCB_CORE_VERSION, false );
			wp_enqueue_script( 'tipr', CCB_CORE_URL . 'js/vendor/tipr.min.js', [ 'jquery' ], CCB_CORE_VERSION, false );
			wp_localize_script(
				'ccb-core',
				'CCB_CORE_SETTINGS',
				[
					'nonce' => wp_create_nonce( 'ccb_core_nonce' ),
					'translations' => [
						'credentialsSuccessful' => esc_html__( 'The credentials were successfully authenticated.', 'ccb-core' ),
						'credentialsFailed' => esc_html__( 'The credentials failed authentication', 'ccb-core' ),
						'syncInProgress' => esc_html__( 'Syncronization in progress... You can safely navigate away from this page while we work in the background.', 'ccb-core' ),
					],
				]
			);
		}

	}

	/**
	 * Converts any legacy options to the new format
	 *
	 * @return void
	 */
	private function upgrade_to_1_0_0() {
		$current_options = CCB_Core_Helpers::instance()->get_options();
		$updated_options = [];
		$options_hash = [
			'subdomain' => 'subdomain',
			'credentials' => 'credentials',
			'groups-enabled' => 'groups_enabled',
			'groups-name' => 'groups_name',
			'groups-slug' => 'groups_slug',
			'groups-import-images' => 'groups_import_images',
			'groups-advanced' => 'groups_advanced',
			'groups-exclude-from-search' => 'groups_exclude_from_search',
			'groups-publicly-queryable' => 'groups_publicly_queryable',
			'groups-show-ui' => 'groups_show_ui',
			'groups-show-in-nav-menus' => 'groups_show_in_nav_menus',
			'calendar-enabled' => 'calendar_enabled',
			'calendar-name' => 'calendar_name',
			'calendar-slug' => 'calendar_slug',
			'calendar-advanced' => 'calendar_advanced',
			'calendar-date-range-type' => 'calendar_date_range_type',
			'calendar-relative-weeks-past' => 'calendar_relative_weeks_past',
			'calendar-relative-weeks-future' => 'calendar_relative_weeks_future',
			'calendar-specific-start' => 'calendar_specific_start',
			'calendar-specific-end' => 'calendar_specific_end',
			'calendar-exclude-from-search' => 'calendar_exclude_from_search',
			'calendar-publicly-queryable' => 'calendar_publicly_queryable',
			'calendar-show-ui' => 'calendar_show_ui',
			'calendar-show-in-nav-menus' => 'calendar_show_in_nav_menus',
		];

		if ( ! empty( $current_options ) ) {
			foreach ( $options_hash as $old => $new ) {
				if ( isset( $current_options[ $old ] ) ) {
					$updated_options[ $new ] = $current_options[ $old ];
				}
			}
			update_option( 'ccb_core_settings', $updated_options );
		}
	}

	/**
	 * Decrypts any existing API password using the old
	 * scheme and encrypts it back using the current method.
	 *
	 * @return void
	 */
	private function upgrade_to_1_0_7() {
		// If mcrypt isn't installed it doesn't matter, we cannot
		// decrypt any existing passwords.
		if ( ! function_exists( 'mcrypt_decrypt' ) ) {
			return;
		}

		// Ensure the legacy encryption class is loaded regardless of
		// the current version of PHP running.
		require_once CCB_CORE_PATH . 'lib/class-ccb-core-vendor-encryption.php';

		$current_options = CCB_Core_Helpers::instance()->get_options();
		$decrypted_value = false;
		if ( ! empty( $current_options['credentials']['password'] ) ) {
			$key = wp_salt() . md5( 'ccb-core' );
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$decrypted_value = $e->decrypt( base64_decode( $current_options['credentials']['password'] ), $key );
			} catch ( Exception $ex ) {
				$decrypted_value = false;
			}

			$encrypted_value = CCB_Core_Helpers::instance()->encrypt( $decrypted_value );
			if ( ! is_wp_error( $encrypted_value ) ) {
				$current_options['credentials']['password'] = $encrypted_value;
			} else {
				$current_options['credentials']['password'] = '';
			}
			update_option( 'ccb_core_settings', $current_options );
		}
	}
}
