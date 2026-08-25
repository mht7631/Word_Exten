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
                     * Initial deterministic order.
                     *
                     * Priority is handled explicitly below.
                     * Date DESC provides a stable fallback for
                     * automations that have the same Priority.
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

        if (
            empty(
                $automations
            )
        ) {
            return;
        }

        /*
         * Collect explicit priorities.
         *
         * Priority:
         * - Must be a positive integer.
         * - Lower number executes first.
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

        /*
         * Automations without an explicit Priority are placed
         * after all explicit priorities.
         *
         * Their original newest-to-oldest order is preserved
         * through their generated fallback priorities.
         */
        $fallback_priority_base =
            $max_explicit_priority +
            10;

        if (
            $fallback_priority_base < 10
        ) {
            $fallback_priority_base =
                10;
        }

        $fallback_index =
            0;

        $normalized_priorities =
            array();

        foreach (
            $automations as $automation
        ) {
            $automation_id =
                absint(
                    $automation->ID
                );

            if (
                isset(
                    $explicit_priorities[
                        $automation_id
                    ]
                )
            ) {
                $normalized_priorities[
                    $automation_id
                ] =
                    $explicit_priorities[
                        $automation_id
                    ];

                continue;
            }

            $normalized_priorities[
                $automation_id
            ] =
                $fallback_priority_base +
                $fallback_index;

            $fallback_index += 10;
        }

        /*
         * Deterministic sorting.
         *
         * Sort order:
         *
         * 1. Lower Priority first.
         * 2. If Priority is equal:
         *    newer post date first.
         * 3. If post dates are exactly equal:
         *    higher ID first.
         *
         * The previous implementation used the array index from
         * get_posts() as the second comparison key. Since every
         * automation had a unique index, the ID comparison was
         * effectively unreachable.
         *
         * This implementation performs a real deterministic
         * tie-break:
         *
         * Priority → post_date_gmt/post_date → ID
         */
        usort(
            $automations,
            function(
                $a,
                $b
            ) use (
                $normalized_priorities
            ) {

                $automation_id_a =
                    absint(
                        $a->ID
                    );

                $automation_id_b =
                    absint(
                        $b->ID
                    );

                $priority_a =
                    isset(
                        $normalized_priorities[
                            $automation_id_a
                        ]
                    )
                        ? (int)
                            $normalized_priorities[
                                $automation_id_a
                            ]
                        : PHP_INT_MAX;

                $priority_b =
                    isset(
                        $normalized_priorities[
                            $automation_id_b
                        ]
                    )
                        ? (int)
                            $normalized_priorities[
                                $automation_id_b
                            ]
                        : PHP_INT_MAX;

                /*
                 * Primary sort:
                 * Lower Priority executes first.
                 */
                if (
                    $priority_a <
                    $priority_b
                ) {
                    return -1;
                }

                if (
                    $priority_a >
                    $priority_b
                ) {
                    return 1;
                }

                /*
                 * Secondary sort:
                 * Newer creation date first.
                 *
                 * Prefer GMT date because it gives a stable
                 * canonical timestamp independent of site timezone.
                 */
                $date_a =
                    ! empty(
                        $a->post_date_gmt
                    )
                        ? $a->post_date_gmt
                        : $a->post_date;

                $date_b =
                    ! empty(
                        $b->post_date_gmt
                    )
                        ? $b->post_date_gmt
                        : $b->post_date;

                if (
                    $date_a >
                    $date_b
                ) {
                    return -1;
                }

                if (
                    $date_a <
                    $date_b
                ) {
                    return 1;
                }

                /*
                 * Final deterministic tie-breaker:
                 * Higher ID first.
                 *
                 * This is reached when Priority and creation
                 * timestamp are identical.
                 */
                if (
                    $automation_id_a >
                    $automation_id_b
                ) {
                    return -1;
                }

                if (
                    $automation_id_a <
                    $automation_id_b
                ) {
                    return 1;
                }

                return 0;
            }
        );

        $automation_ids =
            array();

        $automation_priorities =
            array();

        foreach (
            $automations as $automation
        ) {
            $automation_id =
                absint(
                    $automation->ID
                );

            $automation_ids[] =
                $automation_id;

            $automation_priorities[
                $automation_id
            ] =
                isset(
                    $normalized_priorities[
                        $automation_id
                    ]
                )
                    ? $normalized_priorities[
                        $automation_id
                    ]
                    : 0;
        }

        $this->logger->log(
            'automation_scan',
            'بررسی اتوماسیونهای فعال برای رویداد انجام شد.',
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

            /*
             * first_match:
             *
             * Stop immediately after the first automation whose
             * conditions are satisfied.
             */
            if (
                'first_match' ===
                $execution_policy &&
                ! empty(
                    $result['matched']
                )
            ) {
                break;
            }

            /*
             * first_success:
             *
             * Continue after a condition match if execution fails.
             * Stop only after a complete successful execution.
             */
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

        /*
         * Load the current stored Conditions.
         */
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

        /*
         * Normalize only for runtime compatibility.
         *
         * IMPORTANT:
         *
         * This method does NOT update the Automation metadata.
         * Execution must never silently modify the user's
         * Automation configuration.
         *
         * The exact normalized structure is then passed to
         * Execution History as the immutable execution snapshot.
         */
        $conditions =
            $this->normalize_conditions_for_current_mvp(
                $conditions
            );

        /*
         * Load Actions.
         */
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

        /*
         * Normalize action array indexes so the snapshot and
         * execution-result indexes remain deterministic.
         */
        $actions =
            array_values(
                $actions
            );

        $order_id =
            isset(
                $context['order_id']
            )
                ? absint(
                    $context['order_id']
                )
                : 0;

        /*
         * Start immutable execution snapshot BEFORE condition
         * evaluation and action execution.
         *
         * This guarantees that the history represents exactly
         * the configuration used for this execution.
         */
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

        /*
         * Evaluate Conditions.
         */
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

        /*
         * Execute Actions.
         */
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

        /*
         * Keep action result indexes stable.
         */
        $action_results =
            array_values(
                $action_results
            );

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
     * Normalize Conditions for current MVP runtime.
     *
     * The current MVP supports one Condition per Automation.
     *
     * IMPORTANT:
     * This method is intentionally read-only.
     *
     * It never calls update_post_meta().
     * Execution History must contain a snapshot of the actual
     * configuration used during execution without changing the
     * Automation itself.
     *
     * @param array $conditions Stored Conditions.
     *
     * @return array
     */
    private function normalize_conditions_for_current_mvp(
        $conditions
    ) {
        if (
            ! is_array(
                $conditions
            ) ||
            empty(
                $conditions
            )
        ) {
            return array();
        }

        /*
         * Remove malformed entries while preserving the original
         * order of valid Conditions.
         */
        $valid_conditions =
            array();

        foreach (
            $conditions as $condition
        ) {
            if (
                ! is_array(
                    $condition
                )
            ) {
                continue;
            }

            $field =
                isset(
                    $condition['field']
                )
                    ? sanitize_key(
                        $condition['field']
                    )
                    : '';

            $operator =
                isset(
                    $condition['operator']
                )
                    ? sanitize_key(
                        $condition['operator']
                    )
                    : '';

            if (
                empty(
                    $field
                ) ||
                empty(
                    $operator
                )
            ) {
                continue;
            }

            $condition['field'] =
                $field;

            $condition['operator'] =
                $operator;

            /*
             * Preserve the Condition value exactly.
             *
             * This is important for operators such as:
             *
             * between:
             * [
             *     'min' => '1700000',
             *     'max' => '7000000',
             * ]
             *
             * Do not cast this to a scalar.
             */
            if (
                ! array_key_exists(
                    'value',
                    $condition
                )
            ) {
                $condition['value'] =
                    '';
            }

            $valid_conditions[] =
                $condition;
        }

        if (
            empty(
                $valid_conditions
            )
        ) {
            return array();
        }

        /*
         * Current MVP:
         * one authoritative Condition.
         *
         * If legacy development data contains multiple Conditions,
         * use the last valid Condition without modifying the
         * original Automation metadata.
         */
        $current_condition =
            end(
                $valid_conditions
            );

        return array(
            $current_condition,
        );
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
