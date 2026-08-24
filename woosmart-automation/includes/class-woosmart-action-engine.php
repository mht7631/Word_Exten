<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Action Engine for WooSmart Automation.
 */
class WooSmart_Action_Engine {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Action Registry instance.
     *
     * @var WooSmart_Action_Registry
     */
    private $action_registry;

    /**
     * Whether the engine is currently capturing a mail error.
     *
     * @var bool
     */
    private $capturing_mail_error = false;

    /**
     * Last captured WordPress mail error.
     *
     * @var WP_Error|null
     */
    private $last_mail_error = null;

    /**
     * Initialize Action Engine.
     */
    public function __construct() {

        $this->logger =
            new WooSmart_Logger();

        $this->action_registry =
            new WooSmart_Action_Registry();

        /*
         * Capture the real PHPMailer error when wp_mail()
         * fails. WordPress provides this through wp_mail_failed.
         */
        add_action(
            'wp_mail_failed',
            array(
                $this,
                'capture_mail_error',
            ),
            10,
            1
        );
    }

    /**
     * Capture wp_mail() failure details.
     *
     * @param WP_Error $error WordPress mail error.
     *
     * @return void
     */
    public function capture_mail_error(
        $error
    ) {

        /*
         * Only capture the error when this Action Engine
         * is actively sending its own administrator notification.
         */
        if (
            ! $this->capturing_mail_error
        ) {

            return;
        }

        if (
            $error instanceof WP_Error
        ) {

            $this->last_mail_error =
                $error;

            return;
        }

        $this->last_mail_error =
            new WP_Error(
                'wp_mail_failed',
                'خطای نامشخص در سیستم ارسال ایمیل.'
            );
    }

    /**
     * Execute automation actions.
     *
     * @param array $actions Actions configuration.
     * @param array $context Execution context.
     *
     * @return bool
     */
    public function execute(
        $actions,
        $context = array()
    ) {

        if (
            empty( $actions ) ||
            ! is_array( $actions )
        ) {

            return true;
        }

        $all_successful =
            true;

        $action_total =
            count(
                $actions
            );

        foreach (
            $actions as $index =>
            $action
        ) {

            $action_number =
                $index + 1;

            $action_type =
                '';

            if (
                is_array(
                    $action
                ) &&
                isset(
                    $action['type']
                )
            ) {

                $action_type =
                    sanitize_key(
                        $action['type']
                    );
            }

            /*
             * Build a per-Action execution context.
             *
             * The original trigger context remains available while
             * the Action metadata makes logs and future diagnostics
             * traceable to a specific Action.
             */
            $action_context =
                $context;

            $action_context[
                'action_index'
            ] =
                $action_number;

            $action_context[
                'action_total'
            ] =
                $action_total;

            $action_context[
                'action_type'
            ] =
                $action_type;

            /*
             * Invalid Action structure.
             */
            if (
                ! is_array(
                    $action
                )
            ) {

                $all_successful =
                    false;

                $this->log_action_result(
                    $action_number,
                    $action_total,
                    $action_type,
                    false,
                    $context
                );

                continue;
            }

            /*
             * Missing Action type.
             */
            if (
                empty( $action_type )
            ) {

                $all_successful =
                    false;

                $this->log_action_result(
                    $action_number,
                    $action_total,
                    $action_type,
                    false,
                    $context
                );

                continue;
            }

            $result =
                $this->execute_action(
                    $action_type,
                    $action,
                    $action_context
                );

            $result =
                (bool) $result;

            /*
             * Register the result of this specific Action.
             *
             * This does not alter the existing Action logs.
             * It adds a separate machine-readable execution result
             * so each Action can be identified independently.
             */
            $this->log_action_result(
                $action_number,
                $action_total,
                $action_type,
                $result,
                $context
            );

            if (
                ! $result
            ) {

                $all_successful =
                    false;
            }
        }

        return $all_successful;
    }

    /**
     * Log the result of one Action.
     *
     * @param int    $action_number Action number.
     * @param int    $action_total  Total Action count.
     * @param string $action_type   Action type.
     * @param bool   $success       Action result.
     * @param array  $context       Execution context.
     *
     * @return void
     */
    private function log_action_result(
        $action_number,
        $action_total,
        $action_type,
        $success,
        $context
    ) {

        $this->logger->log(
            'action_result',
            $success
                ? 'نتیجه عملیات با موفقیت ثبت شد.'
                : 'نتیجه عملیات با شکست ثبت شد.',
            array(
                'action_index' =>
                    absint(
                        $action_number
                    ),

                'action_total' =>
                    absint(
                        $action_total
                    ),

                'action_type' =>
                    sanitize_key(
                        $action_type
                    ),

                'success' =>
                    (bool) $success,

                'context' =>
                    $context,
            )
        );
    }

