<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post types class for WooSmart Automation.
 */
class WooSmart_Post_Types {

    /**
     * Initialize post types.
     */
    public function __construct() {

        add_action(
            'init',
            array( $this, 'register_automation_post_type' )
        );
    }

    /**
     * Register WooSmart Automation post type.
     *
     * @return void
     */
    public function register_automation_post_type() {

        $labels = array(
            'name'                  => 'Automations',
            'singular_name'         => 'Automation',
            'menu_name'             => 'Automations',
            'name_admin_bar'        => 'Automation',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Automation',
            'new_item'              => 'New Automation',
            'edit_item'             => 'Edit Automation',
            'view_item'             => 'View Automation',
            'all_items'             => 'All Automations',
            'search_items'          => 'Search Automations',
            'not_found'             => 'No automations found.',
            'not_found_in_trash'    => 'No automations found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'show_in_admin_bar'  => false,
            'show_in_nav_menus'  => false,
            'exclude_from_search'=> true,
            'publicly_queryable' => false,
            'has_archive'        => false,
            'rewrite'            => false,
            'query_var'          => false,
            'supports'           => array(
                'title',
            ),
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        );

        register_post_type(
            'woosmart_automation',
            $args
        );
    }
}