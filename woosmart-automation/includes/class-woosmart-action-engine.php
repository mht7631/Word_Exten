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
     * Currency service instance.
     *
     * @var WooSmart_Currency
     */
    private $currency;

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

        $this->currency =
            new WooSmart_Currency();

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

        $all_successful = true;

        foreach (
            $actions as $action
        ) {

            if (
                ! is_array( $action )
            ) {

                $all_successful = false;
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
                empty( $type )
            ) {

                $all_successful = false;
                continue;
            }

            $result =
                $this->execute_action(
                    $type,
                    $action,
                    $context
                );

            if (
                ! $result
            ) {

                $all_successful = false;
            }
        }

        return $all_successful;
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

        if (
            ! $this->action_registry->has(
                $type
            )
        ) {

            $this->logger->log(
                'action_failed',
                'Unknown action type.',
                array(
                    'action_type' => $type,
                    'context'     => $context,
                )
            );

            return false;
        }

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
                    'action_type' => $type,
                    'handler'     => $handler,
                    'context'     => $context,
                )
            );

            return false;
        }

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
                    'action_type' => 'change_order_status',
                )
            );

            return false;
        }

        $order_id =
            isset( $context['order_id'] )
                ? absint( $context['order_id'] )
                : 0;

        $new_status =
            isset( $action['status'] )
                ? sanitize_key( $action['status'] )
                : '';

        if (
            ! $order_id
        ) {

            $this->logger->log(
                'action_failed',
                'Order ID is missing from action context.',
                array(
                    'action_type' => 'change_order_status',
                    'context'     => $context,
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
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
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
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
                )
            );

            return false;
        }

        $old_status =
            $order->get_status();

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
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
                    'old_status'  => $old_status,
                    'new_status'  => $new_status,
                )
            );

            return false;
        }

        $this->logger->log(
            'action_executed',
            'Order status was changed successfully.',
            array(
                'action_type' => 'change_order_status',
                'order_id'    => $order_id,
                'old_status'  => $old_status,
                'new_status'  => $new_status,
            )
        );

        return true;
    }

    /**
     * Send an email notification to the configured WooSmart recipient.
     *
     * WooSmart intentionally does not set the From address here.
     * The active WordPress mail transport controls the From address.
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
            ! function_exists( 'wp_mail' )
        ) {

            $this->logger->log(
                'action_failed',
                'WordPress mail system is not available.',
                array(
                    'action_type' => 'notify_admin',
                )
            );

            return false;
        }

        if (
            ! class_exists(
                'WooSmart_Notification_Settings'
            )
        ) {

            $this->logger->log(
                'action_failed',
                'WooSmart notification settings service is not available.',
                array(
                    'action_type' => 'notify_admin',
                )
            );

            return false;
        }

        $recipient =
            WooSmart_Notification_Settings::get_recipient_email();

        if (
            empty( $recipient ) ||
            ! is_email( $recipient )
        ) {

            $this->logger->log(
                'action_failed',
                'WooSmart notification recipient email address is invalid.',
                array(
                    'action_type' => 'notify_admin',
                    'recipient'   => $recipient,
                )
            );

            return false;
        }

        $configured_recipient = sanitize_email(
            get_option(
                WooSmart_Notification_Settings::RECIPIENT_OPTION,
                ''
            )
        );

        $recipient_source =
            ! empty( $configured_recipient ) &&
            is_email( $configured_recipient )
                ? 'WooSmart Settings'
                : 'WordPress admin_email fallback';

        $subject =
            isset( $action['subject'] )
                ? sanitize_text_field( $action['subject'] )
                : '';

        $message =
            isset( $action['message'] )
                ? sanitize_textarea_field( $action['message'] )
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
                "وضعیت سفارش: {order_status}\n" .
                "نام مشتری: {customer_name}";
        }

        $message =
            $this->replace_order_placeholders(
                $message,
                $context
            );

        $this->last_mail_error =
            null;

        $this->capturing_mail_error =
            true;

        /*
         * Do not set wp_mail_from or wp_mail_from_name here.
         * The configured WordPress mail transport controls From.
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
                'action_type'     => 'notify_admin',
                'recipient'       => $recipient,
                'recipient_source' => $recipient_source,
                'order_id'        => isset( $context['order_id'] )
                    ? absint( $context['order_id'] )
                    : 0,
            );

            if (
                $this->last_mail_error instanceof WP_Error
            ) {

                $error_message =
                    $this->last_mail_error->get_error_message();

                if (
                    ! empty( $error_message )
                ) {

                    $error_context['mail_error'] =
                        $error_message;
                }

                $error_data =
                    $this->last_mail_error->get_error_data();

                if (
                    is_array( $error_data ) &&
                    isset( $error_data['phpmailer_exception_code'] )
                ) {

                    $error_context['mail_error_code'] =
                        $error_data['phpmailer_exception_code'];
                }

            } else {

                $error_context['mail_error'] =
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
            'اعلان ایمیل با موفقیت ارسال شد.',
            array(
                'action_type'     => 'notify_admin',
                'recipient'       => $recipient,
                'recipient_source' => $recipient_source,
                'from_source'     => 'WordPress Mail Transport',
                'order_id'        => isset( $context['order_id'] )
                    ? absint( $context['order_id'] )
                    : 0,
            )
        );

        return true;
    }

    /**
     * Replace order placeholders in notification message.
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
            isset( $context['order_id'] )
                ? absint( $context['order_id'] )
                : 0;

        $order_total  = '';
        $order_status = '';
        $customer_name = '';

        if (
            $order_id &&
            function_exists( 'wc_get_order' )
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
                        (float) $order->get_total(),
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

        $currency_unit =
            $this->currency->get_display_unit();

        $replacements = array(
            '{order_id}' => $order_id,
            '{order_total}' => $order_total . ' ' . $currency_unit,
            '{order_status}' => $order_status,
            '{customer_name}' => $customer_name,
        );

        return strtr(
            $message,
            $replacements
        );
    }
}
