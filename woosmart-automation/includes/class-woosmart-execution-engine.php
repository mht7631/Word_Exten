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
     * @param WooSmart_Action_Engine          $action_engine    Action engine.
     * @param WooSmart_Execution_History|null $execution_history History.
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

        $automation_ids =
            array();

        if (
            is_array(
                $automations
            )
        ) {

            foreach (
                $automations as $automation
            ) {

                if (
                    isset(
                        $automation->ID
                    )
                ) {

                    $automation_ids[] =
                        absint(
                            $automation->ID
                        );
                }
            }
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
     * @param int    $automation_id    Automation ID.
     * @param string $trigger          Trigger name.
     * @param array  $context          Trigger context.
     * @param string $execution_policy Current policy.
     *
     * @return array
     */
    private function execute_automation(
        $automation_id,
        $trigger,
        $context,
        $execution_policy
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

        /*
         * Snapshot is captured before Conditions/Actions execute.
         */
        $execution_id =
            $this->execution_history->start_execution(
                $automation_id,
                $order_id,
                $trigger,
                $execution_policy,
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

        /*
         * Execute Actions and receive per-Action results.
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
