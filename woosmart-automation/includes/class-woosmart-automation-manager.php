<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Automation Manager class.
 */
class WooSmart_Automation_Manager {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Initialize Automation Manager.
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

        add_action(
            'admin_post_woosmart_save_automation',
            array( $this, 'save_automation' )
        );

        add_action(
            'admin_post_woosmart_update_automation',
            array( $this, 'update_automation' )
        );

        add_action(
            'admin_post_woosmart_toggle_automation',
            array( $this, 'toggle_automation' )
        );

        add_action(
            'admin_post_woosmart_delete_automation',
            array( $this, 'delete_automation' )
        );

        add_action(
            'admin_post_woosmart_duplicate_automation',
            array( $this, 'duplicate_automation' )
        );
    }

    /**
     * Save new automation.
     *
     * @return void
     */
    public function save_automation() {

        $this->verify_admin_access();

        check_admin_referer(
            'woosmart_save_automation',
            'woosmart_automation_nonce'
        );

        $name = isset( $_POST['automation_name'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['automation_name'] )
            )
            : '';

        $trigger = isset( $_POST['automation_trigger'] )
            ? sanitize_key(
                wp_unslash( $_POST['automation_trigger'] )
            )
            : '';

        if ( empty( $name ) || empty( $trigger ) ) {
            wp_die(
                'Automation name and trigger are required.'
            );
        }

        $conditions =
            $this->get_conditions_from_request();

        $actions =
            $this->get_actions_from_request();

        $automation_id = wp_insert_post(
            array(
                'post_type'   => 'woosmart_automation',
                'post_status' => 'publish',
                'post_title'  => $name,
            ),
            true
        );

        if ( is_wp_error( $automation_id ) ) {
            wp_die(
                esc_html(
                    $automation_id->get_error_message()
                )
            );
        }

        update_post_meta(
            $automation_id,
            '_woosmart_status',
            'active'
        );

        update_post_meta(
            $automation_id,
            '_woosmart_trigger',
            $trigger
        );

        update_post_meta(
            $automation_id,
            '_woosmart_conditions',
            $conditions
        );

        update_post_meta(
            $automation_id,
            '_woosmart_actions',
            $actions
        );

        $this->logger->log(
            'automation_created',
            'A new automation was created.',
            array(
                'automation_id' => $automation_id,
                'trigger'       => $trigger,
                'conditions'    => $conditions,
                'actions'       => $actions,
            )
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=woosmart-automations'
            )
        );

        exit;
    }

    /**
     * Update existing automation.
     *
     * @return void
     */
    public function update_automation() {

        $this->verify_admin_access();

        check_admin_referer(
            'woosmart_update_automation',
            'woosmart_automation_nonce'
        );

        $automation_id = isset( $_POST['automation_id'] )
            ? absint( $_POST['automation_id'] )
            : 0;

        if ( ! $automation_id ) {
            wp_die( 'Invalid automation ID.' );
        }

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'Invalid automation.' );
        }

        $name = isset( $_POST['automation_name'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['automation_name'] )
            )
            : '';

        $trigger = isset( $_POST['automation_trigger'] )
            ? sanitize_key(
                wp_unslash( $_POST['automation_trigger'] )
            )
            : '';

        if ( empty( $name ) || empty( $trigger ) ) {
            wp_die(
                'Automation name and trigger are required.'
            );
        }

        $conditions =
            $this->get_conditions_from_request();

        $actions =
            $this->get_actions_from_request();

        wp_update_post(
            array(
                'ID'         => $automation_id,
                'post_title' => $name,
            )
        );

        update_post_meta(
            $automation_id,
            '_woosmart_trigger',
            $trigger
        );

        update_post_meta(
            $automation_id,
            '_woosmart_conditions',
            $conditions
        );

        update_post_meta(
            $automation_id,
            '_woosmart_actions',
            $actions
        );

        $this->logger->log(
            'automation_updated',
            'Automation was updated.',
            array(
                'automation_id' => $automation_id,
                'trigger'       => $trigger,
                'conditions'    => $conditions,
                'actions'       => $actions,
            )
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=woosmart-automations'
            )
        );

        exit;
    }

    /**
     * Toggle automation.
     *
     * @return void
     */
    public function toggle_automation() {

        $this->verify_admin_access();

        $automation_id = isset( $_GET['automation_id'] )
            ? absint( $_GET['automation_id'] )
            : 0;

        if ( ! $automation_id ) {
            wp_die( 'Invalid automation ID.' );
        }

        check_admin_referer(
            'woosmart_toggle_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'Invalid automation.' );
        }

        $current_status = get_post_meta(
            $automation_id,
            '_woosmart_status',
            true
        );

        $new_status =
            ( 'active' === $current_status )
                ? 'inactive'
                : 'active';

        update_post_meta(
            $automation_id,
            '_woosmart_status',
            $new_status
        );

        $this->logger->log(
            'automation_status_changed',
            'Automation status was changed.',
            array(
                'automation_id' => $automation_id,
                'status'        => $new_status,
            )
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=woosmart-automations'
            )
        );

        exit;
    }

    /**
     * Delete automation.
     *
     * @return void
     */
    public function delete_automation() {

        $this->verify_admin_access();

        $automation_id = isset( $_GET['automation_id'] )
            ? absint( $_GET['automation_id'] )
            : 0;

        if ( ! $automation_id ) {
            wp_die( 'Invalid automation ID.' );
        }

        check_admin_referer(
            'woosmart_delete_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'Invalid automation.' );
        }

        wp_trash_post( $automation_id );

        $this->logger->log(
            'automation_deleted',
            'Automation was moved to trash.',
            array(
                'automation_id' => $automation_id,
            )
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=woosmart-automations'
            )
        );

        exit;
    }

    /**
     * Duplicate automation.
     *
     * @return void
     */
    public function duplicate_automation() {

        $this->verify_admin_access();

        $automation_id = isset( $_GET['automation_id'] )
            ? absint( $_GET['automation_id'] )
            : 0;

        if ( ! $automation_id ) {
            wp_die( 'Invalid automation ID.' );
        }

        check_admin_referer(
            'woosmart_duplicate_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'Invalid automation.' );
        }

        $automation = get_post(
            $automation_id
        );

        if ( ! $automation ) {
            wp_die( 'Automation not found.' );
        }

        $new_automation_id = wp_insert_post(
            array(
                'post_type'   => 'woosmart_automation',
                'post_status' => 'publish',
                'post_title'  => $automation->post_title .
                    ' - Copy',
            ),
            true
        );

        if ( is_wp_error( $new_automation_id ) ) {
            wp_die(
                esc_html(
                    $new_automation_id->get_error_message()
                )
            );
        }

        $trigger = get_post_meta(
            $automation_id,
            '_woosmart_trigger',
            true
        );

        $conditions = get_post_meta(
            $automation_id,
            '_woosmart_conditions',
            true
        );

        $actions = get_post_meta(
            $automation_id,
            '_woosmart_actions',
            true
        );

        if ( ! is_array( $conditions ) ) {
            $conditions = array();
        }

        if ( ! is_array( $actions ) ) {
            $actions = array();
        }

        update_post_meta(
            $new_automation_id,
            '_woosmart_status',
            'inactive'
        );

        update_post_meta(
            $new_automation_id,
            '_woosmart_trigger',
            $trigger
        );

        update_post_meta(
            $new_automation_id,
            '_woosmart_conditions',
            $conditions
        );

        update_post_meta(
            $new_automation_id,
            '_woosmart_actions',
            $actions
        );

        $this->logger->log(
            'automation_duplicated',
            'Automation was duplicated.',
            array(
                'source_automation_id' => $automation_id,
                'new_automation_id'    => $new_automation_id,
            )
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=woosmart-automations'
            )
        );

        exit;
    }

    /**
     * Get conditions from request.
     *
     * @return array
     */
    private function get_conditions_from_request() {

        $conditions = array();

        if (
            isset( $_POST['condition_field'] ) &&
            isset( $_POST['condition_operator'] ) &&
            isset( $_POST['condition_value'] )
        ) {

            $field = sanitize_key(
                wp_unslash(
                    $_POST['condition_field']
                )
            );

            $operator = sanitize_key(
                wp_unslash(
                    $_POST['condition_operator']
                )
            );

            $value = sanitize_text_field(
                wp_unslash(
                    $_POST['condition_value']
                )
            );

            if (
                ! empty( $field ) &&
                ! empty( $operator ) &&
                '' !== $value
            ) {

                $conditions[] = array(
                    'field'    => $field,
                    'operator' => $operator,
                    'value'    => $value,
                );
            }
        }

        return $conditions;
    }

    /**
     * Get actions from request.
     *
     * @return array
     */
    private function get_actions_from_request() {

        $actions = array();

        $action_type = isset(
            $_POST['action_type']
        )
            ? sanitize_key(
                wp_unslash(
                    $_POST['action_type']
                )
            )
            : '';

        $action_status = isset(
            $_POST['action_order_status']
        )
            ? sanitize_key(
                wp_unslash(
                    $_POST['action_order_status']
                )
            )
            : '';

        if (
            'change_order_status' === $action_type &&
            ! empty( $action_status )
        ) {

            $actions[] = array(
                'type'   => 'change_order_status',
                'status' => $action_status,
            );
        }

        return $actions;
    }

    /**
     * Verify administrator access.
     *
     * @return void
     */
    private function verify_admin_access() {

        if ( ! current_user_can( 'manage_options' ) ) {

            wp_die(
                'You do not have permission to perform this action.'
            );
        }
    }
}