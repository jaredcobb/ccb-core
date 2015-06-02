<?php
/**
 * Church Community Builder Core API
 *
 * @link              http://jaredcobb.com/ccb-core
 * @since             0.9.0
 * @package           CCB_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Church Community Builder Core API
 * Plugin URI:        http://jaredcobb.com/ccb-core/
 * Description:       A plugin to provide a core integration of the Church Community Builder API into WordPress custom post types
 * Version:           0.9.0
 * Author:            Jared Cobb
 * Author URI:        http://jaredcobb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ccb-core
 * Domain Path:       /languages
 */

// do not allow direct access to this file
if ( ! defined( 'WPINC' ) ) {
	die;
}

// parent class for entire plugin (name, version, other helpful properties and utility methods)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ccb-core-plugin.php';

// code that runs during plugin activation
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ccb-core-activator.php';
register_activation_hook( __FILE__, array( 'CCB_Core_Activator', 'activate' ) );

// internationalization, dashboard-specific hooks, and public-facing site hooks.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ccb-core.php';

/**
 * Begin execution of the plugin.
 *
 * @since    0.9.0
 */
function run_ccb_core() {

	$plugin_basename = plugin_basename( __FILE__ );
	$plugin = new CCB_Core( $plugin_basename );
	$plugin->run();

}
run_ccb_core();
