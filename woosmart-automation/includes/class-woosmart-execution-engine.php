<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Execution Engine for WooSmart Automation.
 */
class WooSmart_Execution_Engine {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Condition Engine.
     *
     * @var WooSmart_Condition_Engine
     */
    private $condition_engine;

    /**
     * Action Engine.
     *
     * @var WooSmart_Action_Engine
     */
    private $action_engine;

    /**
     * Execution History instance.
     *
     * @var WooSmart_Execution_History
     */
    private $execution_history;

    /**
     * Initialize execution engine.
     *
     * @param WooSmart_Condition_Engine       $condition_engine Condition engine.
     * @param WooSmart_Action_Engine          $action_engine Action engine.
     * @param WooSmart_Execution_History|null $execution_history History service.
     */
    public function __construct(
        WooSmart_Condition_Engine $condition_engine,
        WooSmart_Action_Engine $action_engine,
        $execution_history = null
    ) {

        $this->logger =
            new WooSmart_Logger();

        $this->condition_engine =
            $condition_engine;

        $this->action_engine =
            $action_engine;

        $this->execution_history =
            (
                $execution_history instanceof
                WooSmart_Execution_History
            )
                ? $execution_history
                : new WooSmart_Execution_History();
    }

    /**
     * Execute automations for a trigger.
     *
     * @param string $trigger Trigger name.
     * @param array  $context Trigger context.
     *
     * @return void
     */
    public function execute(
        $trigger,
        $context = array()
    ) {

        $trigger =
            sanitize_key(
                $trigger
            );

        if (
            empty(
                $trigger
            )
        ) {

            return;
        }

        $execution_policy =
            $this->get_execution_policy();

        $automations =
            get_posts(
                array(
                    'post_type' =>
                        'woosmart_automation',

                    'post_status' =>
                        'publish',

                    'posts_per_page' =>
                        -1,

                    /*
                     * First fetch in the historical order.
                     * Priority sorting is applied below.
                     */
                    'orderby' =>
                        'date',

                    'order' =>
                        'DESC',

                    'meta_query' =>
                        array(
                            'relation' =>
                                'AND',

                            array(
                                'key' =>
                                    '_woosmart_status',

                                'value' =>
                                    'active',

                                'compare' =>
                                    '=',
                            ),

                            array(
                                'key' =>
                                    '_woosmart_trigger',

                                'value' =>
                                    $trigger,

                                'compare' =>
                                    '=',
                            ),
                        ),
                )
            );

        /*
         * Preserve original date order as the tie-breaker.
         */
        $date_order =
            array();

        foreach (
            $automations as $index =>
            $automation
        ) {

            $date_order[
                $automation->ID
            ] =
                $index;
        }

        /*
         * Normalize priorities.
         *
         * Explicit priorities are respected.
         * Automations without a stored Priority receive a fallback
         * after all explicit priorities, while preserving their
         * original newest-to-oldest order.
         */
        $explicit_priorities =
            array();

        $max_explicit_priority =
            0;

        foreach (
            $automations as $automation
        ) {

            $priority =
                get_post_meta(
                    $automation->ID,
                    '_woosmart_priority',
                    true
                );

            if (
                '' ===
                $priority
            ) {

                continue;
            }

            $priority =
                absint(
                    $priority
                );

            if (
                $priority < 1
            ) {

                continue;
            }

            $explicit_priorities[
                $automation->ID
            ] =
                $priority;

            if (
                $priority >
                $max_explicit_priority
            ) {

                $max_explicit_priority =
                    $priority;
            }
        }

        $fallback_priority_base =
            max(
                0,
                $max_explicit_priority
            ) + 10;

        $fallback_index =
            0;

        $normalized_priorities =
            array();

        foreach (
            $automations as $automation
        ) {

            if (
                isset(
                    $explicit_priorities[
                        $automation->ID
                    ]
                )
            ) {

                $normalized_priorities[
                    $automation->ID
                ] =
                    $explicit_priorities[
                        $automation->ID
                    ];

                continue;
            }

            $normalized_priorities[
                $automation->ID
            ] =
                $fallback_priority_base +
                $fallback_index;

            $fallback_index += 10;
        }

        usort(
            $automations,
            function(
                $a,
                $b
            ) use (
                $normalized_priorities,
                $date_order
            ) {

                $priority_a =
                    isset(
                        $normalized_priorities[
                            $a->ID
                        ]
                    )
                        ? $normalized_priorities[
                            $a->ID
                        ]
                        : PHP_INT_MAX;

                $priority_b =
                    isset(
                        $normalized_priorities[
                            $b->ID
                        ]
                    )
                        ? $normalized_priorities[
                            $b->ID
                        ]
                        : PHP_INT_MAX;

                if (
                    $priority_a !==
                    $priority_b
                ) {

                    return
                        $priority_a <
                        $priority_b
                            ? -1
                            : 1;
                }

                $date_a =
                    isset(
                        $date_order[
                            $a->ID
                        ]
                    )
                        ? $date_order[
                            $a->ID
                        ]
                        : PHP_INT_MAX;

                $date_b =
                    isset(
                        $date_order[
                            $b->ID
                        ]
                    )
                        ? $date_order[
                            $b->ID
                        ]
                        : PHP_INT_MAX;

                if (
                    $date_a ===
                    $date_b
                ) {

                    return 0;
                }

                return
                    $date_a <
                    $date_b
                        ? -1
                        : 1;
            }
        );

        $automation_ids =
            array();

        $automation_priorities =
            array();

        foreach (
            $automations as $automation
        ) {

            $automation_ids[] =
                absint(
                    $automation->ID
                );

            $automation_priorities[
                $automation->ID
            ] =
                isset(
                    $normalized_priorities[
                        $automation->ID
                    ]
                )
                    ? $normalized_priorities[
                        $automation->ID
                    ]
                    : 0;
        }

        $this->logger->log(
            'automation_scan',
            'بررسی اتوماسیون‌های فعال برای رویداد انجام شد.',
            array(
                'trigger' =>
                    $trigger,

                'context' =>
                    $context,

                'found_count' =>
                    count(
                        $automation_ids
                    ),

                'automation_ids' =>
                    $automation_ids,

                'automation_priorities' =>
                    $automation_priorities,

                'execution_policy' =>
                    $execution_policy,
            )
        );

        if (
            empty(
                $automations
            )
        ) {

            return;
        }

        foreach (
            $automations as $automation
        ) {

            $result =
                $this->execute_automation(
                    $automation->ID,
                    $trigger,
                    $context,
                    $execution_policy
                );

            if (
                ! is_array(
                    $result
                )
            ) {

                continue;
            }

            if (
                'first_match' ===
                $execution_policy &&
                ! empty(
                    $result['matched']
                )
            ) {

                break;
            }

            if (
                'first_success' ===
                $execution_policy &&
                ! empty(
                    $result['matched']
                ) &&
                ! empty(
                    $result['successful']
                )
            ) {

                break;
            }
        }
    }

