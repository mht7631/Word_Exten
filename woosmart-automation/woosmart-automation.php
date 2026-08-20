<?php
/**
 * Plugin Name: WooSmart Automation
 * Plugin URI: http://localhost/woosmart/
 * Description: WooCommerce automation toolkit for WordPress.
 * Version: 1.0.0
 * Author: WooSmart
 * Text Domain: woosmart-automation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load plugin classes.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-core.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-automation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-triggers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-post-types.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-automation-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-condition-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-action-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-execution-engine.php';


/**
 * Initialize WooSmart Automation.
 *
 * @return void
 */
function woosmart_automation_init() {

    new WooSmart_Core();
    new WooSmart_Admin();
    new WooSmart_Automation();
    new WooSmart_Condition_Engine();
    new WooSmart_Execution_Engine();
    new WooSmart_Triggers();
    new WooSmart_Post_Types();
    new WooSmart_Automation_Manager();
}

add_action(
    'plugins_loaded',
    'woosmart_automation_init'
);


/**
 * Runs when the plugin is activated.
 *
 * @return void
 */
function woosmart_automation_activate() {

    update_option(
        'woosmart_automation_status',
        'Plugin activated successfully'
    );
}

register_activation_hook(
    __FILE__,
    'woosmart_automation_activate'
);