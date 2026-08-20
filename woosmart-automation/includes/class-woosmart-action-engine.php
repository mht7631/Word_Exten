<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Action Engine for WooSmart Automation.
 */
class WooSmart_Action_Engine {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Initialize Action Engine.
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();
    }

    /**
     * Execute automation actions.
     *
     * @param array $actions Actions configuration.
     * @param array $context Execution context.
     *
     * @return bool
     */
    public function execute( $actions, $context = array() ) {

        if ( empty( $actions ) || ! is_array( $actions ) ) {
            return true;
        }

        $all_successful = true;

        foreach ( $actions as $action ) {

            if ( ! is_array( $action ) ) {
                continue;
            }

            $type = isset( $action['type'] )
                ? sanitize_key( $action['type'] )
                : '';

            if ( empty( $type ) ) {
                continue;
            }

            $result = $this->execute_action(
                $type,
                $action,
                $context
            );

            if ( ! $result ) {
                $all_successful = false;
            }
        }

        return $all_successful;
    }

    /**
     * Execute a single action.
     *
     * @param string $type    Action type.
     * @param array  $action  Action configuration.
     * @param array  $context Execution context.
     *
     * @return bool
     */
    private function execute_action(
        $type,
        $action,
        $context
    ) {

        switch ( $type ) {

            case 'change_order_status':

                return $this->change_order_status(
                    $action,
                    $context
                );

            default:

                $this->logger->log(
                    'action_failed',
                    'Unknown action type.',
                    array(
                        'action_type' => $type,
                        'context'     => $context,
                    )
                );

                return false;
        }
    }

    /**
     * Change WooCommerce order status.
     *
     * @param array $action  Action configuration.
     * @param array $context Execution context.
     *
     * @return bool
     */
    private function change_order_status(
        $action,
        $context
    ) {

        if ( ! function_exists( 'wc_get_order' ) ) {

            $this->logger->log(
                'action_failed',
                'WooCommerce is not available.',
                array(
                    'action_type' => 'change_order_status',
                )
            );

            return false;
        }

        $order_id = isset( $context['order_id'] )
            ? absint( $context['order_id'] )
            : 0;

        $new_status = isset( $action['status'] )
            ? sanitize_key( $action['status'] )
            : '';

        if ( ! $order_id ) {

            $this->logger->log(
                'action_failed',
                'Order ID is missing from action context.',
                array(
                    'action_type' => 'change_order_status',
                    'context'     => $context,
                )
            );

            return false;
        }

        if ( empty( $new_status ) ) {

            $this->logger->log(
                'action_failed',
                'Order status is missing.',
                array(
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
                )
            );

            return false;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {

            $this->logger->log(
                'action_failed',
                'WooCommerce order could not be found.',
                array(
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
                )
            );

            return false;
        }

        $old_status = $order->get_status();

        $result = $order->update_status(
            $new_status,
            'WooSmart Automation changed the order status.'
        );

        if ( false === $result ) {

            $this->logger->log(
                'action_failed',
                'Failed to change order status.',
                array(
                    'action_type' => 'change_order_status',
                    'order_id'    => $order_id,
                    'old_status'  => $old_status,
                    'new_status'  => $new_status,
                )
            );

            return false;
        }

        $this->logger->log(
            'action_executed',
            'Order status was changed successfully.',
            array(
                'action_type' => 'change_order_status',
                'order_id'    => $order_id,
                'old_status'  => $old_status,
                'new_status'  => $new_status,
            )
        );

        return true;
    }
}