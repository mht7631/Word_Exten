<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class for WooSmart Automation.
 */
class WooSmart_Admin {

    /**
     * Logger instance.
     *
     * @var WooSmart_Logger
     */
    private $logger;

    /**
     * Condition Registry instance.
     *
     * @var WooSmart_Condition_Registry
     */
    private $condition_registry;

    /**
     * Currency service instance.
     *
     * @var WooSmart_Currency
     */
    private $currency;

    /**
     * Initialize admin functionality.
     */
    public function __construct() {

        $this->logger =
            new WooSmart_Logger();

        $this->condition_registry =
            new WooSmart_Condition_Registry();

        $this->currency =
            new WooSmart_Currency();

        add_action(
            'admin_menu',
            array( $this, 'add_admin_menu' )
        );
    }

    /**
     * Add WooSmart admin menu.
     *
     * @return void
     */
    public function add_admin_menu() {

        add_menu_page(
            'WooSmart Automation',
            'WooSmart',
            'manage_options',
            'woosmart-automation',
            array(
                $this,
                'render_dashboard_page',
            ),
            'dashicons-controls-repeat',
            30
        );

        add_submenu_page(
            'woosmart-automation',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'woosmart-automation',
            array(
                $this,
                'render_dashboard_page',
            )
        );

        add_submenu_page(
            'woosmart-automation',
            'اتوماسیون‌ها',
            'اتوماسیون‌ها',
            'manage_options',
            'woosmart-automations',
            array(
                $this,
                'render_automations_page',
            )
        );

        add_submenu_page(
            'woosmart-automation',
            'افزودن اتوماسیون',
            'افزودن اتوماسیون',
            'manage_options',
            'woosmart-add-automation',
            array(
                $this,
                'render_add_automation_page',
            )
        );

        add_submenu_page(
            'woosmart-automation',
            'گزارش‌ها',
            'گزارش‌ها',
            'manage_options',
            'woosmart-automation-logs',
            array(
                $this,
                'render_logs_page',
            )
        );
    }

    /**
     * Dashboard.
     *
     * @return void
     */
    public function render_dashboard_page() {

        $status = get_option(
            'woosmart_automation_status',
            'وضعیتی ثبت نشده است'
        );
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                WooSmart Automation
            </h1>

            <p>
                افزونه WooSmart Automation با موفقیت اجرا شده است.
            </p>

            <hr>

            <h2>
                وضعیت افزونه
            </h2>

            <p>
                <strong>
                    <?php echo esc_html( $status ); ?>
                </strong>
            </p>

        </div>

        <?php
    }

