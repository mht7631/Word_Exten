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

        /*
         * Apply Iranian Rial display formatting
         * only when WooCommerce currency is IRR.
         */
        add_filter(
            'woocommerce_currency_symbol',
            array( $this, 'filter_irr_currency_symbol' ),
            10,
            2
        );

        add_filter(
            'woocommerce_currency_pos',
            array( $this, 'filter_irr_currency_position' )
        );

        add_filter(
            'wc_get_price_thousand_separator',
            array( $this, 'filter_irr_thousand_separator' )
        );

        add_filter(
            'wc_get_price_decimal_separator',
            array( $this, 'filter_irr_decimal_separator' )
        );

        add_filter(
            'wc_get_price_decimals',
            array( $this, 'filter_irr_price_decimals' )
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
     * Check whether the current store currency is IRR.
     *
     * @return bool
     */
    private function is_irr_currency() {

        if ( ! function_exists( 'get_woocommerce_currency' ) ) {
            return false;
        }

        return 'IRR' === get_woocommerce_currency();
    }

    /**
     * Change IRR currency symbol from ﷼ to ریال.
     *
     * @param string $currency_symbol Currency symbol.
     * @param string $currency        Currency code.
     *
     * @return string
     */
    public function filter_irr_currency_symbol(
        $currency_symbol,
        $currency
    ) {

        if ( 'IRR' !== $currency ) {
            return $currency_symbol;
        }

        return 'ریال';
    }

    /**
     * Set IRR currency position to right with space.
     *
     * @param string $currency_position Currency position.
     *
     * @return string
     */
    public function filter_irr_currency_position(
        $currency_position
    ) {

        if ( ! $this->is_irr_currency() ) {
            return $currency_position;
        }

        return 'right_space';
    }

    /**
     * Set IRR thousand separator.
     *
     * @param string $separator Thousand separator.
     *
     * @return string
     */
    public function filter_irr_thousand_separator(
        $separator
    ) {

        if ( ! $this->is_irr_currency() ) {
            return $separator;
        }

        return ',';
    }

    /**
     * Set IRR decimal separator.
     *
     * @param string $separator Decimal separator.
     *
     * @return string
     */
    public function filter_irr_decimal_separator(
        $separator
    ) {

        if ( ! $this->is_irr_currency() ) {
            return $separator;
        }

        return '.';
    }

    /**
     * Remove decimal places for IRR prices.
     *
     * @param int $decimals Number of decimals.
     *
     * @return int
     */
    public function filter_irr_price_decimals(
        $decimals
    ) {

        if ( ! $this->is_irr_currency() ) {
            return $decimals;
        }

        return 0;
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
