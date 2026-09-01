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
     * Status hook diagnostic timers.
     *
     * @var array
     */
    private $status_hook_timers = array();

    /**
     * Status hooks being profiled.
     *
     * @var array
     */
    private $profiled_status_hooks = array(
        'woocommerce_order_status_pending_to_processing',
        'woocommerce_order_status_processing',
        'woocommerce_order_status_changed',
        'woocommerce_order_payment_status_changed',
    );

    /**
     * Hook callback IDs already wrapped.
     *
     * @var array
     */
    private static $wrapped_callback_ids = array();

    /**
     * Initialize Action Engine.
     */
    public function __construct() {

        $this->logger =
            new WooSmart_Logger();

        $this->action_registry =
            new WooSmart_Action_Registry();

        add_action(
            'wp_mail_failed',
            array(
                $this,
                'capture_mail_error',
            ),
            10,
            1
        );

        /*
         * Diagnostic-only hook timing.
         */
        $this->register_status_hook_diagnostics();
    }

    /**
     * Register diagnostic timing callbacks for WooCommerce
     * order status transition hooks.
     *
     * @return void
     */
    private function register_status_hook_diagnostics() {

        foreach (
            $this->profiled_status_hooks as $hook
        ) {

            add_action(
                $hook,
                array(
                    $this,
                    'start_status_hook_timer',
                ),
                -PHP_INT_MAX,
                4
            );

            add_action(
                $hook,
                array(
                    $this,
                    'finish_status_hook_timer',
                ),
                PHP_INT_MAX,
                4
            );
        }
    }

    /**
     * Ensure callback-level profiling is installed immediately
     * before a status transition is executed.
     *
     * This is intentionally called at runtime because third-party
     * plugins may register their callbacks after Action Engine
     * construction.
     *
     * @param string $hook Hook name.
     *
     * @return void
     */
    private function ensure_callback_level_diagnostics(
        $hook
    ) {

        global $wp_filter;

        if (
            empty(
                $hook
            ) ||
            ! isset(
                $wp_filter[
                    $hook
                ]
            )
        ) {
            return;
        }

        if (
            ! isset(
                $wp_filter[
                    $hook
                ]->callbacks
            ) ||
            ! is_array(
                $wp_filter[
                    $hook
                ]->callbacks
            )
        ) {
            return;
        }

        /*
         * Snapshot callback definitions at the exact moment of
         * status transition.
         */
        $callbacks =
            $wp_filter[
                $hook
            ]->callbacks;

        foreach (
            $callbacks as $priority =>
            $priority_callbacks
        ) {

            if (
                ! is_array(
                    $priority_callbacks
                )
            ) {
                continue;
            }

            foreach (
                $priority_callbacks as $callback_id =>
                $callback_data
            ) {

                if (
                    ! is_array(
                        $callback_data
                    )
                ) {
                    continue;
                }

                if (
                    ! isset(
                        $callback_data[
                            'function'
                        ]
                    )
                ) {
                    continue;
                }

                $original_callback =
                    $callback_data[
                        'function'
                    ];

                $accepted_args =
                    isset(
                        $callback_data[
                            'accepted_args'
                        ]
                    )
                        ? absint(
                            $callback_data[
                                'accepted_args'
                            ]
                        )
                        : 1;

                /*
                 * Never wrap WooSmart diagnostic callbacks.
                 */
                if (
                    $this->is_own_diagnostic_callback(
                        $original_callback
                    )
                ) {
                    continue;
                }

                /*
                 * Build a stable identifier.
                 */
                $wrapper_key =
                    md5(
                        $hook .
                        '|' .
                        (string) $priority .
                        '|' .
                        (string) $callback_id
                    );

                if (
                    isset(
                        self::$wrapped_callback_ids[
                            $wrapper_key
                        ]
                    )
                ) {
                    continue;
                }

                /*
                 * Mark before modifying the hook.
                 */
                self::$wrapped_callback_ids[
                    $wrapper_key
                ] =
                    true;

                $callback_name =
                    $this->get_callback_name(
                        $original_callback
                    );

                /*
                 * Remove original callback.
                 */
                $removed =
                    remove_action(
                        $hook,
                        $original_callback,
                        $priority
                    );

                if (
                    ! $removed
                ) {

                    unset(
                        self::$wrapped_callback_ids[
                            $wrapper_key
                        ]
                    );

                    continue;
                }

                $engine =
                    $this;

                /*
                 * Re-register at exactly the same priority.
                 */
                add_action(
                    $hook,
                    function(
                        ...$args
                    ) use (
                        $engine,
                        $original_callback,
                        $accepted_args,
                        $callback_name,
                        $hook,
                        $priority
                    ) {

                        $call_args =
                            $args;

                        if (
                            $accepted_args >= 0
                        ) {

                            $call_args =
                                array_slice(
                                    $args,
                                    0,
                                    $accepted_args
                                );
                        }

                        $started =
                            microtime(
                                true
                            );

                        $result =
                            call_user_func_array(
                                $original_callback,
                                $call_args
                            );

                        $duration_ms =
                            (int)
                            round(
                                (
                                    microtime(
                                        true
                                    ) -
                                    $started
                                ) *
                                1000
                            );

                        $order_id =
                            $engine->resolve_order_id_from_arguments(
                                $call_args
                            );

                        $engine->logger->log(
                            'status_hook_callback_timing',
                            'مدت اجرای Callback مربوط به Hook تغییر وضعیت سفارش ثبت شد.',
                            array(
                                'hook' =>
                                    $hook,

                                'priority' =>
                                    (int)
                                    $priority,

                                'callback_id' =>
                                    md5(
                                        $hook .
                                        '|' .
                                        (string) $priority .
                                        '|' .
                                        $callback_name
                                    ),

                                'callback' =>
                                    $callback_name,

                                'order_id' =>
                                    $order_id,

                                'duration_ms' =>
                                    max(
                                        0,
                                        $duration_ms
                                    ),

                                'request_uri' =>
                                    isset(
                                        $_SERVER['REQUEST_URI']
                                    )
                                        ? sanitize_text_field(
                                            wp_unslash(
                                                $_SERVER['REQUEST_URI']
                                            )
                                        )
                                        : '',
                            )
                        );

                        return $result;
                    },
                    $priority,
                    $accepted_args
                );
            }
        }
    }

    /**
     * Determine whether a callback belongs to this diagnostic engine.
     *
     * @param mixed $callback Callback.
     *
     * @return bool
     */
    private function is_own_diagnostic_callback(
        $callback
    ) {

        if (
            ! is_array(
                $callback
            )
        ) {
            return false;
        }

        if (
            ! isset(
                $callback[0],
                $callback[1]
            )
        ) {
            return false;
        }

        if (
            $callback[0] !==
            $this
        ) {
            return false;
        }

        $method =
            (string)
            $callback[1];

        return in_array(
            $method,
            array(
                'start_status_hook_timer',
                'finish_status_hook_timer',
            ),
            true
        );
    }

    /**
     * Get a readable callback name.
     *
     * @param mixed $callback WordPress callback.
     *
     * @return string
     */
    private function get_callback_name(
        $callback
    ) {

        if (
            is_string(
                $callback
            )
        ) {

            return $callback;
        }

        if (
            is_array(
                $callback
            ) &&
            count(
                $callback
            ) >= 2
        ) {

            $target =
                $callback[0];

            $method =
                (string)
                $callback[1];

            if (
                is_object(
                    $target
                )
            ) {

                return (
                    get_class(
                        $target
                    ) .
                    '->' .
                    $method
                );
            }

            return (
                (string)
                $target .
                '::' .
                $method
            );
        }

        if (
            $callback instanceof Closure
        ) {

            return 'Closure';
        }

        if (
            is_object(
                $callback
            ) &&
            method_exists(
                $callback,
                '__invoke'
            )
        ) {

            return (
                get_class(
                    $callback
                ) .
                '::__invoke'
            );
        }

        return 'Unknown callback';
    }

    /**
     * Resolve Order ID from callback arguments.
     *
     * @param array $arguments Callback arguments.
     *
     * @return int
     */
    private function resolve_order_id_from_arguments(
        $arguments
    ) {

        if (
            ! is_array(
                $arguments
            )
        ) {
            return 0;
        }

        foreach (
            $arguments as $argument
        ) {

            if (
                is_object(
                    $argument
                ) &&
                method_exists(
                    $argument,
                    'get_id'
                )
            ) {

                $order_id =
                    absint(
                        $argument->get_id()
                    );

                if (
                    $order_id
                ) {

                    return $order_id;
                }
            }

            if (
                is_numeric(
                    $argument
                )
            ) {

                $order_id =
                    absint(
                        $argument
                    );

                if (
                    $order_id
                ) {

                    return $order_id;
                }
            }

            if (
                is_array(
                    $argument
                ) &&
                isset(
                    $argument['order_id']
                )
            ) {

                $order_id =
                    absint(
                        $argument['order_id']
                    );

                if (
                    $order_id
                ) {

                    return $order_id;
                }
            }
        }

        return 0;
    }

    /**
     * Start timing one WooCommerce status hook.
     *
     * @param mixed $order_id First hook argument.
     * @param mixed $arg_2    Second hook argument.
     * @param mixed $arg_3    Third hook argument.
     * @param mixed $arg_4    Fourth hook argument.
     *
     * @return void
     */
    public function start_status_hook_timer(
        $order_id = null,
        $arg_2 = null,
        $arg_3 = null,
        $arg_4 = null
    ) {

        $hook =
            current_filter();

        if (
            empty(
                $hook
            )
        ) {
            return;
        }

        $this->status_hook_timers[
            $hook
        ][] =
            array(
                'started_at' =>
                    microtime(
                        true
                    ),

                'order_id' =>
                    $this->resolve_order_id_from_arguments(
                        array(
                            $order_id,
                            $arg_2,
                            $arg_3,
                            $arg_4,
                        )
                    ),
            );
    }

    /**
     * Finish timing one WooCommerce status hook.
     *
     * @param mixed $order_id First hook argument.
     * @param mixed $arg_2    Second hook argument.
     * @param mixed $arg_3    Third hook argument.
     * @param mixed $arg_4    Fourth hook argument.
     *
     * @return void
     */
    public function finish_status_hook_timer(
        $order_id = null,
        $arg_2 = null,
        $arg_3 = null,
        $arg_4 = null
    ) {

        $hook =
            current_filter();

        if (
            empty(
                $hook
            )
        ) {
            return;
        }

        if (
            empty(
                $this->status_hook_timers[
                    $hook
                ]
            )
        ) {
            return;
        }

        $timer =
            array_pop(
                $this->status_hook_timers[
                    $hook
                ]
            );

        $duration_ms =
            (int)
            round(
                (
                    microtime(
                        true
                    ) -
                    $timer['started_at']
                ) *
                1000
            );

        $resolved_order_id =
            $this->resolve_order_id_from_arguments(
                array(
                    $order_id,
                    $arg_2,
                    $arg_3,
                    $arg_4,
                )
            );

        if (
            ! $resolved_order_id &&
            isset(
                $timer['order_id']
            )
        ) {
            $resolved_order_id =
                absint(
                    $timer['order_id']
                );
        }

        $this->logger->log(
            'status_hook_timing',
            'مدت اجرای Hook تغییر وضعیت سفارش ثبت شد.',
            array(
                'hook' =>
                    $hook,

                'order_id' =>
                    $resolved_order_id,

                'duration_ms' =>
                    max(
                        0,
                        $duration_ms
                    ),

                'request_uri' =>
                    isset(
                        $_SERVER['REQUEST_URI']
                    )
                        ? sanitize_text_field(
                            wp_unslash(
                                $_SERVER['REQUEST_URI']
                            )
                        )
                        : '',
            )
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
     * Backward-compatible public method.
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

        $result =
            $this->execute_with_results(
                $actions,
                $context
            );

        return ! empty(
            $result['success']
        );
    }

    /**
     * Execute automation actions and return per-Action results.
     *
     * @param array $actions Actions configuration.
     * @param array $context Execution context.
     *
     * @return array
     */
    public function execute_with_results(
        $actions,
        $context = array()
    ) {

        $result = array(
            'success' =>
                true,

            'actions_total' =>
                0,

            'actions_successful' =>
                0,

            'actions_executed' =>
                0,

            'failed_action_index' =>
                0,

            'actions_stopped' =>
                false,

            'actions' =>
                array(),
        );

        if (
            empty(
                $actions
            ) ||
            ! is_array(
                $actions
            )
        ) {

            return $result;
        }

        $action_total =
            count(
                $actions
            );

        $result[
            'actions_total'
        ] =
            $action_total;

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

            $action_started =
                microtime(
                    true
                );

            $success =
                false;

            $message =
                '';

            $error =
                '';

            if (
                ! is_array(
                    $action
                )
            ) {

                $message =
                    'ساختار عملیات نامعتبر است.';

            } elseif (
                empty(
                    $action_type
                )
            ) {

                $message =
                    'نوع عملیات مشخص نشده است.';

            } else {

                $success =
                    $this->execute_action(
                        $action_type,
                        $action,
                        $action_context
                    );

                if (
                    $success
                ) {

                    $message =
                        'عملیات با موفقیت اجرا شد.';

                } else {

                    $message =
                        'اجرای عملیات با شکست مواجه شد.';
                }
            }

            $action_duration_ms =
                (int)
                round(
                    (
                        microtime(
                            true
                        ) -
                        $action_started
                    ) *
                    1000
                );

            $action_result = array(
                'index' =>
                    $action_number,

                'type' =>
                    $action_type,

                'success' =>
                    (bool) $success,

                'duration_ms' =>
                    max(
                        0,
                        $action_duration_ms
                    ),

                'message' =>
                    $message,

                'configuration' =>
                    $this->sanitize_action_snapshot(
                        $action
                    ),
            );

            if (
                ! $success &&
                ! empty(
                    $error
                )
            ) {

                $action_result[
                    'error'
                ] =
                    $error;
            }

            $result[
                'actions'
            ][] =
                $action_result;

            $result[
                'actions_executed'
            ]++;

            if (
                $success
            ) {

                $result[
                    'actions_successful'
                ]++;

            } else {

                $result[
                    'success'
                ] =
                    false;

                $result[
                    'failed_action_index'
                ] =
                    $action_number;

                $result[
                    'actions_stopped'
                ] =
                    (
                        $action_number <
                        $action_total
                    );

                $this->logger->log(
                    'automation_action_chain_stopped',
                    'اجرای عملیات بعدی این اتوماسیون به دلیل شکست یک عملیات متوقف شد.',
                    array(
                        'action_index' =>
                            $action_number,

                        'action_total' =>
                            $action_total,

                        'action_type' =>
                            $action_type,

                        'context' =>
                            $context,
                    )
                );

                $this->log_action_result(
                    $action_number,
                    $action_total,
                    $action_type,
                    false,
                    $context
                );

                break;
            }

            $this->log_action_result(
                $action_number,
                $action_total,
                $action_type,
                true,
                $context
            );
        }

        return $result;
    }

    /**
     * Sanitize an Action configuration for historical storage.
     *
     * @param mixed $action Action configuration.
     *
     * @return array
     */
    private function sanitize_action_snapshot(
        $action
    ) {

        if (
            ! is_array(
                $action
            )
        ) {

            return array();
        }

        $snapshot =
            array();

        if (
            isset(
                $action['type']
            )
        ) {

            $snapshot['type'] =
                sanitize_key(
                    $action['type']
                );
        }

        if (
            isset(
                $action['status']
            )
        ) {

            $snapshot['status'] =
                sanitize_key(
                    $action['status']
                );
        }

        if (
            isset(
                $action['subject']
            )
        ) {

            $snapshot['subject'] =
                sanitize_text_field(
                    $action['subject']
                );
        }

        if (
            isset(
                $action['message']
            )
        ) {

            $snapshot['message'] =
                sanitize_textarea_field(
                    $action['message']
                );
        }

        return $snapshot;
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
     * Execute a single registered Action.
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
                    'action_type' =>
                        $type,

                    'context' =>
                        $context,
                )
            );

            return false;
        }

        $handler =
            $this->action_registry->get_handler(
                $type
            );

        if (
            empty(
                $handler
            ) ||
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
            empty(
                $new_status
            )
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
         * Never trigger a status transition if the requested
         * status is already active.
         */
        if (
            $old_status ===
            $new_status
        ) {

            $this->logger->log(
                'action_skipped',
                'وضعیت سفارش از قبل همان وضعیت درخواستی بود و تغییری انجام نشد.',
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

                    'skipped_reason' =>
                        'status_already_set',
                )
            );

            return true;
        }

        /*
         * IMPORTANT:
         *
         * Register callback-level diagnostics immediately before
         * update_status().
         *
         * This catches callbacks added by WooCommerce, payment
         * gateways, SMTP plugins, themes, and other plugins after
         * the Action Engine itself was constructed.
         */
        $this->ensure_callback_level_diagnostics(
            'woocommerce_order_status_pending_to_processing'
        );

        /*
         * Measure the complete WooCommerce update_status() call.
         */
        $status_update_started =
            microtime(
                true
            );

        $result =
            $order->update_status(
                $new_status,
                'WooSmart Automation changed the order status.'
            );

        $status_update_duration_ms =
            (int)
            round(
                (
                    microtime(
                        true
                    ) -
                    $status_update_started
                ) *
                1000
            );

        $this->logger->log(
            'order_status_update_timing',
            'مدت زمان پردازش تغییر وضعیت سفارش ثبت شد.',
            array(
                'action_type' =>
                    'change_order_status',

                'order_id' =>
                    $order_id,

                'old_status' =>
                    $old_status,

                'new_status' =>
                    $new_status,

                'duration_ms' =>
                    max(
                        0,
                        $status_update_duration_ms
                    ),

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

        if (
            false ===
            $result
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

                    'duration_ms' =>
                        max(
                            0,
                            $status_update_duration_ms
                        ),

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

                'duration_ms' =>
                    max(
                        0,
                        $status_update_duration_ms
                    ),

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

        $recipient =
            sanitize_email(
                get_option(
                    'woosmart_notification_email',
                    ''
                )
            );

        if (
            empty(
                $recipient
            )
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
            empty(
                $subject
            )
        ) {

            $subject =
                'اعلان WooSmart درباره سفارش';
        }

        if (
            empty(
                $message
            )
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

        $this->last_mail_error =
            null;

        $this->capturing_mail_error =
            true;

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        $mail_started =
            microtime(
                true
            );

        $mail_sent =
            wp_mail(
                $recipient,
                $subject,
                $message,
                $headers
            );

        $mail_duration_ms =
            (int)
            round(
                (
                    microtime(
                        true
                    ) -
                    $mail_started
                ) *
                1000
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

                'duration_ms' =>
                    max(
                        0,
                        $mail_duration_ms
                    ),
            );

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
                    ) &&
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

                'duration_ms' =>
                    max(
                        0,
                        $mail_duration_ms
                    ),
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
                $order_total .
                ' تومان',

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
