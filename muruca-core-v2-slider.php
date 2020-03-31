<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://netseven.it
 * @since             1.0.0
 * @package           Muruca_Core_V2_Transcription
 *
 * @wordpress-plugin
 * Plugin Name:       Muruca Core 2 Slider
 * Plugin URI:        http://netseven.it
 * Description:       This plugin is an extesion of Muruca Core v2 plugin to configure the frontend slider
 * Author:            Netseven
 * Author URI:        http://netseven.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       muruca-core-v2-slider
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'MURUCA_CORE_SLIDER_PLUGIN_NAME', 'muruca-core-v2-slider' );
define( 'MURUCA_CORE_SLIDER_PLUGIN_DIR', __DIR__ );


/**M
 * The code that runs during plugin activation.
 * This action is documented in includes/class-muruca-core-v2-activator.php
 */
function activate_muruca_core_v2_slider() {
    if ( !is_plugin_active( 'muruca-core-v2/muruca-core-v2.php' ) ) {
        // Deactivate the plugin.
        deactivate_plugins( plugin_basename( __FILE__ ) );
        // Throw an error in the WordPress admin console.
        $error_message = '<p>' . esc_html__( 'This plugin requires Muruca Core v2') . esc_html__( ' plugin to be active.' ) . '</p>';
        die( $error_message ); // WPCS: XSS ok.
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-muruca-core-v2-deactivator.php
 */
function deactivate_muruca_core_v2_slider() {
}

register_activation_hook( __FILE__, 'activate_muruca_core_v2_slider' );
register_deactivation_hook( __FILE__, 'deactivate_muruca_core_v2_slider' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-muruca-core-v2-slider.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_muruca_core_v2_slider() {
    $plugin = new Muruca_Core_V2_slider();
    $plugin->run();
}

run_muruca_core_v2_slider();