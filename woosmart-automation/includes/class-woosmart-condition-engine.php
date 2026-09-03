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
     * Evaluate all automation conditions.
     *
     * All conditions must pass.
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

        if (
            empty( $conditions ) ||
            ! is_array( $conditions )
        ) {
            return true;
        }

        foreach (
            $conditions
            as $condition
        ) {

            if (
                ! is_array(
                    $condition
                )
            ) {

                if (
                    $log_results
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
                    $log_results
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
                    $log_results
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
                    $log_results
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
                    $log_results
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
                $log_results
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
