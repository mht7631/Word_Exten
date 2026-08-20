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
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

        $this->condition_engine =
            new WooSmart_Condition_Engine();

        $this->action_engine =
            new WooSmart_Action_Engine();
    }

    /**
     * Execute automations for a trigger.
     *
     * @param string $trigger Trigger name.
     * @param array  $context Trigger context.
     *
     * @return void
     */
    public function execute( $trigger, $context = array() ) {

        $trigger = sanitize_key( $trigger );

        if ( empty( $trigger ) ) {
            return;
        }

        $automations = get_posts(
            array(
                'post_type'      => 'woosmart_automation',
                'post_status'    => 'publish',
                'posts_per_page' => -1,

                'meta_query'     => array(
                    'relation' => 'AND',

                    array(
                        'key'     => '_woosmart_status',
                        'value'   => 'active',
                        'compare' => '=',
                    ),

                    array(
                        'key'     => '_woosmart_trigger',
                        'value'   => $trigger,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $automations ) ) {
            return;
        }

        foreach ( $automations as $automation ) {

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

        $automation_id = absint(
            $automation_id
        );

        if ( ! $automation_id ) {
            return;
        }

        $status = get_post_meta(
            $automation_id,
            '_woosmart_status',
            true
        );

        /*
         * Safety check.
         */
        if ( 'active' !== $status ) {

            $this->logger->log(
                'automation_skipped',
                'Automation was skipped because it is inactive.',
                array(
                    'automation_id' => $automation_id,
                    'trigger'       => $trigger,
                )
            );

            return;
        }

        /*
         * Get conditions.
         */
        $conditions = get_post_meta(
            $automation_id,
            '_woosmart_conditions',
            true
        );

        if ( ! is_array( $conditions ) ) {
            $conditions = array();
        }

        /*
         * Evaluate conditions.
         */
        $conditions_passed =
            $this->condition_engine->evaluate(
                $conditions,
                $context
            );

        if ( ! $conditions_passed ) {

            $this->logger->log(
                'automation_conditions_failed',
                'Automation conditions were not satisfied.',
                array(
                    'automation_id' => $automation_id,
                    'trigger'       => $trigger,
                    'context'       => $context,
                )
            );

            return;
        }

        /*
         * Get actions.
         */
        $actions = get_post_meta(
            $automation_id,
            '_woosmart_actions',
            true
        );

        if ( ! is_array( $actions ) ) {
            $actions = array();
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
         * Log automation execution.
         */
        $this->logger->log(
            'automation_executed',
            'Automation was triggered successfully.',
            array(
                'automation_id'     => $automation_id,
                'trigger'           => $trigger,
                'context'           => $context,
                'actions_successful' => $actions_successful,
            )
        );
    }
}