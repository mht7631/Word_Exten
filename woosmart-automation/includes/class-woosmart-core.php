<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core class for WooSmart Automation.
 */
class WooSmart_Core {

    /**
     * WooCommerce availability status.
     *
     * @var bool
     */
    private $woocommerce_active = false;

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Initialize the core functionality.
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

        $this->check_woocommerce();

        $this->register_hooks();
    }

    /**
     * Register core hooks.
     *
     * @return void
     */
    private function register_hooks() {

        add_action(
            'admin_notices',
            array( $this, 'woocommerce_admin_notice' )
        );
    }

    /**
     * Check whether WooCommerce is active.
     *
     * @return void
     */
    private function check_woocommerce() {

        if ( class_exists( 'WooCommerce' ) ) {
            $this->woocommerce_active = true;
        }
    }

    /**
     * Display WooCommerce dependency notice.
     *
     * @return void
     */
    public function woocommerce_admin_notice() {

        if ( $this->woocommerce_active ) {
            return;
        }

        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        ?>

        <div class="notice notice-warning">

            <p>
                <strong>WooSmart Automation:</strong>
                WooCommerce فعال نیست. لطفاً WooCommerce را نصب و فعال کنید.
            </p>

        </div>

        <?php
    }

    /**
     * Check WooCommerce status.
     *
     * @return bool
     */
    public function is_woocommerce_active() {

        return $this->woocommerce_active;
    }
}