    /**
     * Automations page.
     *
     * @return void
     */
    public function render_automations_page() {

        $automations = get_posts(
            array(
                'post_type'      => 'woosmart_automation',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1 class="wp-heading-inline">
                اتوماسیون‌ها
            </h1>

            <a
                href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-add-automation' ) ); ?>"
                class="page-title-action"
            >
                افزودن اتوماسیون
            </a>

            <hr class="wp-header-end">

            <?php if ( empty( $automations ) ) : ?>

                <div class="notice notice-info">

                    <p>
                        هنوز هیچ اتوماسیونی ساخته نشده است.
                    </p>

                </div>

            <?php else : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>

                            <th>
                                شناسه
                            </th>

                            <th>
                                نام
                            </th>

                            <th>
                                رویداد
                            </th>

                            <th>
                                شرایط
                            </th>

                            <th>
                                عملیات
                            </th>

                            <th>
                                وضعیت
                            </th>

                            <th>
                                مدیریت
                            </th>

                            <th>
                                تاریخ
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ( $automations as $automation ) : ?>

                            <?php

                            $automation_id = $automation->ID;

                            $trigger = get_post_meta(
                                $automation_id,
                                '_woosmart_trigger',
                                true
                            );

                            $status = get_post_meta(
                                $automation_id,
                                '_woosmart_status',
                                true
                            );

                            $conditions = get_post_meta(
                                $automation_id,
                                '_woosmart_conditions',
                                true
                            );

                            $actions = get_post_meta(
                                $automation_id,
                                '_woosmart_actions',
                                true
                            );

                            if ( ! is_array( $conditions ) ) {
                                $conditions = array();
                            }

                            if ( ! is_array( $actions ) ) {
                                $actions = array();
                            }

                            if ( 'active' === $status ) {

                                $status_label = 'فعال';
                                $status_class = 'notice-success';
                                $toggle_label = 'غیرفعال کردن';

                            } else {

                                $status_label = 'غیرفعال';
                                $status_class = 'notice-warning';
                                $toggle_label = 'فعال کردن';
                            }

                            $toggle_url = wp_nonce_url(
                                admin_url(
                                    'admin-post.php?action=woosmart_toggle_automation&automation_id=' .
                                    $automation_id
                                ),
                                'woosmart_toggle_automation_' .
                                $automation_id
                            );

                            $delete_url = wp_nonce_url(
                                admin_url(
                                    'admin-post.php?action=woosmart_delete_automation&automation_id=' .
                                    $automation_id
                                ),
                                'woosmart_delete_automation_' .
                                $automation_id
                            );

                            $duplicate_url = wp_nonce_url(
                                admin_url(
                                    'admin-post.php?action=woosmart_duplicate_automation&automation_id=' .
                                    $automation_id
                                ),
                                'woosmart_duplicate_automation_' .
                                $automation_id
                            );

                            $edit_url = admin_url(
                                'admin.php?page=woosmart-add-automation&edit=' .
                                $automation_id
                            );

                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $automation_id
                                    );
                                    ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $automation->post_title
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_trigger_label(
                                            $trigger
                                        )
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ( empty( $conditions ) ) : ?>

                                        <span>
                                            بدون شرط
                                        </span>

                                    <?php else : ?>

                                        <?php foreach ( $conditions as $condition ) : ?>

                                            <?php

                                            $field = isset(
                                                $condition['field']
                                            )
                                                ? sanitize_key(
                                                    $condition['field']
                                                )
                                                : '';

                                            $operator = isset(
                                                $condition['operator']
                                            )
                                                ? sanitize_key(
                                                    $condition['operator']
                                                )
                                                : '';

                                            $value = isset(
                                                $condition['value']
                                            )
                                                ? $condition['value']
                                                : '';

                                            $definition =
                                                $this->condition_registry->get(
                                                    $field
                                                );

                                            $value_type = (
                                                is_array(
                                                    $definition
                                                ) &&
                                                isset(
                                                    $definition['value_type']
                                                )
                                            )
                                                ? sanitize_key(
                                                    $definition['value_type']
                                                )
                                                : 'text';

                                            ?>

                                            <div
                                                style="
                                                    margin-bottom:6px;
                                                "
                                            >

                                                <strong>
                                                    <?php
                                                    echo esc_html(
                                                        $this->get_condition_field_label(
                                                            $field
                                                        )
                                                    );
                                                    ?>
                                                </strong>

                                                <?php
                                                echo esc_html(
                                                    $this->get_operator_label(
                                                        $operator,
                                                        $field
                                                    )
                                                );
                                                ?>

                                                <?php
                                                if (
                                                    'number' ===
                                                    $value_type
                                                ) {

                                                    echo wp_kses_post(
                                                        $this->format_currency_value(
                                                            $value
                                                        )
                                                    );

                                                } else {

                                                    echo esc_html(
                                                        $value
                                                    );
                                                }
                                                ?>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ( empty( $actions ) ) : ?>

                                        <span>
                                            بدون عملیات
                                        </span>

                                    <?php else : ?>

                                        <?php foreach ( $actions as $index => $action ) : ?>

                                            <?php

                                            $action_type = isset(
                                                $action['type']
                                            )
                                                ? $action['type']
                                                : '';

                                            ?>

                                            <div
                                                style="
                                                    margin-bottom:8px;
                                                    padding-bottom:8px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <strong>
                                                    عملیات
                                                    <?php
                                                    echo esc_html(
                                                        $index + 1
                                                    );
                                                    ?>:
                                                </strong>

                                                <?php if ( 'change_order_status' === $action_type ) : ?>

                                                    <span>
                                                        تغییر وضعیت سفارش
                                                    </span>

                                                    <br>

                                                    <span>
                                                        <?php
                                                        echo esc_html(
                                                            $this->get_order_status_label(
                                                                isset(
                                                                    $action['status']
                                                                )
                                                                    ? $action['status']
                                                                    : ''
                                                            )
                                                        );
                                                        ?>
                                                    </span>

                                                <?php elseif ( 'notify_admin' === $action_type ) : ?>

                                                    <span>
                                                        ارسال اعلان به مدیر فروشگاه
                                                    </span>

                                                <?php else : ?>

                                                    <span>
                                                        <?php
                                                        echo esc_html(
                                                            $this->get_action_label(
                                                                $action_type
                                                            )
                                                        );
                                                        ?>
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span
                                        class="notice <?php echo esc_attr( $status_class ); ?>"
                                        style="
                                            display:inline-block;
                                            padding:4px 8px;
                                            margin:0;
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $status_label
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td>

                                    <a
                                        href="<?php echo esc_url( $toggle_url ); ?>"
                                        class="button"
                                    >
                                        <?php
                                        echo esc_html(
                                            $toggle_label
                                        );
                                        ?>
                                    </a>

                                    <a
                                        href="<?php echo esc_url( $edit_url ); ?>"
                                        class="button"
                                    >
                                        ویرایش
                                    </a>

                                    <a
                                        href="<?php echo esc_url( $duplicate_url ); ?>"
                                        class="button"
                                    >
                                        کپی
                                    </a>

                                    <a
                                        href="<?php echo esc_url( $delete_url ); ?>"
                                        class="button"
                                        onclick="return confirm('آیا مطمئن هستید که می‌خواهید این اتوماسیون را به زباله‌دان منتقل کنید؟');"
                                    >
                                        حذف
                                    </a>

                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        get_the_date(
                                            'Y-m-d H:i:s',
                                            $automation_id
                                        )
                                    );
                                    ?>
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
     * Add/Edit Automation page.
     *
     * @return void
     */
    public function render_add_automation_page() {

        $edit_id = isset( $_GET['edit'] )
            ? absint( $_GET['edit'] )
            : 0;

        $is_edit = false;

        $name = '';

        $trigger = 'order_created';

        $condition_field = 'order_total';
        $condition_operator = 'greater_than';
        $condition_value = '';

        $actions = array();

        $condition_definitions =
            $this->condition_registry->get_all();

        $currency_unit =
            $this->currency->get_display_unit();

        if (
            ! isset(
                $condition_definitions['order_total']
            ) &&
            ! empty( $condition_definitions )
        ) {

            $condition_keys =
                array_keys(
                    $condition_definitions
                );

            $condition_field =
                $condition_keys[0];

            $first_definition =
                $condition_definitions[
                    $condition_field
                ];

            if (
                isset(
                    $first_definition['operators']
                ) &&
                is_array(
                    $first_definition['operators']
                ) &&
                ! empty(
                    $first_definition['operators']
                )
            ) {

                $operator_keys =
                    array_keys(
                        $first_definition['operators']
                    );

                $condition_operator =
                    $operator_keys[0];
            }
        }

        if ( $edit_id ) {

            if (
                'woosmart_automation' ===
                get_post_type( $edit_id )
            ) {

                $is_edit = true;

                $automation = get_post(
                    $edit_id
                );

                if ( $automation ) {

                    $name =
                        $automation->post_title;

                    $trigger =
                        get_post_meta(
                            $edit_id,
                            '_woosmart_trigger',
                            true
                        );

                    $conditions =
                        get_post_meta(
                            $edit_id,
                            '_woosmart_conditions',
                            true
                        );

                    $stored_actions =
                        get_post_meta(
                            $edit_id,
                            '_woosmart_actions',
                            true
                        );

                    if (
                        is_array( $conditions ) &&
                        ! empty( $conditions )
                    ) {

                        $condition =
                            $conditions[0];

                        $condition_field =
                            isset(
                                $condition['field']
                            )
                                ? sanitize_key(
                                    $condition['field']
                                )
                                : $condition_field;

                        $condition_definition =
                            $this->condition_registry->get(
                                $condition_field
                            );

                        $condition_operator =
                            isset(
                                $condition['operator']
                            )
                                ? sanitize_key(
                                    $condition['operator']
                                )
                                : $condition_operator;

                        if (
                            ! is_array(
                                $condition_definition
                            ) ||
                            ! isset(
                                $condition_definition['operators']
                            ) ||
                            ! is_array(
                                $condition_definition['operators']
                            ) ||
                            ! isset(
                                $condition_definition['operators'][
                                    $condition_operator
                                ]
                            )
                        ) {

                            if (
                                is_array(
                                    $condition_definition
                                ) &&
                                ! empty(
                                    $condition_definition['operators']
                                )
                            ) {

                                $operator_keys =
                                    array_keys(
                                        $condition_definition[
                                            'operators'
                                        ]
                                    );

                                $condition_operator =
                                    $operator_keys[0];
                            }
                        }

                        $condition_value =
                            isset(
                                $condition['value']
                            )
                                ? $condition['value']
                                : '';
                    }

                    if (
                        is_array( $stored_actions ) &&
                        ! empty( $stored_actions )
                    ) {

                        $actions =
                            $stored_actions;
                    }
                }
            }
        }

        if ( empty( $actions ) ) {

            $actions[] = array(
                'type'    => 'notify_admin',
                'subject' =>
                    'اعلان سفارش جدید در WooSmart',
                'message' =>
                    "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.\n\n" .
                    "شناسه سفارش: {order_id}\n" .
                    "مبلغ سفارش: {order_total}\n" .
                    "وضعیت سفارش: {order_status}\n" .
                    "نام مشتری: {customer_name}",
            );
        }

        $order_statuses = array();

        if (
            function_exists(
                'wc_get_order_statuses'
            )
        ) {

            $wc_statuses =
                wc_get_order_statuses();

            foreach (
                $wc_statuses
                as $status_key =>
                $status_label
            ) {

                $status_slug =
                    str_replace(
                        'wc-',
                        '',
                        $status_key
                    );

                $order_statuses[
                    $status_slug
                ] =
                    $this->get_order_status_label(
                        $status_slug,
                        $status_label
                    );
            }
        }

        if (
            empty(
                $order_statuses
            )
        ) {

            $order_statuses = array(
                'pending' =>
                    'در انتظار پرداخت',
                'processing' =>
                    'در حال پردازش',
                'on-hold' =>
                    'در انتظار',
                'completed' =>
                    'تکمیل‌شده',
                'cancelled' =>
                    'لغوشده',
                'refunded' =>
                    'مستردشده',
                'failed' =>
                    'ناموفق',
            );
        }

        $current_condition_definition =
            $this->condition_registry->get(
                $condition_field
            );

        $current_condition_value_type =
            (
                is_array(
                    $current_condition_definition
                ) &&
                isset(
                    $current_condition_definition[
                        'value_type'
                    ]
                )
            )
                ? sanitize_key(
                    $current_condition_definition[
                        'value_type'
                    ]
                )
                : 'text';

        $condition_value_numeric =
            $this->normalize_numeric_input(
                $condition_value
            );

        $condition_value_display = '';

        if (
            'number' ===
            $current_condition_value_type &&
            '' !== $condition_value_numeric
        ) {

            $condition_value_display =
                $this->format_currency_input(
                    $condition_value_numeric
                );

        } else {

            $condition_value_display =
                (string)
                $condition_value;
        }

        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                <?php
                echo $is_edit
                    ? 'ویرایش اتوماسیون'
                    : 'ایجاد اتوماسیون';
                ?>
            </h1>

            <hr>

            <?php if ( $is_edit ) : ?>

                <form
                    method="post"
                    action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="woosmart_update_automation"
                    >

                    <input
                        type="hidden"
                        name="automation_id"
                        value="<?php echo esc_attr( $edit_id ); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'woosmart_update_automation',
                        'woosmart_automation_nonce'
                    );
                    ?>

            <?php else : ?>

                <form
                    method="post"
                    action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="woosmart_save_automation"
                    >

                    <?php
                    wp_nonce_field(
                        'woosmart_save_automation',
                        'woosmart_automation_nonce'
                    );
                    ?>

            <?php endif; ?>

                <table class="form-table">

                    <tr>

                        <th scope="row">
                            <label for="automation_name">
                                نام اتوماسیون
                            </label>
                        </th>

                        <td>

                            <input
                                type="text"
                                id="automation_name"
                                name="automation_name"
                                class="regular-text"
                                value="<?php echo esc_attr( $name ); ?>"
                                placeholder="مثلاً سفارش مناسب"
                                required
                            >

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">
                            <label for="automation_trigger">
                                رویداد
                            </label>
                        </th>

                        <td>

                            <select
                                id="automation_trigger"
                                name="automation_trigger"
                            >

                                <option
                                    value="order_created"
                                    <?php selected(
                                        $trigger,
                                        'order_created'
                                    ); ?>
                                >
                                    ایجاد سفارش
                                </option>

                            </select>

                        </td>

                    </tr>

                </table>

                <h2>
                    شرایط
                </h2>

                <p>
                    اتوماسیون فقط زمانی اجرا می‌شود که شرط برقرار باشد.
                </p>

                <table class="form-table">

                    <tr>

                        <th scope="row">
                            <label for="condition_field">
                                فیلد
                            </label>
                        </th>

                        <td>

                            <select
                                id="condition_field"
                                name="condition_field"
                            >

                                <?php foreach (
                                    $condition_definitions
                                    as $condition_key =>
                                    $condition_definition
                                ) : ?>

                                    <?php

                                    $condition_label =
                                        isset(
                                            $condition_definition[
                                                'label'
                                            ]
                                        )
                                            ? $condition_definition[
                                                'label'
                                            ]
                                            : $condition_key;

                                    ?>

                                    <option
                                        value="<?php echo esc_attr( $condition_key ); ?>"
                                        <?php selected(
                                            $condition_field,
                                            $condition_key
                                        ); ?>
                                    >
                                        <?php
                                        echo esc_html(
                                            $condition_label
                                        );
                                        ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">

                            <label for="condition_operator">
                                مقایسه
                            </label>

                        </th>

                        <td>

                            <select
                                id="condition_operator"
                                name="condition_operator"
                            >

                                <?php

                                $current_operators =
                                    $this->condition_registry->get_operators(
                                        $condition_field
                                    );

                                ?>

                                <?php foreach (
                                    $current_operators
                                    as $operator_key =>
                                    $operator_label
                                ) : ?>

                                    <option
                                        value="<?php echo esc_attr( $operator_key ); ?>"
                                        <?php selected(
                                            $condition_operator,
                                            $operator_key
                                        ); ?>
                                    >
                                        <?php
                                        echo esc_html(
                                            $operator_label
                                        );
                                        ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">

                            <label for="condition_value_display">
                                مقدار
                            </label>

                        </th>

                        <td>

                            <div
                                id="condition-value-number-wrapper"
                                dir="ltr"
                                style="
                                    display:
                                    <?php
                                    echo 'number' ===
                                        $current_condition_value_type
                                        ? 'flex'
                                        : 'none';
                                    ?>;
                                    align-items:center;
                                    gap:10px;
                                    max-width:420px;
                                "
                            >

                                <div
                                    style="
                                        position:relative;
                                        flex:1;
                                        direction:ltr;
                                    "
                                >

                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        dir="ltr"
                                        id="condition_value_display"
                                        class="regular-text"
                                        value="<?php echo esc_attr( $condition_value_display ); ?>"
                                        placeholder="100,000"
                                        spellcheck="false"
                                        style="
                                            width:100%;
                                            box-sizing:border-box;
                                            padding:8px 12px 8px 90px;
                                            direction:ltr;
                                            unicode-bidi:plaintext;
                                            text-align:left;
                                            font-variant-numeric:tabular-nums;
                                        "
                                    >

                                    <span
                                        id="condition-value-unit"
                                        dir="rtl"
                                        style="
                                            position:absolute;
                                            left:12px;
                                            top:50%;
                                            transform:translateY(-50%);
                                            color:#646970;
                                            pointer-events:none;
                                            font-size:13px;
                                            font-weight:600;
                                            direction:rtl;
                                            unicode-bidi:isolate;
                                            white-space:nowrap;
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $currency_unit
                                        );
                                        ?>
                                    </span>

