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
            array(
                $this,
                'add_admin_menu',
            )
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
                    <?php
                    echo esc_html(
                        $status
                    );
                    ?>
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

                            $automation_id =
                                $automation->ID;

                            $trigger =
                                get_post_meta(
                                    $automation_id,
                                    '_woosmart_trigger',
                                    true
                                );

                            $status =
                                get_post_meta(
                                    $automation_id,
                                    '_woosmart_status',
                                    true
                                );

                            $conditions =
                                get_post_meta(
                                    $automation_id,
                                    '_woosmart_conditions',
                                    true
                                );

                            $actions =
                                get_post_meta(
                                    $automation_id,
                                    '_woosmart_actions',
                                    true
                                );

                            if (
                                ! is_array(
                                    $conditions
                                )
                            ) {
                                $conditions =
                                    array();
                            }

                            if (
                                ! is_array(
                                    $actions
                                )
                            ) {
                                $actions =
                                    array();
                            }

                            if (
                                'active' ===
                                $status
                            ) {

                                $status_label =
                                    'فعال';

                                $status_class =
                                    'notice-success';

                                $toggle_label =
                                    'غیرفعال کردن';

                            } else {

                                $status_label =
                                    'غیرفعال';

                                $status_class =
                                    'notice-warning';

                                $toggle_label =
                                    'فعال کردن';
                            }

                            $toggle_url =
                                wp_nonce_url(
                                    admin_url(
                                        'admin-post.php?action=woosmart_toggle_automation&automation_id=' .
                                        $automation_id
                                    ),
                                    'woosmart_toggle_automation_' .
                                    $automation_id
                                );

                            $delete_url =
                                wp_nonce_url(
                                    admin_url(
                                        'admin-post.php?action=woosmart_delete_automation&automation_id=' .
                                        $automation_id
                                    ),
                                    'woosmart_delete_automation_' .
                                    $automation_id
                                );

                            $duplicate_url =
                                wp_nonce_url(
                                    admin_url(
                                        'admin-post.php?action=woosmart_duplicate_automation&automation_id=' .
                                        $automation_id
                                    ),
                                    'woosmart_duplicate_automation_' .
                                    $automation_id
                                );

                            $edit_url =
                                admin_url(
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

                                    <?php
                                    $this->render_condition_summary(
                                        $conditions
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php if ( empty( $actions ) ) : ?>

                                        <span>
                                            بدون عملیات
                                        </span>

                                    <?php else : ?>

                                        <?php foreach (
                                            $actions
                                            as $index =>
                                            $action
                                        ) : ?>

                                            <?php

                                            $action_type =
                                                isset(
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

                                                <?php if (
                                                    'change_order_status' ===
                                                    $action_type
                                                ) : ?>

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

                                                <?php elseif (
                                                    'notify_admin' ===
                                                    $action_type
                                                ) : ?>

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

        $edit_id =
            isset(
                $_GET['edit']
            )
                ? absint(
                    $_GET['edit']
                )
                : 0;

        $is_edit =
            false;

        $name =
            '';

        $trigger =
            'order_created';

        $condition_definitions =
            $this->condition_registry->get_all();

        $currency_unit =
            $this->currency->get_display_unit();

        /*
         * Select the first registered condition as fallback.
         */
        $default_condition_field =
            'order_total';

        if (
            ! isset(
                $condition_definitions[
                    $default_condition_field
                ]
            ) &&
            ! empty(
                $condition_definitions
            )
        ) {

            $condition_keys =
                array_keys(
                    $condition_definitions
                );

            $default_condition_field =
                $condition_keys[0];
        }

        $default_condition_operator =
            '';

        $default_definition =
            $this->condition_registry->get(
                $default_condition_field
            );

        if (
            is_array(
                $default_definition
            ) &&
            isset(
                $default_definition['operators']
            ) &&
            is_array(
                $default_definition['operators']
            ) &&
            ! empty(
                $default_definition['operators']
            )
        ) {

            $operator_keys =
                array_keys(
                    $default_definition['operators']
                );

            $default_condition_operator =
                $operator_keys[0];
        }

        /*
         * Conditions used by the form.
         */
        $conditions_for_form =
            array(
                array(
                    'field' =>
                        $default_condition_field,

                    'operator' =>
                        $default_condition_operator,

                    'value' =>
                        '',
                ),
            );

        $actions =
            array();

        /*
         * Load existing Automation data.
         */
        if ( $edit_id ) {

            if (
                'woosmart_automation' ===
                get_post_type(
                    $edit_id
                )
            ) {

                $is_edit =
                    true;

                $automation =
                    get_post(
                        $edit_id
                    );

                if (
                    $automation
                ) {

                    $name =
                        $automation->post_title;

                    $trigger =
                        get_post_meta(
                            $edit_id,
                            '_woosmart_trigger',
                            true
                        );

                    $stored_conditions =
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
                        is_array(
                            $stored_conditions
                        ) &&
                        ! empty(
                            $stored_conditions
                        )
                    ) {

                        $conditions_for_form =
                            array();

                        foreach (
                            $stored_conditions as $stored_condition
                        ) {

                            if (
                                ! is_array(
                                    $stored_condition
                                )
                            ) {
                                continue;
                            }

                            $stored_field =
                                isset(
                                    $stored_condition['field']
                                )
                                    ? sanitize_key(
                                        $stored_condition['field']
                                    )
                                    : $default_condition_field;

                            if (
                                ! $this->condition_registry->has(
                                    $stored_field
                                )
                            ) {
                                $stored_field =
                                    $default_condition_field;
                            }

                            $stored_definition =
                                $this->condition_registry->get(
                                    $stored_field
                                );

                            $stored_operator =
                                isset(
                                    $stored_condition['operator']
                                )
                                    ? sanitize_key(
                                        $stored_condition['operator']
                                    )
                                    : '';

                            if (
                                ! is_array(
                                    $stored_definition
                                ) ||
                                ! isset(
                                    $stored_definition['operators']
                                ) ||
                                ! is_array(
                                    $stored_definition['operators']
                                ) ||
                                ! isset(
                                    $stored_definition['operators'][
                                        $stored_operator
                                    ]
                                )
                            ) {

                                $stored_operator =
                                    '';

                                if (
                                    is_array(
                                        $stored_definition
                                    ) &&
                                    ! empty(
                                        $stored_definition['operators']
                                    )
                                ) {

                                    $stored_operator_keys =
                                        array_keys(
                                            $stored_definition['operators']
                                        );

                                    $stored_operator =
                                        $stored_operator_keys[0];
                                }
                            }

                            $stored_value =
                                isset(
                                    $stored_condition['value']
                                )
                                    ? $stored_condition['value']
                                    : '';

                            $normalized_condition =
                                array(
                                    'field' =>
                                        $stored_field,

                                    'operator' =>
                                        $stored_operator,

                                    'value' =>
                                        $stored_value,
                                );

                            $conditions_for_form[] =
                                $normalized_condition;
                        }

                        if (
                            empty(
                                $conditions_for_form
                            )
                        ) {

                            $conditions_for_form =
                                array(
                                    array(
                                        'field' =>
                                            $default_condition_field,

                                        'operator' =>
                                            $default_condition_operator,

                                        'value' =>
                                            '',
                                    ),
                                );
                        }
                    }

                    if (
                        is_array(
                            $stored_actions
                        ) &&
                        ! empty(
                            $stored_actions
                        )
                    ) {

                        $actions =
                            $stored_actions;
                    }
                }
            }
        }

        /*
         * Normalize condition values for display.
         */
        foreach (
            $conditions_for_form as $condition_index =>
            $form_condition
        ) {

            $field =
                isset(
                    $form_condition['field']
                )
                    ? sanitize_key(
                        $form_condition['field']
                    )
                    : $default_condition_field;

            $operator =
                isset(
                    $form_condition['operator']
                )
                    ? sanitize_key(
                        $form_condition['operator']
                    )
                    : '';

            $definition =
                $this->condition_registry->get(
                    $field
                );

            $value_type =
                (
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

            $value =
                isset(
                    $form_condition['value']
                )
                    ? $form_condition['value']
                    : '';

            if (
                'between' ===
                $operator &&
                is_array(
                    $value
                )
            ) {

                $minimum =
                    isset(
                        $value['min']
                    )
                        ? $this->normalize_numeric_input(
                            $value['min']
                        )
                        : '';

                $maximum =
                    isset(
                        $value['max']
                    )
                        ? $this->normalize_numeric_input(
                            $value['max']
                        )
                        : '';

                $conditions_for_form[
                    $condition_index
                ]['value'] =
                    array(
                        'min' =>
                            $minimum,

                        'max' =>
                            $maximum,
                    );

            } elseif (
                'number' ===
                $value_type
            ) {

                $conditions_for_form[
                    $condition_index
                ]['value'] =
                    $this->normalize_numeric_input(
                        $value
                    );

            } else {

                $conditions_for_form[
                    $condition_index
                ]['value'] =
                    (string)
                    $value;
            }
        }

        /*
         * Default Action for new Automations.
         */
        if (
            empty(
                $actions
            )
        ) {

            $actions[] =
                array(
                    'type' =>
                        'notify_admin',

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

        /*
         * WooCommerce order statuses.
         */
        $order_statuses =
            array();

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

            $order_statuses =
                array(
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
                    اتوماسیون فقط زمانی اجرا می‌شود که تمام شرایط برقرار باشند.
                </p>

                <div
                    id="woosmart-conditions-container"
                    style="
                        max-width:1000px;
                    "
                >

                    <?php foreach (
                        $conditions_for_form
                        as $condition_index =>
                        $condition
                    ) : ?>

                        <?php
                        $this->render_condition_row(
                            $condition_index,
                            $condition,
                            $condition_definitions,
                            $currency_unit
                        );
                        ?>

                    <?php endforeach; ?>

                </div>

                <p>

                    <button
                        type="button"
                        class="button"
                        id="woosmart-add-condition"
                    >
                        + افزودن شرط
                    </button>

                </p>

                <p class="description">
                    می‌توانید چند شرط تعریف کنید. تمام شرط‌ها باید هم‌زمان برقرار باشند.
                </p>

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

                    const conditionsContainer =
                        document.getElementById(
                            'woosmart-conditions-container'
                        );

                    const addConditionButton =
                        document.getElementById(
                            'woosmart-add-condition'
                        );

                    function normalizeNumber(
                        value
                    ) {

                        value =
                            String(
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

                    function getDefinition(
                        field
                    ) {

                        if (
                            ! field ||
                            ! conditionDefinitions[
                                field
                            ]
                        ) {

                            return null;
                        }

                        return conditionDefinitions[
                            field
                        ];
                    }

                    function isNumberField(
                        field
                    ) {

                        const definition =
                            getDefinition(
                                field
                            );

                        return (
                            definition &&
                            definition.value_type ===
                                'number'
                        );
                    }

                    function buildOperatorOptions(
                        field,
                        selectedOperator
                    ) {

                        const definition =
                            getDefinition(
                                field
                            );

                        let html =
                            '';

                        if (
                            ! definition ||
                            ! definition.operators
                        ) {

                            return html;
                        }

                        Object.keys(
                            definition.operators
                        ).forEach(
                            function(
                                operatorKey
                            ) {

                                html +=
                                    '<option value="' +
                                    escapeHtml(
                                        operatorKey
                                    ) +
                                    '"' +
                                    (
                                        operatorKey ===
                                        selectedOperator
                                            ? ' selected'
                                            : ''
                                    ) +
                                    '>' +
                                    escapeHtml(
                                        definition.operators[
                                            operatorKey
                                        ]
                                    ) +
                                    '</option>';
                            }
                        );

                        return html;
                    }

                    function escapeHtml(
                        value
                    ) {

                        const div =
                            document.createElement(
                                'div'
                            );

                        div.textContent =
                            String(
                                value || ''
                            );

                        return div.innerHTML;
                    }

                    function getFirstOperator(
                        field
                    ) {

                        const definition =
                            getDefinition(
                                field
                            );

                        if (
                            ! definition ||
                            ! definition.operators
                        ) {

                            return '';
                        }

                        const keys =
                            Object.keys(
                                definition.operators
                            );

                        return keys.length
                            ? keys[0]
                            : '';
                    }

                    function syncConditionRow(
                        row
                    ) {

                        if (
                            ! row
                        ) {

                            return;
                        }

                        const fieldSelect =
                            row.querySelector(
                                '.woosmart-condition-field'
                            );

                        const operatorSelect =
                            row.querySelector(
                                '.woosmart-condition-operator'
                            );

                        const valueHidden =
                            row.querySelector(
                                '.woosmart-condition-value'
                            );

                        const minHidden =
                            row.querySelector(
                                '.woosmart-condition-min'
                            );

                        const maxHidden =
                            row.querySelector(
                                '.woosmart-condition-max'
                            );

                        const valueDisplay =
                            row.querySelector(
                                '.woosmart-condition-value-display'
                            );

                        const minDisplay =
                            row.querySelector(
                                '.woosmart-condition-min-display'
                            );

                        const maxDisplay =
                            row.querySelector(
                                '.woosmart-condition-max-display'
                            );

                        const textInput =
                            row.querySelector(
                                '.woosmart-condition-text'
                            );

                        const field =
                            fieldSelect
                                ? fieldSelect.value
                                : '';

                        const operator =
                            operatorSelect
                                ? operatorSelect.value
                                : '';

                        if (
                            'between' ===
                            operator
                        ) {

                            const minimum =
                                normalizeNumber(
                                    minDisplay
                                        ? minDisplay.value
                                        : ''
                                );

                            const maximum =
                                normalizeNumber(
                                    maxDisplay
                                        ? maxDisplay.value
                                        : ''
                                );

                            if (
                                minHidden
                            ) {

                                minHidden.value =
                                    minimum;
                            }

                            if (
                                maxHidden
                            ) {

                                maxHidden.value =
                                    maximum;
                            }

                            if (
                                valueHidden
                            ) {

                                valueHidden.value =
                                    '';
                            }

                            if (
                                minDisplay
                            ) {

                                minDisplay.value =
                                    formatNumber(
                                        minimum
                                    );
                            }

                            if (
                                maxDisplay
                            ) {

                                maxDisplay.value =
                                    formatNumber(
                                        maximum
                                    );
                            }

                            return;
                        }

                        let value =
                            '';

                        if (
                            isNumberField(
                                field
                            )
                        ) {

                            value =
                                normalizeNumber(
                                    valueDisplay
                                        ? valueDisplay.value
                                        : ''
                                );

                            if (
                                valueDisplay
                            ) {

                                valueDisplay.value =
                                    formatNumber(
                                        value
                                    );
                            }

                        } else {

                            value =
                                textInput
                                    ? textInput.value
                                    : '';
                        }

                        if (
                            valueHidden
                        ) {

                            valueHidden.value =
                                value;
                        }

                        if (
                            minHidden
                        ) {

                            minHidden.value =
                                '';
                        }

                        if (
                            maxHidden
                        ) {

                            maxHidden.value =
                                '';
                        }
                    }

                    function updateConditionRowValueUI(
                        row
                    ) {

                        if (
                            ! row
                        ) {

                            return;
                        }

                        const fieldSelect =
                            row.querySelector(
                                '.woosmart-condition-field'
                            );

                        const operatorSelect =
                            row.querySelector(
                                '.woosmart-condition-operator'
                            );

                        const singleWrapper =
                            row.querySelector(
                                '.woosmart-condition-single-wrapper'
                            );

                        const rangeWrapper =
                            row.querySelector(
                                '.woosmart-condition-range-wrapper'
                            );

                        const textWrapper =
                            row.querySelector(
                                '.woosmart-condition-text-wrapper'
                            );

                        const valueUnit =
                            row.querySelector(
                                '.woosmart-condition-unit'
                            );

                        const description =
                            row.querySelector(
                                '.woosmart-condition-description'
                            );

                        if (
                            ! fieldSelect ||
                            ! operatorSelect
                        ) {

                            return;
                        }

                        const field =
                            fieldSelect.value;

                        const operator =
                            operatorSelect.value;

                        const numberField =
                            isNumberField(
                                field
                            );

                        const range =
                            numberField &&
                            'between' ===
                                operator;

                        if (
                            singleWrapper
                        ) {

                            singleWrapper.style.display =
                                (
                                    numberField &&
                                    ! range
                                )
                                    ? 'flex'
                                    : 'none';
                        }

                        if (
                            rangeWrapper
                        ) {

                            rangeWrapper.style.display =
                                range
                                    ? 'flex'
                                    : 'none';
                        }

                        if (
                            textWrapper
                        ) {

                            textWrapper.style.display =
                                numberField
                                    ? 'none'
                                    : 'block';
                        }

                        if (
                            valueUnit
                        ) {

                            valueUnit.textContent =
                                currencyUnit;
                        }

                        if (
                            description
                        ) {

                            if (
                                ! numberField
                            ) {

                                description.textContent =
                                    'مقدار شرط را وارد کنید.';

                            } else if (
                                range
                            ) {

                                description.textContent =
                                    'حداقل و حداکثر مبلغ را به واحد پول فروشگاه وارد کنید؛ هر دو سر بازه شامل شرط هستند.';

                            } else {

                                description.textContent =
                                    'مبلغ را به واحد پول فروشگاه وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.';
                            }
                        }
                    }

                    function updateConditionRowOperators(
                        row,
                        preferredOperator
                    ) {

                        if (
                            ! row
                        ) {

                            return;
                        }

                        const fieldSelect =
                            row.querySelector(
                                '.woosmart-condition-field'
                            );

                        const operatorSelect =
                            row.querySelector(
                                '.woosmart-condition-operator'
                            );

                        if (
                            ! fieldSelect ||
                            ! operatorSelect
                        ) {

                            return;
                        }

                        const selected =
                            preferredOperator ||
                            operatorSelect.value ||
                            '';

                        operatorSelect.innerHTML =
                            buildOperatorOptions(
                                fieldSelect.value,
                                selected
                            );

                        if (
                            ! operatorSelect.value
                        ) {

                            operatorSelect.value =
                                getFirstOperator(
                                    fieldSelect.value
                                );
                        }

                        updateConditionRowValueUI(
                            row
                        );
                    }

                    function bindNumericInput(
                        input
                    ) {

                        if (
                            ! input
                        ) {

                            return;
                        }

                        input.addEventListener(
                            'input',
                            function() {

                                const raw =
                                    normalizeNumber(
                                        input.value
                                    );

                                input.value =
                                    formatNumber(
                                        raw
                                    );

                                try {

                                    input.setSelectionRange(
                                        input.value.length,
                                        input.value.length
                                    );

                                } catch (
                                    error
                                ) {
                                    /*
                                     * Ignore cursor-position errors
                                     * on unsupported input contexts.
                                     */
                                }

                                const row =
                                    input.closest(
                                        '.woosmart-condition-row'
                                    );

                                syncConditionRow(
                                    row
                                );
                            }
                        );

                        input.addEventListener(
                            'blur',
                            function() {

                                const raw =
                                    normalizeNumber(
                                        input.value
                                    );

                                input.value =
                                    formatNumber(
                                        raw
                                    );

                                const row =
                                    input.closest(
                                        '.woosmart-condition-row'
                                    );

                                syncConditionRow(
                                    row
                                );
                            }
                        );
                    }

                    function bindConditionRow(
                        row
                    ) {

                        if (
                            ! row
                        ) {

                            return;
                        }

                        const fieldSelect =
                            row.querySelector(
                                '.woosmart-condition-field'
                            );

                        const operatorSelect =
                            row.querySelector(
                                '.woosmart-condition-operator'
                            );

                        const valueDisplay =
                            row.querySelector(
                                '.woosmart-condition-value-display'
                            );

                        const minDisplay =
                            row.querySelector(
                                '.woosmart-condition-min-display'
                            );

                        const maxDisplay =
                            row.querySelector(
                                '.woosmart-condition-max-display'
                            );

                        const textInput =
                            row.querySelector(
                                '.woosmart-condition-text'
                            );

                        const removeButton =
                            row.querySelector(
                                '.woosmart-remove-condition'
                            );

                        const moveUpButton =
                            row.querySelector(
                                '.woosmart-condition-move-up'
                            );

                        const moveDownButton =
                            row.querySelector(
                                '.woosmart-condition-move-down'
                            );

                        if (
                            fieldSelect
                        ) {

                            fieldSelect.addEventListener(
                                'change',
                                function() {

                                    updateConditionRowOperators(
                                        row,
                                        ''
                                    );

                                    syncConditionRow(
                                        row
                                    );
                                }
                            );
                        }

                        if (
                            operatorSelect
                        ) {

                            operatorSelect.addEventListener(
                                'change',
                                function() {

                                    updateConditionRowValueUI(
                                        row
                                    );

                                    syncConditionRow(
                                        row
                                    );
                                }
                            );
                        }

                        bindNumericInput(
                            valueDisplay
                        );

                        bindNumericInput(
                            minDisplay
                        );

                        bindNumericInput(
                            maxDisplay
                        );

                        if (
                            textInput
                        ) {

                            textInput.addEventListener(
                                'input',
                                function() {

                                    syncConditionRow(
                                        row
                                    );
                                }
                            );
                        }

                        if (
                            removeButton
                        ) {

                            removeButton.addEventListener(
                                'click',
                                function() {

                                    const rows =
                                        conditionsContainer.querySelectorAll(
                                            '.woosmart-condition-row'
                                        );

                                    if (
                                        rows.length <= 1
                                    ) {

                                        alert(
                                            'حداقل یک شرط باید وجود داشته باشد.'
                                        );

                                        return;
                                    }

                                    row.remove();

                                    renumberConditionRows();
                                }
                            );
                        }

                        if (
                            moveUpButton
                        ) {

                            moveUpButton.addEventListener(
                                'click',
                                function() {

                                    const previousRow =
                                        row.previousElementSibling;

                                    if (
                                        ! previousRow ||
                                        ! previousRow.classList.contains(
                                            'woosmart-condition-row'
                                        )
                                    ) {

                                        return;
                                    }

                                    conditionsContainer.insertBefore(
                                        row,
                                        previousRow
                                    );

                                    renumberConditionRows();
                                }
                            );
                        }

                        if (
                            moveDownButton
                        ) {

                            moveDownButton.addEventListener(
                                'click',
                                function() {

                                    const nextRow =
                                        row.nextElementSibling;

                                    if (
                                        ! nextRow ||
                                        ! nextRow.classList.contains(
                                            'woosmart-condition-row'
                                        )
                                    ) {

                                        return;
                                    }

                                    conditionsContainer.insertBefore(
                                        nextRow,
                                        row
                                    );

                                    renumberConditionRows();
                                }
                            );
                        }

                        updateConditionRowOperators(
                            row,
                            operatorSelect
                                ? operatorSelect.value
                                : ''
                        );

                        updateConditionRowValueUI(
                            row
                        );
                    }

                    function createConditionRow(
                        index
                    ) {

                        const defaultField =
                            <?php
                            echo wp_json_encode(
                                $default_condition_field,
                                JSON_UNESCAPED_UNICODE |
                                JSON_UNESCAPED_SLASHES
                            );
                            ?>;

                        const defaultOperator =
                            getFirstOperator(
                                defaultField
                            );

                        const row =
                            document.createElement(
                                'div'
                            );

                        row.className =
                            'woosmart-condition-row';

                        row.setAttribute(
                            'data-index',
                            index
                        );

                        row.style.cssText =
                            'margin-bottom:16px;' +
                            'padding:18px;' +
                            'border:1px solid #ccd0d4;' +
                            'background:#fff;' +
                            'position:relative;';

                        let fieldOptions =
                            '';

                        Object.keys(
                            conditionDefinitions
                        ).forEach(
                            function(
                                conditionKey
                            ) {

                                const definition =
                                    conditionDefinitions[
                                        conditionKey
                                    ];

                                const label =
                                    definition &&
                                    definition.label
                                        ? definition.label
                                        : conditionKey;

                                fieldOptions +=
                                    '<option value="' +
                                    escapeHtml(
                                        conditionKey
                                    ) +
                                    '"' +
                                    (
                                        conditionKey ===
                                        defaultField
                                            ? ' selected'
                                            : ''
                                    ) +
                                    '>' +
                                    escapeHtml(
                                        label
                                    ) +
                                    '</option>';
                            }
                        );

                        row.innerHTML =
                            `
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
                                    شرط ${index + 1}
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
                                        class="button woosmart-condition-move-up"
                                        title="انتقال شرط به بالا"
                                    >
                                        ↑ بالا
                                    </button>

                                    <button
                                        type="button"
                                        class="button woosmart-condition-move-down"
                                        title="انتقال شرط به پایین"
                                    >
                                        ↓ پایین
                                    </button>

                                    <button
                                        type="button"
                                        class="button-link-delete woosmart-remove-condition"
                                    >
                                        حذف شرط
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
                                            فیلد
                                        </label>

                                    </th>

                                    <td>

                                        <select
                                            class="woosmart-condition-field"
                                            name="conditions[${index}][field]"
                                            style="min-width:300px;"
                                        >
                                            ${fieldOptions}
                                        </select>

                                    </td>

                                </tr>

                                <tr>

                                    <th scope="row">

                                        <label>
                                            مقایسه
                                        </label>

                                    </th>

                                    <td>

                                        <select
                                            class="woosmart-condition-operator"
                                            name="conditions[${index}][operator]"
                                            style="min-width:300px;"
                                        >
                                            ${buildOperatorOptions(
                                                defaultField,
                                                defaultOperator
                                            )}
                                        </select>

                                    </td>

                                </tr>

                                <tr>

                                    <th scope="row">

                                        <label>
                                            مقدار
                                        </label>

                                    </th>

                                    <td>

                                        <div
                                            class="woosmart-condition-single-wrapper"
                                            dir="ltr"
                                            style="
                                                display:flex;
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
                                                    class="regular-text woosmart-condition-value-display"
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
                                                    class="woosmart-condition-unit"
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
                                                    ${escapeHtml(
                                                        currencyUnit
                                                    )}
                                                </span>

                                            </div>

                                        </div>

                                        <div
                                            class="woosmart-condition-range-wrapper"
                                            dir="rtl"
                                            style="
                                                display:none;
                                                flex-wrap:wrap;
                                                align-items:center;
                                                gap:10px;
                                                max-width:700px;
                                            "
                                        >

                                            <div
                                                style="
                                                    position:relative;
                                                    min-width:220px;
                                                    flex:1;
                                                    direction:ltr;
                                                "
                                            >

                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    autocomplete="off"
                                                    dir="ltr"
                                                    class="regular-text woosmart-condition-min-display"
                                                    placeholder="1,000,000"
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
                                                    حداقل
                                                </span>

                                            </div>

                                            <span
                                                style="
                                                    color:#646970;
                                                    font-weight:600;
                                                "
                                            >
                                                تا
                                            </span>

                                            <div
                                                style="
                                                    position:relative;
                                                    min-width:220px;
                                                    flex:1;
                                                    direction:ltr;
                                                "
                                            >

                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    autocomplete="off"
                                                    dir="ltr"
                                                    class="regular-text woosmart-condition-max-display"
                                                    placeholder="5,000,000"
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
                                                    حداکثر
                                                </span>

                                            </div>

                                        </div>

                                        <div
                                            class="woosmart-condition-text-wrapper"
                                            style="
                                                display:none;
                                                max-width:420px;
                                            "
                                        >

                                            <input
                                                type="text"
                                                class="regular-text woosmart-condition-text"
                                            >

                                        </div>

                                        <input
                                            type="hidden"
                                            class="woosmart-condition-value"
                                            name="conditions[${index}][value]"
                                            value=""
                                        >

                                        <input
                                            type="hidden"
                                            class="woosmart-condition-min"
                                            name="conditions[${index}][min]"
                                            value=""
                                        >

                                        <input
                                            type="hidden"
                                            class="woosmart-condition-max"
                                            name="conditions[${index}][max]"
                                            value=""
                                        >

                                        <p
                                            class="description woosmart-condition-description"
                                        >
                                            مبلغ را به واحد پول فروشگاه وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.
                                        </p>

                                    </td>

                                </tr>

                            </table>
                            `;

                        return row;
                    }

                    function renumberConditionRows() {

                        const rows =
                            conditionsContainer.querySelectorAll(
                                '.woosmart-condition-row'
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

                                if (
                                    title
                                ) {

                                    title.textContent =
                                        'شرط ' +
                                        (
                                            rowIndex +
                                            1
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
                                                /conditions\[\d+\]/,
                                                'conditions[' +
                                                rowIndex +
                                                ']'
                                            );
                                    }
                                );

                                const removeButton =
                                    row.querySelector(
                                        '.woosmart-remove-condition'
                                    );

                                if (
                                    removeButton
                                ) {

                                    removeButton.disabled =
                                        rows.length <= 1;
                                }

                                const moveUpButton =
                                    row.querySelector(
                                        '.woosmart-condition-move-up'
                                    );

                                const moveDownButton =
                                    row.querySelector(
                                        '.woosmart-condition-move-down'
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

                    if (
                        conditionsContainer
                    ) {

                        conditionsContainer
                            .querySelectorAll(
                                '.woosmart-condition-row'
                            )
                            .forEach(
                                function(
                                    row
                                ) {

                                    bindConditionRow(
                                        row
                                    );
                                }
                            );

                        renumberConditionRows();
                    }

                    if (
                        addConditionButton &&
                        conditionsContainer
                    ) {

                        addConditionButton.addEventListener(
                            'click',
                            function() {

                                const rows =
                                    conditionsContainer.querySelectorAll(
                                        '.woosmart-condition-row'
                                    );

                                const nextIndex =
                                    rows.length;

                                const row =
                                    createConditionRow(
                                        nextIndex
                                    );

                                conditionsContainer.appendChild(
                                    row
                                );

                                bindConditionRow(
                                    row
                                );

                                renumberConditionRows();
                            }
                        );
                    }

                    const form =
                        document.querySelector(
                            '#woosmart-conditions-container'
                        )
                            ? document.querySelector(
                                '#woosmart-conditions-container'
                              ).closest(
                                'form'
                              )
                            : null;

                    if (
                        form &&
                        conditionsContainer
                    ) {

                        form.addEventListener(
                            'submit',
                            function() {

                                conditionsContainer
                                    .querySelectorAll(
                                        '.woosmart-condition-row'
                                    )
                                    .forEach(
                                        function(
                                            row
                                        ) {

                                            syncConditionRow(
                                                row
                                            );
                                        }
                                    );
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

                    /**
                     * Remove all dynamically-created Conflict UI boxes.
                     *
                     * @return void
                     */
                    function removeAllConflictBoxes() {

                        const boxes =
                            document.querySelectorAll(
                                '.woosmart-action-conflict-box'
                            );

                        boxes.forEach(
                            function(
                                box
                            ) {

                                box.remove();
                            }
                        );
                    }

                    /**
                     * Get Action status label.
                     *
                     * @param string status Status slug.
                     *
                     * @return string
                     */
                    function getActionStatusLabel(
                        status
                    ) {

                        const labels = {
                            pending:
                                'در انتظار پرداخت',

                            processing:
                                'در حال پردازش',

                            'on-hold':
                                'در انتظار',

                            completed:
                                'تکمیل‌شده',

                            cancelled:
                                'لغوشده',

                            refunded:
                                'مستردشده',

                            failed:
                                'ناموفق'
                        };

                        return (
                            labels[
                                status
                            ] ||
                            status
                        );
                    }

                    /**
                     * Create a single Conflict UI box.
                     *
                     * @param array conflicts Conflict definitions.
                     *
                     * @return void
                     */
                    function createConflictBox(
                        conflicts
                    ) {

                        if (
                            ! conflicts ||
                            ! conflicts.length
                        ) {

                            return;
                        }

                        const box =
                            document.createElement(
                                'div'
                            );

                        box.className =
                            'woosmart-action-conflict-box';

                        box.setAttribute(
                            'dir',
                            'rtl'
                        );

                        box.setAttribute(
                            'aria-live',
                            'polite'
                        );

                        box.style.cssText =
                            'display:block;' +
                            'width:100%;' +
                            'max-width:900px;' +
                            'box-sizing:border-box;' +
                            'margin:0 0 20px 0;' +
                            'padding:16px 18px;' +
                            'background:#fff8db;' +
                            'background-color:#fff8db;' +
                            'border:1px solid #e6c84f;' +
                            'border-right:4px solid #d4a72c;' +
                            'border-radius:2px;' +
                            'color:#3c434a;' +
                            'font-size:14px;' +
                            'line-height:1.8;' +
                            'box-shadow:none;';

                        const title =
                            document.createElement(
                                'div'
                            );

                        title.style.cssText =
                            'font-size:16px;' +
                            'font-weight:700;' +
                            'margin-bottom:12px;' +
                            'color:#5f4700;';

                        title.textContent =
                            '⚠ هشدارهای این اتوماسیون';

                        box.appendChild(
                            title
                        );

                        conflicts.forEach(
                            function(
                                conflict,
                                conflictIndex
                            ) {

                                const conflictBox =
                                    document.createElement(
                                        'div'
                                    );

                                conflictBox.style.cssText =
                                    'margin:0 0 14px 0;' +
                                    'padding:0 0 14px 0;' +
                                    (
                                        conflictIndex <
                                        conflicts.length - 1
                                            ? 'border-bottom:1px solid #eadf9c;'
                                            : ''
                                    );

                                const conflictTitle =
                                    document.createElement(
                                        'strong'
                                    );

                                conflictTitle.style.cssText =
                                    'display:block;' +
                                    'font-size:14px;' +
                                    'font-weight:700;' +
                                    'color:#4f410f;' +
                                    'margin-bottom:5px;';

                                conflictTitle.textContent =
                                    conflict.title;

                                conflictBox.appendChild(
                                    conflictTitle
                                );

                                const conflictMessage =
                                    document.createElement(
                                        'p'
                                    );

                                conflictMessage.style.cssText =
                                    'margin:0 0 8px 0;' +
                                    'padding:0;' +
                                    'color:#4b5560;' +
                                    'font-size:13px;';

                                conflictMessage.textContent =
                                    conflict.message;

                                conflictBox.appendChild(
                                    conflictMessage
                                );

                                if (
                                    conflict.actions &&
                                    conflict.actions.length
                                ) {

                                    const actionList =
                                        document.createElement(
                                            'div'
                                        );

                                    actionList.style.cssText =
                                        'font-size:13px;' +
                                        'color:#4b5560;';

                                    conflict.actions.forEach(
                                        function(
                                            action
                                        ) {

                                            if (
                                                ! action
                                            ) {

                                                return;
                                            }

                                            const actionItem =
                                                document.createElement(
                                                    'div'
                                                );

                                            actionItem.style.cssText =
                                                'margin-top:3px;';

                                            actionItem.textContent =
                                                'عملیات ' +
                                                String(
                                                    action.index
                                                ) +
                                                ' → ' +
                                                getActionStatusLabel(
                                                    action.status ||
                                                    action.target_status ||
                                                    ''
                                                );

                                            actionList.appendChild(
                                                actionItem
                                            );
                                        }
                                    );

                                    conflictBox.appendChild(
                                        actionList
                                    );
                                }

                                box.appendChild(
                                    conflictBox
                                );
                            }
                        );

                        const footer =
                            document.createElement(
                                'p'
                            );

                        footer.style.cssText =
                            'margin:0;' +
                            'padding:0;' +
                            'font-size:12px;' +
                            'color:#756a42;';

                        footer.textContent =
                            'این هشدارها فعلاً مانع ذخیره یا اجرای اتوماسیون نمی‌شوند.';

                        box.appendChild(
                            footer
                        );

                        actionsContainer.parentNode.insertBefore(
                            box,
                            actionsContainer.nextSibling
                        );
                    }

                    /**
                     * Render Action conflicts.
                     *
                     * @return void
                     */
                    function renderActionConflicts() {

                        removeAllConflictBoxes();

                        const actions =
                            getActionData();

                        const statusActions =
                            actions.filter(
                                function(
                                    action
                                ) {

                                    return (
                                        action.type ===
                                        'change_order_status'
                                    );
                                }
                            );

                        const conflicts =
                            [];

                        if (
                            statusActions.length > 1
                        ) {

                            conflicts.push(
                                {
                                    type:
                                        'multiple_order_status_changes',

                                    title:
                                        'چند تغییر وضعیت سفارش',

                                    message:
                                        'این اتوماسیون چند تغییر وضعیت سفارش دارد. هر تغییر ممکن است Hookها، ایمیل‌ها یا رفتارهای وابسته WooCommerce و افزونه‌های دیگر را فعال کند.',

                                    actions:
                                        statusActions
                                }
                            );
                        }

                        const seenStatuses =
                            {};

                        statusActions.forEach(
                            function(
                                action
                            ) {

                                if (
                                    ! action.status
                                ) {

                                    return;
                                }

                                if (
                                    Object.prototype.hasOwnProperty.call(
                                        seenStatuses,
                                        action.status
                                    )
                                ) {

                                    conflicts.push(
                                        {
                                            type:
                                                'duplicate_order_status_target',

                                            title:
                                                'تکرار وضعیت مقصد',

                                            message:
                                                'بیش از یک عملیات، سفارش را به یک وضعیت یکسان تغییر می‌دهد.',

                                            status:
                                                action.status,

                                            actions:
                                                [
                                                    seenStatuses[
                                                        action.status
                                                    ],

                                                    action
                                                ]
                                        }
                                    );

                                } else {

                                    seenStatuses[
                                        action.status
                                    ] =
                                        action;
                                }
                            }
                        );

                        if (
                            conflicts.length
                        ) {

                            createConflictBox(
                                conflicts
                            );
                        }
                    }

                    /**
                     * Get next Action index.
                     *
                     * @return int
                     */
                    function getNextIndex() {

                        const rows =
                            actionsContainer.querySelectorAll(
                                '.woosmart-action-row'
                            );

                        let maxIndex =
                            -1;

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

                        return (
                            maxIndex +
                            1
                        );
                    }

                    /**
                     * Create a new Action row.
                     *
                     * @param int index Action index.
                     *
                     * @return HTMLElement
                     */
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
                            'margin-bottom:16px;' +
                            'padding:18px;' +
                            'border:1px solid #ccd0d4;' +
                            'background:#fff;' +
                            'position:relative;';

                        row.innerHTML =
                            `
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
                                    عملیات ${index + 1}
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
                                            class="woosmart-action-status"
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

                    /**
                     * Update Action fields visibility.
                     *
                     * @param HTMLElement row Action row.
                     *
                     * @return void
                     */
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

                    /**
                     * Read current Action data from DOM.
                     *
                     * @return array
                     */
                    function getActionData() {

                        const rows =
                            Array.from(
                                actionsContainer.querySelectorAll(
                                    '.woosmart-action-row'
                                )
                            );

                        return rows.map(
                            function(
                                row,
                                rowIndex
                            ) {

                                const typeSelect =
                                    row.querySelector(
                                        '.woosmart-action-type'
                                    );

                                const statusSelect =
                                    row.querySelector(
                                        '.woosmart-action-status'
                                    );

                                return {
                                    index:
                                        rowIndex +
                                        1,

                                    type:
                                        typeSelect
                                            ? typeSelect.value
                                            : '',

                                    status:
                                        statusSelect
                                            ? statusSelect.value
                                            : '',
                                };
                            }
                        );
                    }

                    /**
                     * Update Action move button state.
                     *
                     * @return void
                     */
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
                                        rowIndex ===
                                        0;
                                }

                                if (
                                    moveDownButton
                                ) {

                                    moveDownButton.disabled =
                                        rowIndex ===
                                        rows.length -
                                        1;
                                }
                            }
                        );
                    }

                    /**
                     * Bind one Action row.
                     *
                     * @param HTMLElement row Action row.
                     *
                     * @return void
                     */
                    function bindActionRow(
                        row
                    ) {

                        const typeSelect =
                            row.querySelector(
                                '.woosmart-action-type'
                            );

                        const statusSelect =
                            row.querySelector(
                                '.woosmart-action-status'
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

                        if (
                            typeSelect
                        ) {

                            typeSelect.addEventListener(
                                'change',
                                function() {

                                    updateActionFields(
                                        row
                                    );

                                    renderActionConflicts();
                                }
                            );
                        }

                        if (
                            statusSelect
                        ) {

                            statusSelect.addEventListener(
                                'change',
                                function() {

                                    renderActionConflicts();
                                }
                            );
                        }

                        if (
                            removeButton
                        ) {

                            removeButton.addEventListener(
                                'click',
                                function() {

                                    const rows =
                                        actionsContainer.querySelectorAll(
                                            '.woosmart-action-row'
                                        );

                                    if (
                                        rows.length <=
                                        1
                                    ) {

                                        alert(
                                            'حداقل یک عملیات باید وجود داشته باشد.'
                                        );

                                        return;
                                    }

                                    row.remove();

                                    renumberActionRows();

                                    renderActionConflicts();
                                }
                            );
                        }

                        if (
                            moveUpButton
                        ) {

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

                                    renderActionConflicts();
                                }
                            );
                        }

                        if (
                            moveDownButton
                        ) {

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

                                    renderActionConflicts();
                                }
                            );
                        }

                        updateActionFields(
                            row
                        );
                    }

                    /**
                     * Renumber Action rows and input names.
                     *
                     * @return void
                     */
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

                                if (
                                    title
                                ) {

                                    title.textContent =
                                        'عملیات ' +
                                        (
                                            rowIndex +
                                            1
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

                    /*
                     * Bind existing Action rows.
                     */
                    actionsContainer
                        .querySelectorAll(
                            '.woosmart-action-row'
                        )
                        .forEach(
                            function(
                                row
                            ) {

                                bindActionRow(
                                    row
                                );
                            }
                        );

                    renumberActionRows();

                    renderActionConflicts();

                    /*
                     * Add new Action.
                     */
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

                            renderActionConflicts();
                        }
                    );
                }
            );
        </script>

        <?php
    }

    /**
     * Render one Condition row.
     *
     * @param int   $index                 Condition index.
     * @param array $condition             Condition configuration.
     * @param array $condition_definitions Condition definitions.
     * @param string $currency_unit        Currency display unit.
     *
     * @return void
     */
    private function render_condition_row(
        $index,
        $condition,
        $condition_definitions,
        $currency_unit
    ) {

        $condition_field =
            isset(
                $condition['field']
            )
                ? sanitize_key(
                    $condition['field']
                )
                : 'order_total';

        if (
            ! isset(
                $condition_definitions[
                    $condition_field
                ]
            )
        ) {

            $condition_keys =
                array_keys(
                    $condition_definitions
                );

            $condition_field =
                ! empty(
                    $condition_keys
                )
                    ? $condition_keys[0]
                    : 'order_total';
        }

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
                : '';

        if (
            ! is_array(
                $condition_definition
            ) ||
            ! isset(
                $condition_definition['operators']
            ) ||
            ! is_array(
                $condition_definition['operators']
            )
        ) {

            $condition_definition =
                array(
                    'label' =>
                        $condition_field,

                    'value_type' =>
                        'text',

                    'operators' =>
                        array(),
                );
        }

        if (
            empty(
                $condition_operator
            ) ||
            ! isset(
                $condition_definition['operators'][
                    $condition_operator
                ]
            )
        ) {

            if (
                ! empty(
                    $condition_definition['operators']
                )
            ) {

                $operator_keys =
                    array_keys(
                        $condition_definition['operators']
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

        $value_type =
            isset(
                $condition_definition['value_type']
            )
                ? sanitize_key(
                    $condition_definition['value_type']
                )
                : 'text';

        $condition_value_scalar =
            '';

        $condition_min =
            '';

        $condition_max =
            '';

        if (
            'between' ===
            $condition_operator &&
            is_array(
                $condition_value
            )
        ) {

            $condition_min =
                isset(
                    $condition_value['min']
                )
                    ? $this->normalize_numeric_input(
                        $condition_value['min']
                    )
                    : '';

            $condition_max =
                isset(
                    $condition_value['max']
                )
                    ? $this->normalize_numeric_input(
                        $condition_value['max']
                    )
                    : '';

        } else {

            $condition_value_scalar =
                'number' ===
                    $value_type
                        ? $this->normalize_numeric_input(
                            $condition_value
                        )
                        : (string)
                            $condition_value;
        }

        $condition_value_display =
            'number' ===
                $value_type
                    ? $this->format_currency_input(
                        $condition_value_scalar
                    )
                    : '';

        $condition_min_display =
            $this->format_currency_input(
                $condition_min
            );

        $condition_max_display =
            $this->format_currency_input(
                $condition_max
            );

        if (
            'number' !==
            $value_type
        ) {

            $condition_value_display =
                $condition_value_scalar;
        }

        ?>

        <div
            class="woosmart-condition-row"
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
                    شرط
                    <?php
                    echo esc_html(
                        $index + 1
                    );
                    ?>
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
                        class="button woosmart-condition-move-up"
                        title="انتقال شرط به بالا"
                    >
                        ↑ بالا
                    </button>

                    <button
                        type="button"
                        class="button woosmart-condition-move-down"
                        title="انتقال شرط به پایین"
                    >
                        ↓ پایین
                    </button>

                    <button
                        type="button"
                        class="button-link-delete woosmart-remove-condition"
                    >
                        حذف شرط
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
                            فیلد
                        </label>

                    </th>

                    <td>

                        <select
                            class="woosmart-condition-field"
                            name="conditions[<?php echo esc_attr( $index ); ?>][field]"
                            style="min-width:300px;"
                        >

                            <?php foreach (
                                $condition_definitions
                                as $condition_key =>
                                $condition_definition_item
                            ) : ?>

                                <?php
                                $condition_label =
                                    isset(
                                        $condition_definition_item['label']
                                    )
                                        ? $condition_definition_item['label']
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

                        <label>
                            مقایسه
                        </label>

                    </th>

                    <td>

                        <select
                            class="woosmart-condition-operator"
                            name="conditions[<?php echo esc_attr( $index ); ?>][operator]"
                            style="min-width:300px;"
                        >

                            <?php foreach (
                                $condition_definition['operators']
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

                        <label>
                            مقدار
                        </label>

                    </th>

                    <td>

                        <div
                            class="woosmart-condition-single-wrapper"
                            dir="ltr"
                            style="
                                display:
                                <?php
                                echo (
                                    'number' ===
                                    $value_type &&
                                    'between' !==
                                        $condition_operator
                                )
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
                                    class="regular-text woosmart-condition-value-display"
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
                                    class="woosmart-condition-unit"
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
                            class="woosmart-condition-range-wrapper"
                            dir="rtl"
                            style="
                                display:
                                <?php
                                echo (
                                    'number' ===
                                    $value_type &&
                                    'between' ===
                                        $condition_operator
                                )
                                    ? 'flex'
                                    : 'none';
                                ?>;
                                flex-wrap:wrap;
                                align-items:center;
                                gap:10px;
                                max-width:700px;
                            "
                        >

                            <div
                                style="
                                    position:relative;
                                    min-width:220px;
                                    flex:1;
                                    direction:ltr;
                                "
                            >

                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    dir="ltr"
                                    class="regular-text woosmart-condition-min-display"
                                    value="<?php echo esc_attr( $condition_min_display ); ?>"
                                    placeholder="1,000,000"
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
                                    حداقل
                                </span>

                            </div>

                            <span
                                style="
                                    color:#646970;
                                    font-weight:600;
                                "
                            >
                                تا
                            </span>

                            <div
                                style="
                                    position:relative;
                                    min-width:220px;
                                    flex:1;
                                    direction:ltr;
                                "
                            >

                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    dir="ltr"
                                    class="regular-text woosmart-condition-max-display"
                                    value="<?php echo esc_attr( $condition_max_display ); ?>"
                                    placeholder="5,000,000"
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
                                    حداکثر
                                </span>

                            </div>

                        </div>

                        <div
                            class="woosmart-condition-text-wrapper"
                            style="
                                display:
                                <?php
                                echo (
                                    'number' ===
                                    $value_type
                                )
                                    ? 'none'
                                    : 'block';
                                ?>;
                                max-width:420px;
                            "
                        >

                            <input
                                type="text"
                                class="regular-text woosmart-condition-text"
                                value="<?php echo esc_attr( $condition_value_display ); ?>"
                            >

                        </div>

                        <input
                            type="hidden"
                            class="woosmart-condition-value"
                            name="conditions[<?php echo esc_attr( $index ); ?>][value]"
                            value="<?php echo esc_attr( $condition_value_scalar ); ?>"
                        >

                        <input
                            type="hidden"
                            class="woosmart-condition-min"
                            name="conditions[<?php echo esc_attr( $index ); ?>][min]"
                            value="<?php echo esc_attr( $condition_min ); ?>"
                        >

                        <input
                            type="hidden"
                            class="woosmart-condition-max"
                            name="conditions[<?php echo esc_attr( $index ); ?>][max]"
                            value="<?php echo esc_attr( $condition_max ); ?>"
                        >

                        <p
                            class="description woosmart-condition-description"
                        >
                            <?php
                            if (
                                'number' !==
                                $value_type
                            ) {

                                echo esc_html(
                                    'مقدار شرط را وارد کنید.'
                                );

                            } elseif (
                                'between' ===
                                $condition_operator
                            ) {

                                echo esc_html(
                                    'حداقل و حداکثر مبلغ را به واحد پول فروشگاه وارد کنید؛ هر دو سر بازه شامل شرط هستند.'
                                );

                            } else {

                                echo esc_html(
                                    'مبلغ را به واحد پول فروشگاه وارد کنید؛ جداکننده هزارگان به‌صورت خودکار اضافه می‌شود.'
                                );
                            }
                            ?>
                        </p>

                    </td>

                </tr>

            </table>

        </div>

        <?php
    }

    /**
     * Render one Action row.
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

        $action_type =
            isset(
                $action['type']
            )
                ? sanitize_key(
                    $action['type']
                )
                : 'notify_admin';

        $action_status =
            isset(
                $action['status']
            )
                ? sanitize_key(
                    $action['status']
                )
                : 'processing';

        $action_subject =
            isset(
                $action['subject']
            )
                ? $action['subject']
                : 'اعلان سفارش جدید در WooSmart';

        $action_message =
            isset(
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
                    عملیات
                    <?php
                    echo esc_html(
                        $index + 1
                    );
                    ?>
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
                    echo (
                        'change_order_status' ===
                        $action_type
                    )
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
                            class="woosmart-action-status"
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
                    echo (
                        'notify_admin' ===
                        $action_type
                    )
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
                    echo (
                        'notify_admin' ===
                        $action_type
                    )
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
     * Render condition summary in the Automation list.
     *
     * Supports both scalar and range conditions.
     *
     * @param array $conditions Conditions.
     *
     * @return void
     */
    private function render_condition_summary(
        $conditions
    ) {

        if (
            empty(
                $conditions
            )
        ) {

            echo '<span>بدون شرط</span>';

            return;
        }

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

            $field =
                isset(
                    $condition['field']
                )
                    ? sanitize_key(
                        $condition['field']
                    )
                    : '';

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

            $definition =
                $this->condition_registry->get(
                    $field
                );

            $value_type =
                (
                    is_array(
                        $definition
                    ) &&
                    isset(
                        $definition['value_type']
                    )
                )
                    ? sanitize_key(
                        $definition[
                            'value_type'
                        ]
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

                <?php if (
                    'between' ===
                    $operator &&
                    is_array(
                        $value
                    )
                ) : ?>

                    <?php

                    $minimum =
                        isset(
                            $value['min']
                        )
                            ? $value['min']
                            : '';

                    $maximum =
                        isset(
                            $value['max']
                        )
                            ? $value['max']
                            : '';

                    ?>

                    <?php if (
                        'number' ===
                        $value_type
                    ) : ?>

                        <?php
                        echo wp_kses_post(
                            $this->format_currency_value(
                                $minimum
                            )
                        );
                        ?>

                        <span>
                            تا
                        </span>

                        <?php
                        echo wp_kses_post(
                            $this->format_currency_value(
                                $maximum
                            )
                        );
                        ?>

                    <?php else : ?>

                        <span>
                            <?php
                            echo esc_html(
                                (string)
                                $minimum
                            );
                            ?>
                        </span>

                        <span>
                            تا
                        </span>

                        <span>
                            <?php
                            echo esc_html(
                                (string)
                                $maximum
                            );
                            ?>
                        </span>

                    <?php endif; ?>

                <?php else : ?>

                    <?php if (
                        'number' ===
                        $value_type
                    ) : ?>

                        <?php
                        echo wp_kses_post(
                            $this->format_currency_value(
                                $value
                            )
                        );
                        ?>

                    <?php else : ?>

                        <?php
                        echo esc_html(
                            (string)
                            $value
                        );
                        ?>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        <?php
        }
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
                            array_reverse(
                                $logs
                            )
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

        $labels =
            array(
                'order_created' =>
                    'ایجاد سفارش',
            );

        return isset(
            $labels[
                $trigger
            ]
        )
            ? $labels[
                $trigger
            ]
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
                $definition[
                    'label'
                ]
            )
        ) {

            return $definition[
                'label'
            ];
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
            ! empty(
                $field
            )
        ) {

            $operators =
                $this->condition_registry->get_operators(
                    $field
                );

            if (
                isset(
                    $operators[
                        $operator
                    ]
                )
            ) {

                return $operators[
                    $operator
                ];
            }
        }

        $labels =
            array(
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

                'between' =>
                    'بین',
            );

        return isset(
            $labels[
                $operator
            ]
        )
            ? $labels[
                $operator
            ]
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

        $labels =
            array(
                'change_order_status' =>
                    'تغییر وضعیت سفارش',

                'notify_admin' =>
                    'ارسال اعلان به مدیر فروشگاه',
            );

        return isset(
            $labels[
                $action_type
            ]
        )
            ? $labels[
                $action_type
            ]
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

        $labels =
            array(
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
                $labels[
                    $status_slug
                ]
            )
        ) {

            return $labels[
                $status_slug
            ];
        }

        if (
            null !==
            $default_label
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
            '' ===
            $value
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
            '' ===
            $value
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
                (float)
                $parts[0],
                0,
                '.',
                ','
            );

        if (
            isset(
                $parts[1]
            ) &&
            '' !==
                $parts[1]
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
            (string)
            $value;

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
            null ===
            $value
        ) {

            return '';
        }

        $first_dot =
            strpos(
                $value,
                '.'
            );

        if (
            false !==
            $first_dot
        ) {

            $value =
                substr(
                    $value,
                    0,
                    $first_dot +
                    1
                ) .
                str_replace(
                    '.',
                    '',
                    substr(
                        $value,
                        $first_dot +
                        1
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

        $labels =
            array(
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

                'action_result' =>
                    'نتیجه عملیات',

                'action_side_effect' =>
                    'اثر جانبی عملیات',

                'automation_conflict_detected' =>
                    'تعارض اتوماسیون',
            );

        return isset(
            $labels[
                $event
            ]
        )
            ? $labels[
                $event
            ]
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

        $messages =
            array(
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

                'action_result' =>
                    'نتیجه هر عملیات به‌صورت مستقل ثبت شد.',

                'action_side_effect' =>
                    'عملیات باعث یک اثر جانبی در WooCommerce شد.',

                'automation_conflict_detected' =>
                    'در پیکربندی عملیات اتوماسیون تعارض یا اثر جانبی بالقوه شناسایی شد.',
            );

        return isset(
            $messages[
                $event
            ]
        )
            ? $messages[
                $event
            ]
            : $message;
    }
}