    /**
     * Log a WooCommerce side effect caused by an Action.
     *
     * @param array $context Action execution context.
     * @param array $data    Side effect data.
     *
     * @return void
     */
    private function log_side_effect(
        $context,
        $data
    ) {

        $base_context = array(
            'action_index' =>
                isset(
                    $context['action_index']
                )
                    ? absint(
                        $context['action_index']
                    )
                    : 0,

            'action_total' =>
                isset(
                    $context['action_total']
                )
                    ? absint(
                        $context['action_total']
                    )
                    : 0,

            'action_type' =>
                isset(
                    $context['action_type']
                )
                    ? sanitize_key(
                        $context['action_type']
                    )
                    : '',

            'context' =>
                isset(
                    $context['order_id']
                )
                    ? array(
                        'order_id' =>
                            absint(
                                $context['order_id']
                            ),
                    )
                    : array(),
        );

        if (
            is_array(
                $data
            )
        ) {

            $base_context =
                array_merge(
                    $base_context,
                    $data
                );
        }

        $this->logger->log(
            'action_side_effect',
            'عملیات باعث یک اثر جانبی در WooCommerce شد.',
            $base_context
        );
    }

    /**
     * Execute a single registered action.
     *
     * @param string $type    Action type.
     * @param array  $action  Action configuration.
     * @param array  $context Execution context.
     *
     * @return bool
     */
    private function execute_action(
        $type,
        $action,
        $context
    ) {

        /*
         * Resolve the Action through the Registry.
         */
        if (
            ! $this->action_registry->has(
                $type
            )
        ) {

            $this->logger->log(
                'action_failed',
                'Unknown action type.',
                array(
                    'action_type' =>
                        $type,

                    'context' =>
                        $context,
                )
            );

            return false;
        }

        /*
         * Get the registered handler method.
         */
        $handler =
            $this->action_registry->get_handler(
                $type
            );

        if (
            empty( $handler ) ||
            ! method_exists(
                $this,
                $handler
            )
        ) {

            $this->logger->log(
                'action_failed',
                'Registered action handler could not be found.',
                array(
                    'action_type' =>
                        $type,

                    'handler' =>
                        $handler,

                    'context' =>
                        $context,
                )
            );

            return false;
        }

        /*
         * Execute the registered handler.
         */
        return (bool) call_user_func(
            array(
                $this,
                $handler,
            ),
            $action,
            $context
        );
    }

    /**
     * Change WooCommerce order status.
     *
     * @param array $action  Action configuration.
     * @param array $context Execution context.
     *
     * @return bool
     */
    private function change_order_status(
        $action,
        $context
    ) {

        if (
            ! function_exists(
                'wc_get_order'
            )
        ) {

            $this->logger->log(
                'action_failed',
                'WooCommerce is not available.',
                array(
                    'action_type' =>
                        'change_order_status',
                )
            );

            return false;
        }

        $order_id =
            isset(
                $context['order_id']
            )
                ? absint(
                    $context['order_id']
                )
                : 0;

        $new_status =
            isset(
                $action['status']
            )
                ? sanitize_key(
                    $action['status']
                )
                : '';

        if (
            ! $order_id
        ) {

            $this->logger->log(
                'action_failed',
                'Order ID is missing from action context.',
                array(
                    'action_type' =>
                        'change_order_status',

                    'context' =>
                        $context,
                )
            );

            return false;
        }

        if (
            empty( $new_status )
        ) {

            $this->logger->log(
                'action_failed',
                'Order status is missing.',
                array(
                    'action_type' =>
                        'change_order_status',

                    'order_id' =>
                        $order_id,
                )
            );

            return false;
        }

        $order =
            wc_get_order(
                $order_id
            );

        if (
            ! $order
        ) {

            $this->logger->log(
                'action_failed',
                'WooCommerce order could not be found.',
                array(
                    'action_type' =>
                        'change_order_status',

                    'order_id' =>
                        $order_id,
                )
            );

            return false;
        }

        $old_status =
            $order->get_status();

        /*
         * WooCommerce status changes can trigger downstream hooks,
         * status-specific hooks, emails, and other plugin behavior.
         *
         * WooSmart intentionally does not suppress those effects here.
         * The current phase is diagnostic: record the transition so
         * downstream effects can be identified before introducing
         * optional suppression or conflict policies.
         */
        $result =
            $order->update_status(
                $new_status,
                'WooSmart Automation changed the order status.'
            );

        if (
            false === $result
        ) {

            $this->logger->log(
                'action_failed',
                'Failed to change order status.',
                array(
                    'action_type' =>
                        'change_order_status',

                    'order_id' =>
                        $order_id,

                    'old_status' =>
                        $old_status,

                    'new_status' =>
                        $new_status,

                    'action_index' =>
                        isset(
                            $context['action_index']
                        )
                            ? absint(
                                $context['action_index']
                            )
                            : 0,

                    'action_total' =>
                        isset(
                            $context['action_total']
                        )
                            ? absint(
                                $context['action_total']
                            )
                            : 0,
                )
            );

            return false;
        }

        /*
         * Record the order-status transition as a WooCommerce
         * side effect of this specific WooSmart Action.
         */
        $this->log_side_effect(
            $context,
            array(
                'side_effect_type' =>
                    'woocommerce_order_status_transition',

                'order_id' =>
                    $order_id,

                'old_status' =>
                    $old_status,

                'new_status' =>
                    $new_status,

                'downstream_hooks' =>
                    array(
                        'woocommerce_order_status_changed',
                        'woocommerce_order_status_' .
                            $new_status,
                    ),

                'possible_downstream_effects' =>
                    array(
                        'woocommerce_transactional_emails',
                        'other_plugin_hooks',
                    ),
            )
        );

        $this->logger->log(
            'action_executed',
            'Order status was changed successfully.',
            array(
                'action_type' =>
                    'change_order_status',

                'order_id' =>
                    $order_id,

                'old_status' =>
                    $old_status,

                'new_status' =>
                    $new_status,

                'action_index' =>
                    isset(
                        $context['action_index']
                    )
                        ? absint(
                            $context['action_index']
                        )
                        : 0,

                'action_total' =>
                    isset(
                        $context['action_total']
                    )
                        ? absint(
                            $context['action_total']
                        )
                        : 0,
            )
        );

        return true;
    }

