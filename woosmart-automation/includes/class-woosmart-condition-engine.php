<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Condition Engine for WooSmart Automation.
 */
class WooSmart_Condition_Engine {

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
    private $registry;

    /**
     * Initialize Condition Engine.
     */
    public function __construct() {

        $this->logger =
            new WooSmart_Logger();

        $this->registry =
            new WooSmart_Condition_Registry();
    }

    /**
     * Evaluate automation conditions.
     *
     * Supported formats:
     *
     * 1. Legacy flat conditions:
     *
     *    [
     *        condition,
     *        condition,
     *        condition,
     *    ]
     *
     *    Meaning:
     *
     *    Condition 1 AND Condition 2 AND Condition 3
     *
     * 2. Grouped conditions:
     *
     *    [
     *        'version' => 1,
     *        'groups' => [
     *            [
     *                'conditions' => [
     *                    condition,
     *                    condition,
     *                ],
     *            ],
     *            [
     *                'conditions' => [
     *                    condition,
     *                    condition,
     *                ],
     *            ],
     *        ],
     *    ]
     *
     *    Meaning:
     *
     *    (A AND B) OR (C AND D)
     *
     * The flat legacy format remains fully backward compatible.
     *
     * @param array $conditions   Conditions configuration.
     * @param array $context      Execution context.
     * @param bool  $log_results  Whether condition results should be logged.
     *
     * @return bool
     */
    public function evaluate(
        $conditions,
        $context = array(),
        $log_results = true
    ) {

        $result =
            $this->evaluate_with_results(
                $conditions,
                $context,
                $log_results
            );

        return ! empty(
            $result['matched']
        );
    }

    /**
     * Evaluate conditions and return detailed results.
     *
     * This method is intentionally additive so the existing
     * evaluate() boolean API remains unchanged.
     *
     * @param array $conditions   Conditions configuration.
     * @param array $context      Execution context.
     * @param bool  $log_results  Whether condition results should be logged.
     *
     * @return array
     */
    public function evaluate_with_results(
        $conditions,
        $context = array(),
        $log_results = true
    ) {

        if (
            ! is_array(
                $conditions
            )
        ) {

            if (
                $log_results
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Invalid condition configuration.',
                    array(
                        'condition_configuration' =>
                            $conditions,
                    )
                );
            }

            return array(
                'matched' =>
                    false,

                'format' =>
                    'invalid',

                'conditions' =>
                    array(),

                'groups' =>
                    array(),
            );
        }

        /*
         * Legacy empty Conditions retain the current
         * WooSmart behavior: an Automation without
         * Conditions is considered matched.
         */
        if (
            empty(
                $conditions
            )
        ) {

            return array(
                'matched' =>
                    true,

                'format' =>
                    'legacy',

                'conditions' =>
                    array(),

                'groups' =>
                    array(),
            );
        }

        /*
         * Explicit Grouped Condition structure.
         */
        if (
            array_key_exists(
                'groups',
                $conditions
            )
        ) {

            return $this->evaluate_grouped_conditions(
                $conditions,
                $context,
                $log_results
            );
        }

