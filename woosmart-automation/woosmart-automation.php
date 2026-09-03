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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-currency.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-core.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-notification-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-automation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-triggers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-post-types.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-automation-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-condition-registry.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-condition-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-action-registry.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-action-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-execution-history.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-execution-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-execution-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-priority-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosmart-conflict-detector.php';


/**
 * Initialize WooSmart Automation.
 *
 * @return void
 */
function woosmart_automation_init() {

    /*
     * Core plugin services.
     */
    new WooSmart_Core();
    new WooSmart_Admin();
    new WooSmart_Notification_Settings();
    new WooSmart_Post_Types();
    new WooSmart_Automation_Manager();

    /*
     * Create shared automation services.
     */
    $condition_engine =
        new WooSmart_Condition_Engine();

    $action_engine =
        new WooSmart_Action_Engine();

    /*
     * Create one shared Execution History service.
     *
     * Both the Execution Engine and the admin history page
     * use the same storage service and database table.
     */
    $execution_history =
        new WooSmart_Execution_History();

    /*
     * Create one execution engine using
     * the shared condition, action, and history services.
     */
    $execution_engine =
        new WooSmart_Execution_Engine(
            $condition_engine,
            $action_engine,
            $execution_history
        );

    /*
     * Pass the shared execution engine to
     * the trigger system.
     */
    new WooSmart_Triggers(
        $execution_engine
    );

    /*
     * Execution History and Execution Policy admin UI.
     */
    new WooSmart_Execution_Admin(
        $execution_history
    );

    /*
     * Automation Priority admin UI.
     */
    new WooSmart_Priority_Admin();

    /*
     * Cross-Automation Conflict Detection.
     *
     * This detector is advisory and never blocks execution.
     */
    new WooSmart_Conflict_Detector();

    /*
     * Main automation class.
     */
    new WooSmart_Automation();
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

    /*
     * Register custom post types before flushing rewrite rules.
     */
    if (
        class_exists(
            'WooSmart_Post_Types'
        )
    ) {

        $post_types =
            new WooSmart_Post_Types();

        $post_types->register_automation_post_type();
    }

    /*
     * Ensure Execution History storage is available after activation.
     */
    if (
        class_exists(
            'WooSmart_Execution_History'
        )
    ) {

        new WooSmart_Execution_History();
    }

    flush_rewrite_rules();
}

register_activation_hook(
    __FILE__,
    'woosmart_automation_activate'
);


/**
 * Runs when the plugin is deactivated.
 *
 * @return void
 */
function woosmart_automation_deactivate() {

    flush_rewrite_rules();
}

register_deactivation_hook(
    __FILE__,
    'woosmart_automation_deactivate'
);
