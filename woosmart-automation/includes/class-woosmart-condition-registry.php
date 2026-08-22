<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registry for WooSmart Automation conditions.
 *
 * Stores condition definitions in one central location and evaluates them
 * against the WooSmart execution context.
 */
class WooSmart_Condition_Registry {

    /**
     * Registered condition definitions.
     *
     * @var array
     */
    private $conditions = array();

    /**
     * Initialize the condition registry.
     */
    public function __construct() {

        $this->register_default_conditions();
    }

    /**
     * Register a condition definition.
     *
     * @param string $key        Condition key.
     * @param array  $definition Condition definition.
     *
     * @return bool
     */
    public function register(
        $key,
        $definition
    ) {

        $key =
            sanitize_key(
                $key
            );

        if (
            empty( $key ) ||
            ! is_array( $definition )
        ) {

            return false;
        }

        if (
            empty(
                $definition['label']
            ) ||
            empty(
                $definition['operators']
            ) ||
            ! is_array(
                $definition['operators']
            ) ||
            ! isset(
                $definition['evaluator']
            ) ||
            ! is_callable(
                $definition['evaluator']
            )
        ) {

            return false;
        }

        $this->conditions[
            $key
        ] =
            $definition;

        return true;
    }

    /**
     * Check whether a condition is registered.
     *
     * @param string $key Condition key.
     *
     * @return bool
     */
    public function has(
        $key
    ) {

        $key =
            sanitize_key(
                $key
            );

        return isset(
            $this->conditions[
                $key
            ]
        );
    }

    /**
     * Get a public condition definition.
     *
     * @param string $key Condition key.
     *
     * @return array|null
     */
    public function get(
        $key
    ) {

        $key =
            sanitize_key(
                $key
            );

        if (
            ! $this->has(
                $key
            )
        ) {

            return null;
        }

        return $this->get_public_definition(
            $this->conditions[
                $key
            ]
        );
    }

    /**
     * Get all registered public condition definitions.
     *
     * @return array
     */
    public function get_all() {

        $definitions =
            array();

        foreach (
            $this->conditions
            as $key =>
            $definition
        ) {

            $definitions[
                $key
            ] =
                $this->get_public_definition(
                    $definition
                );
        }

        return $definitions;
    }

    /**
     * Get operators for a condition.
     *
     * @param string $key Condition key.
     *
     * @return array
     */
    public function get_operators(
        $key
    ) {

        $key =
            sanitize_key(
                $key
            );

        if (
            ! $this->has(
                $key
            )
        ) {

            return array();
        }

        return $this->conditions[
            $key
        ]['operators'];
    }

    /**
     * Evaluate one registered condition.
     *
     * @param string $key      Condition key.
     * @param string $operator Operator key.
     * @param mixed  $value    Configured condition value.
     * @param array  $context  Execution context.
     *
     * @return bool
     */
    public function evaluate(
        $key,
        $operator,
        $value,
        $context = array()
    ) {

        $key =
            sanitize_key(
                $key
            );

        $operator =
            sanitize_key(
                $operator
            );

        if (
            ! $this->has(
                $key
            )
        ) {

            return false;
        }

        $definition =
            $this->conditions[
                $key
            ];

        if (
            ! isset(
                $definition[
                    'operators'
                ][
                    $operator
                ]
            ) ||
            ! is_callable(
                $definition[
                    'evaluator'
                ]
            )
        ) {

            return false;
        }

        return (bool)
            call_user_func(
                $definition[
                    'evaluator'
                ],
                $operator,
                $value,
                is_array(
                    $context
                )
                    ? $context
                    : array()
            );
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
                'label' =>
                    'مبلغ سفارش',

                'value_type' =>
                    'number',

                /*
                 * The internal value remains Rial,
                 * but WooSmart presents the value
                 * to Iranian users in Toman.
                 */
                'value_unit' =>
                    'toman',

                'operators' =>
                    array(
                        'is_equal' =>
                            'برابر است با',

                        'is_not_equal' =>
                            'برابر نیست با',

                        'greater_than' =>
                            'بیشتر از',

                        'greater_than_or_equal' =>
                            'بیشتر یا مساوی با',

                        'less_than' =>
                            'کمتر از',

                        'less_than_or_equal' =>
                            'کمتر یا مساوی با',
                    ),

                'evaluator' =>
                    function (
                        $operator,
                        $value,
                        $context
                    ) {

                        return $this->evaluate_order_total(
                            $operator,
                            $value,
                            $context
                        );
                    },
            )
        );
    }

    /**
     * Evaluate the WooCommerce order total condition.
     *
     * @param string $operator Operator key.
     * @param mixed  $value    Configured amount.
     * @param array  $context  Execution context.
     *
     * @return bool
     */
    private function evaluate_order_total(
        $operator,
        $value,
        $context
    ) {

        if (
            ! function_exists(
                'wc_get_order'
            ) ||
            ! isset(
                $context['order_id']
            )
        ) {

            return false;
        }

        $order_id =
            absint(
                $context['order_id']
            );

        if (
            ! $order_id
        ) {

            return false;
        }

        $order =
            wc_get_order(
                $order_id
            );

        if (
            ! $order
        ) {

            return false;
        }

        /*
         * IMPORTANT:
         *
         * $value is already stored in the internal
         * WooCommerce currency unit.
         *
         * The conversion from Toman to Rial happens
         * in the Admin layer before the value is stored.
         *
         * Therefore the Condition Engine does not
         * perform any currency conversion here.
         */
        $configured_value =
            str_replace(
                ',',
                '',
                (string) $value
            );

        if (
            '' === $configured_value ||
            ! is_numeric(
                $configured_value
            )
        ) {

            return false;
        }

        $order_total =
            (float)
            $order->get_total();

        $condition_value =
            (float)
            $configured_value;

        switch (
            $operator
        ) {

            case 'is_equal':

                return (
                    $order_total ===
                    $condition_value
                );

            case 'is_not_equal':

                return (
                    $order_total !==
                    $condition_value
                );

            case 'greater_than':

                return (
                    $order_total >
                    $condition_value
                );

            case 'greater_than_or_equal':

                return (
                    $order_total >=
                    $condition_value
                );

            case 'less_than':

                return (
                    $order_total <
                    $condition_value
                );

            case 'less_than_or_equal':

                return (
                    $order_total <=
                    $condition_value
                );
        }

        return false;
    }

    /**
     * Remove internal implementation details from a condition definition.
     *
     * @param array $definition Condition definition.
     *
     * @return array
     */
    private function get_public_definition(
        $definition
    ) {

        $public_definition =
            $definition;

        unset(
            $public_definition[
                'evaluator'
            ]
        );

        return $public_definition;
    }
}
