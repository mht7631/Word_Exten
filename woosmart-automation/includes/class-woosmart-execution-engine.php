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
     * Initialize execution engine.
     *
     * @param WooSmart_Condition_Engine $condition_engine Condition engine.
     * @param WooSmart_Action_Engine    $action_engine    Action engine.
     */
    public function __construct(
        WooSmart_Condition_Engine $condition_engine,
        WooSmart_Action_Engine $action_engine
    ) {

        $this->logger =
            new WooSmart_Logger();

        $this->condition_engine =
            $condition_engine;

        $this->action_engine =
            $action_engine;
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
            empty( $trigger )
        ) {
            return;
        }

        /*
         * Find all active, published Automations
         * that use the received Trigger.
         */
        $automations =
            get_posts(
                array(
                    'post_type' =>
                        'woosmart_automation',

                    'post_status' =>
                        'publish',

                    'posts_per_page' =>
                        -1,

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
         * Diagnostic logging:
         * record exactly how many Automations were found
         * and which Automation IDs were returned.
         *
         * This is temporary and will be removed after
         * the query problem is identified.
         */
        $automation_ids =
            array();

        if (
            is_array(
                $automations
            )
        ) {

            foreach (
                $automations
                as $automation
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
         * Execute every matching Automation.
         */
        foreach (
            $automations
            as $automation
        ) {

            $this->execute_automation(
                $automation->ID,
                $trigger,
                $context
            );
        }
    }

    /**
     * Execute a single automation.
     *
     * @param int    $automation_id Automation ID.
     * @param string $trigger       Trigger name.
     * @param array  $context       Trigger context.
     *
     * @return void
     */
    private function execute_automation(
        $automation_id,
        $trigger,
        $context
    ) {

        $automation_id =
            absint(
                $automation_id
            );

        if (
            ! $automation_id
        ) {
            return;
        }

        $status =
            get_post_meta(
                $automation_id,
                '_woosmart_status',
                true
            );

        /*
         * Safety check.
         */
        if (
            'active' !== $status
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

            return;
        }

        /*
         * Get conditions.
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
         * Evaluate conditions.
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

            return;
        }

        /*
         * Get actions.
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
         * Execute actions.
         */
        $actions_successful =
            $this->action_engine->execute(
                $actions,
                $context
            );

        /*
         * Log execution result.
         */
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
    }
}
