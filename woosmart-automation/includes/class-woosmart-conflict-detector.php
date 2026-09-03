<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cross-Automation Conflict Detector.
 *
 * Detects potential runtime conflicts between active Automations.
 * Findings are advisory only and never block execution or changes.
 */
class WooSmart_Conflict_Detector {

    /**
     * Get cross-Automation conflicts for one Automation.
     *
     * @param int  $automation_id  Automation ID.
     * @param bool $include_self   Whether the current Automation may be returned as a pair.
     * @return array
     */
    public function get_conflicts( $automation_id, $include_self = false ) {

        $automation_id = absint( $automation_id );

        if ( ! $automation_id ) {
            return array();
        }

        $automation = get_post( $automation_id );

        if (
            ! $automation ||
            'woosmart_automation' !== $automation->post_type
        ) {
            return array();
        }

        $automations = get_posts(
            array(
                'post_type'      => 'woosmart_automation',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $conflicts = array();

        foreach ( $automations as $candidate ) {

            $candidate_id = absint( $candidate->ID );

            if (
                ! $include_self &&
                $candidate_id === $automation_id
            ) {
                continue;
            }

            if (
                'active' !==
                get_post_meta(
                    $candidate_id,
                    '_woosmart_status',
                    true
                )
            ) {
                continue;
            }

            $pair_conflicts =
                $this->compare_automations(
                    $automation_id,
                    $candidate_id
                );

            if ( ! empty( $pair_conflicts ) ) {
                $conflicts = array_merge(
                    $conflicts,
                    $pair_conflicts
                );
            }
        }

        return $conflicts;
    }

    /**
     * Get all cross-Automation conflicts among active Automations.
     *
     * Each pair is evaluated once.
     *
     * @return array
     */
    public function get_all_conflicts() {

        $automations = get_posts(
            array(
                'post_type'      => 'woosmart_automation',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $active = array();

        foreach ( $automations as $automation ) {

            $automation_id = absint( $automation->ID );

            if (
                'active' !==
                get_post_meta(
                    $automation_id,
                    '_woosmart_status',
                    true
                )
            ) {
                continue;
            }

            $active[] = $automation_id;
        }

        $conflicts = array();
        $count     = count( $active );

        for ( $i = 0; $i < $count; $i++ ) {

            for ( $j = $i + 1; $j < $count; $j++ ) {

                $pair_conflicts =
                    $this->compare_automations(
                        $active[ $i ],
                        $active[ $j ]
                    );

                if ( ! empty( $pair_conflicts ) ) {
                    $conflicts = array_merge(
                        $conflicts,
                        $pair_conflicts
                    );
                }
            }
        }

        return $conflicts;
    }

    /**
     * Compare two Automations.
     *
     * @param int $first_id  First Automation ID.
     * @param int $second_id Second Automation ID.
     * @return array
     */
    private function compare_automations( $first_id, $second_id ) {

        if ( $first_id === $second_id ) {
            return array();
        }

        $first_trigger = sanitize_key(
            get_post_meta(
                $first_id,
                '_woosmart_trigger',
                true
            )
        );

        $second_trigger = sanitize_key(
            get_post_meta(
                $second_id,
                '_woosmart_trigger',
                true
            )
        );

        if (
            empty( $first_trigger ) ||
            $first_trigger !== $second_trigger
        ) {
            return array();
        }

        $first_conditions =
            get_post_meta(
                $first_id,
                '_woosmart_conditions',
                true
            );

        $second_conditions =
            get_post_meta(
                $second_id,
                '_woosmart_conditions',
                true
            );

        $first_conditions =
            $this->normalize_conditions(
                $first_conditions
            );

        $second_conditions =
            $this->normalize_conditions(
                $second_conditions
            );

        $overlap =
            $this->conditions_overlap(
                $first_conditions,
                $second_conditions
            );

        if ( ! $overlap ) {
            return array();
        }

        $first_actions =
            get_post_meta(
                $first_id,
                '_woosmart_actions',
                true
            );

        $second_actions =
            get_post_meta(
                $second_id,
                '_woosmart_actions',
                true
            );

        if ( ! is_array( $first_actions ) ) {
            $first_actions = array();
        }

        if ( ! is_array( $second_actions ) ) {
            $second_actions = array();
        }

        $conflicts = array();

        $conflicts[] = array(
            'code'         => 'overlapping_automation_conditions',
            'severity'     => 'warning',
            'automation_a' => $first_id,
            'automation_b' => $second_id,
            'message'      => 'شرایط این دو اتوماسیون می‌تواند برای یک سفارش به‌صورت همزمان برقرار شود؛ بنابراین ممکن است هر دو در یک اجرا وارد زنجیره شوند.',
        );

        $first_statuses =
            $this->get_status_targets(
                $first_actions
            );

        $second_statuses =
            $this->get_status_targets(
                $second_actions
            );

        if (
            ! empty( $first_statuses ) &&
            ! empty( $second_statuses )
        ) {

            $common_statuses =
                array_values(
                    array_intersect(
                        $first_statuses,
                        $second_statuses
                    )
                );

            if ( ! empty( $common_statuses ) ) {

                $conflicts[] = array(
                    'code'            => 'duplicate_cross_automation_status_target',
                    'severity'        => 'warning',
                    'automation_a'    => $first_id,
                    'automation_b'    => $second_id,
                    'statuses'        => $common_statuses,
                    'message'         => 'هر دو اتوماسیون در شرایط همپوشان می‌توانند وضعیت سفارش را به یک وضعیت یکسان تغییر دهند؛ این وضعیت ممکن است باعث اجرای رفتارهای تکراری یا Hookهای وابسته شود.',
                );

            } else {

                $conflicts[] = array(
                    'code'         => 'cross_automation_status_transition',
                    'severity'     => 'warning',
                    'automation_a' => $first_id,
                    'automation_b' => $second_id,
                    'messages'    => array(
                        'automation_a_statuses' => $first_statuses,
                        'automation_b_statuses' => $second_statuses,
                    ),
                    'message'      => 'هر دو اتوماسیون در شرایط همپوشان می‌توانند وضعیت سفارش را تغییر دهند؛ ترتیب Priority و وضعیت واقعی سفارش روی نتیجه اثر می‌گذارد.',
                );
            }
        }

        return $conflicts;
    }

    /**
     * Normalize current MVP condition storage.
     *
     * @param mixed $conditions Stored conditions.
     * @return array
     */
    private function normalize_conditions( $conditions ) {

        if ( ! is_array( $conditions ) ) {
            return array();
        }

        $valid = array();

        foreach ( $conditions as $condition ) {
            if ( ! is_array( $condition ) ) {
                continue;
            }

            if (
                empty( $condition['field'] ) ||
                empty( $condition['operator'] )
            ) {
                continue;
            }

            $valid[] = $condition;
        }

        if ( count( $valid ) > 1 ) {
            $valid = array( end( $valid ) );
        }

        return $valid;
    }

    /**
     * Determine whether two current-MVP condition sets can overlap.
     *
     * Empty conditions are treated as matching all values.
     * Supported deterministic interval analysis is currently limited
     * to order_total. Other same-trigger conditions are treated as
     * potentially overlapping so the UI remains conservative.
     *
     * @param array $first_conditions First condition set.
     * @param array $second_conditions Second condition set.
     * @return bool
     */
    private function conditions_overlap(
        $first_conditions,
        $second_conditions
    ) {

        if (
            empty( $first_conditions ) ||
            empty( $second_conditions )
        ) {
            return true;
        }

        $first = reset( $first_conditions );
        $second = reset( $second_conditions );

        if (
            ! is_array( $first ) ||
            ! is_array( $second )
        ) {
            return true;
        }

        $first_field = isset( $first['field'] )
            ? sanitize_key( $first['field'] )
            : '';

        $second_field = isset( $second['field'] )
            ? sanitize_key( $second['field'] )
            : '';

        if (
            'order_total' !== $first_field ||
            'order_total' !== $second_field
        ) {
            return true;
        }

        $first_interval =
            $this->condition_to_interval( $first );

        $second_interval =
            $this->condition_to_interval( $second );

        if (
            false === $first_interval ||
            false === $second_interval
        ) {
            return true;
        }

        if (
            $first_interval['max'] <
            $second_interval['min']
        ) {
            return false;
        }

        if (
            $second_interval['max'] <
            $first_interval['min']
        ) {
            return false;
        }

        return true;
    }

    /**
     * Convert an order_total condition into an inclusive interval.
     *
     * @param array $condition Condition.
     * @return array|false
     */
    private function condition_to_interval( $condition ) {

        $operator = isset( $condition['operator'] )
            ? sanitize_key( $condition['operator'] )
            : '';

        $value = isset( $condition['value'] )
            ? $condition['value']
            : '';

        switch ( $operator ) {
            case 'is_equal':
                return array(
                    'min' => (float) $value,
                    'max' => (float) $value,
                );

            case 'greater_than':
                return array(
                    'min' => (float) $value + 0.0000001,
                    'max' => INF,
                );

            case 'greater_than_or_equal':
                return array(
                    'min' => (float) $value,
                    'max' => INF,
                );

            case 'less_than':
                return array(
                    'min' => -INF,
                    'max' => (float) $value - 0.0000001,
                );

            case 'less_than_or_equal':
                return array(
                    'min' => -INF,
                    'max' => (float) $value,
                );

            case 'between':
                if ( ! is_array( $value ) ) {
                    return false;
                }

                if (
                    ! isset( $value['min'] ) ||
                    ! isset( $value['max'] )
                ) {
                    return false;
                }

                return array(
                    'min' => (float) $value['min'],
                    'max' => (float) $value['max'],
                );

            case 'is_not_equal':
                return array(
                    'min' => -INF,
                    'max' => INF,
                );
        }

        return false;
    }

    /**
     * Get order-status targets from Actions.
     *
     * @param array $actions Actions.
     * @return array
     */
    private function get_status_targets( $actions ) {

        $statuses = array();

        foreach ( $actions as $action ) {
            if (
                ! is_array( $action ) ||
                'change_order_status' !==
                    ( isset( $action['type'] )
                        ? sanitize_key( $action['type'] )
                        : '' )
            ) {
                continue;
            }

            $status = isset( $action['status'] )
                ? sanitize_key( $action['status'] )
                : '';

            if ( '' !== $status ) {
                $statuses[] = $status;
            }
        }

        return array_values(
            array_unique( $statuses )
        );
    }

    /**
     * Get a human-readable Automation name.
     *
     * @param int $automation_id Automation ID.
     * @return string
     */
    public function get_automation_name( $automation_id ) {

        $title = get_the_title(
            absint( $automation_id )
        );

        return $title
            ? $title
            : 'Automation #' . absint( $automation_id );
    }

    /**
     * Get a human-readable status label.
     *
     * @param string $status Status slug.
     * @return string
     */
    public function get_status_label( $status ) {

        $status = sanitize_key( $status );

        if (
            function_exists( 'wc_get_order_statuses' )
        ) {
            $statuses = wc_get_order_statuses();
            $key      = 'wc-' . $status;

            if ( isset( $statuses[ $key ] ) ) {
                return wp_strip_all_tags(
                    $statuses[ $key ]
                );
            }
        }

        return $status;
    }
}