        /*
         * Otherwise preserve the existing flat
         * Multiple Conditions behavior.
         */
        return $this->evaluate_legacy_conditions(
            $conditions,
            $context,
            $log_results
        );
    }

    /**
     * Evaluate legacy flat Conditions using AND.
     *
     * @param array $conditions  Flat Conditions.
     * @param array $context     Execution context.
     * @param bool  $log_results Whether results should be logged.
     *
     * @return array
     */
    private function evaluate_legacy_conditions(
        $conditions,
        $context,
        $log_results
    ) {

        $results =
            array();

        foreach (
            $conditions as $index => $condition
        ) {

            $condition_result =
                $this->evaluate_single_condition(
                    $condition,
                    $context,
                    $log_results
                );

            $results[] =
                array(
                    'index' =>
                        $index,

                    'passed' =>
                        $condition_result,
                );

            /*
             * Legacy behavior is strict AND.
             */
            if (
                ! $condition_result
            ) {

                return array(
                    'matched' =>
                        false,

                    'format' =>
                        'legacy',

                    'conditions' =>
                        $results,

                    'groups' =>
                        array(),
                );
            }
        }

        return array(
            'matched' =>
                true,

            'format' =>
                'legacy',

            'conditions' =>
                $results,

            'groups' =>
                array(),
        );
    }

    /**
     * Evaluate grouped Conditions.
     *
     * Group behavior:
     *
     * - Conditions inside one Group = AND.
     * - Groups themselves = OR.
     *
     * Example:
     *
     * (A AND B) OR (C AND D)
     *
     * @param array $configuration Grouped Condition configuration.
     * @param array $context       Execution context.
     * @param bool  $log_results   Whether results should be logged.
     *
     * @return array
     */
    private function evaluate_grouped_conditions(
        $configuration,
        $context,
        $log_results
    ) {

        if (
            ! isset(
                $configuration['groups']
            ) ||
            ! is_array(
                $configuration['groups']
            )
        ) {

            if (
                $log_results
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Invalid condition group configuration.',
                    array(
                        'condition_configuration' =>
                            $configuration,
                    )
                );
            }

            return array(
                'matched' =>
                    false,

                'format' =>
                    'groups',

                'conditions' =>
                    array(),

                'groups' =>
                    array(),
            );
        }

        /*
         * An explicitly grouped configuration must contain
         * at least one Group.
         *
         * This prevents an accidental empty grouped
         * configuration from behaving like "no conditions".
         */
        if (
            empty(
                $configuration['groups']
            )
        ) {

            if (
                $log_results
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Grouped condition configuration contains no groups.',
                    array(
                        'condition_configuration' =>
                            $configuration,
                    )
                );
            }

            return array(
                'matched' =>
                    false,

                'format' =>
                    'groups',

                'conditions' =>
                    array(),

                'groups' =>
                    array(),
            );
        }

        $group_results =
            array();

        foreach (
            $configuration['groups'] as $group_index =>
            $group
        ) {

            if (
                ! is_array(
                    $group
                ) ||
                ! isset(
                    $group['conditions']
                ) ||
                ! is_array(
                    $group['conditions']
                ) ||
                empty(
                    $group['conditions']
                )
            ) {

                if (
                    $log_results
                ) {

                    $this->logger->log(
                        'condition_failed',
                        'Invalid or empty condition group.',
                        array(
                            'group_index' =>
                                $group_index,

                            'group' =>
                                $group,
                        )
                    );
                }

                $group_results[] =
                    array(
                        'index' =>
                            $group_index,

                        'matched' =>
                            false,

                        'conditions' =>
                            array(),
                    );

                /*
                 * Invalid Group cannot satisfy the OR expression,
                 * so continue evaluating the remaining Groups.
                 */
                continue;
            }

            $group_matched =
                true;

            $condition_results =
                array();

            foreach (
                $group['conditions'] as $condition_index =>
                $condition
            ) {

                $condition_result =
                    $this->evaluate_single_condition(
                        $condition,
                        $context,
                        $log_results
                    );

                $condition_results[] =
                    array(
                        'index' =>
                            $condition_index,

                        'passed' =>
                            $condition_result,
                    );

                /*
                 * Conditions within a Group use AND.
                 */
                if (
                    ! $condition_result
                ) {

                    $group_matched =
                        false;

                    /*
                     * There is no reason to evaluate more
                     * Conditions inside this failed Group.
                     */
                    break;
                }
            }

            $group_results[] =
                array(
                    'index' =>
                        $group_index,

                    'matched' =>
                        $group_matched,

                    'conditions' =>
                        $condition_results,
                );

            /*
             * Groups use OR.
             *
             * The first fully matched Group is sufficient
             * to satisfy the complete configuration.
             */
            if (
                $group_matched
            ) {

                return array(
                    'matched' =>
                        true,

                    'format' =>
                        'groups',

                    'conditions' =>
                        array(),

                    'groups' =>
                        $group_results,
                );
            }
        }

        return array(
            'matched' =>
                false,

            'format' =>
                'groups',

            'conditions' =>
                array(),

            'groups' =>
                $group_results,
        );
    }

    /**
     * Evaluate one atomic Condition.
     *
     * @param mixed $condition Condition configuration.
     * @param array $context   Execution context.
     * @param bool  $log_result Whether result should be logged.
     *
     * @return bool
     */
    private function evaluate_single_condition(
        $condition,
        $context,
        $log_result
    ) {

        if (
            ! is_array(
                $condition
            )
        ) {

            if (
                $log_result
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Invalid condition configuration.',
                    array(
                        'condition' =>
                            $condition,
                    )
                );
            }

            return false;
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

        $value =
            isset(
                $condition['value']
            )
                ? $condition['value']
                : '';

        if (
            empty(
                $field
            )
        ) {

            if (
                $log_result
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Condition field is missing.',
                    array(
                        'condition' =>
                            $condition,
                    )
                );
            }

            return false;
        }

        if (
            empty(
                $operator
            )
        ) {

            if (
                $log_result
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Condition operator is missing.',
                    array(
                        'condition' =>
                            $condition,
                    )
                );
            }

            return false;
        }

        if (
            ! $this->registry->has(
                $field
            )
        ) {

            if (
                $log_result
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Unknown condition field.',
                    array(
                        'field' =>
                            $field,

                        'operator' =>
                            $operator,

                        'value' =>
                            $value,
                    )
                );
            }

            return false;
        }

        $result =
            $this->registry->evaluate(
                $field,
                $operator,
                $value,
                $context
            );

        if (
            ! $result
        ) {

            if (
                $log_result
            ) {

                $this->logger->log(
                    'condition_failed',
                    'Automation condition was not satisfied.',
                    array(
                        'field' =>
                            $field,

                        'operator' =>
                            $operator,

                        'value' =>
                            $value,

                        'context' =>
                            $this->get_safe_context(
                                $context
                            ),
                    )
                );
            }

            return false;
        }

        if (
            $log_result
        ) {

            $this->logger->log(
                'condition_passed',
                'Automation condition was satisfied.',
                array(
                    'field' =>
                        $field,

                    'operator' =>
                        $operator,

                    'value' =>
                        $value,
                )
            );
        }

        return true;
    }

    /**
     * Get registered conditions.
     *
     * Useful for admin UI and future API.
     *
     * @return array
     */
    public function get_conditions() {

        return $this->registry->get_all();
    }

    /**
     * Get condition definition.
     *
     * @param string $field Condition field.
     *
     * @return array|null
     */
    public function get_condition(
        $field
    ) {

        return $this->registry->get(
            $field
        );
    }

    /**
     * Get condition operators.
     *
     * @param string $field Condition field.
     *
     * @return array
     */
    public function get_operators(
        $field
    ) {

        return $this->registry->get_operators(
            $field
        );
    }

    /**
     * Return a safe version of execution context for logging.
     *
     * Avoids logging large WooCommerce objects.
     *
     * @param array $context Execution context.
     *
     * @return array
     */
    private function get_safe_context(
        $context
    ) {

        if (
            ! is_array(
                $context
            )
        ) {
            return array();
        }

        $safe_context =
            array();

        foreach (
            $context
            as $key => $value
        ) {

            if (
                is_object(
                    $value
                )
            ) {

                if (
                    'order' === $key &&
                    method_exists(
                        $value,
                        'get_id'
                    )
                ) {

                    $safe_context[
                        'order_id'
                    ] =
                        absint(
                            $value->get_id()
                        );
                }

                continue;
            }

            if (
                is_array(
                    $value
                )
            ) {
                continue;
            }

            $safe_context[
                sanitize_key(
                    $key
                )
            ] =
                is_scalar(
                    $value
                )
                    ? $value
                    : '';
        }

        return $safe_context;
    }
}
