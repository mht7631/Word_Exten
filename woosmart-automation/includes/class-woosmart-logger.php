<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger class for WooSmart Automation.
 */
class WooSmart_Logger {

    /**
     * Log an event.
     *
     * @param string $event   Event name.
     * @param string $message Event message.
     * @param array  $context Additional context.
     *
     * @return void
     */
    public function log( $event, $message, $context = array() ) {

        $logs = get_option(
            'woosmart_automation_logs',
            array()
        );

        if ( ! is_array( $logs ) ) {
            $logs = array();
        }

        $logs[] = array(
            'time'    => current_time( 'mysql' ),
            'event'   => sanitize_text_field( $event ),
            'message' => sanitize_text_field( $message ),
            'context' => $context,
        );

        /*
         * Keep only the latest 100 log entries.
         */
        if ( count( $logs ) > 100 ) {
            $logs = array_slice( $logs, -100 );
        }

        update_option(
            'woosmart_automation_logs',
            $logs,
            false
        );
    }

    /**
     * Get all logs.
     *
     * @return array
     */
    public function get_logs() {

        $logs = get_option(
            'woosmart_automation_logs',
            array()
        );

        return is_array( $logs ) ? $logs : array();
    }

    /**
     * Clear all logs.
     *
     * @return void
     */
    public function clear_logs() {

        delete_option(
            'woosmart_automation_logs'
        );
    }
}