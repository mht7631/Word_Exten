<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registry for WooSmart Automation actions.
 */
class WooSmart_Action_Registry {

    /**
     * Registered action definitions.
     *
     * @var array
     */
    private $actions = array();

    /**
     * Initialize the action registry.
     */
    public function __construct() {

        $this->register_default_actions();
    }

    /**
     * Register an action definition.
     *
     * @param string $key        Action key.
     * @param array  $definition Action definition.
     *
     * @return bool
     */
    public function register(
        $key,
        $definition
    ) {

        $key = sanitize_key(
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
                $definition['handler']
            )
        ) {
            return false;
        }

        $handler =
            sanitize_key(
                $definition['handler']
            );

        if ( empty( $handler ) ) {
            return false;
        }

        $definition['handler'] =
            $handler;

        if (
            ! isset(
                $definition['fields']
            ) ||
            ! is_array(
                $definition['fields']
            )
        ) {

            $definition['fields'] =
                array();
        }

        $this->actions[
            $key
        ] = $definition;

        return true;
    }

    /**
     * Check whether an action is registered.
     *
     * @param string $key Action key.
     *
     * @return bool
     */
    public function has(
        $key
    ) {

        $key = sanitize_key(
            $key
        );

        return isset(
            $this->actions[
                $key
            ]
        );
    }

    /**
     * Get one public action definition.
     *
     * @param string $key Action key.
     *
     * @return array|null
     */
    public function get(
        $key
    ) {

        $key = sanitize_key(
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
            $this->actions[
                $key
            ]
        );
    }

    /**
     * Get all registered public actions.
     *
     * @return array
     */
    public function get_all() {

        $definitions = array();

        foreach (
            $this->actions
            as $key => $definition
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
     * Get action handler method.
     *
     * @param string $key Action key.
     *
     * @return string|null
     */
    public function get_handler(
        $key
    ) {

        $key = sanitize_key(
            $key
        );

        if (
            ! $this->has(
                $key
            )
        ) {
            return null;
        }

        return isset(
            $this->actions[
                $key
            ]['handler']
        )
            ? $this->actions[
                $key
            ]['handler']
            : null;
    }

    /**
     * Get fields for an action.
     *
     * @param string $key Action key.
     *
     * @return array
     */
    public function get_fields(
        $key
    ) {

        $key = sanitize_key(
            $key
        );

        if (
            ! $this->has(
                $key
            )
        ) {
            return array();
        }

        return isset(
            $this->actions[
                $key
            ]['fields']
        )
            ? $this->actions[
                $key
            ]['fields']
            : array();
    }

    /**
     * Register default actions.
     *
     * @return void
     */
    private function register_default_actions() {

        $this->register(
            'change_order_status',
            array(
                'label' =>
                    'تغییر وضعیت سفارش',

                'handler' =>
                    'change_order_status',

                'fields' =>
                    array(
                        'status' => array(
                            'label' =>
                                'وضعیت سفارش',

                            'type' =>
                                'select',

                            'required' =>
                                true,
                        ),
                    ),
            )
        );

        $this->register(
            'notify_admin',
            array(
                'label' =>
                    'ارسال اعلان به مدیر فروشگاه',

                'handler' =>
                    'notify_admin',

                'fields' =>
                    array(
                        'subject' => array(
                            'label' =>
                                'موضوع اعلان',

                            'type' =>
                                'text',

                            'required' =>
                                true,
                        ),

                        'message' => array(
                            'label' =>
                                'متن اعلان',

                            'type' =>
                                'textarea',

                            'required' =>
                                true,
                        ),
                    ),
            )
        );
    }

    /**
     * Remove internal implementation details.
     *
     * @param array $definition Action definition.
     *
     * @return array
     */
    private function get_public_definition(
        $definition
    ) {

        /*
         * The current implementation does not
         * expose any private runtime data.
         *
         * This method exists so future internal
         * metadata can remain hidden from the UI.
         */
        return $definition;
    }
}
