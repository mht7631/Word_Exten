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
     * Initialize admin UI.
     */
    public function __construct() {

        add_action(
            'admin_menu',
            array(
                $this,
                'add_admin_menu',
            )
        );
    }

    /**
     * Add conflict analysis admin page.
     *
     * @return void
     */
    public function add_admin_menu() {

        add_submenu_page(
            'woosmart-automation',
            'تعارض‌ها',
            'تعارض‌ها',
            'manage_options',
            'woosmart-conflicts',
            array(
                $this,
                'render_conflicts_page',
            )
        );
    }

    /**
     * Render cross-Automation conflict analysis.
     *
     * @return void
     */
    public function render_conflicts_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                'شما اجازه مشاهده این بخش را ندارید.'
            );
        }

        $conflicts =
            $this->get_all_conflicts();
        ?>

```
    <div
        class="wrap"
        dir="rtl"
    >

        <h1>
            تعارض بین اتوماسیون‌ها
        </h1>

        <p>
            این بخش فقط تعارض یا همپوشانی بالقوه را شناسایی و توضیح می‌دهد.
            هیچ اتوماسیونی به‌صورت خودکار غیرفعال یا مسدود نمی‌شود.
        </p>

        <?php if ( empty( $conflicts ) ) : ?>

            <div class="notice notice-success inline">

                <p>
                    در حال حاضر تعارض قابل تشخیصی بین اتوماسیون‌های فعال پیدا نشد.
                </p>

            </div>

        <?php else : ?>

            <div class="notice notice-warning inline">

                <p>
                    <?php
                    echo esc_html(
                        count( $conflicts ) .
                        ' مورد هشدار بین اتوماسیون‌های فعال شناسایی شد.'
                    );
                    ?>
                </p>

            </div>

            <table class="widefat fixed striped">

                <thead>

                    <tr>
                        <th>
                            شدت
                        </th>

                        <th>
                            اتوماسیون اول
                        </th>

                        <th>
                            اتوماسیون دوم
                        </th>

                        <th>
                            نوع هشدار
                        </th>

                        <th>
                            توضیح
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ( $conflicts as $conflict ) : ?>

                        <?php

                        $automation_a = isset(
                            $conflict['automation_a']
                        )
                            ? absint(
                                $conflict['automation_a']
                            )
                            : 0;

                        $automation_b = isset(
                            $conflict['automation_b']
                        )
                            ? absint(
                                $conflict['automation_b']
                            )
                            : 0;

                        $code = isset(
                            $conflict['code']
                        )
                            ? sanitize_key(
                                $conflict['code']
                            )
                            : '';

                        $severity = isset(
                            $conflict['severity']
                        )
                            ? sanitize_key(
                                $conflict['severity']
                            )
                            : 'warning';

                        $message = isset(
                            $conflict['message']
                        )
                            ? $conflict['message']
                            : '';

                        ?>

                        <tr>

                            <td>

                                <span
                                    class="notice notice-warning inline"
                                    style="display:inline-block;margin:0;padding:3px 8px;"
                                >

                                    <?php
                                    echo esc_html(
                                        'warning' === $severity
                                            ? 'هشدار'
                                            : $severity
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <strong>

                                    <?php
                                    echo esc_html(
                                        $this->get_automation_name(
                                            $automation_a
                                        )
                                    );
                                    ?>

                                </strong>

                                <br>

                                <small>
                                    #<?php echo esc_html( $automation_a ); ?>
                                </small>

                                <?php
                                $priority_a =
                                    $this->get_automation_priority(
                                        $automation_a
                                    );
                                ?>

                                <?php if ( null !== $priority_a ) : ?>

                                    <br>

                                    <small>
                                        اولویت:
                                        <?php
                                        echo esc_html(
                                            $priority_a
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>

                            <td>

                                <strong>

                                    <?php
                                    echo esc_html(
                                        $this->get_automation_name(
                                            $automation_b
                                        )
                                    );
                                    ?>

                                </strong>

                                <br>

                                <small>
                                    #<?php echo esc_html( $automation_b ); ?>
                                </small>

                                <?php
                                $priority_b =
                                    $this->get_automation_priority(
                                        $automation_b
                                    );
                                ?>

                                <?php if ( null !== $priority_b ) : ?>

                                    <br>

                                    <small>
                                        اولویت:
                                        <?php
                                        echo esc_html(
                                            $priority_b
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php
                                echo esc_html(
                                    $this->get_conflict_label(
                                        $code
                                    )
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo esc_html(
                                    $message
                                );
                                ?>

                                <?php if (
                                    'duplicate_cross_automation_status_target' ===
                                    $code &&
                                    ! empty(
                                        $conflict['statuses']
                                    )
                                ) : ?>

                                    <br>

                                    <strong>
                                        وضعیت مشترک:
                                    </strong>

                                    <?php

                                    $labels = array();

                                    foreach (
                                        $conflict['statuses']
                                        as $status
                                    ) {

                                        $labels[] =
                                            $this->get_status_label(
                                                $status
                                            );
                                    }

                                    echo esc_html(
                                        implode(
                                            '، ',
                                            $labels
                                        )
                                    );

                                    ?>

                                <?php endif; ?>

                                <?php if (
                                    'cross_automation_status_transition' ===
                                    $code
                                ) : ?>

                                    <?php
                                    $automation_a_statuses =
                                        ! empty(
                                            $conflict[
                                                'automation_a_statuses'
                                            ]
                                        )
                                            ? $conflict[
                                                'automation_a_statuses'
                                            ]
                                            : array();

                                    $automation_b_statuses =
                                        ! empty(
                                            $conflict[
                                                'automation_b_statuses'
                                            ]
                                        )
                                            ? $conflict[
                                                'automation_b_statuses'
                                            ]
                                            : array();

                                    ?>

                                    <?php if (
                                        ! empty(
                                            $automation_a_statuses
                                        )
                                    ) : ?>

                                        <br>

                                        <strong>
                                            وضعیت‌های اتوماسیون اول:
                                        </strong>

                                        <?php

                                        $labels_a = array();

                                        foreach (
                                            $automation_a_statuses
                                            as $status
                                        ) {

                                            $labels_a[] =
                                                $this->get_status_label(
                                                    $status
                                                );
                                        }

                                        echo esc_html(
                                            implode(
                                                ' ← ',
                                                $labels_a
                                            )
                                        );

                                        ?>

                                    <?php endif; ?>

                                    <?php if (
                                        ! empty(
                                            $automation_b_statuses
                                        )
                                    ) : ?>

                                        <br>

                                        <strong>
                                            وضعیت‌های اتوماسیون دوم:
                                        </strong>

                                        <?php

                                        $labels_b = array();

                                        foreach (
                                            $automation_b_statuses
                                            as $status
                                        ) {

                                            $labels_b[] =
                                                $this->get_status_label(
                                                    $status
                                                );
                                        }

                                        echo esc_html(
                                            implode(
                                                ' ← ',
                                                $labels_b
                                            )
                                        );

                                        ?>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

    <?php
}

/**
 * Get cross-Automation conflicts for one Automation.
 *
 * @param int  $automation_id Automation ID.
 * @param bool $include_self  Whether the current Automation may be returned as a pair.
 *
 * @return array
 */
public function get_conflicts(
    $automation_id,
    $include_self = false
) {

    $automation_id = absint(
        $automation_id
    );

    if ( ! $automation_id ) {
        return array();
    }

    $automation = get_post(
        $automation_id
    );

    if (
        ! $automation ||
        'woosmart_automation' !==
        $automation->post_type
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

    foreach (
        $automations
        as $candidate
    ) {

        $candidate_id =
            absint(
                $candidate->ID
            );

        if (
            ! $include_self &&
            $candidate_id ===
            $automation_id
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

        if (
            ! empty(
                $pair_conflicts
            )
        ) {

            $conflicts =
                array_merge(
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

    foreach (
        $automations
        as $automation
    ) {

        $automation_id =
            absint(
                $automation->ID
            );

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

        $active[] =
            $automation_id;
    }

    $conflicts = array();

    $count =
        count(
            $active
        );

    for (
        $i = 0;
        $i < $count;
        $i++
    ) {

        for (
            $j = $i + 1;
            $j < $count;
            $j++
        ) {

            $pair_conflicts =
                $this->compare_automations(
                    $active[ $i ],
                    $active[ $j ]
                );

            if (
                ! empty(
                    $pair_conflicts
                )
            ) {

                $conflicts =
                    array_merge(
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
 *
 * @return array
 */
private function compare_automations(
    $first_id,
    $second_id
) {

    if (
        $first_id ===
        $second_id
    ) {
        return array();
    }

    $first_trigger =
        sanitize_key(
            get_post_meta(
                $first_id,
                '_woosmart_trigger',
                true
            )
        );

    $second_trigger =
        sanitize_key(
            get_post_meta(
                $second_id,
                '_woosmart_trigger',
                true
            )
        );

    if (
        empty( $first_trigger ) ||
        $first_trigger !==
        $second_trigger
    ) {
        return array();
    }

    $first_conditions =
        $this->normalize_conditions(
            get_post_meta(
                $first_id,
                '_woosmart_conditions',
                true
            )
        );

    $second_conditions =
        $this->normalize_conditions(
            get_post_meta(
                $second_id,
                '_woosmart_conditions',
                true
            )
        );

    if (
        ! $this->conditions_overlap(
            $first_conditions,
            $second_conditions
        )
    ) {
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

    if (
        ! is_array(
            $first_actions
        )
    ) {
        $first_actions =
            array();
    }

    if (
        ! is_array(
            $second_actions
        )
    ) {
        $second_actions =
            array();
    }

    $conflicts = array();

    /*
     * Conflict 1:
     * The two Automations may both match
     * the same order.
     */
    $conflicts[] = array(
        'code' =>
            'overlapping_automation_conditions',

        'severity' =>
            'warning',

        'automation_a' =>
            $first_id,

        'automation_b' =>
            $second_id,

        'message' =>
            'شرایط این دو اتوماسیون می‌تواند برای یک سفارش به‌صورت همزمان برقرار شود؛ بنابراین ممکن است هر دو در یک اجرا وارد زنجیره شوند.',
    );

    $first_statuses =
        $this->get_status_targets(
            $first_actions
        );

    $second_statuses =
        $this->get_status_targets(
            $second_actions
        );

    /*
     * Only analyze Action-level status interaction
     * when both Automations actually modify order status.
     */
    if (
        ! empty(
            $first_statuses
        ) &&
        ! empty(
            $second_statuses
        )
    ) {

        /*
         * Conflict 2:
         * Both Automations can request
         * the same target status.
         *
         * This warning is kept even when one Automation
         * also has additional different status targets.
         */
        $common_statuses =
            array_values(
                array_intersect(
                    $first_statuses,
                    $second_statuses
                )
            );

        if (
            ! empty(
                $common_statuses
            )
        ) {

            $conflicts[] = array(
                'code' =>
                    'duplicate_cross_automation_status_target',

                'severity' =>
                    'warning',

                'automation_a' =>
                    $first_id,

                'automation_b' =>
                    $second_id,

                'statuses' =>
                    $common_statuses,

                'message' =>
                    'هر دو اتوماسیون در شرایط همپوشان می‌توانند وضعیت سفارش را به یک وضعیت یکسان تغییر دهند؛ WooSmart در صورت یکسان بودن وضعیت فعلی، تغییر تکراری را اجرا نمی‌کند.',
            );
        }

        /*
         * Conflict 3:
         * The Automations have different status targets.
         *
         * This check is intentionally independent from
         * the duplicate-target warning. Therefore a pair
         * such as:
         *
         * A → processing
         * B → processing → completed
         *
         * produces both warnings.
         */
        $different_statuses =
            $this->get_different_status_targets(
                $first_statuses,
                $second_statuses
            );

        if (
            ! empty(
                $different_statuses
            )
        ) {

            $message =
                $this->build_status_transition_message(
                    $first_id,
                    $second_id,
                    $first_statuses,
                    $second_statuses
                );

            $conflicts[] = array(
                'code' =>
                    'cross_automation_status_transition',

                'severity' =>
                    'warning',

                'automation_a' =>
                    $first_id,

                'automation_b' =>
                    $second_id,

                'automation_a_statuses' =>
                    $first_statuses,

                'automation_b_statuses' =>
                    $second_statuses,

                'message' =>
                    $message,
            );
        }
    }

    return $conflicts;
}

/**
 * Normalize current MVP condition storage.
 *
 * @param mixed $conditions Stored conditions.
 *
 * @return array
 */
private function normalize_conditions(
    $conditions
) {

    if (
        ! is_array(
            $conditions
        )
    ) {
        return array();
    }

    $valid = array();

    foreach (
        $conditions
        as $condition
    ) {

        if (
            ! is_array(
                $condition
            )
        ) {
            continue;
        }

        if (
            empty(
                $condition['field']
            ) ||
            empty(
                $condition['operator']
            )
        ) {
            continue;
        }

        $valid[] =
            $condition;
    }

    /*
     * Current MVP supports one authoritative Condition.
     * Preserve the latest valid condition if legacy data
     * contains more than one entry.
     */
    if (
        count(
            $valid
        ) > 1
    ) {

        $last =
            end(
                $valid
            );

        $valid =
            array(
                $last,
            );
    }

    return $valid;
}

/**
 * Determine whether two current-MVP condition sets can overlap.
 *
 * Empty conditions are treated as matching all values.
 * Deterministic interval analysis is currently limited to
 * order_total. Unknown same-trigger condition types are treated
 * conservatively.
 *
 * @param array $first_conditions  First condition set.
 * @param array $second_conditions Second condition set.
 *
 * @return bool
 */
private function conditions_overlap(
    $first_conditions,
    $second_conditions
) {

    if (
        empty(
            $first_conditions
        ) ||
        empty(
            $second_conditions
        )
    ) {
        return true;
    }

    $first =
        reset(
            $first_conditions
        );

    $second =
        reset(
            $second_conditions
        );

    if (
        ! is_array(
            $first
        ) ||
        ! is_array(
            $second
        )
    ) {
        return true;
    }

    $first_field =
        isset(
            $first['field']
        )
            ? sanitize_key(
                $first['field']
            )
            : '';

    $second_field =
        isset(
            $second['field']
        )
            ? sanitize_key(
                $second['field']
            )
            : '';

    if (
        'order_total' !==
            $first_field ||
        'order_total' !==
            $second_field
    ) {
        return true;
    }

    $first_interval =
        $this->condition_to_interval(
            $first
        );

    $second_interval =
        $this->condition_to_interval(
            $second
        );

    if (
        false ===
            $first_interval ||
        false ===
            $second_interval
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
 * Convert an order_total condition into an interval.
 *
 * @param array $condition Condition.
 *
 * @return array|false
 */
private function condition_to_interval(
    $condition
) {

    $operator =
        isset(
            $condition['operator']
        )
            ? sanitize_key(
                $condition['operator']
            )
            : '';

    $value =
        isset(
            $condition['value']
        )
            ? $condition['value']
            : '';

    switch (
        $operator
    ) {

        case 'is_equal':

            return array(
                'min' =>
                    (float) $value,

                'max' =>
                    (float) $value,
            );

        case 'greater_than':

            return array(
                'min' =>
                    (float) $value +
                    0.0000001,

                'max' =>
                    INF,
            );

        case 'greater_than_or_equal':

            return array(
                'min' =>
                    (float) $value,

                'max' =>
                    INF,
            );

        case 'less_than':

            return array(
                'min' =>
                    -INF,

                'max' =>
                    (float) $value -
                    0.0000001,
            );

        case 'less_than_or_equal':

            return array(
                'min' =>
                    -INF,

                'max' =>
                    (float) $value,
            );

        case 'between':

            if (
                ! is_array(
                    $value
                )
            ) {
                return false;
            }

            if (
                ! isset(
                    $value['min']
                ) ||
                ! isset(
                    $value['max']
                )
            ) {
                return false;
            }

            return array(
                'min' =>
                    (float)
                    $value['min'],

                'max' =>
                    (float)
                    $value['max'],
            );

        case 'is_not_equal':

            /*
             * For MVP conflict analysis,
             * "not equal" is conservatively treated
             * as potentially overlapping any other range.
             */
            return array(
                'min' =>
                    -INF,

                'max' =>
                    INF,
            );
    }

    return false;
}

/**
 * Get unique order-status targets from Actions.
 *
 * @param array $actions Actions.
 *
 * @return array
 */
private function get_status_targets(
    $actions
) {

    $statuses = array();

    foreach (
        $actions
        as $action
    ) {

        if (
            ! is_array(
                $action
            )
        ) {
            continue;
        }

        $type =
            isset(
                $action['type']
            )
                ? sanitize_key(
                    $action['type']
                )
                : '';

        if (
            'change_order_status' !==
            $type
        ) {
            continue;
        }

        $status =
            isset(
                $action['status']
            )
                ? sanitize_key(
                    $action['status']
                )
                : '';

        if (
            '' ===
            $status
        ) {
            continue;
        }

        $statuses[] =
            $status;
    }

    return array_values(
        array_unique(
            $statuses
        )
    );
}

/**
 * Get statuses present in either automation
 * but not shared by both.
 *
 * @param array $first_statuses  First status targets.
 * @param array $second_statuses Second status targets.
 *
 * @return array
 */
private function get_different_status_targets(
    $first_statuses,
    $second_statuses
) {

    $different = array();

    foreach (
        $first_statuses
        as $status
    ) {

        if (
            ! in_array(
                $status,
                $second_statuses,
                true
            )
        ) {

            $different[] =
                array(
                    'source' =>
                        'automation_a',

                    'status' =>
                        $status,
                );
        }
    }

    foreach (
        $second_statuses
        as $status
    ) {

        if (
            ! in_array(
                $status,
                $first_statuses,
                true
            )
        ) {

            $different[] =
                array(
                    'source' =>
                        'automation_b',

                    'status' =>
                        $status,
                );
        }
    }

    return $different;
}

/**
 * Build a customer-readable status transition warning.
 *
 * @param int   $first_id        First Automation ID.
 * @param int   $second_id       Second Automation ID.
 * @param array $first_statuses  First status targets.
 * @param array $second_statuses Second status targets.
 *
 * @return string
 */
private function build_status_transition_message(
    $first_id,
    $second_id,
    $first_statuses,
    $second_statuses
) {

    $first_labels = array();

    foreach (
        $first_statuses
        as $status
    ) {

        $first_labels[] =
            $this->get_status_label(
                $status
            );
    }

    $second_labels = array();

    foreach (
        $second_statuses
        as $status
    ) {

        $second_labels[] =
            $this->get_status_label(
                $status
            );
    }

    $priority_a =
        $this->get_automation_priority(
            $first_id
        );

    $priority_b =
        $this->get_automation_priority(
            $second_id
        );

    $message =
        'هر دو اتوماسیون در شرایط همپوشان می‌توانند وضعیت سفارش را تغییر دهند. ';

    $message .=
        'اتوماسیون اول: ' .
        implode(
            ' ← ',
            $first_labels
        ) .
        '؛ ';

    $message .=
        'اتوماسیون دوم: ' .
        implode(
            ' ← ',
            $second_labels
        ) .
        '. ';

    if (
        null !== $priority_a &&
        null !== $priority_b
    ) {

        $message .=
            'ترتیب Priority (' .
            $priority_a .
            ' سپس ' .
            $priority_b .
            ') می‌تواند روی نتیجه نهایی اثر بگذارد.';
    } else {

        $message .=
            'ترتیب اجرای اتوماسیون‌ها و وضعیت واقعی سفارش می‌تواند روی نتیجه نهایی اثر بگذارد.';
    }

    return $message;
}

/**
 * Get Automation priority.
 *
 * @param int $automation_id Automation ID.
 *
 * @return int|null
 */
private function get_automation_priority(
    $automation_id
) {

    $automation_id =
        absint(
            $automation_id
        );

    if (
        ! $automation_id
    ) {
        return null;
    }

    $raw_priority =
        get_post_meta(
            $automation_id,
            '_woosmart_priority',
            true
        );

    if (
        '' ===
        $raw_priority ||
        null ===
        $raw_priority
    ) {
        return null;
    }

    return absint(
        $raw_priority
    );
}

/**
 * Get a human-readable Automation name.
 *
 * @param int $automation_id Automation ID.
 *
 * @return string
 */
public function get_automation_name(
    $automation_id
) {

    $title =
        get_the_title(
            absint(
                $automation_id
            )
        );

    return $title
        ? $title
        : 'Automation #' .
            absint(
                $automation_id
            );
}

/**
 * Get a human-readable conflict label.
 *
 * @param string $code Conflict code.
 *
 * @return string
 */
public function get_conflict_label(
    $code
) {

    switch (
        sanitize_key(
            $code
        )
    ) {

        case 'overlapping_automation_conditions':

            return 'همپوشانی شرایط';

        case 'duplicate_cross_automation_status_target':

            return 'مقصد وضعیت یکسان';

        case 'cross_automation_status_transition':

            return 'تغییر وضعیت متداخل';
    }

    return 'هشدار تعارض';
}

/**
 * Get a human-readable order-status label.
 *
 * @param string $status Status slug.
 *
 * @return string
 */
public function get_status_label(
    $status
) {

    $status =
        sanitize_key(
            $status
        );

    if (
        function_exists(
            'wc_get_order_statuses'
        )
    ) {

        $statuses =
            wc_get_order_statuses();

        $key =
            'wc-' .
            $status;

        if (
            isset(
                $statuses[
                    $key
                ]
            )
        ) {

            return wp_strip_all_tags(
                $statuses[
                    $key
                ]
            );
        }
    }

    return $status;
}
}
