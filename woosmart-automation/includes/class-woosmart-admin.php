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
     * Initialize admin functionality.
     */
    public function __construct() {

        $this->logger = new WooSmart_Logger();

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
            array( $this, 'render_dashboard_page' ),
            'dashicons-controls-repeat',
            30
        );

        add_submenu_page(
            'woosmart-automation',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'woosmart-automation',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'اتوماسیون‌ها',
            'اتوماسیون‌ها',
            'manage_options',
            'woosmart-automations',
            array( $this, 'render_automations_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'افزودن اتوماسیون',
            'افزودن اتوماسیون',
            'manage_options',
            'woosmart-add-automation',
            array( $this, 'render_add_automation_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'گزارش‌ها',
            'گزارش‌ها',
            'manage_options',
            'woosmart-automation-logs',
            array( $this, 'render_logs_page' )
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
                                                ? $condition['field']
                                                : '';

                                            $operator = isset(
                                                $condition['operator']
                                            )
                                                ? $condition['operator']
                                                : '';

                                            $value = isset(
                                                $condition['value']
                                            )
                                                ? $condition['value']
                                                : '';

                                            ?>

                                            <div style="margin-bottom:6px;">

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
                                                        $operator
                                                    )
                                                );
                                                ?>

                                                <?php
                                                if (
                                                    'order_total' ===
                                                    $field
                                                ) {

                                                    echo wp_kses_post(
                                                        $this->format_irr_value(
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

                                        <?php foreach ( $actions as $action ) : ?>

                                            <?php

                                            $action_type = isset(
                                                $action['type']
                                            )
                                                ? $action['type']
                                                : '';

                                            $action_status = isset(
                                                $action['status']
                                            )
                                                ? $action['status']
                                                : '';

                                            ?>

                                            <?php if ( 'change_order_status' === $action_type ) : ?>

                                                <div style="margin-bottom:6px;">

                                                    <strong>
                                                        تغییر وضعیت سفارش
                                                    </strong>

                                                    <br>

                                                    <span>
                                                        <?php
                                                        echo esc_html(
                                                            $this->get_order_status_label(
                                                                $action_status
                                                            )
                                                        );
                                                        ?>
                                                    </span>

                                                </div>

                                            <?php elseif ( 'notify_admin' === $action_type ) : ?>

                                                <div style="margin-bottom:6px;">

                                                    <strong>
                                                        ارسال اعلان به مدیر فروشگاه
                                                    </strong>

                                                    <br>

                                                    <span>
                                                        ایمیل مدیر فروشگاه
                                                    </span>

                                                </div>

                                            <?php else : ?>

                                                <div>
                                                    <?php
                                                    echo esc_html(
                                                        $this->get_action_label(
                                                            $action_type
                                                        )
                                                    );
                                                    ?>
                                                </div>

                                            <?php endif; ?>

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

        $action_type = 'notify_admin';
        $action_order_status = 'processing';

        $action_email_subject =
            'اعلان سفارش جدید در WooSmart';

        $action_email_message =
            "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.\n\n" .
            "شناسه سفارش: {order_id}\n" .
            "مبلغ سفارش: {order_total}\n" .
            "وضعیت سفارش: {order_status}\n" .
            "نام مشتری: {customer_name}";

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

                    $name = $automation->post_title;

                    $trigger = get_post_meta(
                        $edit_id,
                        '_woosmart_trigger',
                        true
                    );

                    $conditions = get_post_meta(
                        $edit_id,
                        '_woosmart_conditions',
                        true
                    );

                    $actions = get_post_meta(
                        $edit_id,
                        '_woosmart_actions',
                        true
                    );

                    if (
                        is_array( $conditions ) &&
                        ! empty( $conditions )
                    ) {

                        $condition = $conditions[0];

                        $condition_field = isset(
                            $condition['field']
                        )
                            ? $condition['field']
                            : 'order_total';

                        $condition_operator = isset(
                            $condition['operator']
                        )
                            ? $condition['operator']
                            : 'greater_than';

                        $condition_value = isset(
                            $condition['value']
                        )
                            ? $condition['value']
                            : '';
                    }

                    if (
                        is_array( $actions ) &&
                        ! empty( $actions )
                    ) {

                        $action = $actions[0];

                        $action_type = isset(
                            $action['type']
                        )
                            ? $action['type']
                            : 'notify_admin';

                        $action_order_status = isset(
                            $action['status']
                        )
                            ? $action['status']
                            : 'processing';

                        $action_email_subject = isset(
                            $action['subject']
                        )
                            ? $action['subject']
                            : $action_email_subject;

                        $action_email_message = isset(
                            $action['message']
                        )
                            ? $action['message']
                            : $action_email_message;
                    }
                }
            }
        }

        $order_statuses = array();

        if ( function_exists( 'wc_get_order_statuses' ) ) {

            $wc_statuses = wc_get_order_statuses();

            foreach ( $wc_statuses as $status_key => $status_label ) {

                $status_slug = str_replace(
                    'wc-',
                    '',
                    $status_key
                );

                $order_statuses[ $status_slug ] =
                    $this->get_order_status_label(
                        $status_slug,
                        $status_label
                    );
            }
        }

        if ( empty( $order_statuses ) ) {

            $order_statuses = array(
                'pending'    => 'در انتظار پرداخت',
                'processing' => 'در حال پردازش',
                'on-hold'    => 'در انتظار',
                'completed'  => 'تکمیل‌شده',
                'cancelled'  => 'لغوشده',
                'refunded'   => 'مستردشده',
                'failed'     => 'ناموفق',
            );
        }

        $condition_value_numeric = $this->normalize_irr_input(
            $condition_value
        );

        $condition_value_display = '';

        if ( '' !== $condition_value_numeric ) {

            $condition_value_display =
                $this->format_irr_input(
                    $condition_value_numeric
                );
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
                                placeholder="مثلاً اطلاع‌رسانی سفارش ویژه"
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

                                <option
                                    value="order_total"
                                    <?php selected(
                                        $condition_field,
                                        'order_total'
                                    ); ?>
                                >
                                    مبلغ سفارش
                                </option>

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

                                <option
                                    value="is_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'is_equal'
                                    ); ?>
                                >
                                    برابر با
                                </option>

                                <option
                                    value="is_not_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'is_not_equal'
                                    ); ?>
                                >
                                    نابرابر با
                                </option>

                                <option
                                    value="greater_than"
                                    <?php selected(
                                        $condition_operator,
                                        'greater_than'
                                    ); ?>
                                >
                                    بیشتر از
                                </option>

                                <option
                                    value="greater_than_or_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'greater_than_or_equal'
                                    ); ?>
                                >
                                    بیشتر یا مساوی
                                </option>

                                <option
                                    value="less_than"
                                    <?php selected(
                                        $condition_operator,
                                        'less_than'
                                    ); ?>
                                >
                                    کمتر از
                                </option>

                                <option
                                    value="less_than_or_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'less_than_or_equal'
                                    ); ?>
                                >
                                    کمتر یا مساوی
                                </option>

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
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:8px;
                                    max-width:420px;
                                "
                            >

                                <div
                                    style="
                                        position:relative;
                                        flex:1;
                                    "
                                >

                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        id="condition_value_display"
                                        class="regular-text"
                                        value="<?php echo esc_attr( $condition_value_display ); ?>"
                                        placeholder="1,000,000"
                                        style="
                                            width:100%;
                                            padding-left:70px;
                                            direction:ltr;
                                            text-align:left;
                                        "
                                    >

                                    <span
                                        style="
                                            position:absolute;
                                            left:12px;
                                            top:50%;
                                            transform:translateY(-50%);
                                            color:#646970;
                                            pointer-events:none;
                                            font-size:13px;
                                            font-weight:600;
                                        "
                                    >
                                        ریال
                                    </span>

                                </div>

                            </div>

                            <input
                                type="hidden"
                                id="condition_value"
                                name="condition_value"
                                value="<?php echo esc_attr( $condition_value_numeric ); ?>"
                            >

                            <p class="description">
                                مبلغ را به ریال وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.
                            </p>

                        </td>

                    </tr>

                </table>

                <h2>
                    عملیات
                </h2>

                <p>
                    پس از برقرار شدن شرایط، عملیات انتخاب‌شده اجرا می‌شود.
                </p>

                <table class="form-table">

                    <tr>

                        <th scope="row">

                            <label for="action_type">
                                نوع عملیات
                            </label>

                        </th>

                        <td>

                            <select
                                id="action_type"
                                name="action_type"
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
                        id="woosmart_order_status_row"
                        <?php
                        echo 'change_order_status' === $action_type
                            ? ''
                            : 'style="display:none;"';
                        ?>
                    >

                        <th scope="row">

                            <label for="action_order_status">
                                وضعیت سفارش
                            </label>

                        </th>

                        <td>

                            <select
                                id="action_order_status"
                                name="action_order_status"
                            >

                                <?php foreach ( $order_statuses as $status_slug => $status_label ) : ?>

                                    <option
                                        value="<?php echo esc_attr( $status_slug ); ?>"
                                        <?php selected(
                                            $action_order_status,
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
                        id="woosmart_email_subject_row"
                        <?php
                        echo 'notify_admin' === $action_type
                            ? ''
                            : 'style="display:none;"';
                        ?>
                    >

                        <th scope="row">

                            <label for="action_email_subject">
                                موضوع اعلان
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="action_email_subject"
                                name="action_email_subject"
                                class="regular-text"
                                value="<?php echo esc_attr( $action_email_subject ); ?>"
                            >

                        </td>

                    </tr>

                    <tr
                        id="woosmart_email_message_row"
                        <?php
                        echo 'notify_admin' === $action_type
                            ? ''
                            : 'style="display:none;"';
                        ?>
                    >

                        <th scope="row">

                            <label for="action_email_message">
                                متن اعلان
                            </label>

                        </th>

                        <td>

                            <textarea
                                id="action_email_message"
                                name="action_email_message"
                                rows="8"
                                class="large-text"
                            ><?php echo esc_textarea( $action_email_message ); ?></textarea>

                            <p class="description">

                                مقادیر قابل استفاده:

                                <code>{order_id}</code>
                                <code>{order_total}</code>
                                <code>{order_status}</code>
                                <code>{customer_name}</code>

                            </p>

                        </td>

                    </tr>

                </table>

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

                    const displayInput =
                        document.getElementById(
                            'condition_value_display'
                        );

                    const hiddenInput =
                        document.getElementById(
                            'condition_value'
                        );

                    if ( displayInput && hiddenInput ) {

                        function normalizeNumber(value) {

                            value = String(value || '');

                            value = value.replace(/,/g, '');

                            value = value.replace(/[^\d.]/g, '');

                            const firstDot =
                                value.indexOf('.');

                            if ( firstDot !== -1 ) {

                                value =
                                    value.substring(
                                        0,
                                        firstDot + 1
                                    ) +
                                    value.substring(
                                        firstDot + 1
                                    ).replace(/\./g, '');
                            }

                            return value;
                        }

                        function formatNumber(value) {

                            value =
                                normalizeNumber(
                                    value
                                );

                            if ( value === '' ) {
                                return '';
                            }

                            const parts =
                                value.split('.');

                            const integerPart =
                                parts[0].replace(
                                    /\B(?=(\d{3})+(?!\d))/g,
                                    ','
                                );

                            if ( parts.length > 1 ) {

                                return (
                                    integerPart +
                                    '.' +
                                    parts[1]
                                );
                            }

                            return integerPart;
                        }

                        function syncValues() {

                            const rawValue =
                                normalizeNumber(
                                    displayInput.value
                                );

                            hiddenInput.value =
                                rawValue;

                            displayInput.value =
                                formatNumber(
                                    rawValue
                                );
                        }

                        displayInput.addEventListener(
                            'input',
                            function() {

                                syncValues();

                                displayInput.setSelectionRange(
                                    displayInput.value.length,
                                    displayInput.value.length
                                );
                            }
                        );

                        displayInput.addEventListener(
                            'blur',
                            function() {

                                syncValues();
                            }
                        );

                        displayInput.form.addEventListener(
                            'submit',
                            function() {

                                hiddenInput.value =
                                    normalizeNumber(
                                        displayInput.value
                                    );
                            }
                        );

                        syncValues();
                    }

                    const actionType =
                        document.getElementById(
                            'action_type'
                        );

                    const statusRow =
                        document.getElementById(
                            'woosmart_order_status_row'
                        );

                    const subjectRow =
                        document.getElementById(
                            'woosmart_email_subject_row'
                        );

                    const messageRow =
                        document.getElementById(
                            'woosmart_email_message_row'
                        );

                    if (
                        actionType &&
                        statusRow &&
                        subjectRow &&
                        messageRow
                    ) {

                        function updateActionFields() {

                            if (
                                actionType.value ===
                                'change_order_status'
                            ) {

                                statusRow.style.display =
                                    '';

                                subjectRow.style.display =
                                    'none';

                                messageRow.style.display =
                                    'none';

                            } else {

                                statusRow.style.display =
                                    'none';

                                subjectRow.style.display =
                                    '';

                                messageRow.style.display =
                                    '';
                            }
                        }

                        actionType.addEventListener(
                            'change',
                            updateActionFields
                        );

                        updateActionFields();
                    }
                }
            );
        </script>

        <?php
    }

    /**
     * Logs page.
     *
     * @return void
     */
    public function render_logs_page() {

        $logs = $this->logger->get_logs();
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

                        <?php foreach ( array_reverse( $logs ) as $log ) : ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        isset( $log['time'] )
                                            ? $log['time']
                                            : ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_event_label(
                                            isset( $log['event'] )
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
                                            isset( $log['event'] )
                                                ? $log['event']
                                                : '',
                                            isset( $log['message'] )
                                                ? $log['message']
                                                : ''
                                        )
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php
                                    if ( isset( $log['context'] ) ) {

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
    private function get_trigger_label( $trigger ) {

        $labels = array(
            'order_created' => 'ایجاد سفارش',
        );

        return isset( $labels[ $trigger ] )
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
    private function get_condition_field_label( $field ) {

        $labels = array(
            'order_total' => 'مبلغ سفارش',
        );

        return isset( $labels[ $field ] )
            ? $labels[ $field ]
            : $field;
    }

    /**
     * Get comparison operator label.
     *
     * @param string $operator Operator key.
     *
     * @return string
     */
    private function get_operator_label( $operator ) {

        $labels = array(
            'is_equal'              => 'برابر با',
            'is_not_equal'          => 'نابرابر با',
            'greater_than'          => 'بیشتر از',
            'greater_than_or_equal' => 'بیشتر یا مساوی',
            'less_than'             => 'کمتر از',
            'less_than_or_equal'    => 'کمتر یا مساوی',
        );

        return isset( $labels[ $operator ] )
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
    private function get_action_label( $action_type ) {

        $labels = array(
            'change_order_status' => 'تغییر وضعیت سفارش',
            'notify_admin'        => 'ارسال اعلان به مدیر فروشگاه',
        );

        return isset( $labels[ $action_type ] )
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
            'pending'    => 'در انتظار پرداخت',
            'processing' => 'در حال پردازش',
            'on-hold'    => 'در انتظار',
            'completed'  => 'تکمیل‌شده',
            'cancelled'  => 'لغوشده',
            'refunded'   => 'مستردشده',
            'failed'     => 'ناموفق',
        );

        if ( isset( $labels[ $status_slug ] ) ) {
            return $labels[ $status_slug ];
        }

        if ( null !== $default_label ) {
            return $default_label;
        }

        return $status_slug;
    }

    /**
     * Format an IRR monetary value for the admin UI.
     *
     * @param mixed $value Monetary value.
     *
     * @return string
     */
    private function format_irr_value( $value ) {

        $value = $this->normalize_irr_input(
            $value
        );

        if ( '' === $value ) {
            return '';
        }

        return esc_html(
            $this->format_irr_input(
                $value
            )
        ) . ' ریال';
    }

    /**
     * Format a numeric value for the amount field.
     *
     * @param mixed $value Numeric value.
     *
     * @return string
     */
    private function format_irr_input( $value ) {

        $value = $this->normalize_irr_input(
            $value
        );

        if ( '' === $value ) {
            return '';
        }

        $parts = explode(
            '.',
            $value,
            2
        );

        $integer_part = number_format(
            (float) $parts[0],
            0,
            '.',
            ','
        );

        if (
            isset( $parts[1] ) &&
            '' !== $parts[1]
        ) {

            return $integer_part . '.' . $parts[1];
        }

        return $integer_part;
    }

    /**
     * Normalize amount input.
     *
     * @param mixed $value Amount value.
     *
     * @return string
     */
    private function normalize_irr_input( $value ) {

        $value = (string) $value;

        $value = str_replace(
            ',',
            '',
            $value
        );

        $value = preg_replace(
            '/[^\d.]/',
            '',
            $value
        );

        if ( null === $value ) {
            return '';
        }

        $first_dot = strpos(
            $value,
            '.'
        );

        if ( false !== $first_dot ) {

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
    private function get_event_label( $event ) {

        $labels = array(
            'order_created'                => 'ایجاد سفارش',
            'automation_created'           => 'ایجاد اتوماسیون',
            'automation_updated'           => 'ویرایش اتوماسیون',
            'automation_status_changed'    => 'تغییر وضعیت اتوماسیون',
            'automation_deleted'           => 'حذف اتوماسیون',
            'automation_duplicated'        => 'کپی اتوماسیون',
            'automation_skipped'           => 'رد شدن اتوماسیون',
            'automation_conditions_failed' => 'شرایط برقرار نبود',
            'automation_executed'          => 'اجرای اتوماسیون',
            'action_failed'                => 'خطا در عملیات',
            'action_executed'              => 'اجرای عملیات',
        );

        return isset( $labels[ $event ] )
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
                'اتوماسیون با موفقیت پردازش شد.',

            'action_failed' =>
                'اجرای عملیات با خطا مواجه شد.',

            'action_executed' =>
                'عملیات با موفقیت اجرا شد.',
        );

        return isset( $messages[ $event ] )
            ? $messages[ $event ]
            : $message;
    }
}
