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
            $this->get_candidate_automations(
                $trigger
            );

        if (
            empty(
                $automations
            )
        ) {
            return;
        }

        $automations =
            $this->sort_automations_by_priority(
                $automations
            );

        /*
         * Build a formal execution plan before
         * any Action is executed.
         *
         * The plan contains:
         *
         * - ordered Automations
         * - Priority
         * - Conditions
         * - Condition result
         * - Actions
         * - Execution Policy
         *
         * Action side effects are not performed while
         * the plan is being built.
         */
        $execution_plan =
            $this->build_execution_plan(
                $automations,
                $trigger,
                $context,
                $execution_policy
            );

        /*
         * Log the complete planning result before
         * executing the selected Automation path.
         */
        $this->logger->log(
            'execution_plan',
            'برنامه اجرای اتوماسیون‌ها قبل از اجرا ساخته شد.',
            array(
                'trigger' =>
                    $trigger,

                'context' =>
                    $context,

                'execution_policy' =>
                    $execution_policy,

                'plan' =>
                    $execution_plan,
            )
        );

        /*
         * The existing scan log remains available for
         * technical diagnostics and backward compatibility.
         */
        $automation_ids =
            array();

        $automation_priorities =
            array();

        foreach (
            $execution_plan['automations']
            as $planned_automation
        ) {

            $automation_id =
                isset(
                    $planned_automation['automation_id']
                )
                    ? absint(
                        $planned_automation['automation_id']
                    )
                    : 0;

            if (
                ! $automation_id
            ) {
                continue;
            }

            $automation_ids[] =
                $automation_id;

            $automation_priorities[
                $automation_id
            ] =
                isset(
                    $planned_automation['priority']
                )
                    ? $planned_automation['priority']
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

                'planned_match_count' =>
                    isset(
                        $execution_plan['matched_count']
                    )
                        ? absint(
                            $execution_plan['matched_count']
                        )
                        : 0,
            )
        );

        /*
         * Execute only the Automations selected by the
         * formal plan.
         */
        foreach (
            $execution_plan['automations']
            as $planned_automation
        ) {

            if (
                empty(
                    $planned_automation['should_execute']
                )
            ) {
                continue;
            }

            $automation_id =
                isset(
                    $planned_automation['automation_id']
                )
                    ? absint(
                        $planned_automation['automation_id']
                    )
                    : 0;

            if (
                ! $automation_id
            ) {
                continue;
            }

            $result =
                $this->execute_automation(
                    $automation_id,
                    $trigger,
                    $context,
                    $execution_policy
                );

            /*
             * The plan already determined the relevant
             * Automations according to the current policy.
             *
             * The runtime result is still evaluated because
             * first_success depends on complete Action success.
             */
            if (
                'first_match' ===
                $execution_policy &&
                ! empty(
                    $planned_automation['matched']
                )
            ) {
                break;
            }

            if (
                'first_success' ===
                $execution_policy &&
                is_array(
                    $result
                ) &&
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
     * Get active candidate Automations for a Trigger.
     *
     * @param string $trigger Trigger name.
     *
     * @return array
     */
    private function get_candidate_automations(
        $trigger
    ) {

        return get_posts(
            array(
                'post_type' =>
                    'woosmart_automation',

                'post_status' =>
                    'publish',

                'posts_per_page' =>
                    -1,

                /*
                 * Initial deterministic order.
                 * Priority is normalized and applied
                 * explicitly below.
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
    }

    /**
     * Sort Automations according to deterministic Priority rules.
     *
     * @param array $automations Candidate Automations.
     *
     * @return array
     */
    private function sort_automations_by_priority(
        $automations
    ) {

        if (
            empty(
                $automations
            )
        ) {
            return array();
        }

        /*
         * Collect explicit priorities.
         */
        $explicit_priorities =
            array();

        $max_explicit_priority =
            0;

        foreach (
            $automations
            as $automation
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
         * Automations without explicit Priority are
         * placed after all explicit priorities.
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
            $automations
            as $automation
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

            $fallback_index +=
                10;
        }

        /*
         * Deterministic sorting:
         *
         * 1. Lower Priority first.
         * 2. Newer creation date first.
         * 3. Higher Automation ID first when timestamps match.
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

        return $automations;
    }

    /**
     * Build the formal execution plan.
     *
     * Planning evaluates Conditions and determines which
     * Automations are eligible to execute. It never performs
     * Action side effects.
     *
     * @param array  $automations      Ordered Automations.
     * @param string $trigger           Trigger name.
     * @param array  $context           Trigger context.
     * @param string $execution_policy Execution Policy.
     *
     * @return array
     */
    private function build_execution_plan(
        $automations,
        $trigger,
        $context,
        $execution_policy
    ) {

        $plan = array(
            'trigger' =>
                $trigger,

            'execution_policy' =>
                $execution_policy,

            'automations' =>
                array(),

            'matched_count' =>
                0,

            'planned_count' =>
                0,
        );

        if (
            empty(
                $automations
            )
        ) {
            return $plan;
        }

        $first_match_planned =
            false;

        $first_success_planned =
            false;

        foreach (
            $automations
            as $automation
        ) {

            $automation_id =
                absint(
                    $automation->ID
                );

            if (
                ! $automation_id
            ) {
                continue;
            }

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

            $conditions =
                $this->normalize_conditions_for_current_mvp(
                    $conditions
                );

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

            $actions =
                array_values(
                    $actions
                );

            $priority =
                $this->get_runtime_priority(
                    $automation_id,
                    $automations
                );

            /*
             * Condition evaluation during planning is safe
             * because it does not execute Actions.
             */
            $matched =
                (bool)
                $this->condition_engine->evaluate(
                    $conditions,
                    $context
                );

            $plan_entry = array(
                'automation_id' =>
                    $automation_id,

                'automation_title' =>
                    $automation->post_title,

                'priority' =>
                    $priority,

                'trigger' =>
                    $trigger,

                'conditions' =>
                    $conditions,

                'matched' =>
                    $matched,

                'actions' =>
                    $this->build_action_plan(
                        $actions
                    ),

                'should_execute' =>
                    false,

                'planning_status' =>
                    $matched
                        ? 'matched'
                        : 'conditions_failed',
            );

            if (
                $matched
            ) {

                $plan['matched_count'] +=
                    1;

                /*
                 * ALL:
                 * Every matched Automation is planned.
                 */
                if (
                    'all' ===
                    $execution_policy
                ) {

                    $plan_entry[
                        'should_execute'
                    ] =
                        true;

                    $plan_entry[
                        'planning_status'
                    ] =
                        'planned';

                    $plan[
                        'planned_count'
                    ] +=
                        1;
                }

                /*
                 * FIRST_MATCH:
                 * First matching Automation is planned,
                 * all later Automations remain unplanned.
                 */
                elseif (
                    'first_match' ===
                    $execution_policy
                ) {

                    if (
                        ! $first_match_planned
                    ) {

                        $plan_entry[
                            'should_execute'
                        ] =
                            true;

                        $plan_entry[
                            'planning_status'
                        ] =
                            'planned';

                        $plan[
                            'planned_count'
                        ] +=
                            1;

                        $first_match_planned =
                            true;

                    } else {

                        $plan_entry[
                            'planning_status'
                        ] =
                            'blocked_by_policy';
                    }
                }

                /*
                 * FIRST_SUCCESS:
                 *
                 * The planner cannot know whether Actions will
                 * succeed without executing them.
                 *
                 * Therefore every matching Automation remains
                 * eligible in Priority order and runtime execution
                 * stops after the first complete success.
                 */
                elseif (
                    'first_success' ===
                    $execution_policy
                ) {

                    if (
                        ! $first_success_planned
                    ) {

                        $plan_entry[
                            'should_execute'
                        ] =
                            true;

                        $plan_entry[
                            'planning_status'
                        ] =
                            'planned';

                        $plan[
                            'planned_count'
                        ] +=
                            1;

                        /*
                         * Do not set a final stop marker here.
                         * Additional matches may still be needed
                         * if the current Automation later fails.
                         */
                    }
                }
            }

            $plan[
                'automations'
            ][] =
                $plan_entry;
        }

        /*
         * For FIRST_SUCCESS all matching Automations must remain
         * runtime-eligible because success is only known after
         * Action execution.
         *
         * Rewrite the entries accordingly.
         */
        if (
            'first_success' ===
            $execution_policy
        ) {

            $plan['planned_count'] =
                0;

            foreach (
                $plan['automations']
                as $index => $plan_entry
            ) {

                if (
                    empty(
                        $plan_entry['matched']
                    )
                ) {
                    continue;
                }

                $plan['automations'][
                    $index
                ]['should_execute'] =
                    true;

                $plan['automations'][
                    $index
                ]['planning_status'] =
                    'runtime_eligible';

                $plan['planned_count'] +=
                    1;
            }
        }

        return $plan;
    }

    /**
     * Build a side-effect-free Action execution plan.
     *
     * @param array $actions Actions.
     *
     * @return array
     */
    private function build_action_plan(
        $actions
    ) {

        if (
            ! is_array(
                $actions
            )
        ) {
            return array();
        }

        $planned_actions =
            array();

        foreach (
            $actions as $index => $action
        ) {

            if (
                ! is_array(
                    $action
                )
            ) {
                continue;
            }

            $planned_action = array(
                'action_index' =>
                    $index + 1,

                'type' =>
                    isset(
                        $action['type']
                    )
                        ? sanitize_key(
                            $action['type']
                        )
                        : '',
            );

            if (
                'change_order_status' ===
                $planned_action['type']
            ) {

                $planned_action[
                    'target_status'
                ] =
                    isset(
                        $action['status']
                    )
                        ? sanitize_key(
                            $action['status']
                        )
                        : '';
            }

            if (
                'notify_admin' ===
                $planned_action['type']
            ) {

                $planned_action[
                    'subject'
                ] =
                    isset(
                        $action['subject']
                    )
                        ? sanitize_text_field(
                            $action['subject']
                        )
                        : '';
            }

            $planned_actions[] =
                $planned_action;
        }

        return $planned_actions;
    }

    /**
     * Get normalized runtime Priority for one Automation.
     *
     * This uses the same deterministic rules as the main sort.
     *
     * @param int   $automation_id Automation ID.
     * @param array $automations   Ordered candidate Automations.
     *
     * @return int
     */
    private function get_runtime_priority(
        $automation_id,
        $automations
    ) {

        $automation_id =
            absint(
                $automation_id
            );

        if (
            ! $automation_id
        ) {
            return 0;
        }

        $explicit_priorities =
            array();

        $max_explicit_priority =
            0;

        foreach (
            $automations
            as $automation
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

        if (
            isset(
                $explicit_priorities[
                    $automation_id
                ]
            )
        ) {
            return
                $explicit_priorities[
                    $automation_id
                ];
        }

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

        foreach (
            $automations
            as $automation
        ) {

            $current_id =
                absint(
                    $automation->ID
                );

            if (
                isset(
                    $explicit_priorities[
                        $current_id
                    ]
                )
            ) {
                continue;
            }

            if (
                $current_id ===
                $automation_id
            ) {
                return
                    $fallback_priority_base +
                    $fallback_index;
            }

            $fallback_index +=
                10;
        }

        return $fallback_priority_base;
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
         * Load current Conditions.
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
         * This method is read-only and never changes
         * the Automation metadata.
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
         * Normalize action indexes.
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
         * Start immutable execution snapshot before
         * Action execution.
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
         * Evaluate Conditions again at execution time.
         *
         * This preserves the existing runtime safety behavior:
         * the actual Automation execution always validates its
         * current runtime condition immediately before Actions.
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
         * Keep Action result indexes stable.
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
         * Remove malformed entries while preserving valid entries.
         */
        $valid_conditions =
            array();

        foreach (
            $conditions
            as $condition
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
         * Legacy structures containing multiple Conditions
         * use the last valid Condition.
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

        $allowed_policies =
            array(
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
