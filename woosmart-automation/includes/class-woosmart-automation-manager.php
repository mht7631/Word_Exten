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
                'نام اتوماسیون و رویداد الزامی است.'
            );
        }

        $trigger_error =
            $this->validate_trigger(
                $trigger
            );

        if ( is_wp_error( $trigger_error ) ) {
            wp_die(
                esc_html(
                    $trigger_error->get_error_message()
                )
            );
        }

        $conditions =
            $this->get_conditions_from_request();

        $actions =
            $this->get_actions_from_request();

        $conditions_error =
            $this->validate_conditions(
                $conditions
            );

        if ( is_wp_error( $conditions_error ) ) {
            wp_die(
                esc_html(
                    $conditions_error->get_error_message()
                )
            );
        }

        $actions_error =
            $this->validate_actions(
                $actions
            );

        if ( is_wp_error( $actions_error ) ) {
            wp_die(
                esc_html(
                    $actions_error->get_error_message()
                )
            );
        }

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
            wp_die( 'شناسه اتوماسیون نامعتبر است.' );
        }

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'اتوماسیون نامعتبر است.' );
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
                'نام اتوماسیون و رویداد الزامی است.'
            );
        }

        $trigger_error =
            $this->validate_trigger(
                $trigger
            );

        if ( is_wp_error( $trigger_error ) ) {
            wp_die(
                esc_html(
                    $trigger_error->get_error_message()
                )
            );
        }

        $conditions =
            $this->get_conditions_from_request();

        $actions =
            $this->get_actions_from_request();

        $conditions_error =
            $this->validate_conditions(
                $conditions
            );

        if ( is_wp_error( $conditions_error ) ) {
            wp_die(
                esc_html(
                    $conditions_error->get_error_message()
                )
            );
        }

        $actions_error =
            $this->validate_actions(
                $actions
            );

        if ( is_wp_error( $actions_error ) ) {
            wp_die(
                esc_html(
                    $actions_error->get_error_message()
                )
            );
        }

        $update_result = wp_update_post(
            array(
                'ID'         => $automation_id,
                'post_title' => $name,
            ),
            true
        );

        if ( is_wp_error( $update_result ) ) {
            wp_die(
                esc_html(
                    $update_result->get_error_message()
                )
            );
        }

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
            wp_die( 'شناسه اتوماسیون نامعتبر است.' );
        }

        check_admin_referer(
            'woosmart_toggle_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'اتوماسیون نامعتبر است.' );
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

        if ( 'active' === $new_status ) {

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

            $trigger_error =
                $this->validate_trigger(
                    $trigger
                );

            if ( is_wp_error( $trigger_error ) ) {
                wp_die(
                    esc_html(
                        'امکان فعال‌سازی اتوماسیون وجود ندارد: ' .
                        $trigger_error->get_error_message()
                    )
                );
            }

            $conditions_error =
                $this->validate_conditions(
                    $conditions
                );

            if ( is_wp_error( $conditions_error ) ) {
                wp_die(
                    esc_html(
                        'امکان فعال‌سازی اتوماسیون وجود ندارد: ' .
                        $conditions_error->get_error_message()
                    )
                );
            }

            $actions_error =
                $this->validate_actions(
                    $actions
                );

            if ( is_wp_error( $actions_error ) ) {
                wp_die(
                    esc_html(
                        'امکان فعال‌سازی اتوماسیون وجود ندارد: ' .
                        $actions_error->get_error_message()
                    )
                );
            }
        }

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
            wp_die( 'شناسه اتوماسیون نامعتبر است.' );
        }

        check_admin_referer(
            'woosmart_delete_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'اتوماسیون نامعتبر است.' );
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
            wp_die( 'شناسه اتوماسیون نامعتبر است.' );
        }

        check_admin_referer(
            'woosmart_duplicate_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die( 'اتوماسیون نامعتبر است.' );
        }

        $automation = get_post(
            $automation_id
        );

        if ( ! $automation ) {
            wp_die( 'اتوماسیون پیدا نشد.' );
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

            $value = str_replace(
                ',',
                '',
                $value
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

        if (
            'change_order_status' ===
            $action_type
        ) {

            $action_status = isset(
                $_POST['action_order_status']
            )
                ? sanitize_key(
                    wp_unslash(
                        $_POST['action_order_status']
                    )
                )
                : '';

            if ( ! empty( $action_status ) ) {

                $actions[] = array(
                    'type'   => 'change_order_status',
                    'status' => $action_status,
                );
            }

        } elseif (
            'notify_admin' ===
            $action_type
        ) {

            $subject = isset(
                $_POST['action_email_subject']
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['action_email_subject']
                    )
                )
                : '';

            $message = isset(
                $_POST['action_email_message']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['action_email_message']
                    )
                )
                : '';

            $actions[] = array(
                'type'    => 'notify_admin',
                'subject' => $subject,
                'message' => $message,
            );
        }

        return $actions;
    }

    /**
     * Validate trigger.
     *
     * @param string $trigger Trigger key.
     *
     * @return true|WP_Error
     */
    private function validate_trigger( $trigger ) {

        $allowed_triggers = array(
            'order_created',
        );

        if (
            ! in_array(
                $trigger,
                $allowed_triggers,
                true
            )
        ) {
            return new WP_Error(
                'invalid_trigger',
                'رویداد انتخاب‌شده معتبر نیست.'
            );
        }

        return true;
    }

    /**
     * Validate conditions.
     *
     * @param array $conditions Conditions.
     *
     * @return true|WP_Error
     */
    private function validate_conditions(
        $conditions
    ) {

        if ( ! is_array( $conditions ) ) {
            return new WP_Error(
                'invalid_conditions',
                'ساختار شرایط نامعتبر است.'
            );
        }

        if ( empty( $conditions ) ) {
            return true;
        }

        $allowed_fields = array(
            'order_total',
        );

        $allowed_operators = array(
            'is_equal',
            'is_not_equal',
            'greater_than',
            'greater_than_or_equal',
            'less_than',
            'less_than_or_equal',
        );

        foreach ( $conditions as $condition ) {

            if ( ! is_array( $condition ) ) {
                return new WP_Error(
                    'invalid_condition',
                    'یکی از شرایط ساختار نامعتبر دارد.'
                );
            }

            $field = isset(
                $condition['field']
            )
                ? sanitize_key(
                    $condition['field']
                )
                : '';

            $operator = isset(
                $condition['operator']
            )
                ? sanitize_key(
                    $condition['operator']
                )
                : '';

            $value = isset(
                $condition['value']
            )
                ? $condition['value']
                : '';

            if (
                ! in_array(
                    $field,
                    $allowed_fields,
                    true
                )
            ) {
                return new WP_Error(
                    'invalid_condition_field',
                    'فیلد شرط انتخاب‌شده معتبر نیست.'
                );
            }

            if (
                ! in_array(
                    $operator,
                    $allowed_operators,
                    true
                )
            ) {
                return new WP_Error(
                    'invalid_condition_operator',
                    'عملگر شرط انتخاب‌شده معتبر نیست.'
                );
            }

            $value = str_replace(
                ',',
                '',
                (string) $value
            );

            if (
                '' === $value ||
                ! is_numeric( $value )
            ) {
                return new WP_Error(
                    'invalid_condition_value',
                    'مقدار شرط باید یک عدد معتبر باشد.'
                );
            }

            if ( (float) $value < 0 ) {
                return new WP_Error(
                    'negative_condition_value',
                    'مقدار شرط نمی‌تواند منفی باشد.'
                );
            }
        }

        return true;
    }

    /**
     * Validate actions.
     *
     * @param array $actions Actions.
     *
     * @return true|WP_Error
     */
    private function validate_actions(
        $actions
    ) {

        if ( ! is_array( $actions ) ) {
            return new WP_Error(
                'invalid_actions',
                'ساختار عملیات نامعتبر است.'
            );
        }

        if ( empty( $actions ) ) {
            return new WP_Error(
                'missing_action',
                'اتوماسیون باید حداقل یک عملیات داشته باشد.'
            );
        }

        $allowed_action_types = array(
            'change_order_status',
            'notify_admin',
        );

        foreach ( $actions as $action ) {

            if ( ! is_array( $action ) ) {
                return new WP_Error(
                    'invalid_action',
                    'یکی از عملیات‌ها ساختار نامعتبر دارد.'
                );
            }

            $action_type = isset(
                $action['type']
            )
                ? sanitize_key(
                    $action['type']
                )
                : '';

            if (
                ! in_array(
                    $action_type,
                    $allowed_action_types,
                    true
                )
            ) {
                return new WP_Error(
                    'invalid_action_type',
                    'نوع عملیات انتخاب‌شده معتبر نیست.'
                );
            }

            if (
                'change_order_status' ===
                $action_type
            ) {

                $status = isset(
                    $action['status']
                )
                    ? sanitize_key(
                        $action['status']
                    )
                    : '';

                if ( empty( $status ) ) {
                    return new WP_Error(
                        'missing_order_status',
                        'وضعیت سفارش برای عملیات الزامی است.'
                    );
                }

                if (
                    ! $this->is_valid_order_status(
                        $status
                    )
                ) {
                    return new WP_Error(
                        'invalid_order_status',
                        'وضعیت سفارش انتخاب‌شده معتبر نیست.'
                    );
                }
            }

            if (
                'notify_admin' ===
                $action_type
            ) {

                $subject = isset(
                    $action['subject']
                )
                    ? sanitize_text_field(
                        $action['subject']
                    )
                    : '';

                $message = isset(
                    $action['message']
                )
                    ? sanitize_textarea_field(
                        $action['message']
                    )
                    : '';

                if ( empty( $subject ) ) {
                    return new WP_Error(
                        'missing_email_subject',
                        'موضوع ایمیل اعلان الزامی است.'
                    );
                }

                if ( empty( $message ) ) {
                    return new WP_Error(
                        'missing_email_message',
                        'متن ایمیل اعلان الزامی است.'
                    );
                }

                $admin_email = sanitize_email(
                    get_option(
                        'admin_email',
                        ''
                    )
                );

                if (
                    empty( $admin_email ) ||
                    ! is_email( $admin_email )
                ) {
                    return new WP_Error(
                        'invalid_admin_email',
                        'ایمیل مدیر فروشگاه در تنظیمات وردپرس معتبر نیست.'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Check whether an order status is valid.
     *
     * @param string $status Order status slug.
     *
     * @return bool
     */
    private function is_valid_order_status(
        $status
    ) {

        if (
            function_exists(
                'wc_get_order_statuses'
            )
        ) {

            $statuses =
                wc_get_order_statuses();

            $status_key = 'wc-' . $status;

            if (
                isset(
                    $statuses[ $status_key ]
                )
            ) {
                return true;
            }
        }

        $standard_statuses = array(
            'pending',
            'processing',
            'on-hold',
            'completed',
            'cancelled',
            'refunded',
            'failed',
        );

        return in_array(
            $status,
            $standard_statuses,
            true
        );
    }

    /**
     * Verify administrator access.
     *
     * @return void
     */
    private function verify_admin_access() {

        if ( ! current_user_can( 'manage_options' ) ) {

            wp_die(
                'شما اجازه انجام این عملیات را ندارید.'
            );
        }
    }
}
