<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trigger class for WooSmart Automation.
 */
class WooSmart_Triggers {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Execution engine.
     *
     * @var WooSmart_Execution_Engine
     */
    private $engine;

    /**
     * Initialize trigger system.
     *
     * @param WooSmart_Execution_Engine $engine Shared execution engine.
     */
    public function __construct(
        WooSmart_Execution_Engine $engine
    ) {

        $this->logger = new WooSmart_Logger();

        $this->engine = $engine;

        $this->register_hooks();
    }

    /**
     * Register WooCommerce triggers.
     *
     * @return void
     */
    private function register_hooks() {

        add_action(
            'woocommerce_new_order',
            array( $this, 'order_created' ),
            10,
            1
        );

        add_action(
            'woocommerce_order_status_changed',
            array( $this, 'order_status_changed' ),
            10,
            4
        );
    }

    /**
     * Handle new WooCommerce order.
     *
     * @param int $order_id WooCommerce order ID.
     *
     * @return void
     */
    public function order_created( $order_id ) {

        $order_id = absint( $order_id );

        if ( ! $order_id ) {
            return;
        }

        $context = array(
            'order_id' => $order_id,
        );

        $this->logger->log(
            'order_created',
            'A new WooCommerce order was created.',
            $context
        );

        $this->engine->execute(
            'order_created',
            $context
        );
    }

    /**
     * Handle WooCommerce order status changes.
     *
     * @param int        $order_id   WooCommerce order ID.
     * @param string     $old_status Previous order status slug.
     * @param string     $new_status New order status slug.
     * @param WC_Order   $order      WooCommerce order object.
     *
     * @return void
     */
    public function order_status_changed(
        $order_id,
        $old_status,
        $new_status,
        $order
    ) {

        $order_id = absint( $order_id );

        if ( ! $order_id ) {
            return;
        }

        $old_status = sanitize_key( $old_status );
        $new_status = sanitize_key( $new_status );

        if ( empty( $new_status ) ) {
            return;
        }

        if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order ) {
            return;
        }

        $context = array(
            'order'      => $order,
            'order_id'   => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
        );

        $this->logger->log(
            'order_status_changed',
            'WooCommerce order status was changed.',
            $context
        );

        $this->engine->execute(
            'order_status_changed',
            $context
        );
    }
}
