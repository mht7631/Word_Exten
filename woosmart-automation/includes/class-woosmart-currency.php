<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currency helper for WooSmart Automation.
 *
 * WooCommerce remains the source of truth for currency and monetary values.
 *
 * This class does NOT convert Rial to Toman, does NOT modify WooCommerce
 * prices, and does NOT change the store currency.
 *
 * Its only responsibility is to expose the current WooCommerce currency
 * information to the WooSmart administration layer when needed.
 */
class WooSmart_Currency {

    /**
     * Get the current WooCommerce currency code.
     *
     * @return string
     */
    public function get_currency_code() {

        if (
            function_exists(
                'get_woocommerce_currency'
            )
        ) {

            return (string)
                get_woocommerce_currency();
        }

        return '';
    }

    /**
     * Check whether WooCommerce is currently using IRR.
     *
     * This method is informational only and does not alter anything.
     *
     * @return bool
     */
    public function is_irr_currency() {

        return 'IRR' ===
            $this->get_currency_code();
    }

    /**
     * Check whether WooCommerce is currently using IRT.
     *
     * This method is informational only and does not alter anything.
     *
     * @return bool
     */
    public function is_irt_currency() {

        return 'IRT' ===
            $this->get_currency_code();
    }

    /**
     * Get the current WooCommerce currency symbol.
     *
     * @return string
     */
    public function get_currency_symbol() {

        if (
            function_exists(
                'get_woocommerce_currency_symbol'
            )
        ) {

            $symbol =
                get_woocommerce_currency_symbol(
                    $this->get_currency_code()
                );

            if (
                ! empty( $symbol )
            ) {

                return (string) $symbol;
            }
        }

        return $this->get_currency_code();
    }

    /**
     * Get a user-facing currency label.
     *
     * For the current project, IRT is shown as تومان and IRR as ریال.
     * Other currencies use the WooCommerce currency symbol when available.
     *
     * This is display-only. It does not convert monetary values.
     *
     * @return string
     */
    public function get_display_unit() {

        $currency =
            $this->get_currency_code();

        if (
            'IRT' === $currency
        ) {

            return 'تومان';
        }

        if (
            'IRR' === $currency
        ) {

            return 'ریال';
        }

        return $this->get_currency_symbol();
    }

    /**
     * Get the current display unit used by WooSmart.
     *
     * @return string
     */
    public function get_storage_unit() {

        return $this->get_display_unit();
    }

    /**
     * Normalize a numeric value without changing its currency unit.
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

        $value =
            str_replace(
                ',',
                '',
                $value
            );

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
     * Return the amount exactly as WooCommerce represents it internally.
     *
     * No currency conversion is performed.
     *
     * @param mixed $amount Amount.
     *
     * @return float
     */
    public function to_storage_value(
        $amount
    ) {

        $normalized =
            $this->normalize_numeric_value(
                $amount
            );

        if (
            '' === $normalized
        ) {

            return 0;
        }

        return (float) $normalized;
    }

    /**
     * Return the amount exactly as supplied, for display-only use.
     *
     * No currency conversion is performed.
     *
     * @param mixed $amount Amount.
     *
     * @return float
     */
    public function to_display_value(
        $amount
    ) {

        return $this->to_storage_value(
            $amount
        );
    }

    /**
     * Format an amount for WooSmart display.
     *
     * No currency conversion is performed.
     *
     * @param mixed $amount   Amount.
     * @param int   $decimals Decimal count.
     *
     * @return string
     */
    public function format_display_value(
        $amount,
        $decimals = 0
    ) {

        $value =
            $this->to_display_value(
                $amount
            );

        return number_format(
            $value,
            $decimals,
            '.',
            ','
        );
    }

    /**
     * Format an amount with the current WooCommerce display unit.
     *
     * @param mixed $amount Amount.
     *
     * @return string
     */
    public function format_display_money(
        $amount
    ) {

        $value =
            $this->format_display_value(
                $amount,
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
     * Normalize an amount for storage without currency conversion.
     *
     * @param mixed $amount Amount.
     *
     * @return string
     */
    public function normalize_for_storage(
        $amount
    ) {

        $normalized =
            $this->normalize_numeric_value(
                $amount
            );

        if (
            '' === $normalized
        ) {

            return '';
        }

        return $normalized;
    }

    /**
     * Get the currency conversion factor.
     *
     * No conversion is performed by this class.
     * This method exists only for backward compatibility with the
     * previous helper interface and always returns 1.
     *
     * @return int
     */
    public function get_conversion_factor() {

        return 1;
    }
}