                                </div>

                            </div>

                            <div
                                id="condition-value-text-wrapper"
                                style="
                                    display:
                                    <?php
                                    echo 'number' ===
                                        $current_condition_value_type
                                        ? 'none'
                                        : 'block';
                                    ?>;
                                    max-width:420px;
                                "
                            >

                                <input
                                    type="text"
                                    id="condition_value_text"
                                    class="regular-text"
                                    value="<?php echo esc_attr( $condition_value_display ); ?>"
                                >

                            </div>

                            <input
                                type="hidden"
                                id="condition_value"
                                name="condition_value"
                                value="<?php echo esc_attr( $condition_value_numeric ); ?>"
                            >

                            <p
                                class="description"
                                id="condition-value-description"
                            >
                                <?php
                                if (
                                    'number' ===
                                    $current_condition_value_type
                                ) {

                                    echo esc_html(
                                        'مبلغ را به واحد پول فروشگاه وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.'
                                    );

                                } else {

                                    echo esc_html(
                                        'مقدار شرط را وارد کنید.'
                                    );
                                }
                                ?>
                            </p>

                        </td>

                    </tr>

                </table>

                <h2>
                    عملیات
                </h2>

                <p>
                    تمام عملیات انتخاب‌شده پس از برقرار شدن شرایط، به ترتیب اجرا می‌شوند.
                </p>

