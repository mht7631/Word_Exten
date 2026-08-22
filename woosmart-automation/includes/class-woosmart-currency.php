<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currency service for WooSmart Automation.
 *
 * WooCommerce remains the source of truth for monetary values.
 *
 * For IRR stores:
 *
 * Storage:
 *     Rial
 *
 * WooSmart display:
 *     Toman
 *
 * Conversion:
 *     1 Toman = 10 Rial
 */
class WooSmart_Currency {

    /**
     * Rial-to-Toman conversion factor.
     *
     * @var int
     */
    private $irr_to_toman = 10;

    /**
     * Check whether the current WooCommerce currency is IRR.
     *
     * @return bool
     */
    public function is_irr_currency() {

        if (
            ! function_exists(
                'get_woocommerce_currency'
            )
        ) {
            return false;
        }

        return 'IRR' ===
            get_woocommerce_currency();
    }

    /**
     * Get the internal storage currency label.
     *
     * @return string
     */
    public function get_storage_unit() {

        if (
            $this->is_irr_currency()
        ) {
            return 'ریال';
        }

        if (
            function_exists(
                'get_woocommerce_currency'
            )
        ) {

            return get_woocommerce_currency();
        }

        return '';
    }

    /**
     * Get the WooSmart display unit.
     *
     * @return string
     */
    public function get_display_unit() {

        if (
            $this->is_irr_currency()
        ) {
            return 'تومان';
        }

        if (
            function_exists(
                'get_woocommerce_currency_symbol'
            )
        ) {

            $symbol =
                get_woocommerce_currency_symbol();

            if (
                ! empty( $symbol )
            ) {
                return $symbol;
            }
        }

        return $this->get_storage_unit();
    }

    /**
     * Convert an internal WooCommerce amount to
     * the value displayed to the WooSmart user.
     *
     * @param mixed $internal_amount Internal amount.
     *
     * @return float
     */
    public function to_display_value(
        $internal_amount
    ) {

        $amount =
            $this->normalize_numeric_value(
                $internal_amount
            );

        if (
            '' === $amount
        ) {
            return 0;
        }

        if (
            ! $this->is_irr_currency()
        ) {
            return (float) $amount;
        }

        return (
            (float) $amount /
            $this->irr_to_toman
        );
    }

    /**
     * Convert a WooSmart display amount back to
     * the internal WooCommerce amount.
     *
     * @param mixed $display_amount Display amount.
     *
     * @return float
     */
    public function to_storage_value(
        $display_amount
    ) {

        $amount =
            $this->normalize_numeric_value(
                $display_amount
            );

        if (
            '' === $amount
        ) {
            return 0;
        }

        if (
            ! $this->is_irr_currency()
        ) {
            return (float) $amount;
        }

        return (
            (float) $amount *
            $this->irr_to_toman
        );
    }

    /**
     * Format an internal amount for WooSmart display.
     *
     * @param mixed $internal_amount Internal amount.
     * @param int   $decimals        Decimal count.
     *
     * @return string
     */
    public function format_display_value(
        $internal_amount,
        $decimals = 0
    ) {

        $display_value =
            $this->to_display_value(
                $internal_amount
            );

        return number_format(
            $display_value,
            $decimals,
            '.',
            ','
        );
    }

    /**
     * Format an internal amount together with
     * the WooSmart display unit.
     *
     * @param mixed $internal_amount Internal amount.
     *
     * @return string
     */
    public function format_display_money(
        $internal_amount
    ) {

        $value =
            $this->format_display_value(
                $internal_amount,
                0
            );

        $unit =
            $this->get_display_unit();

        if (
            '' === $unit
        ) {
            return $value;
        }

        return $value . ' ' . $unit;
    }

    /**
     * Normalize a numeric value.
     *
     * @param mixed $value Numeric value.
     *
     * @return string
     */
    public function normalize_numeric_value(
        $value
    ) {

        $value =
            (string) $value;

        /*
         * Remove thousands separators.
         */
        $value =
            str_replace(
                ',',
                '',
                $value
            );

        /*
         * Remove anything except digits and decimal point.
         */
        $value =
            preg_replace(
                '/[^\d.]/',
                '',
                $value
            );

        if (
            null === $value
        ) {
            return '';
        }

        /*
         * Keep only the first decimal point.
         */
        $first_dot =
            strpos(
                $value,
                '.'
            );

        if (
            false !== $first_dot
        ) {

            $value =
                substr(
                    $value,
                    0,
                    $first_dot + 1
                ) .
                str_replace(
                    '.',
                    '',
                    substr(
                        $value,
                        $first_dot + 1
                    )
                );
        }

        return $value;
    }

    /**
     * Convert a display input string to its
     * internal storage representation.
     *
     * @param mixed $display_amount Display amount.
     *
     * @return string
     */
    public function normalize_for_storage(
        $display_amount
    ) {

        $storage_value =
            $this->to_storage_value(
                $display_amount
            );

        /*
         * WooCommerce IRR values are stored as the
         * smallest configured monetary unit.
         *
         * For the current project this means an integer.
         */
        if (
            $this->is_irr_currency()
        ) {

            return (string)
                (int)
                round(
                    $storage_value
                );
        }

        return (string)
            $storage_value;
    }

    /**
     * Get the conversion factor.
     *
     * @return int
     */
    public function get_conversion_factor() {

        return $this->irr_to_toman;
    }
}