    /**
     * Execute one Automation.
     *
     * @param int    $automation_id Automation ID.
     * @param string $trigger       Trigger name.
     * @param array  $context       Trigger context.
     * @param string $policy        Execution policy.
     *
     * @return array
     */
    private function execute_automation(
        $automation_id,
        $trigger,
        $context,
        $policy
    ) {

        $automation_id =
            absint(
                $automation_id
            );

        $result = array(
            'matched' =>
                false,

            'successful' =>
                false,

            'status' =>
                'skipped',
        );

        if (
            ! $automation_id
        ) {

            return $result;
        }

        $status =
            get_post_meta(
                $automation_id,
                '_woosmart_status',
                true
            );

        if (
            'active' !==
            $status
        ) {

            $this->logger->log(
                'automation_skipped',
                'اتوماسیون به دلیل غیرفعال بودن اجرا نشد.',
                array(
                    'automation_id' =>
                        $automation_id,

                    'trigger' =>
                        $trigger,
                )
            );

            return $result;
        }

        $automation =
            get_post(
                $automation_id
            );

        $automation_title =
            $automation
                ? $automation->post_title
                : '';

        $conditions =
            get_post_meta(
                $automation_id,
                '_woosmart_conditions',
                true
            );

        if (
            ! is_array(
                $conditions
            )
        ) {

            $conditions =
                array();
        }

        $actions =
            get_post_meta(
                $automation_id,
                '_woosmart_actions',
                true
            );

        if (
            ! is_array(
                $actions
            )
        ) {

            $actions =
                array();
        }

        $order_id =
            isset(
                $context['order_id']
            )
                ? absint(
                    $context['order_id']
                )
                : 0;

        $execution_id =
            $this->execution_history->start_execution(
                $automation_id,
                $order_id,
                $trigger,
                $policy,
                $context,
                $automation_title,
                $conditions,
                $actions
            );

        $conditions_passed =
            $this->condition_engine->evaluate(
                $conditions,
                $context
            );

        if (
            ! $conditions_passed
        ) {

            $this->logger->log(
                'automation_conditions_failed',
                'شرایط اتوماسیون برقرار نبود.',
                array(
                    'automation_id' =>
                        $automation_id,

                    'trigger' =>
                        $trigger,

                    'context' =>
                        $context,
                )
            );

            if (
                $execution_id
            ) {

                $this->execution_history->finish_execution(
                    $execution_id,
                    'conditions_failed',
                    0,
                    false,
                    'شرایط اتوماسیون برقرار نبود.',
                    false,
                    array()
                );
            }

            return $result;
        }

        $result[
            'matched'
        ] =
            true;

        $action_execution =
            $this->action_engine->execute_with_results(
                $actions,
                $context
            );

        $actions_successful =
            ! empty(
                $action_execution['success']
            );

        $actions_total =
            isset(
                $action_execution['actions_total']
            )
                ? absint(
                    $action_execution['actions_total']
                )
                : count(
                    $actions
                );

        $action_results =
            isset(
                $action_execution['actions']
            ) &&
            is_array(
                $action_execution['actions']
            )
                ? $action_execution['actions']
                : array();

        $result[
            'successful'
        ] =
            $actions_successful;

        $result[
            'status'
        ] =
            $actions_successful
                ? 'completed'
                : 'failed';

        if (
            $actions_successful
        ) {

            $this->logger->log(
                'automation_executed',
                'اتوماسیون با موفقیت اجرا شد.',
                array(
                    'automation_id' =>
                        $automation_id,

                    'trigger' =>
                        $trigger,

                    'context' =>
                        $context,

                    'actions_successful' =>
                        true,
                )
            );

        } else {

            $this->logger->log(
                'automation_failed',
                'اجرای اتوماسیون با شکست مواجه شد.',
                array(
                    'automation_id' =>
                        $automation_id,

                    'trigger' =>
                        $trigger,

                    'context' =>
                        $context,

                    'actions_successful' =>
                        false,
                )
            );
        }

        if (
            $execution_id
        ) {

            $this->execution_history->finish_execution(
                $execution_id,

                $actions_successful
                    ? 'completed'
                    : 'failed',

                $actions_total,

                $actions_successful,

                $actions_successful
                    ? 'تمام عملیات اتوماسیون با موفقیت اجرا شدند.'
                    : 'حداقل یکی از عملیات اتوماسیون با شکست مواجه شد.',

                true,

                $action_results
            );
        }

        return $result;
    }

    /**
     * Get current Execution Policy.
     *
     * @return string
     */
    private function get_execution_policy() {

        $policy =
            get_option(
                'woosmart_execution_policy',
                'all'
            );

        $allowed_policies = array(
            'all',
            'first_match',
            'first_success',
        );

        if (
            ! in_array(
                $policy,
                $allowed_policies,
                true
            )
        ) {

            $policy =
                'all';
        }

        return $policy;
    }
}