    /**
     * Send an email notification to the store administrator.
     *
     * WooSmart intentionally does not set the From address here.
     * The active WordPress mail transport is responsible for the
     * final From address.
     *
     * @param array $action  Action configuration.
     * @param array $context Execution context.
     *
     * @return bool
     */
    private function notify_admin(
        $action,
        $context
    ) {

        if (
            ! function_exists(
                'wp_mail'
            )
        ) {

            $this->logger->log(
                'action_failed',
                'WordPress mail system is not available.',
                array(
                    'action_type' =>
                        'notify_admin',

                    'action_index' =>
                        isset(
                            $context['action_index']
                        )
                            ? absint(
                                $context['action_index']
                            )
                            : 0,

                    'action_total' =>
                        isset(
                            $context['action_total']
                        )
                            ? absint(
                                $context['action_total']
                            )
                            : 0,
                )
            );

            return false;
        }

        /*
         * Use the WooSmart notification recipient when configured.
         * Fall back to the WordPress administrator email so existing
         * installations continue working without additional setup.
         */
        $recipient =
            sanitize_email(
                get_option(
                    'woosmart_notification_email',
                    ''
                )
            );

        if (
            empty( $recipient )
        ) {

            $recipient =
                sanitize_email(
                    get_option(
                        'admin_email',
                        ''
                    )
                );
        }

        if (
            empty(
                $recipient
            ) ||
            ! is_email(
                $recipient
            )
        ) {

            $this->logger->log(
                'action_failed',
                'آدرس ایمیل دریافت اعلان‌های WooSmart معتبر نیست.',
                array(
                    'action_type' =>
                        'notify_admin',

                    'action_index' =>
                        isset(
                            $context['action_index']
                        )
                            ? absint(
                                $context['action_index']
                            )
                            : 0,

                    'action_total' =>
                        isset(
                            $context['action_total']
                        )
                            ? absint(
                                $context['action_total']
                            )
                            : 0,
                )
            );

            return false;
        }

        $subject =
            isset(
                $action['subject']
            )
                ? sanitize_text_field(
                    $action['subject']
                )
                : '';

        $message =
            isset(
                $action['message']
            )
                ? sanitize_textarea_field(
                    $action['message']
                )
                : '';

        if (
            empty( $subject )
        ) {

            $subject =
                'اعلان WooSmart درباره سفارش';
        }

        if (
            empty( $message )
        ) {

            $message =
                "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.\n\n" .
                "شناسه سفارش: {order_id}\n" .
                "مبلغ سفارش: {order_total}\n" .
                "وضعیت سفارش: {order_status}";
        }

        $message =
            $this->replace_order_placeholders(
                $message,
                $context
            );

        /*
         * Reset the previous mail error before each attempt.
         */
        $this->last_mail_error =
            null;

        /*
         * Enable mail error capture only for this
         * WooSmart notification attempt.
         */
        $this->capturing_mail_error =
            true;

        /*
         * IMPORTANT:
         *
         * Do NOT add wp_mail_from or wp_mail_from_name filters here.
         *
         * WooSmart must not override the mail transport's
         * configured From address.
         *
         * WP Mail SMTP / Resend will determine the final
         * From address.
         */
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        $mail_sent =
            wp_mail(
                $recipient,
                $subject,
                $message,
                $headers
            );

        $this->capturing_mail_error =
            false;

        if (
            ! $mail_sent
        ) {

            $error_context = array(
                'action_type' =>
                    'notify_admin',

                'recipient' =>
                    $recipient,

                'order_id' =>
                    isset(
                        $context['order_id']
                    )
                        ? absint(
                            $context['order_id']
                        )
                        : 0,

                'action_index' =>
                    isset(
                        $context['action_index']
                    )
                        ? absint(
                            $context['action_index']
                        )
                        : 0,

                'action_total' =>
                    isset(
                        $context['action_total']
                    )
                        ? absint(
                            $context['action_total']
                        )
                        : 0,
            );

            /*
             * Add the real PHPMailer error when WordPress
             * provides it through wp_mail_failed.
             */
            if (
                $this->last_mail_error
                instanceof WP_Error
            ) {

                $error_message =
                    $this->last_mail_error
                        ->get_error_message();

                if (
                    ! empty(
                        $error_message
                    )
                ) {

                    $error_context[
                        'mail_error'
                    ] =
                        $error_message;
                }

                $error_data =
                    $this->last_mail_error
                        ->get_error_data();

                if (
                    is_array(
                        $error_data
                    )
                ) {

                    if (
                        isset(
                            $error_data[
                                'phpmailer_exception_code'
                            ]
                        )
                    ) {

                        $error_context[
                            'mail_error_code'
                        ] =
                            $error_data[
                                'phpmailer_exception_code'
                            ];
                    }
                }

            } else {

                $error_context[
                    'mail_error'
                ] =
                    'wp_mail() بدون ارائه خطای دقیق، مقدار false برگرداند.';
            }

            $this->logger->log(
                'action_failed',
                'ارسال اعلان ایمیل با خطا مواجه شد.',
                $error_context
            );

            return false;
        }

        $this->logger->log(
            'action_executed',
            'اعلان ایمیل مدیر فروشگاه با موفقیت ارسال شد.',
            array(
                'action_type' =>
                    'notify_admin',

                'recipient' =>
                    $recipient,

                'from_source' =>
                    'WP Mail SMTP / WordPress Mail Transport',

                'order_id' =>
                    isset(
                        $context['order_id']
                    )
                        ? absint(
                            $context['order_id']
                        )
                        : 0,

                'action_index' =>
                    isset(
                        $context['action_index']
                    )
                        ? absint(
                            $context['action_index']
                        )
                        : 0,

                'action_total' =>
                    isset(
                        $context['action_total']
                    )
                        ? absint(
                            $context['action_total']
                        )
                        : 0,
            )
        );

        return true;
    }