                <div
                    id="woosmart-actions-container"
                    style="
                        max-width:900px;
                    "
                >

                    <?php foreach (
                        $actions as $index => $action
                    ) : ?>

                        <?php
                        $this->render_action_row(
                            $index,
                            $action,
                            $order_statuses
                        );
                        ?>

                    <?php endforeach; ?>

                </div>

                <p>

                    <button
                        type="button"
                        class="button"
                        id="woosmart-add-action"
                    >
                        + افزودن عملیات
                    </button>

                </p>

                <p class="description">
                    می‌توانید چند عملیات را برای یک اتوماسیون تعریف کنید.
                </p>

                <?php
                submit_button(
                    $is_edit
                        ? 'ذخیره تغییرات'
                        : 'ذخیره اتوماسیون'
                );
                ?>

            </form>

        </div>

        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function() {

                    const conditionDefinitions =
                        <?php
                        echo wp_json_encode(
                            $condition_definitions,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        );
                        ?>;

                    const currencyUnit =
                        <?php
                        echo wp_json_encode(
                            $currency_unit,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        );
                        ?>;

                    const conditionField =
                        document.getElementById(
                            'condition_field'
                        );

                    const conditionOperator =
                        document.getElementById(
                            'condition_operator'
                        );

                    const conditionValue =
                        document.getElementById(
                            'condition_value'
                        );

                    const amountDisplay =
                        document.getElementById(
                            'condition_value_display'
                        );

                    const amountWrapper =
                        document.getElementById(
                            'condition-value-number-wrapper'
                        );

                    const textWrapper =
                        document.getElementById(
                            'condition-value-text-wrapper'
                        );

                    const textInput =
                        document.getElementById(
                            'condition_value_text'
                        );

                    const valueDescription =
                        document.getElementById(
                            'condition-value-description'
                        );

                    const valueUnit =
                        document.getElementById(
                            'condition-value-unit'
                        );

                    function normalizeNumber(
                        value
                    ) {

                        value = String(
                            value || ''
                        );

                        value =
                            value.replace(
                                /,/g,
                                ''
                            );

                        value =
                            value.replace(
                                /[^\d.]/g,
                                ''
                            );

                        const firstDot =
                            value.indexOf(
                                '.'
                            );

                        if (
                            firstDot !== -1
                        ) {

                            value =
                                value.substring(
                                    0,
                                    firstDot + 1
                                ) +
                                value.substring(
                                    firstDot + 1
                                ).replace(
                                    /\./g,
                                    ''
                                );
                        }

                        return value;
                    }

                    function formatNumber(
                        value
                    ) {

                        value =
                            normalizeNumber(
                                value
                            );

                        if (
                            value === ''
                        ) {
                            return '';
                        }

                        const parts =
                            value.split(
                                '.'
                            );

                        const integerPart =
                            parts[0].replace(
                                /\B(?=(\d{3})+(?!\d))/g,
                                ','
                            );

                        if (
                            parts.length > 1
                        ) {

                            return (
                                integerPart +
                                '.' +
                                parts[1]
                            );
                        }

                        return integerPart;
                    }

                    function getSelectedDefinition() {

                        if (
                            ! conditionField
                        ) {
                            return null;
                        }

                        const key =
                            conditionField.value;

                        if (
                            ! conditionDefinitions[
                                key
                            ]
                        ) {
                            return null;
                        }

                        return conditionDefinitions[
                            key
                        ];
                    }

                    function updateOperatorOptions(
                        preferredOperator
                    ) {

                        if (
                            ! conditionOperator
                        ) {
                            return;
                        }

                        const definition =
                            getSelectedDefinition();

                        conditionOperator.innerHTML =
                            '';

                        if (
                            ! definition ||
                            ! definition.operators
                        ) {
                            return;
                        }

                        let selectedOperator =
                            preferredOperator || '';

                        let firstOperator = '';

                        Object.keys(
                            definition.operators
                        ).forEach(
                            function(
                                operatorKey
                            ) {

                                if (
                                    ! firstOperator
                                ) {
                                    firstOperator =
                                        operatorKey;
                                }

                                const option =
                                    document.createElement(
                                        'option'
                                    );

                                option.value =
                                    operatorKey;

                                option.textContent =
                                    definition.operators[
                                        operatorKey
                                    ];

                                if (
                                    operatorKey ===
                                    selectedOperator
                                ) {

                                    option.selected =
                                        true;
                                }

                                conditionOperator.appendChild(
                                    option
                                );
                            }
                        );

                        if (
                            ! conditionOperator.value &&
                            firstOperator
                        ) {

                            conditionOperator.value =
                                firstOperator;
                        }
                    }

                    function updateValueField() {

                        const definition =
                            getSelectedDefinition();

                        const valueType =
                            definition &&
                            definition.value_type
                                ? definition.value_type
                                : 'text';

                        const currentValue =
                            conditionValue.value || '';

                        if (
                            valueType ===
                            'number'
                        ) {

                            amountWrapper.style.display =
                                'flex';

                            textWrapper.style.display =
                                'none';

                            amountDisplay.value =
                                formatNumber(
                                    currentValue
                                );

                            conditionValue.value =
                                normalizeNumber(
                                    currentValue
                                );

                            if (
                                valueDescription
                            ) {

                                valueDescription.textContent =
                                    'مبلغ را به واحد پول فروشگاه وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.';
                            }

                            if (
                                valueUnit
                            ) {

                                valueUnit.textContent =
                                    currencyUnit;
                            }

                        } else {

                            amountWrapper.style.display =
                                'none';

                            textWrapper.style.display =
                                'block';

                            textInput.value =
                                currentValue;

                            if (
                                valueDescription
                            ) {

                                valueDescription.textContent =
                                    'مقدار شرط را وارد کنید.';
                            }
                        }
                    }

                    function syncConditionValue() {

                        const definition =
                            getSelectedDefinition();

                        const valueType =
                            definition &&
                            definition.value_type
                                ? definition.value_type
                                : 'text';

                        if (
                            valueType ===
                            'number'
                        ) {

                            const rawValue =
                                normalizeNumber(
                                    amountDisplay.value
                                );

                            conditionValue.value =
                                rawValue;

                            amountDisplay.value =
                                formatNumber(
                                    rawValue
                                );

                        } else {

                            conditionValue.value =
                                textInput.value;
                        }
                    }

                    if (
                        conditionField &&
                        conditionOperator
                    ) {

                        const initialOperator =
                            conditionOperator.value;

                        updateOperatorOptions(
                            initialOperator
                        );

                        updateValueField();

                        conditionField.addEventListener(
                            'change',
                            function() {

                                const previousOperator =
                                    conditionOperator.value;

                                updateOperatorOptions(
                                    previousOperator
                                );

                                updateValueField();
                            }
                        );

                        conditionOperator.addEventListener(
                            'change',
                            function() {

                                updateValueField();
                            }
                        );
                    }

                    if (
                        amountDisplay &&
                        conditionValue
                    ) {

                        amountDisplay.addEventListener(
                            'input',
                            function() {

                                const rawValue =
                                    normalizeNumber(
                                        amountDisplay.value
                                    );

                                conditionValue.value =
                                    rawValue;

                                /*
                                 * Keep the value visually LTR
                                 * and restore the cursor to the end.
                                 */
                                amountDisplay.value =
                                    formatNumber(
                                        rawValue
                                    );

                                amountDisplay.setSelectionRange(
                                    amountDisplay.value.length,
                                    amountDisplay.value.length
                                );
                            }
                        );

                        amountDisplay.addEventListener(
                            'blur',
                            function() {

                                const rawValue =
                                    normalizeNumber(
                                        amountDisplay.value
                                    );

                                conditionValue.value =
                                    rawValue;

                                amountDisplay.value =
                                    formatNumber(
                                        rawValue
                                    );
                            }
                        );
                    }

                    if (
                        textInput &&
                        conditionValue
                    ) {

                        textInput.addEventListener(
                            'input',
                            function() {

                                conditionValue.value =
                                    textInput.value;
                            }
                        );
                    }

                    const form =
                        conditionValue
                            ? conditionValue.form
                            : null;

                    if ( form ) {

                        form.addEventListener(
                            'submit',
                            function() {

                                syncConditionValue();
                            }
                        );
                    }

                    const actionsContainer =
                        document.getElementById(
                            'woosmart-actions-container'
                        );

                    const addActionButton =
                        document.getElementById(
                            'woosmart-add-action'
                        );

                    if (
                        ! actionsContainer ||
                        ! addActionButton
                    ) {
                        return;
                    }

                    function getNextIndex() {

                        const rows =
                            actionsContainer.querySelectorAll(
                                '.woosmart-action-row'
                            );

                        let maxIndex = -1;

                        rows.forEach(
                            function(
                                row
                            ) {

                                const index =
                                    parseInt(
                                        row.getAttribute(
                                            'data-index'
                                        ),
                                        10
                                    );

                                if (
                                    ! isNaN(
                                        index
                                    ) &&
                                    index > maxIndex
                                ) {

                                    maxIndex =
                                        index;
                                }
                            }
                        );

                        return maxIndex + 1;
                    }

                    function createActionRow(
                        index
                    ) {

                        const row =
                            document.createElement(
                                'div'
                            );

                        row.className =
                            'woosmart-action-row';

                        row.setAttribute(
                            'data-index',
                            index
                        );

                        row.style.cssText =
                            'margin-bottom:16px;padding:18px;border:1px solid #ccd0d4;background:#fff;position:relative;';

                        row.innerHTML =
                            `
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;gap:15px;">

                                <strong>
                                    عملیات ${index + 1}
                                </strong>

                                <div style="display:flex;align-items:center;gap:6px;">

                                    <button
                                        type="button"
                                        class="button woosmart-move-up"
                                        title="انتقال عملیات به بالا"
                                    >
                                        ↑ بالا
                                    </button>

                                    <button
                                        type="button"
                                        class="button woosmart-move-down"
                                        title="انتقال عملیات به پایین"
                                    >
                                        ↓ پایین
                                    </button>

                                    <button
                                        type="button"
                                        class="button-link-delete woosmart-remove-action"
                                    >
                                        حذف عملیات
                                    </button>

                                </div>

                            </div>

                            <table class="form-table" style="margin:0;">

                                <tr>

                                    <th scope="row">
                                        <label>
                                            نوع عملیات
                                        </label>
                                    </th>

                                    <td>

                                        <select
                                            class="woosmart-action-type"
                                            name="actions[${index}][type]"
                                            style="min-width:300px;"
                                        >

                                            <option value="notify_admin">
                                                ارسال اعلان به مدیر فروشگاه
                                            </option>

                                            <option value="change_order_status">
                                                تغییر وضعیت سفارش
                                            </option>

                                        </select>

                                    </td>

                                </tr>

                                <tr class="woosmart-status-fields">

                                    <th scope="row">
                                        <label>
                                            وضعیت سفارش
                                        </label>
                                    </th>

                                    <td>

                                        <select
                                            name="actions[${index}][status]"
                                            style="min-width:300px;"
                                        >

                                            <?php foreach (
                                                $order_statuses
                                                as $status_slug =>
                                                $status_label
                                            ) : ?>

                                                <option
                                                    value="<?php echo esc_attr( $status_slug ); ?>"
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        $status_label
                                                    );
                                                    ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </td>

                                </tr>

                                <tr class="woosmart-notify-fields">

                                    <th scope="row">
                                        <label>
                                            موضوع اعلان
                                        </label>
                                    </th>

                                    <td>

                                        <input
                                            type="text"
                                            name="actions[${index}][subject]"
                                            class="regular-text"
                                            value="اعلان سفارش جدید در WooSmart"
                                        >

                                    </td>

                                </tr>

                                <tr class="woosmart-notify-fields">

                                    <th scope="row">
                                        <label>
                                            متن اعلان
                                        </label>
                                    </th>

                                    <td>

                                        <textarea
                                            name="actions[${index}][message]"
                                            rows="7"
                                            class="large-text"
                                        >یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.

شناسه سفارش: {order_id}
مبلغ سفارش: {order_total}
وضعیت سفارش: {order_status}
نام مشتری: {customer_name}</textarea>

                                        <p class="description">
                                            متغیرهای قابل استفاده:
                                            <code>{order_id}</code>
                                            <code>{order_total}</code>
                                            <code>{order_status}</code>
                                            <code>{customer_name}</code>
                                        </p>

                                    </td>

                                </tr>

                            </table>

                            `;

                        return row;
                    }

                    function updateActionFields(
                        row
                    ) {

                        const typeSelect =
                            row.querySelector(
                                '.woosmart-action-type'
                            );

                        const statusFields =
                            row.querySelector(
                                '.woosmart-status-fields'
                            );

                        const notifyFields =
                            row.querySelectorAll(
                                '.woosmart-notify-fields'
                            );

                        if (
                            ! typeSelect ||
                            ! statusFields
                        ) {
                            return;
                        }

                        if (
                            typeSelect.value ===
                            'change_order_status'
                        ) {

                            statusFields.style.display =
                                '';

                            notifyFields.forEach(
                                function(
                                    field
                                ) {

                                    field.style.display =
                                        'none';
                                }
                            );

                        } else {

                            statusFields.style.display =
                                'none';

                            notifyFields.forEach(
                                function(
                                    field
                                ) {

                                    field.style.display =
                                        '';
                                }
                            );
                        }
                    }

                    function updateActionMoveButtons() {

                        const rows =
                            Array.from(
                                actionsContainer.querySelectorAll(
                                    '.woosmart-action-row'
                                )
                            );

                        rows.forEach(
                            function(
                                row,
                                rowIndex
                            ) {

                                const moveUpButton =
                                    row.querySelector(
                                        '.woosmart-move-up'
                                    );

                                const moveDownButton =
                                    row.querySelector(
                                        '.woosmart-move-down'
                                    );

                                if (
                                    moveUpButton
                                ) {

                                    moveUpButton.disabled =
                                        rowIndex === 0;
                                }

                                if (
                                    moveDownButton
                                ) {

                                    moveDownButton.disabled =
                                        rowIndex ===
                                        rows.length - 1;
                                }
                            }
                        );
                    }

                    function bindActionRow(
                        row
                    ) {

                        const typeSelect =
                            row.querySelector(
                                '.woosmart-action-type'
                            );

                        const removeButton =
                            row.querySelector(
                                '.woosmart-remove-action'
                            );

                        const moveUpButton =
                            row.querySelector(
                                '.woosmart-move-up'
                            );

                        const moveDownButton =
                            row.querySelector(
                                '.woosmart-move-down'
                            );

                        if ( typeSelect ) {

                            typeSelect.addEventListener(
                                'change',
                                function() {

                                    updateActionFields(
                                        row
                                    );
                                }
                            );
                        }

                        if ( removeButton ) {

                            removeButton.addEventListener(
                                'click',
                                function() {

                                    const rows =
                                        actionsContainer.querySelectorAll(
                                            '.woosmart-action-row'
                                        );

                                    if (
                                        rows.length <= 1
                                    ) {

                                        alert(
                                            'حداقل یک عملیات باید وجود داشته باشد.'
                                        );

                                        return;
                                    }

                                    row.remove();

                                    renumberActionRows();
                                }
                            );
                        }

                        if ( moveUpButton ) {

                            moveUpButton.addEventListener(
                                'click',
                                function() {

                                    const previousRow =
                                        row.previousElementSibling;

                                    if (
                                        ! previousRow ||
                                        ! previousRow.classList.contains(
                                            'woosmart-action-row'
                                        )
                                    ) {
                                        return;
                                    }

                                    actionsContainer.insertBefore(
                                        row,
                                        previousRow
                                    );

                                    renumberActionRows();
                                }
                            );
                        }

                        if ( moveDownButton ) {

                            moveDownButton.addEventListener(
                                'click',
                                function() {

                                    const nextRow =
                                        row.nextElementSibling;

                                    if (
                                        ! nextRow ||
                                        ! nextRow.classList.contains(
                                            'woosmart-action-row'
                                        )
                                    ) {
                                        return;
                                    }

                                    actionsContainer.insertBefore(
                                        nextRow,
                                        row
                                    );

                                    renumberActionRows();
                                }
                            );
                        }

                        updateActionFields(
                            row
                        );
                    }

                    function renumberActionRows() {

                        const rows =
                            actionsContainer.querySelectorAll(
                                '.woosmart-action-row'
                            );

                        rows.forEach(
                            function(
                                row,
                                rowIndex
                            ) {

                                row.setAttribute(
                                    'data-index',
                                    rowIndex
                                );

                                const title =
                                    row.querySelector(
                                        'strong'
                                    );

                                if ( title ) {

                                    title.textContent =
                                        'عملیات ' +
                                        (
                                            rowIndex + 1
                                        );
                                }

                                row.querySelectorAll(
                                    '[name]'
                                ).forEach(
                                    function(
                                        input
                                    ) {

                                        input.name =
                                            input.name.replace(
                                                /actions\[\d+\]/,
                                                'actions[' +
                                                rowIndex +
                                                ']'
                                            );
                                    }
                                );
                            }
                        );

                        updateActionMoveButtons();
                    }

                    actionsContainer.querySelectorAll(
                        '.woosmart-action-row'
                    ).forEach(
                        function(
                            row
                        ) {

                            bindActionRow(
                                row
                            );
                        }
                    );

                    renumberActionRows();

                    addActionButton.addEventListener(
                        'click',
                        function() {

                            const nextIndex =
                                getNextIndex();

                            const row =
                                createActionRow(
                                    nextIndex
                                );

                            actionsContainer.appendChild(
                                row
                            );

                            bindActionRow(
                                row
                            );

                            updateActionFields(
                                row
                            );

                            renumberActionRows();
                        }
                    );
                }
            );
        </script>

        <?php
    }

    /**
     * Render one action row.
     *
     * @param int   $index          Action index.
     * @param array $action         Action configuration.
     * @param array $order_statuses Order statuses.
     *
     * @return void
     */
    private function render_action_row(
        $index,
        $action,
        $order_statuses
    ) {

        $action_type = isset(
            $action['type']
        )
            ? sanitize_key(
                $action['type']
            )
            : 'notify_admin';

        $action_status = isset(
            $action['status']
        )
            ? sanitize_key(
                $action['status']
            )
            : 'processing';

        $action_subject = isset(
            $action['subject']
        )
            ? $action['subject']
            : 'اعلان سفارش جدید در WooSmart';

        $action_message = isset(
            $action['message']
        )
            ? $action['message']
            : "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.\n\n" .
            "شناسه سفارش: {order_id}\n" .
            "مبلغ سفارش: {order_total}\n" .
            "وضعیت سفارش: {order_status}\n" .
            "نام مشتری: {customer_name}";

        ?>

        <div
            class="woosmart-action-row"
            data-index="<?php echo esc_attr( $index ); ?>"
            style="
                margin-bottom:16px;
                padding:18px;
                border:1px solid #ccd0d4;
                background:#fff;
                position:relative;
            "
        >

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:15px;
                    gap:15px;
                "
            >

                <strong>
                    عملیات <?php echo esc_html( $index + 1 ); ?>
                </strong>

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:6px;
                    "
                >

                    <button
                        type="button"
                        class="button woosmart-move-up"
                        title="انتقال عملیات به بالا"
                    >
                        ↑ بالا
                    </button>

                    <button
                        type="button"
                        class="button woosmart-move-down"
                        title="انتقال عملیات به پایین"
                    >
                        ↓ پایین
                    </button>

                    <button
                        type="button"
                        class="button-link-delete woosmart-remove-action"
                    >
                        حذف عملیات
                    </button>

                </div>

            </div>

            <table
                class="form-table"
                style="margin:0;"
            >

                <tr>

                    <th scope="row">
                        <label>
                            نوع عملیات
                        </label>
                    </th>

                    <td>

                        <select
                            class="woosmart-action-type"
                            name="actions[<?php echo esc_attr( $index ); ?>][type]"
                            style="min-width:300px;"
                        >

                            <option
                                value="notify_admin"
                                <?php selected(
                                    $action_type,
                                    'notify_admin'
                                ); ?>
                            >
                                ارسال اعلان به مدیر فروشگاه
                            </option>

                            <option
                                value="change_order_status"
                                <?php selected(
                                    $action_type,
                                    'change_order_status'
                                ); ?>
                            >
                                تغییر وضعیت سفارش
                            </option>

                        </select>

                    </td>

                </tr>

                <tr
                    class="woosmart-status-fields"
                    <?php
                    echo 'change_order_status' === $action_type
                        ? ''
                        : 'style="display:none;"';
                    ?>
                >

                    <th scope="row">
                        <label>
                            وضعیت سفارش
                        </label>
                    </th>

                    <td>

                        <select
                            name="actions[<?php echo esc_attr( $index ); ?>][status]"
                            style="min-width:300px;"
                        >

                            <?php foreach (
                                $order_statuses
                                as $status_slug =>
                                $status_label
                            ) : ?>

                                <option
                                    value="<?php echo esc_attr( $status_slug ); ?>"
                                    <?php selected(
                                        $action_status,
                                        $status_slug
                                    ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $status_label
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </td>

                </tr>

                <tr
                    class="woosmart-notify-fields"
                    <?php
                    echo 'notify_admin' === $action_type
                        ? ''
                        : 'style="display:none;"';
                    ?>
                >

                    <th scope="row">
                        <label>
                            موضوع اعلان
                        </label>
                    </th>

                    <td>

                        <input
                            type="text"
                            name="actions[<?php echo esc_attr( $index ); ?>][subject]"
                            class="regular-text"
                            value="<?php echo esc_attr( $action_subject ); ?>"
                        >

                    </td>

                </tr>

                <tr
                    class="woosmart-notify-fields"
                    <?php
                    echo 'notify_admin' === $action_type
                        ? ''
                        : 'style="display:none;"';
                    ?>
                >

                    <th scope="row">
                        <label>
                            متن اعلان
                        </label>
                    </th>

                    <td>

                        <textarea
                            name="actions[<?php echo esc_attr( $index ); ?>][message]"
                            rows="7"
                            class="large-text"
                        ><?php echo esc_textarea( $action_message ); ?></textarea>

                        <p class="description">
                            متغیرهای قابل استفاده:
                            <code>{order_id}</code>
                            <code>{order_total}</code>
                            <code>{order_status}</code>
                            <code>{customer_name}</code>
                        </p>

                    </td>

                </tr>

            </table>

        </div>

        <?php
    }

    /**
     * Logs page.
     *
     * @return void
     */
    public function render_logs_page() {

        $logs =
            $this->logger->get_logs();
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                گزارش‌های WooSmart
            </h1>

            <p>
                رویدادهای ثبت‌شده توسط سیستم اتوماسیون.
            </p>

            <hr>

            <?php if ( empty( $logs ) ) : ?>

                <div class="notice notice-info">

                    <p>
                        هنوز هیچ گزارشی ثبت نشده است.
                    </p>

                </div>

            <?php else : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>

                            <th>
                                زمان
                            </th>

                            <th>
                                رویداد
                            </th>

                            <th>
                                پیام
                            </th>

                            <th>
                                اطلاعات
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            array_reverse( $logs )
                            as $log
                        ) : ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        isset(
                                            $log['time']
                                        )
                                            ? $log['time']
                                            : ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_event_label(
                                            isset(
                                                $log['event']
                                            )
                                                ? $log['event']
                                                : ''
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_event_message(
                                            isset(
                                                $log['event']
                                            )
                                                ? $log['event']
                                                : '',
                                            isset(
                                                $log['message']
                                            )
                                                ? $log['message']
                                                : ''
                                        )
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php
                                    if (
                                        isset(
                                            $log['context']
                                        )
                                    ) {

                                        echo esc_html(
                                            wp_json_encode(
                                                $log['context'],
                                                JSON_UNESCAPED_UNICODE
                                            )
                                        );
                                    }
                                    ?>

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
     * Get trigger label.
     *
     * @param string $trigger Trigger key.
     *
     * @return string
     */
    private function get_trigger_label(
        $trigger
    ) {

        $labels = array(
            'order_created' =>
                'ایجاد سفارش',
        );

        return isset(
            $labels[ $trigger ]
        )
            ? $labels[ $trigger ]
            : $trigger;
    }

    /**
     * Get condition field label.
     *
     * @param string $field Field key.
     *
     * @return string
     */
    private function get_condition_field_label(
        $field
    ) {

        $definition =
            $this->condition_registry->get(
                $field
            );

        if (
            is_array(
                $definition
            ) &&
            isset(
                $definition['label']
            )
        ) {

            return $definition['label'];
        }

        return $field;
    }

    /**
     * Get comparison operator label.
     *
     * @param string $operator Operator key.
     * @param string $field    Condition field.
     *
     * @return string
     */
    private function get_operator_label(
        $operator,
        $field = ''
    ) {

        if (
            ! empty( $field )
        ) {

            $operators =
                $this->condition_registry->get_operators(
                    $field
                );

            if (
                isset(
                    $operators[ $operator ]
                )
            ) {

                return $operators[
                    $operator
                ];
            }
        }

        $labels = array(
            'is_equal' =>
                'برابر با',

            'is_not_equal' =>
                'نابرابر با',

            'greater_than' =>
                'بیشتر از',

            'greater_than_or_equal' =>
                'بیشتر یا مساوی',

            'less_than' =>
                'کمتر از',

            'less_than_or_equal' =>
                'کمتر یا مساوی',
        );

        return isset(
            $labels[ $operator ]
        )
            ? $labels[ $operator ]
            : $operator;
    }

    /**
     * Get action label.
     *
     * @param string $action_type Action key.
     *
     * @return string
     */
    private function get_action_label(
        $action_type
    ) {

        $labels = array(
            'change_order_status' =>
                'تغییر وضعیت سفارش',

            'notify_admin' =>
                'ارسال اعلان به مدیر فروشگاه',
        );

        return isset(
            $labels[ $action_type ]
        )
            ? $labels[ $action_type ]
            : $action_type;
    }

    /**
     * Get order status label.
     *
     * @param string      $status_slug   Status slug.
     * @param string|null $default_label Default WooCommerce label.
     *
     * @return string
     */
    private function get_order_status_label(
        $status_slug,
        $default_label = null
    ) {

        $labels = array(
            'pending' =>
                'در انتظار پرداخت',

            'processing' =>
                'در حال پردازش',

            'on-hold' =>
                'در انتظار',

            'completed' =>
                'تکمیل‌شده',

            'cancelled' =>
                'لغوشده',

            'refunded' =>
                'مستردشده',

            'failed' =>
                'ناموفق',
        );

        if (
            isset(
                $labels[ $status_slug ]
            )
        ) {

            return $labels[
                $status_slug
            ];
        }

        if (
            null !== $default_label
        ) {

            return $default_label;
        }

        return $status_slug;
    }

    /**
     * Format monetary value using the current
     * WooCommerce currency display unit.
     *
     * No numerical conversion is performed.
     *
     * @param mixed $value Monetary value.
     *
     * @return string
     */
    private function format_currency_value(
        $value
    ) {

        $value =
            $this->normalize_numeric_input(
                $value
            );

        if (
            '' === $value
        ) {

            return '';
        }

        return esc_html(
            $this->format_currency_input(
                $value
            )
        ) .
        ' ' .
        esc_html(
            $this->currency->get_display_unit()
        );
    }

    /**
     * Format numeric currency input.
     *
     * @param mixed $value Numeric value.
     *
     * @return string
     */
    private function format_currency_input(
        $value
    ) {

        $value =
            $this->normalize_numeric_input(
                $value
            );

        if (
            '' === $value
        ) {

            return '';
        }

        $parts =
            explode(
                '.',
                $value,
                2
            );

        $integer_part =
            number_format(
                (float) $parts[0],
                0,
                '.',
                ','
            );

        if (
            isset(
                $parts[1]
            ) &&
            '' !== $parts[1]
        ) {

            return (
                $integer_part .
                '.' .
                $parts[1]
            );
        }

        return $integer_part;
    }

    /**
     * Normalize numeric input.
     *
     * @param mixed $value Numeric value.
     *
     * @return string
     */
    private function normalize_numeric_input(
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
     * Get localized event label.
     *
     * @param string $event Event key.
     *
     * @return string
     */
    private function get_event_label(
        $event
    ) {

        $labels = array(
            'order_created' =>
                'ایجاد سفارش',

            'automation_created' =>
                'ایجاد اتوماسیون',

            'automation_updated' =>
                'ویرایش اتوماسیون',

            'automation_status_changed' =>
                'تغییر وضعیت اتوماسیون',

            'automation_deleted' =>
                'حذف اتوماسیون',

            'automation_duplicated' =>
                'کپی اتوماسیون',

            'automation_skipped' =>
                'رد شدن اتوماسیون',

            'automation_conditions_failed' =>
                'شرایط برقرار نبود',

            'automation_executed' =>
                'اجرای اتوماسیون',

            'automation_failed' =>
                'خطای اجرای اتوماسیون',

            'action_failed' =>
                'خطا در عملیات',

            'action_executed' =>
                'اجرای عملیات',
        );

        return isset(
            $labels[ $event ]
        )
            ? $labels[ $event ]
            : $event;
    }

    /**
     * Get localized event message.
     *
     * @param string $event   Event key.
     * @param string $message Original message.
     *
     * @return string
     */
    private function get_event_message(
        $event,
        $message
    ) {

        $messages = array(
            'order_created' =>
                'یک سفارش جدید ایجاد شد.',

            'automation_created' =>
                'یک اتوماسیون جدید ایجاد شد.',

            'automation_updated' =>
                'اتوماسیون ویرایش شد.',

            'automation_status_changed' =>
                'وضعیت اتوماسیون تغییر کرد.',

            'automation_deleted' =>
                'اتوماسیون به زباله‌دان منتقل شد.',

            'automation_duplicated' =>
                'اتوماسیون کپی شد.',

            'automation_skipped' =>
                'اتوماسیون به دلیل غیرفعال بودن اجرا نشد.',

            'automation_conditions_failed' =>
                'شرایط اتوماسیون برقرار نبود.',

            'automation_executed' =>
                'اتوماسیون با موفقیت اجرا شد.',

            'automation_failed' =>
                'اجرای اتوماسیون با شکست مواجه شد.',

            'action_failed' =>
                'اجرای عملیات با خطا مواجه شد.',

            'action_executed' =>
                'عملیات با موفقیت اجرا شد.',
        );

        return isset(
            $messages[ $event ]
        )
            ? $messages[ $event ]
            : $message;
    }
}
