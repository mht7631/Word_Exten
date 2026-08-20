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
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

        $this->engine = new WooSmart_Execution_Engine();

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
}