    /**
     * Replace order placeholders in notification message.
     *
     * Supported placeholders:
     *
     * {order_id}
     * {order_total}
     * {order_status}
     * {customer_name}
     *
     * @param string $message Message template.
     * @param array  $context Execution context.
     *
     * @return string
     */
    private function replace_order_placeholders(
        $message,
        $context
    ) {

        $order_id =
            isset(
                $context['order_id']
            )
                ? absint(
                    $context['order_id']
                )
                : 0;

        $order_total =
            '';

        $order_status =
            '';

        $customer_name =
            '';

        if (
            $order_id &&
            function_exists(
                'wc_get_order'
            )
        ) {

            $order =
                wc_get_order(
                    $order_id
                );

            if (
                $order
            ) {

                $order_total =
                    number_format_i18n(
                        (float)
                        $order->get_total(),
                        0
                    );

                $order_status =
                    $order->get_status();

                if (
                    method_exists(
                        $order,
                        'get_formatted_billing_full_name'
                    )
                ) {

                    $customer_name =
                        $order->get_formatted_billing_full_name();
                }
            }
        }

        $replacements = array(
            '{order_id}' =>
                $order_id,

            '{order_total}' =>
                $order_total . ' تومان',

            '{order_status}' =>
                $order_status,

            '{customer_name}' =>
                $customer_name,
        );

        return strtr(
            $message,
            $replacements
        );
    }
}
