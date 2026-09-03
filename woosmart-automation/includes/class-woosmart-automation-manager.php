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
     * Condition Registry instance.
     *
     * @var WooSmart_Condition_Registry
     */
    private $condition_registry;

    /**
     * Initialize Automation Manager.
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

        $this->condition_registry =
            new WooSmart_Condition_Registry();

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

        /*
         * Detect Action conflicts.
         *
         * Conflict detection is intentionally non-blocking
         * at this stage. The Automation is still allowed to save,
         * while the detected conflicts are recorded in WooSmart Logs.
         */
        $conflicts =
            $this->detect_action_conflicts(
                $actions
            );

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

        /*
         * Log detected conflicts only after the Automation
         * has been created and a valid Automation ID exists.
         */
        if ( ! empty( $conflicts ) ) {

            $this->log_action_conflicts(
                $automation_id,
                $trigger,
                $conflicts
            );
        }

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
            wp_die(
                'شناسه اتوماسیون نامعتبر است.'
            );
        }

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die(
                'اتوماسیون نامعتبر است.'
            );
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

        /*
         * Detect Action conflicts.
         *
         * Conflicts are currently logged without blocking the save.
         */
        $conflicts =
            $this->detect_action_conflicts(
                $actions
            );

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

        if ( ! empty( $conflicts ) ) {

            $this->log_action_conflicts(
                $automation_id,
                $trigger,
                $conflicts
            );
        }

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
            wp_die(
                'شناسه اتوماسیون نامعتبر است.'
            );
        }

        check_admin_referer(
            'woosmart_toggle_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die(
                'اتوماسیون نامعتبر است.'
            );
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

        /*
         * Validate existing configuration before activation.
         */
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

            /*
             * Conflict detection during activation.
             *
             * Conflicts are logged but activation remains allowed.
             */
            $conflicts =
                $this->detect_action_conflicts(
                    $actions
                );

            if ( ! empty( $conflicts ) ) {

                $this->log_action_conflicts(
                    $automation_id,
                    $trigger,
                    $conflicts
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
            wp_die(
                'شناسه اتوماسیون نامعتبر است.'
            );
        }

        check_admin_referer(
            'woosmart_delete_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die(
                'اتوماسیون نامعتبر است.'
            );
        }

        wp_trash_post(
            $automation_id
        );

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
            wp_die(
                'شناسه اتوماسیون نامعتبر است.'
            );
        }

        check_admin_referer(
            'woosmart_duplicate_automation_' .
            $automation_id
        );

        if (
            'woosmart_automation' !==
            get_post_type( $automation_id )
        ) {
            wp_die(
                'اتوماسیون نامعتبر است.'
            );
        }

        $automation = get_post(
            $automation_id
        );

        if ( ! $automation ) {
            wp_die(
                'اتوماسیون پیدا نشد.'
            );
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
     * Supports:
     *
     * 1. Multiple conditions:
     *
     *    conditions[0][field]
     *    conditions[0][operator]
     *    conditions[0][value]
     *
     * 2. Range condition:
     *
     *    conditions[0][field]
     *    conditions[0][operator] = between
     *    conditions[0][min]
     *    conditions[0][max]
     *
     * 3. Previous single-condition form for backward compatibility:
     *
     *    condition_field
     *    condition_operator
     *    condition_value
     *
     * Range values are stored as:
     *
     * array(
     *     'min' => '1000000',
     *     'max' => '5000000',
     * )
     *
     * @return array
     */
    private function get_conditions_from_request() {

        $conditions = array();

        /*
         * New multiple-condition structure.
         */
        if (
            isset( $_POST['conditions'] ) &&
            is_array( $_POST['conditions'] )
        ) {

            $submitted_conditions =
                wp_unslash(
                    $_POST['conditions']
                );

            foreach (
                $submitted_conditions as $submitted_condition
            ) {

                if (
                    ! is_array(
                        $submitted_condition
                    )
                ) {
                    continue;
                }

                $field = isset(
                    $submitted_condition['field']
                )
                    ? sanitize_key(
                        $submitted_condition['field']
                    )
                    : '';

                $operator = isset(
                    $submitted_condition['operator']
                )
                    ? sanitize_key(
                        $submitted_condition['operator']
                    )
                    : '';

                if (
                    empty( $field ) ||
                    empty( $operator )
                ) {
                    continue;
                }

                /*
                 * Range condition.
                 */
                if (
                    'between' ===
                    $operator
                ) {

                    $minimum =
                        isset(
                            $submitted_condition['min']
                        )
                            ? sanitize_text_field(
                                $submitted_condition['min']
                            )
                            : '';

                    $maximum =
                        isset(
                            $submitted_condition['max']
                        )
                            ? sanitize_text_field(
                                $submitted_condition['max']
                            )
                            : '';

                    $minimum =
                        $this->normalize_numeric_input(
                            $minimum
                        );

                    $maximum =
                        $this->normalize_numeric_input(
                            $maximum
                        );

                    /*
                     * Do not add a completely empty range.
                     * Validation is still responsible for validating
                     * an intentionally incomplete submitted condition.
                     */
                    if (
                        '' === $minimum &&
                        '' === $maximum
                    ) {
                        continue;
                    }

                    $conditions[] = array(
                        'field'    => $field,
                        'operator' => $operator,
                        'value'    => array(
                            'min' =>
                                $minimum,

                            'max' =>
                                $maximum,
                        ),
                    );

                    continue;
                }

                /*
                 * Scalar condition.
                 */
                if (
                    ! isset(
                        $submitted_condition['value']
                    )
                ) {
                    continue;
                }

                $value =
                    sanitize_text_field(
                        $submitted_condition['value']
                    );

                $value =
                    $this->normalize_numeric_input_for_condition(
                        $field,
                        $value
                    );

                if (
                    '' ===
                    trim(
                        (string)
                        $value
                    )
                ) {
                    continue;
                }

                $conditions[] = array(
                    'field'    => $field,
                    'operator' => $operator,
                    'value'    => $value,
                );
            }

            return $conditions;
        }

        /*
         * Backward compatibility with the previous
         * single-condition form.
         */
        if (
            ! isset( $_POST['condition_field'] ) ||
            ! isset( $_POST['condition_operator'] )
        ) {

            return $conditions;
        }

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

        if (
            empty( $field ) ||
            empty( $operator )
        ) {

            return $conditions;
        }

        /*
         * Previous range form.
         */
        if (
            'between' ===
            $operator
        ) {

            $minimum =
                isset(
                    $_POST['condition_value_min']
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_POST['condition_value_min']
                        )
                    )
                    : '';

            $maximum =
                isset(
                    $_POST['condition_value_max']
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_POST['condition_value_max']
                        )
                    )
                    : '';

            $minimum =
                $this->normalize_numeric_input(
                    $minimum
                );

            $maximum =
                $this->normalize_numeric_input(
                    $maximum
                );

            if (
                '' !== $minimum &&
                '' !== $maximum
            ) {

                $conditions[] = array(
                    'field'    => $field,
                    'operator' => $operator,
                    'value'    => array(
                        'min' =>
                            $minimum,

                        'max' =>
                            $maximum,
                    ),
                );
            }

            return $conditions;
        }

        /*
         * Previous scalar form.
         */
        if (
            ! isset(
                $_POST['condition_value']
            )
        ) {

            return $conditions;
        }

        $value = sanitize_text_field(
            wp_unslash(
                $_POST['condition_value']
            )
        );

        $value =
            $this->normalize_numeric_input_for_condition(
                $field,
                $value
            );

        if (
            '' !== $value
        ) {

            $conditions[] = array(
                'field'    => $field,
                'operator' => $operator,
                'value'    => $value,
            );
        }

        return $conditions;
    }

    /**
     * Normalize a numeric input.
     *
     * @param mixed $value Numeric input.
     *
     * @return string
     */
    private function normalize_numeric_input(
        $value
    ) {

        $value =
            (string) $value;

        $value =
            str_replace(
                ',',
                '',
                $value
            );

        $value =
            preg_replace(
                '/[^\d.]/',
                '',
                $value
            );

        if (
            null === $value
        ) {

            return '';
        }

        $first_dot =
            strpos(
                $value,
                '.'
            );

        if (
            false !== $first_dot
        ) {

            $value =
                substr(
                    $value,
                    0,
                    $first_dot + 1
                ) .
                str_replace(
                    '.',
                    '',
                    substr(
                        $value,
                        $first_dot + 1
                    )
                );
        }

        return $value;
    }

    /**
     * Normalize condition values while preserving non-numeric values.
     *
     * @param string $field Condition field.
     * @param mixed  $value Condition value.
     *
     * @return mixed
     */
    private function normalize_numeric_input_for_condition(
        $field,
        $value
    ) {

        $definition =
            $this->condition_registry->get(
                $field
            );

        if (
            is_array( $definition ) &&
            isset(
                $definition['value_type']
            ) &&
            'number' ===
                $definition['value_type']
        ) {

            return $this->normalize_numeric_input(
                $value
            );
        }

        return trim(
            (string) $value
        );
    }

    /**
     * Get actions from request.
     *
     * Supports the current array-based Action structure.
     *
     * Also supports the previous single-action form
     * for backward compatibility with existing automations.
     *
     * @return array
     */
    private function get_actions_from_request() {

        $actions = array();

        /*
         * New multiple-action structure.
         */
        if (
            isset( $_POST['actions'] ) &&
            is_array( $_POST['actions'] )
        ) {

            $submitted_actions =
                wp_unslash(
                    $_POST['actions']
                );

            foreach (
                $submitted_actions
                as $submitted_action
            ) {

                if ( ! is_array( $submitted_action ) ) {
                    continue;
                }

                $type = isset(
                    $submitted_action['type']
                )
                    ? sanitize_key(
                        $submitted_action['type']
                    )
                    : '';

                if ( empty( $type ) ) {
                    continue;
                }

                if (
                    'change_order_status' ===
                    $type
                ) {

                    $status = isset(
                        $submitted_action['status']
                    )
                        ? sanitize_key(
                            $submitted_action['status']
                        )
                        : '';

                    if ( empty( $status ) ) {
                        continue;
                    }

                    $actions[] = array(
                        'type'   =>
                            'change_order_status',
                        'status' =>
                            $status,
                    );

                    continue;
                }

                if (
                    'notify_admin' ===
                    $type
                ) {

                    $subject = isset(
                        $submitted_action['subject']
                    )
                        ? sanitize_text_field(
                            $submitted_action['subject']
                        )
                        : '';

                    $message = isset(
                        $submitted_action['message']
                    )
                        ? sanitize_textarea_field(
                            $submitted_action['message']
                        )
                        : '';

                    $actions[] = array(
                        'type' =>
                            'notify_admin',
                        'subject' =>
                            $subject,
                        'message' =>
                            $message,
                    );
                }
            }

            return $actions;
        }

        /*
         * Backward compatibility with the previous
         * single-action form.
         */
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
                    'type'   =>
                        'change_order_status',
                    'status' =>
                        $action_status,
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
                'type' =>
                    'notify_admin',
                'subject' =>
                    $subject,
                'message' =>
                    $message,
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
     * Condition definitions and operators are resolved
     * through the Condition Registry.
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

            /*
             * Condition must exist in the Registry.
             */
            if (
                empty( $field ) ||
                ! $this->condition_registry->has(
                    $field
                )
            ) {
                return new WP_Error(
                    'invalid_condition_field',
                    'فیلد شرط انتخاب‌شده معتبر نیست.'
                );
            }

            /*
             * Operator must exist for the selected condition.
             */
            $operators =
                $this->condition_registry->get_operators(
                    $field
                );

            if (
                empty( $operator ) ||
                ! isset( $operators[ $operator ] )
            ) {
                return new WP_Error(
                    'invalid_condition_operator',
                    'عملگر شرط انتخاب‌شده معتبر نیست.'
                );
            }

            /*
             * Read condition metadata from the Registry.
             */
            $definition =
                $this->condition_registry->get(
                    $field
                );

            if ( ! is_array( $definition ) ) {
                return new WP_Error(
                    'invalid_condition_definition',
                    'تعریف شرط انتخاب‌شده معتبر نیست.'
                );
            }

            /*
             * Validate the value according to the
             * registered condition value type.
             */
            $value_type = isset(
                $definition['value_type']
            )
                ? sanitize_key(
                    $definition['value_type']
                )
                : 'text';

            if ( 'between' === $operator ) {

                /*
                 * Range conditions require an array:
                 *
                 * min
                 * max
                 */
                if (
                    ! is_array(
                        $value
                    )
                ) {

                    return new WP_Error(
                        'invalid_condition_range',
                        'ساختار بازه شرط نامعتبر است.'
                    );
                }

                $minimum =
                    isset(
                        $value['min']
                    )
                        ? $this->normalize_numeric_input(
                            $value['min']
                        )
                        : '';

                $maximum =
                    isset(
                        $value['max']
                    )
                        ? $this->normalize_numeric_input(
                            $value['max']
                        )
                        : '';

                if (
                    '' === $minimum ||
                    '' === $maximum
                ) {

                    return new WP_Error(
                        'incomplete_condition_range',
                        'حداقل و حداکثر بازه باید مشخص شوند.'
                    );
                }

                if (
                    ! is_numeric(
                        $minimum
                    ) ||
                    ! is_numeric(
                        $maximum
                    )
                ) {

                    return new WP_Error(
                        'invalid_condition_range_value',
                        'مقادیر حداقل و حداکثر باید عدد معتبر باشند.'
                    );
                }

                if ( (float) $minimum < 0 ||
                    (float) $maximum < 0
                ) {

                    return new WP_Error(
                        'negative_condition_range_value',
                        'مقادیر بازه نمی‌توانند منفی باشند.'
                    );
                }

                if (
                    (float) $minimum >
                    (float) $maximum
                ) {

                    return new WP_Error(
                        'invalid_condition_range_order',
                        'مقدار حداقل نمی‌تواند از مقدار حداکثر بیشتر باشد.'
                    );
                }

                continue;
            }

            if ( 'number' === $value_type ) {

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
            } else {

                $value = trim(
                    (string) $value
                );

                if ( '' === $value ) {
                    return new WP_Error(
                        'invalid_condition_value',
                        'مقدار شرط نمی‌تواند خالی باشد.'
                    );
                }
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

                $notification_email =
                    sanitize_email(
                        get_option(
                            'woosmart_notification_email',
                            ''
                        )
                    );

                if (
                    empty( $notification_email )
                ) {

                    $notification_email =
                        sanitize_email(
                            get_option(
                                'admin_email',
                                ''
                            )
                        );
                }

                if (
                    empty( $notification_email ) ||
                    ! is_email( $notification_email )
                ) {
                    return new WP_Error(
                        'invalid_admin_email',
                        'ایمیل دریافت اعلان WooSmart در تنظیمات وردپرس معتبر نیست.'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Detect conflicts between Actions.
     *
     * Current detection rules:
     *
     * 1. Multiple change_order_status Actions.
     * 2. Duplicate change_order_status target statuses.
     * 3. Sequential order-status transitions.
     *
     * These findings are warnings for the current MVP and
     * do not block saving or activation.
     *
     * @param array $actions Actions.
     *
     * @return array
     */
    private function detect_action_conflicts(
        $actions
    ) {

        $conflicts = array();

        if (
            ! is_array( $actions ) ||
            empty( $actions )
        ) {
            return $conflicts;
        }

        $status_actions = array();

        foreach (
            $actions as $index =>
            $action
        ) {

            if (
                ! is_array( $action )
            ) {
                continue;
            }

            $type = isset(
                $action['type']
            )
                ? sanitize_key(
                    $action['type']
                )
                : '';

            if (
                'change_order_status' !==
                $type
            ) {
                continue;
            }

            $status = isset(
                $action['status']
            )
                ? sanitize_key(
                    $action['status']
                )
                : '';

            $status_actions[] = array(
                'index' =>
                    $index + 1,

                'status' =>
                    $status,
            );
        }

        /*
         * Conflict 1:
         * Multiple order-status changes inside one Automation.
         */
        if (
            count( $status_actions ) > 1
        ) {

            $conflicts[] = array(
                'code' =>
                    'multiple_order_status_changes',

                'severity' =>
                    'warning',

                'message' =>
                    'این اتوماسیون چند بار وضعیت سفارش را تغییر می‌دهد و ممکن است چند Hook یا رفتار وابسته به وضعیت سفارش را فعال کند.',

                'actions' =>
                    $status_actions,
            );
        }

        /*
         * Conflict 2:
         * Duplicate target statuses.
         */
        $statuses_seen = array();

        foreach (
            $status_actions as $status_action
        ) {

            $status =
                $status_action['status'];

            if (
                '' === $status
            ) {
                continue;
            }

            if (
                isset(
                    $statuses_seen[
                        $status
                    ]
                )
            ) {

                $conflicts[] = array(
                    'code' =>
                        'duplicate_order_status_target',

                    'severity' =>
                        'warning',

                    'message' =>
                        'بیش از یک عملیات وضعیت سفارش را به یک وضعیت یکسان تغییر می‌دهد.',

                    'status' =>
                        $status,

                    'actions' =>
                        array(
                            $statuses_seen[
                                $status
                            ],
                            $status_action['index'],
                        ),
                );

            } else {

                $statuses_seen[
                    $status
                ] =
                    $status_action['index'];
            }
        }

        /*
         * Conflict 3:
         * Sequential status transitions.
         *
         * Example:
         * processing → completed
         *
         * The actual current status is only known at runtime,
         * so this is intentionally classified as a warning.
         */
        if (
            count( $status_actions ) > 1
        ) {

            $conflicts[] = array(
                'code' =>
                    'sequential_order_status_transitions',

                'severity' =>
                    'warning',

                'message' =>
                    'چند تغییر متوالی وضعیت سفارش در یک اجرا تعریف شده است. وضعیت واقعی سفارش در زمان اجرا تعیین می‌شود و هر تغییر می‌تواند رفتارهای وابسته WooCommerce یا افزونه‌های دیگر را فعال کند.',

                'actions' =>
                    array_map(
                        function(
                            $item
                        ) {

                            return array(
                                'index' =>
                                    $item['index'],

                                'target_status' =>
                                    $item['status'],
                            );
                        },
                        $status_actions
                    ),
            );
        }

        return $conflicts;
    }

    /**
     * Log detected Action conflicts.
     *
     * @param int    $automation_id Automation ID.
     * @param string $trigger       Trigger key.
     * @param array  $conflicts     Detected conflicts.
     *
     * @return void
     */
    private function log_action_conflicts(
        $automation_id,
        $trigger,
        $conflicts
    ) {

        $this->logger->log(
            'automation_conflict_detected',
            'در پیکربندی عملیات اتوماسیون تعارض یا اثر جانبی بالقوه شناسایی شد.',
            array(
                'automation_id' =>
                    absint(
                        $automation_id
                    ),

                'trigger' =>
                    sanitize_key(
                        $trigger
                    ),

                'conflict_count' =>
                    count(
                        $conflicts
                    ),

                'conflicts' =>
                    $conflicts,
            )
        );
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

            $status_key =
                'wc-' . $status;

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

        if (
            ! current_user_can(
                'manage_options'
            )
        ) {

            wp_die(
                'شما اجازه انجام این عملیات را ندارید.'
            );
        }
    }
}
