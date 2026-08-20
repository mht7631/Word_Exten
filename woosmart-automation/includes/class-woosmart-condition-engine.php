<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Condition Engine for WooSmart Automation.
 */
class WooSmart_Condition_Engine {

    /**
     * Evaluate all conditions.
     *
     * @param array $conditions Conditions.
     * @param array $context    Trigger context.
     *
     * @return bool
     */
    public function evaluate( $conditions, $context = array() ) {

        if ( empty( $conditions ) ) {
            return true;
        }

        foreach ( $conditions as $condition ) {

            if ( ! is_array( $condition ) ) {
                return false;
            }

            if ( ! $this->evaluate_condition( $condition, $context ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition.
     *
     * @param array $condition Condition definition.
     * @param array $context   Trigger context.
     *
     * @return bool
     */
    private function evaluate_condition( $condition, $context ) {

        $field = isset( $condition['field'] )
            ? sanitize_key( $condition['field'] )
            : '';

        $operator = isset( $condition['operator'] )
            ? sanitize_key( $condition['operator'] )
            : '';

        $expected_value = isset( $condition['value'] )
            ? $condition['value']
            : null;

        if ( empty( $field ) || empty( $operator ) ) {
            return false;
        }

        $actual_value = $this->get_field_value(
            $field,
            $context
        );

        if ( null === $actual_value ) {
            return false;
        }

        return $this->compare(
            $actual_value,
            $operator,
            $expected_value
        );
    }

    /**
     * Get field value from trigger context.
     *
     * @param string $field   Field name.
     * @param array  $context Trigger context.
     *
     * @return mixed
     */
    private function get_field_value( $field, $context ) {

        if ( 'order_total' !== $field ) {
            return null;
        }

        if ( empty( $context['order_id'] ) ) {
            return null;
        }

        $order_id = absint(
            $context['order_id']
        );

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return null;
        }

        return (float) $order->get_total();
    }

    /**
     * Compare actual and expected values.
     *
     * @param mixed  $actual_value   Actual value.
     * @param string $operator       Comparison operator.
     * @param mixed  $expected_value  Expected value.
     *
     * @return bool
     */
    private function compare(
        $actual_value,
        $operator,
        $expected_value
    ) {

        $actual_value   = (float) $actual_value;
        $expected_value = (float) $expected_value;

        switch ( $operator ) {

            case 'is_equal':
                return $actual_value === $expected_value;

            case 'is_not_equal':
                return $actual_value !== $expected_value;

            case 'greater_than':
                return $actual_value > $expected_value;

            case 'greater_than_or_equal':
                return $actual_value >= $expected_value;

            case 'less_than':
                return $actual_value < $expected_value;

            case 'less_than_or_equal':
                return $actual_value <= $expected_value;

            default:
                return false;
        }
    }
}