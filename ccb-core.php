<?php
/**
 * Church Community Builder Core API
 *
 * @link              https://www.wpccb.com
 * @since             0.9.0
 * @package           CCB_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Church Community Builder Core API
 * Plugin URI:        https://www.wpccb.com
 * Description:       A plugin to provide a core integration of the Church Community Builder API into WordPress custom post types
 * Version:           1.0.7
 * Author:            Jared Cobb
 * Author URI:        https://www.jaredcobb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ccb-core
 * Domain Path:       /languages
 */

// Do not allow direct access to this file.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'CCB_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCB_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'CCB_CORE_BASENAME', plugin_basename( __FILE__ ) );
define( 'CCB_CORE_VERSION', '1.0.7' );

// Check minimum requirements before proceeding.
require_once CCB_CORE_PATH . 'includes/class-ccb-core-requirements.php';
$ccb_core_requirements = new CCB_Core_Requirements();

if ( $ccb_core_requirements->requirements_met ) {

	// Code that runs during plugin activation and deactivation.
	require_once CCB_CORE_PATH . 'includes/class-ccb-core-activator.php';
	register_activation_hook( __FILE__, array( 'CCB_Core_Activator', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'CCB_Core_Activator', 'deactivate' ) );

	// Internationalization, dashboard-specific hooks, and public-facing site hooks.
	require_once CCB_CORE_PATH . 'includes/class-ccb-core.php';

	/**
	 * Begin execution of the plugin.
	 *
	 * @access public
	 * @return void
	 */
	function run_ccb_core() {
		$plugin = new CCB_Core();

	}
	run_ccb_core();

}
