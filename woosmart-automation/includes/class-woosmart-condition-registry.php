<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Condition Registry for WooSmart Automation.
 *
 * Centralizes condition definitions and evaluation logic so new
 * conditions can be added without modifying the Condition Engine.
 */
class WooSmart_Condition_Registry {

    /**
     * Registered conditions.
     *
     * @var array
     */
    private $conditions = array();

    /**
     * Initialize registry.
     */
    public function __construct() {
        $this->register_default_conditions();

        /**
         * Allow extensions to register additional conditions.
         *
         * @param WooSmart_Condition_Registry $registry Registry instance.
         */
        do_action(
            'woosmart_condition_registry_init',
            $this
        );
    }

    /**
     * Register a condition.
     *
     * Expected definition:
     *
     * array(
     *     'label'     => 'Condition Label',
     *     'callback'  => callable,
     *     'operators' => array(
     *         'is_equal' => 'Is Equal',
     *     ),
     * )
     *
     * @param string $key        Condition key.
     * @param array  $definition Condition definition.
     *
     * @return bool
     */
    public function register( $key, $definition ) {

        $key = sanitize_key( $key );

        if ( empty( $key ) || ! is_array( $definition ) ) {
            return false;
        }

        if (
            ! isset( $definition['callback'] ) ||
            ! is_callable( $definition['callback'] )
        ) {
            return false;
        }

        if (
            isset( $definition['operators'] ) &&
            ! is_array( $definition['operators'] )
        ) {
            return false;
        }

        $definition['label'] = isset( $definition['label'] )
            ? sanitize_text_field( $definition['label'] )
            : $key;

        $definition['operators'] = isset( $definition['operators'] )
            ? $definition['operators']
            : array();

        $this->conditions[ $key ] = $definition;

        return true;
    }

    /**
     * Check whether a condition exists.
     *
     * @param string $key Condition key.
     *
     * @return bool
     */
    public function has( $key ) {

        $key = sanitize_key( $key );

        return isset( $this->conditions[ $key ] );
    }

    /**
     * Get a condition definition.
     *
     * @param string $key Condition key.
     *
     * @return array|null
     */
    public function get( $key ) {

        $key = sanitize_key( $key );

        if ( ! isset( $this->conditions[ $key ] ) ) {
            return null;
        }

        return $this->conditions[ $key ];
    }

    /**
     * Get all registered conditions.
     *
     * @return array
     */
    public function get_all() {
        return $this->conditions;
    }

    /**
     * Get operators supported by a condition.
     *
     * @param string $key Condition key.
     *
     * @return array
     */
    public function get_operators( $key ) {

        $definition = $this->get( $key );

        if ( ! $definition ) {
            return array();
        }

        return isset( $definition['operators'] )
            ? $definition['operators']
            : array();
    }

    /**
     * Evaluate a single condition.
     *
     * @param string $key       Condition key.
     * @param string $operator  Operator.
     * @param mixed  $value     Expected value.
     * @param array  $context   Execution context.
     *
     * @return bool
     */
    public function evaluate(
        $key,
        $operator,
        $value,
        $context = array()
    ) {

        $key = sanitize_key( $key );
        $operator = sanitize_key( $operator );

        $definition = $this->get( $key );

        if ( ! $definition ) {
            return false;
        }

        $operators = $this->get_operators( $key );

        if (
            ! empty( $operators ) &&
            ! isset( $operators[ $operator ] )
        ) {
            return false;
        }

        try {
            return (bool) call_user_func(
                $definition['callback'],
                $operator,
                $value,
                $context
            );
        } catch ( Throwable $exception ) {

            if ( function_exists( 'error_log' ) ) {
                error_log(
                    'WooSmart Condition Registry Error: ' .
                    $exception->getMessage()
                );
            }

            return false;
        }
    }

    /**
     * Register default WooCommerce conditions.
     *
     * @return void
     */
    private function register_default_conditions() {

        $this->register(
            'order_total',
            array(
                'label'     => 'Order Total',
                'operators' => array(
                    'is_equal'             => 'Is Equal',
                    'is_not_equal'         => 'Is Not Equal',
                    'greater_than'         => 'Greater Than',
                    'greater_than_or_equal'=> 'Greater Than or Equal',
                    'less_than'            => 'Less Than',
                    'less_than_or_equal'  => 'Less Than or Equal',
                ),
                'callback'  => array(
                    $this,
                    'evaluate_order_total',
                ),
            )
        );
    }

    /**
     * Evaluate Order Total condition.
     *
     * @param string $operator Condition operator.
     * @param mixed  $value    Expected value.
     * @param array  $context  Execution context.
     *
     * @return bool
     */
    public function evaluate_order_total(
        $operator,
        $value,
        $context = array()
    ) {

        $order_total = $this->get_order_total_from_context(
            $context
        );

        if ( null === $order_total ) {
            return false;
        }

        $expected_value = is_numeric( $value )
            ? (float) $value
            : null;

        if ( null === $expected_value ) {
            return false;
        }

        switch ( $operator ) {

            case 'is_equal':
                return $order_total === $expected_value;

            case 'is_not_equal':
                return $order_total !== $expected_value;

            case 'greater_than':
                return $order_total > $expected_value;

            case 'greater_than_or_equal':
                return $order_total >= $expected_value;

            case 'less_than':
                return $order_total < $expected_value;

            case 'less_than_or_equal':
                return $order_total <= $expected_value;

            default:
                return false;
        }
    }

    /**
     * Resolve order total from execution context.
     *
     * Supported context forms:
     *
     * - order_id
     * - order => WC_Order
     * - order_total
     *
     * @param array $context Execution context.
     *
     * @return float|null
     */
    private function get_order_total_from_context(
        $context
    ) {

        if ( isset( $context['order_total'] ) ) {

            if ( is_numeric( $context['order_total'] ) ) {
                return (float) $context['order_total'];
            }
        }

        if (
            isset( $context['order'] ) &&
            is_object( $context['order'] ) &&
            method_exists(
                $context['order'],
                'get_total'
            )
        ) {
            return (float) $context['order']->get_total();
        }

        if (
            isset( $context['order_id'] ) &&
            function_exists( 'wc_get_order' )
        ) {

            $order_id = absint(
                $context['order_id']
            );

            if ( $order_id ) {

                $order = wc_get_order(
                    $order_id
                );

                if ( $order ) {
                    return (float) $order->get_total();
                }
            }
        }

        return null;
    }
}
