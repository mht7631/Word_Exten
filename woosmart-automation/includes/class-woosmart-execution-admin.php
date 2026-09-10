<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin UI for WooSmart Execution History and Execution Policy.
 */
class WooSmart_Execution_Admin {

    /**
     * Execution History instance.
     *
     * @var WooSmart_Execution_History
     */
    private $execution_history;

    /**
     * Initialize Execution admin UI.
     *
     * @param WooSmart_Execution_History $execution_history History service.
     */
    public function __construct(
        WooSmart_Execution_History $execution_history
    ) {

        $this->execution_history =
            $execution_history;

        add_action(
            'admin_menu',
            array(
                $this,
                'add_admin_menu',
            )
        );

        add_action(
            'admin_post_woosmart_save_execution_policy',
            array(
                $this,
                'save_execution_policy',
            )
        );
    }

    /**
     * Add Execution History page.
     *
     * @return void
     */
    public function add_admin_menu() {

        add_submenu_page(
            'woosmart-automation',
            'تاریخچه اجرا',
            'تاریخچه اجرا',
            'manage_options',
            'woosmart-executions',
            array(
                $this,
                'render_executions_page',
            )
        );
    }

    /**
     * Save Execution Policy.
     *
     * @return void
     */
    public function save_execution_policy() {

        if (
            ! current_user_can(
                'manage_options'
            )
        ) {

            wp_die(
                'شما اجازه انجام این عملیات را ندارید.'
            );
        }

        check_admin_referer(
            'woosmart_save_execution_policy',
            'woosmart_execution_policy_nonce'
        );

        $policy =
            isset(
                $_POST['execution_policy']
            )
                ? sanitize_key(
                    wp_unslash(
                        $_POST['execution_policy']
                    )
                )
                : 'all';

        $allowed_policies = array(
            'all',
            'first_match',
            'first_success',
        );

        if (
            ! in_array(
                $policy,
                $allowed_policies,
                true
            )
        ) {

            $policy =
                'all';
        }

        update_option(
            'woosmart_execution_policy',
            $policy
        );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' =>
                        'woosmart-executions',

                    'policy_saved' =>
                        1,
                ),
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }

    /**
     * Render Execution History or Detail page.
     *
     * @return void
     */
    public function render_executions_page() {

        $view =
            isset(
                $_GET['view']
            )
                ? sanitize_key(
                    wp_unslash(
                        $_GET['view']
                    )
                )
                : '';

        if (
            'detail' ===
            $view
        ) {

            $this->render_execution_detail_page();

            return;
        }

        $this->render_execution_list_page();
    }

    /**
     * Render Execution History list page.
     *
     * @return void
     */
    private function render_execution_list_page() {

        $current_policy =
            get_option(
                'woosmart_execution_policy',
                'all'
            );

        $allowed_policies = array(
            'all',
            'first_match',
            'first_success',
        );

        if (
            ! in_array(
                $current_policy,
                $allowed_policies,
                true
            )
        ) {

            $current_policy =
                'all';
        }

        $current_page =
            isset(
                $_GET['paged']
            )
                ? max(
                    1,
                    absint(
                        $_GET['paged']
                    )
                )
                : 1;

        $status_filter =
            isset(
                $_GET['status']
            )
                ? sanitize_key(
                    wp_unslash(
                        $_GET['status']
                    )
                )
                : '';

        $allowed_statuses = array(
            '',
            'completed',
            'failed',
            'conditions_failed',
        );

        if (
            ! in_array(
                $status_filter,
                $allowed_statuses,
                true
            )
        ) {

            $status_filter =
                '';
        }

        $per_page =
            25;

        $executions =
            $this->execution_history->get_executions(
                $current_page,
                $per_page,
                $status_filter
            );

        $total_items =
            $this->execution_history->count_executions(
                $status_filter
            );

        $total_pages =
            max(
                1,
                (int) ceil(
                    $total_items /
                    $per_page
                )
            );

        $summary =
            $this->execution_history->get_summary();
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                تاریخچه اجرا
            </h1>

            <p>
                این صفحه نتیجه اجرای اتوماسیون‌ها را مستقل از سیستم ایمیل و لاگ‌های فنی نشان می‌دهد.
            </p>

            <?php if ( isset( $_GET['policy_saved'] ) ) : ?>

                <div class="notice notice-success is-dismissible">

                    <p>
                        سیاست اجرای اتوماسیون با موفقیت ذخیره شد.
                    </p>

                </div>

            <?php endif; ?>

            <hr>

            <h2>
                سیاست اجرای اتوماسیون
            </h2>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                style="
                    max-width:900px;
                    background:#fff;
                    border:1px solid #ccd0d4;
                    padding:20px;
                    margin-bottom:25px;
                "
            >

                <input
                    type="hidden"
                    name="action"
                    value="woosmart_save_execution_policy"
                >

                <?php
                wp_nonce_field(
                    'woosmart_save_execution_policy',
                    'woosmart_execution_policy_nonce'
                );
                ?>

                <table
                    class="form-table"
                    style="margin:0;"
                >

                    <tr>

                        <th scope="row">

                            <label for="woosmart-execution-policy">
                                نحوه اجرای چند اتوماسیون
                            </label>

                        </th>

                        <td>

                            <select
                                id="woosmart-execution-policy"
                                name="execution_policy"
                                style="min-width:360px;"
                            >

                                <option
                                    value="all"
                                    <?php selected(
                                        $current_policy,
                                        'all'
                                    ); ?>
                                >
                                    اجرای همه اتوماسیون‌های منطبق
                                </option>

                                <option
                                    value="first_match"
                                    <?php selected(
                                        $current_policy,
                                        'first_match'
                                    ); ?>
                                >
                                    فقط اولین اتوماسیون منطبق
                                </option>

                                <option
                                    value="first_success"
                                    <?php selected(
                                        $current_policy,
                                        'first_success'
                                    ); ?>
                                >
                                    اولین اتوماسیون موفق
                                </option>

                            </select>

                            <p
                                class="description"
                                style="max-width:760px;"
                            >
                                در حالت «اولین اتوماسیون منطبق»، به‌محض برقرار شدن شرایط یک اتوماسیون، بررسی اتوماسیون‌های بعدی متوقف می‌شود. در حالت «اولین اتوماسیون موفق»، فقط پس از اجرای موفق همه عملیات یک اتوماسیون، بررسی متوقف می‌شود.
                            </p>

                            <p
                                class="description"
                                style="max-width:760px;"
                            >
                                در نسخه فعلی، ترتیب بررسی اتوماسیون‌ها جدیدترین اتوماسیون به قدیمی‌ترین است. Priority مستقل در مرحله بعد اضافه خواهد شد.
                            </p>

                        </td>

                    </tr>

                </table>

                <?php
                submit_button(
                    'ذخیره سیاست اجرا'
                );
                ?>

            </form>

            <h2>
                خلاصه
            </h2>

            <div
                style="
                    display:flex;
                    flex-wrap:wrap;
                    gap:12px;
                    margin:15px 0 25px;
                "
            >

                <?php
                $this->render_summary_card(
                    'کل اجراها',
                    isset( $summary['all'] )
                        ? $summary['all']
                        : 0,
                    '#2271b1'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'موفق',
                    isset( $summary['completed'] )
                        ? $summary['completed']
                        : 0,
                    '#008a20'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'ناموفق',
                    isset( $summary['failed'] )
                        ? $summary['failed']
                        : 0,
                    '#b32d2e'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'شرایط برقرار نبود',
                    isset( $summary['conditions_failed'] )
                        ? $summary['conditions_failed']
                        : 0,
                    '#996800'
                );
                ?>

            </div>

            <h2>
                اجراها
            </h2>

            <p>
                <?php
                echo esc_html(
                    $this->get_policy_label(
                        $current_policy
                    )
                );
                ?>
            </p>

            <p>

                <a
                    class="button <?php echo '' === $status_filter ? 'button-primary' : ''; ?>"
                    href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-executions' ) ); ?>"
                >
                    همه
                </a>

                <a
                    class="button <?php echo 'completed' === $status_filter ? 'button-primary' : ''; ?>"
                    href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-executions&status=completed' ) ); ?>"
                >
                    موفق
                </a>

                <a
                    class="button <?php echo 'failed' === $status_filter ? 'button-primary' : ''; ?>"
                    href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-executions&status=failed' ) ); ?>"
                >
                    ناموفق
                </a>

                <a
                    class="button <?php echo 'conditions_failed' === $status_filter ? 'button-primary' : ''; ?>"
                    href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-executions&status=conditions_failed' ) ); ?>"
                >
                    شرایط برقرار نبود
                </a>

            </p>

            <?php if ( empty( $executions ) ) : ?>

                <div class="notice notice-info">

                    <p>
                        هنوز اجرایی برای نمایش وجود ندارد.
                    </p>

                </div>

            <?php else : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>

                            <th>
                                شناسه اجرا
                            </th>

                            <th>
                                اتوماسیون
                            </th>

                            <th>
                                سفارش
                            </th>

                            <th>
                                رویداد
                            </th>

                            <th>
                                سیاست
                            </th>

                            <th>
                                وضعیت
                            </th>

                            <th>
                                عملیات
                            </th>

                            <th>
                                مدت اجرا
                            </th>

                            <th>
                                شروع
                            </th>

                            <th>
                                پایان
                            </th>

                            <th>
                                توضیح
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ( $executions as $execution ) : ?>

                            <?php
                            $detail_url =
                                admin_url(
                                    'admin.php?page=woosmart-executions&view=detail&execution_id=' .
                                    absint(
                                        $execution['id']
                                    )
                                );

                            $status_data =
                                $this->get_status_data(
                                    isset( $execution['status'] )
                                        ? $execution['status']
                                        : ''
                                );
                            ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?php echo esc_url( $detail_url ); ?>"
                                        style="font-weight:600;"
                                    >
                                        #<?php echo esc_html( $execution['id'] ); ?>
                                    </a>

                                </td>

                                <td>

                                    <a
                                        href="<?php echo esc_url( $detail_url ); ?>"
                                    >
                                        <strong>
                                            #<?php echo esc_html( $execution['automation_id'] ); ?>
                                        </strong>
                                    </a>

                                    <?php if ( ! empty( $execution['automation_title'] ) ) : ?>

                                        <div
                                            style="
                                                margin-top:4px;
                                                color:#646970;
                                                font-size:12px;
                                            "
                                        >
                                            <?php
                                            echo esc_html(
                                                $execution['automation_title']
                                            );
                                            ?>
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ( ! empty( $execution['order_id'] ) ) : ?>

                                        #<?php echo esc_html( $execution['order_id'] ); ?>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        $this->get_trigger_label(
                                            isset( $execution['trigger_key'] )
                                                ? $execution['trigger_key']
                                                : ''
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        $this->get_policy_label(
                                            isset( $execution['execution_policy'] )
                                                ? $execution['execution_policy']
                                                : ''
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:4px 9px;
                                            border:1px solid <?php echo esc_attr( $status_data['border'] ); ?>;
                                            background:<?php echo esc_attr( $status_data['background'] ); ?>;
                                            color:<?php echo esc_attr( $status_data['color'] ); ?>;
                                            font-weight:600;
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $status_data['label']
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        isset( $execution['actions_total'] )
                                            ? $execution['actions_total']
                                            : 0
                                    );
                                    ?>

                                    <?php if ( 'completed' === $execution['status'] ) : ?>

                                        / موفق

                                    <?php elseif ( 'failed' === $execution['status'] ) : ?>

                                        / خطا

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        $this->format_duration(
                                            isset(
                                                $execution['duration_ms']
                                            )
                                                ? $execution['duration_ms']
                                                : 0
                                        )
                                    );
                                    ?>

                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        isset( $execution['started_at'] )
                                            ? $execution['started_at']
                                            : ''
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        ! empty(
                                            $execution['completed_at']
                                        )
                                            ? $execution['completed_at']
                                            : '—'
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        isset( $execution['message'] )
                                            ? $execution['message']
                                            : ''
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <?php if ( $total_pages > 1 ) : ?>

                    <div
                        class="tablenav"
                        style="margin-top:15px;"
                    >

                        <div class="tablenav-pages">

                            <?php
                            echo wp_kses_post(
                                paginate_links(
                                    array(
                                        'base' =>
                                            add_query_arg(
                                                'paged',
                                                '%#%',
                                                admin_url(
                                                    'admin.php?page=woosmart-executions'
                                                )
                                            ),

                                        'format' =>
                                            '',

                                        'current' =>
                                            $current_page,

                                        'total' =>
                                            $total_pages,

                                        'add_args' =>
                                            '' !==
                                            $status_filter
                                                ? array(
                                                    'status' =>
                                                        $status_filter,
                                                )
                                                : array(),

                                        'prev_text' =>
                                            '‹',

                                        'next_text' =>
                                            '›',
                                    )
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

        <?php
    }

    /**
     * Render one Execution detail page.
     *
     * @return void
     */
    private function render_execution_detail_page() {

        $execution_id =
            isset(
                $_GET['execution_id']
            )
                ? absint(
                    $_GET['execution_id']
                )
                : 0;

        if (
            ! $execution_id
        ) {

            wp_die(
                'شناسه اجرای نامعتبر است.'
            );
        }

        $execution =
            $this->execution_history->get_execution(
                $execution_id
            );

        if (
            ! is_array(
                $execution
            )
        ) {

            wp_die(
                'اجرای موردنظر پیدا نشد.'
            );
        }

        $status_data =
            $this->get_status_data(
                isset( $execution['status'] )
                    ? $execution['status']
                    : ''
            );

        $back_url =
            admin_url(
                'admin.php?page=woosmart-executions'
            );
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <p>

                <a
                    href="<?php echo esc_url( $back_url ); ?>"
                    class="button"
                >
                    ← بازگشت به تاریخچه اجرا
                </a>

            </p>

            <h1>
                جزئیات اجرای #<?php echo esc_html( $execution['id'] ); ?>
            </h1>

            <hr>

            <div
                style="
                    max-width:1000px;
                "
            >

                <div
                    style="
                        background:#fff;
                        border:1px solid #ccd0d4;
                        padding:20px;
                        margin-bottom:20px;
                    "
                >

                    <h2
                        style="
                            margin-top:0;
                        "
                    >
                        خلاصه اجرا
                    </h2>

                    <table
                        class="widefat"
                        style="
                            border:0;
                            box-shadow:none;
                        "
                    >

                        <tbody>

                            <tr>
                                <td
                                    style="
                                        width:220px;
                                        font-weight:600;
                                    "
                                >
                                    شناسه اجرا
                                </td>

                                <td>
                                    #<?php echo esc_html( $execution['id'] ); ?>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    اتوماسیون
                                </td>

                                <td>

                                    <strong>
                                        #<?php echo esc_html( $execution['automation_id'] ); ?>
                                    </strong>

                                    <?php if ( ! empty( $execution['automation_title'] ) ) : ?>

                                        —
                                        <?php
                                        echo esc_html(
                                            $execution['automation_title']
                                        );
                                        ?>

                                    <?php endif; ?>

                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    سفارش
                                </td>

                                <td>

                                    <?php if ( ! empty( $execution['order_id'] ) ) : ?>

                                        #<?php
                                        echo esc_html(
                                            $execution['order_id']
                                        );
                                        ?>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>

                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    رویداد
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_trigger_label(
                                            isset( $execution['trigger_key'] )
                                                ? $execution['trigger_key']
                                                : ''
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    سیاست اجرا
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->get_policy_label(
                                            isset( $execution['execution_policy'] )
                                                ? $execution['execution_policy']
                                                : ''
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    نتیجه
                                </td>

                                <td>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border:1px solid <?php echo esc_attr( $status_data['border'] ); ?>;
                                            background:<?php echo esc_attr( $status_data['background'] ); ?>;
                                            color:<?php echo esc_attr( $status_data['color'] ); ?>;
                                            font-weight:600;
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $status_data['label']
                                        );
                                        ?>
                                    </span>

                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    مدت اجرا
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->format_duration(
                                            isset(
                                                $execution['duration_ms']
                                            )
                                                ? $execution['duration_ms']
                                                : 0
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    شروع
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        isset( $execution['started_at'] )
                                            ? $execution['started_at']
                                            : ''
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        font-weight:600;
                                    "
                                >
                                    پایان
                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        ! empty(
                                            $execution['completed_at']
                                        )
                                            ? $execution['completed_at']
                                            : '—'
                                    );
                                    ?>

                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <div
                    style="
                        background:#fff;
                        border:1px solid #ccd0d4;
                        padding:20px;
                        margin-bottom:20px;
                    "
                >

                    <h2
                        style="
                            margin-top:0;
                        "
                    >
                        شرایط
                    </h2>

                    <?php
                    $conditions =
                        isset(
                            $execution['conditions']
                        ) &&
                        is_array(
                            $execution['conditions']
                        )
                            ? $execution['conditions']
                            : array();

                    /*
                     * Detailed condition results are stored separately from
                     * the condition configuration snapshot.
                     *
                     * Keep the local variable name used by the existing
                     * renderers so the legacy and grouped UI remain
                     * backward compatible.
                     */
                    $condition_results =
                        isset(
                            $execution['condition_evaluation']
                        ) &&
                        is_array(
                            $execution['condition_evaluation']
                        )
                            ? $execution['condition_evaluation']
                            : array();

                    $execution_status =
                        isset(
                            $execution['status']
                        )
                            ? sanitize_key(
                                $execution['status']
                            )
                            : '';
                    ?>

                    <?php if ( empty( $conditions ) ) : ?>

                        <div class="notice notice-info inline">

                            <p>
                                این اتوماسیون بدون شرط اجرا شده است.
                            </p>

                        </div>

                    <?php elseif ( $this->is_grouped_conditions( $conditions ) ) : ?>

                        <?php
                        $this->render_grouped_condition_history(
                            $conditions,
                            $condition_results
                        );
                        ?>

                    <?php else : ?>

                        <?php foreach ( $conditions as $index => $condition ) : ?>

                            <?php
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

                            $condition_passed =
                                $this->resolve_legacy_condition_result(
                                    $index,
                                    $conditions,
                                    $condition_results,
                                    $execution,
                                    $execution_status
                                );
                            ?>

                            <div
                                style="
                                    margin-bottom:12px;
                                    padding:14px;
                                    border:1px solid #e2e4e7;
                                    background:#f9f9f9;
                                "
                            >

                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:15px;
                                    "
                                >

                                    <div>

                                        <strong>
                                            شرط <?php echo esc_html( $index + 1 ); ?>
                                        </strong>

                                        <div
                                            style="
                                                margin-top:6px;
                                            "
                                        >

                                            <?php
                                            echo esc_html(
                                                $this->get_condition_display(
                                                    $field,
                                                    $operator,
                                                    $value
                                                )
                                            );
                                            ?>

                                        </div>

                                    </div>

                                    <?php
                                    $this->render_result_badge(
                                        $condition_passed
                                    );
                                    ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div
                    style="
                        background:#fff;
                        border:1px solid #ccd0d4;
                        padding:20px;
                        margin-bottom:20px;
                    "
                >

                    <h2
                        style="
                            margin-top:0;
                        "
                    >
                        عملیات
                    </h2>

                    <?php
                    $actions =
                        isset(
                            $execution['actions']
                        ) &&
                        is_array(
                            $execution['actions']
                        )
                            ? $execution['actions']
                            : array();

                    $action_results =
                        isset(
                            $execution['action_results']
                        ) &&
                        is_array(
                            $execution['action_results']
                        )
                            ? $execution['action_results']
                            : array();

                    $action_results_by_index =
                        array();

                    foreach (
                        $action_results
                        as $action_result
                    ) {

                        if (
                            ! isset(
                                $action_result['index']
                            )
                        ) {
                            continue;
                        }

                        $action_results_by_index[
                            absint(
                                $action_result['index']
                            )
                        ] =
                            $action_result;
                    }
                    ?>

                    <?php if ( empty( $actions ) ) : ?>

                        <div class="notice notice-warning inline">

                            <p>
                                هیچ عملیاتی برای این اجرا ثبت نشده است.
                            </p>

                        </div>

                    <?php else : ?>

                        <?php foreach ( $actions as $index => $action ) : ?>

                            <?php
                            $action_number =
                                $index + 1;

                            $type =
                                isset(
                                    $action['type']
                                )
                                    ? sanitize_key(
                                        $action['type']
                                    )
                                    : '';

                            $action_result =
                                isset(
                                    $action_results_by_index[
                                        $action_number
                                    ]
                                )
                                    ? $action_results_by_index[
                                        $action_number
                                    ]
                                    : array(
                                        'success' =>
                                            null,
                                    );
                            ?>

                            <div
                                style="
                                    margin-bottom:15px;
                                    padding:16px;
                                    border:1px solid #ccd0d4;
                                    background:#fafafa;
                                "
                            >

                                <div
                                    style="
                                        display:flex;
                                        justify-content:space-between;
                                        align-items:flex-start;
                                        gap:20px;
                                    "
                                >

                                    <div>

                                        <strong
                                            style="
                                                font-size:15px;
                                            "
                                        >
                                            عملیات <?php echo esc_html( $action_number ); ?>
                                        </strong>

                                        <div
                                            style="
                                                margin-top:7px;
                                            "
                                        >
                                            <?php
                                            echo esc_html(
                                                $this->get_action_display(
                                                    $action
                                                )
                                            );
                                            ?>
                                        </div>

                                    </div>

                                    <div>

                                        <?php
                                        $action_success =
                                            isset(
                                                $action_result['success']
                                            )
                                                ? $action_result['success']
                                                : null;

                                        $this->render_result_badge(
                                            $action_success
                                        );
                                        ?>

                                    </div>

                                </div>

                                <?php if ( 'notify_admin' === $type ) : ?>

                                    <?php if ( ! empty( $action['subject'] ) ) : ?>

                                        <div
                                            style="
                                                margin-top:12px;
                                                padding-top:10px;
                                                border-top:1px solid #e2e4e7;
                                            "
                                        >

                                            <strong>
                                                موضوع:
                                            </strong>

                                            <?php
                                            echo esc_html(
                                                $action['subject']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php if ( isset( $action_result['message'] ) ) : ?>

                                    <div
                                        style="
                                            margin-top:10px;
                                            color:#646970;
                                            font-size:13px;
                                        "
                                    >

                                        <?php
                                        echo esc_html(
                                            $action_result['message']
                                        );
                                        ?>

                                        <?php if ( isset( $action_result['duration_ms'] ) ) : ?>

                                            —
                                            مدت:
                                            <?php
                                            echo esc_html(
                                                $this->format_duration(
                                                    $action_result['duration_ms']
                                                )
                                            );
                                            ?>

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div
                    style="
                        background:#fff;
                        border:1px solid #ccd0d4;
                        padding:20px;
                    "
                >

                    <h2
                        style="
                            margin-top:0;
                        "
                    >
                        اطلاعات تکمیلی
                    </h2>

                    <p>
                        <strong>
                            توضیح:
                        </strong>

                        <?php
                        echo esc_html(
                            isset( $execution['message'] )
                                ? $execution['message']
                                : ''
                        );
                        ?>
                    </p>

                    <?php if ( ! empty( $execution['context'] ) ) : ?>

                        <details>

                            <summary>
                                نمایش Context فنی
                            </summary>

                            <pre
                                style="
                                    margin-top:12px;
                                    padding:15px;
                                    background:#f6f7f7;
                                    overflow:auto;
                                    direction:ltr;
                                    text-align:left;
                                    white-space:pre-wrap;
                                "
                            ><?php
                            echo esc_html(
                                wp_json_encode(
                                    $execution['context'],
                                    JSON_PRETTY_PRINT |
                                    JSON_UNESCAPED_UNICODE |
                                    JSON_UNESCAPED_SLASHES
                                )
                            );
                            ?></pre>

                        </details>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <?php
    }

    /**
     * Check whether the condition snapshot uses grouped conditions.
     *
     * @param array $conditions Condition snapshot.
     *
     * @return bool
     */
    private function is_grouped_conditions( $conditions ) {

        return (
            is_array( $conditions ) &&
            isset( $conditions['groups'] ) &&
            is_array( $conditions['groups'] )
        );
    }

    /**
     * Render grouped condition history.
     *
     * Conditions inside a group are AND-connected and groups are OR-connected.
     * Detailed per-condition results are shown only when they are actually
     * present in the stored execution result snapshot.
     *
     * @param array $conditions        Grouped condition snapshot.
     * @param array $condition_results  Stored condition result snapshot.
     *
     * @return void
     */
    private function render_grouped_condition_history(
        $conditions,
        $condition_results
    ) {

        $groups =
            isset( $conditions['groups'] ) &&
            is_array( $conditions['groups'] )
                ? $conditions['groups']
                : array();

        foreach ( $groups as $group_index => $group ) {

            if ( $group_index > 0 ) {

                $this->render_logic_separator_badge(
                    'OR'
                );
            }

            $group_conditions =
                isset( $group['conditions'] ) &&
                is_array( $group['conditions'] )
                    ? $group['conditions']
                    : array();

            $group_result =
                $this->resolve_group_result(
                    $group_index,
                    $group_conditions,
                    $condition_results
                );
            ?>

            <div
                style="
                    margin-bottom:15px;
                    padding:16px;
                    border:1px solid #c7d7e3;
                    background:#f8fbfd;
                    border-radius:4px;
                "
            >

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:15px;
                        margin-bottom:12px;
                    "
                >

                    <strong
                        style="
                            font-size:15px;
                        "
                    >
                        گروه <?php echo esc_html( $group_index + 1 ); ?>
                    </strong>

                    <div>
                        <?php
                        $this->render_condition_result_badge(
                            $group_result
                        );
                        ?>
                    </div>

                </div>

                <?php if ( empty( $group_conditions ) ) : ?>

                    <div class="notice notice-warning inline">
                        <p>
                            این گروه شرطی ندارد.
                        </p>
                    </div>

                <?php else : ?>

                    <?php foreach ( $group_conditions as $condition_index => $condition ) : ?>

                        <?php if ( $condition_index > 0 ) : ?>

                            <?php
                            $this->render_logic_separator_badge(
                                'AND'
                            );
                            ?>

                        <?php endif; ?>

                        <?php
                        $field =
                            isset( $condition['field'] )
                                ? sanitize_key(
                                    $condition['field']
                                )
                                : '';

                        $operator =
                            isset( $condition['operator'] )
                                ? sanitize_key(
                                    $condition['operator']
                                )
                                : '';

                        $value =
                            isset( $condition['value'] )
                                ? $condition['value']
                                : '';

                        $condition_passed =
                            $this->get_group_condition_result(
                                $group_index,
                                $condition_index,
                                $condition_results
                            );
                        ?>

                        <div
                            style="
                                margin-bottom:0;
                                padding:14px;
                                border:1px solid #e2e4e7;
                                background:#fff;
                            "
                        >

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:space-between;
                                    gap:15px;
                                "
                            >

                                <div>

                                    <strong>
                                        شرط <?php echo esc_html( $condition_index + 1 ); ?>
                                    </strong>

                                    <div
                                        style="
                                            margin-top:6px;
                                        "
                                    >

                                        <?php
                                        echo esc_html(
                                            $this->get_condition_display(
                                                $field,
                                                $operator,
                                                $value
                                            )
                                        );
                                        ?>

                                    </div>

                                </div>

                                <?php
                                $this->render_condition_result_badge(
                                    $condition_passed
                                );
                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <?php }

    }

    /**
     * Resolve a legacy flat condition result.
     *
     * @param int   $index             Condition index.
     * @param array $conditions        Flat condition snapshot.
     * @param array $condition_results Explicit result snapshot.
     * @param array $execution         Execution record.
     * @param string $execution_status Execution status.
     *
     * @return bool|null
     */
    private function resolve_legacy_condition_result(
        $index,
        $conditions,
        $condition_results,
        $execution,
        $execution_status
    ) {

        $condition_passed =
            null;

        if (
            array_key_exists(
                $index,
                $condition_results
            )
        ) {

            $condition_result =
                $condition_results[
                    $index
                ];

            $condition_passed =
                $this->normalize_result_value(
                    $condition_result
                );

        } elseif (
            isset(
                $execution['condition_result']
            ) &&
            is_array(
                $execution['condition_result']
            ) &&
            array_key_exists(
                $index,
                $execution['condition_result']
            )
        ) {

            $condition_passed =
                (bool)
                $execution['condition_result'][
                    $index
                ];

        } elseif (
            1 === count(
                $conditions
            ) &&
            array_key_exists(
                'condition_result',
                $execution
            ) &&
            ! is_array(
                $execution['condition_result']
            ) &&
            null !==
            $execution['condition_result']
        ) {

            $condition_passed =
                (bool)
                $execution['condition_result'];

        } elseif (
            in_array(
                $execution_status,
                array(
                    'running',
                    'completed',
                    'failed',
                ),
                true
            )
        ) {

            $condition_passed =
                true;
        }

        return $condition_passed;
    }

    /**
     * Resolve a stored group result, when present.
     *
     * @param int   $group_index        Group index.
     * @param array $group_conditions   Conditions inside the group.
     * @param array $condition_results  Stored result snapshot.
     *
     * @return bool|null
     */
    private function resolve_group_result(
        $group_index,
        $group_conditions,
        $condition_results
    ) {

        $group_results =
            $this->extract_group_evaluation_results(
                $condition_results
            );

        if (
            array_key_exists(
                $group_index,
                $group_results
            )
        ) {

            return $this->normalize_result_value(
                $group_results[
                    $group_index
                ]
            );
        }

        if (
            isset(
                $group_results['groups']
            ) &&
            is_array(
                $group_results['groups']
            ) &&
            array_key_exists(
                $group_index,
                $group_results['groups']
            )
        ) {

            $group_result =
                $group_results['groups'][
                    $group_index
                ];

            if ( is_array( $group_result ) ) {

                if ( array_key_exists( 'matched', $group_result ) ) {
                    return (bool) $group_result['matched'];
                }

                if ( array_key_exists( 'passed', $group_result ) ) {
                    return (bool) $group_result['passed'];
                }

                if ( array_key_exists( 'success', $group_result ) ) {
                    return (bool) $group_result['success'];
                }
            }

            return $this->normalize_result_value(
                $group_result
            );
        }

        return null;
    }

    /**
     * Get one condition result from grouped result data, when present.
     *
     * @param int   $group_index       Group index.
     * @param int   $condition_index   Condition index.
     * @param array $condition_results Stored result snapshot.
     *
     * @return bool|null
     */
    private function get_group_condition_result(
        $group_index,
        $condition_index,
        $condition_results
    ) {

        $groups =
            $this->extract_group_evaluation_results(
                $condition_results
            );

        if (
            isset( $groups['groups'] ) &&
            is_array( $groups['groups'] ) &&
            isset( $groups['groups'][ $group_index ] )
        ) {

            $group =
                $groups['groups'][
                    $group_index
                ];

            if (
                is_array( $group ) &&
                isset( $group['conditions'] ) &&
                is_array( $group['conditions'] ) &&
                array_key_exists(
                    $condition_index,
                    $group['conditions']
                )
            ) {

                return $this->normalize_result_value(
                    $group['conditions'][
                        $condition_index
                    ]
                );
            }
        }

        if (
            isset( $groups[ $group_index ] ) &&
            is_array( $groups[ $group_index ] )
        ) {

            $group =
                $groups[
                    $group_index
                ];

            if (
                isset( $group['conditions'] ) &&
                is_array( $group['conditions'] ) &&
                array_key_exists(
                    $condition_index,
                    $group['conditions']
                )
            ) {

                return $this->normalize_result_value(
                    $group['conditions'][
                        $condition_index
                    ]
                );
            }

            if (
                array_key_exists(
                    $condition_index,
                    $group
                )
            ) {

                return $this->normalize_result_value(
                    $group[
                        $condition_index
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Extract possible grouped evaluation results from a stored snapshot.
     *
     * @param array $condition_results Result snapshot.
     *
     * @return array
     */
    private function extract_group_evaluation_results(
        $condition_results
    ) {

        if ( ! is_array( $condition_results ) ) {
            return array();
        }

        if (
            isset(
                $condition_results['groups']
            ) &&
            is_array(
                $condition_results['groups']
            )
        ) {

            return $condition_results;
        }

        if (
            isset(
                $condition_results['condition_evaluation']
            ) &&
            is_array(
                $condition_results['condition_evaluation']
            )
        ) {

            $evaluation =
                $condition_results[
                    'condition_evaluation'
                ];

            if (
                isset( $evaluation['groups'] ) &&
                is_array( $evaluation['groups'] )
            ) {
                return $evaluation;
            }
        }

        return array();
    }

    /**
     * Normalize an arbitrary stored result value to bool/null.
     *
     * @param mixed $value Result value.
     *
     * @return bool|null
     */
    private function normalize_result_value( $value ) {

        if ( is_array( $value ) ) {

            if ( array_key_exists( 'matched', $value ) ) {
                return (bool) $value['matched'];
            }

            if ( array_key_exists( 'passed', $value ) ) {
                return (bool) $value['passed'];
            }

            if ( array_key_exists( 'success', $value ) ) {
                return (bool) $value['success'];
            }

            return null;
        }

        if ( null === $value ) {
            return null;
        }

        return (bool) $value;
    }

    /**
     * Render a logic separator badge.
     *
     * @param string $logic Logic token.
     *
     * @return void
     */
    private function render_logic_separator_badge( $logic ) {
        ?>

        <div
            style="
                display:flex;
                justify-content:center;
                align-items:center;
                margin:10px 0;
            "
        >

            <span
                style="
                    display:inline-block;
                    min-width:55px;
                    text-align:center;
                    padding:4px 10px;
                    border:1px solid #a7aaad;
                    background:#f6f7f7;
                    color:#50575e;
                    font-weight:700;
                    border-radius:3px;
                "
            >
                <?php echo esc_html( $logic ); ?>
            </span>

        </div>

        <?php
    }

    /**
     * Get condition display text.
     *
     * Supports scalar values and range values.
     *
     * @param string $field    Condition field.
     * @param string $operator Operator.
     * @param mixed  $value    Condition value.
     *
     * @return string
     */
    private function get_condition_display(
        $field,
        $operator,
        $value
    ) {

        $field_labels = array(
            'order_total' =>
                'مبلغ سفارش',

            'order_status' =>
                'وضعیت سفارش',
        );

        $operator_labels = array(
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

            'in_range' =>
                'در بازه',

            'not_in_range' =>
                'خارج از بازه',
        );

        $field_label =
            isset(
                $field_labels[
                    $field
                ]
            )
                ? $field_labels[
                    $field
                ]
                : $field;

        $operator_label =
            isset(
                $operator_labels[
                    $operator
                ]
            )
                ? $operator_labels[
                    $operator
                ]
                : $operator;

        if (
            'order_status' ===
            $field &&
            is_scalar(
                $value
            )
        ) {

            $value =
                $this->get_order_status_label(
                    $value
                );
        }

        if (
            is_array(
                $value
            )
        ) {

            $min =
                '';

            $max =
                '';

            if (
                isset(
                    $value['min']
                )
            ) {

                $min =
                    $value['min'];

            } elseif (
                isset(
                    $value['from']
                )
            ) {

                $min =
                    $value['from'];

            } elseif (
                isset(
                    $value[0]
                )
            ) {

                $min =
                    $value[0];
            }

            if (
                isset(
                    $value['max']
                )
            ) {

                $max =
                    $value['max'];

            } elseif (
                isset(
                    $value['to']
                )
            ) {

                $max =
                    $value['to'];

            } elseif (
                isset(
                    $value[1]
                )
            ) {

                $max =
                    $value[1];
            }

            if (
                'order_total' ===
                $field
            ) {

                $min =
                    $this->format_condition_number(
                        $min,
                        true
                    );

                $max =
                    $this->format_condition_number(
                        $max,
                        true
                    );
            } else {

                $min =
                    $this->format_condition_value(
                        $min
                    );

                $max =
                    $this->format_condition_value(
                        $max
                    );
            }

            if (
                '' !== $min &&
                '' !== $max
            ) {

                return (
                    $field_label .
                    ' ' .
                    $operator_label .
                    ' ' .
                    $min .
                    ' تا ' .
                    $max
                );
            }

            if (
                '' !== $min
            ) {

                return (
                    $field_label .
                    ' ' .
                    $operator_label .
                    ' ' .
                    $min
                );
            }

            if (
                '' !== $max
            ) {

                return (
                    $field_label .
                    ' ' .
                    $operator_label .
                    ' ' .
                    $max
                );
            }

            return (
                $field_label .
                ' ' .
                $operator_label
            );
        }

        if (
            is_string(
                $value
            ) &&
            in_array(
                $operator,
                array(
                    'between',
                    'in_range',
                    'not_in_range',
                ),
                true
            ) &&
            false !==
            strpos(
                $value,
                ','
            )
        ) {

            $parts =
                array_map(
                    'trim',
                    explode(
                        ',',
                        $value,
                        2
                    )
                );

            $min =
                isset(
                    $parts[0]
                )
                    ? $parts[0]
                    : '';

            $max =
                isset(
                    $parts[1]
                )
                    ? $parts[1]
                    : '';

            if (
                'order_total' ===
                $field
            ) {

                $min =
                    $this->format_condition_number(
                        $min,
                        true
                    );

                $max =
                    $this->format_condition_number(
                        $max,
                        true
                    );
            }

            return (
                $field_label .
                ' ' .
                $operator_label .
                ' ' .
                $min .
                ' تا ' .
                $max
            );
        }

        if (
            'order_total' ===
            $field
        ) {

            $value =
                $this->format_condition_number(
                    $value,
                    true
                );

        } else {

            $value =
                $this->format_condition_value(
                    $value
                );
        }

        return (
            $field_label .
            ' ' .
            $operator_label .
            ' ' .
            $value
        );
    }

    /**
     * Format a condition numeric value.
     *
     * @param mixed $value Value.
     * @param bool  $with_unit Add Toman unit.
     *
     * @return string
     */
    private function format_condition_number(
        $value,
        $with_unit = false
    ) {

        if (
            is_array(
                $value
            )
        ) {

            return '';
        }

        $numeric_value =
            str_replace(
                ',',
                '',
                trim(
                    (string) $value
                )
            );

        if (
            '' ===
            $numeric_value ||
            ! is_numeric(
                $numeric_value
            )
        ) {

            return esc_html(
                (string) $value
            );
        }

        $formatted =
            number_format(
                (float)
                $numeric_value,
                0,
                '.',
                ','
            );

        if (
            $with_unit
        ) {

            $formatted .=
                ' تومان';
        }

        return $formatted;
    }

    /**
     * Format a generic condition value safely.
     *
     * @param mixed $value Value.
     *
     * @return string
     */
    private function format_condition_value(
        $value
    ) {

        if (
            is_array(
                $value
            )
        ) {

            return implode(
                '، ',
                array_map(
                    array(
                        $this,
                        'format_condition_value',
                    ),
                    $value
                )
            );
        }

        if (
            is_object(
                $value
            )
        ) {

            return '';
        }

        return (string) $value;
    }

    /**
     * Get Action display text.
     *
     * @param array $action Action snapshot.
     *
     * @return string
     */
    private function get_action_display(
        $action
    ) {

        $type =
            isset(
                $action['type']
            )
                ? sanitize_key(
                    $action['type']
                )
                : '';

        if (
            'change_order_status' ===
            $type
        ) {

            $status =
                isset(
                    $action['status']
                )
                    ? sanitize_key(
                        $action['status']
                    )
                    : '';

            return (
                'تغییر وضعیت سفارش → ' .
                $this->get_order_status_label(
                    $status
                )
            );
        }

        if (
            'notify_admin' ===
            $type
        ) {

            return 'ارسال اعلان به مدیر فروشگاه';
        }

        return $type;
    }

    /**
     * Render a Condition result badge.
     *
     * A null Condition result means the Condition or Group was not evaluated,
     * typically because of logical short-circuiting.
     *
     * @param mixed $result Condition result.
     *
     * @return void
     */
    private function render_condition_result_badge(
        $result
    ) {

        if (
            null ===
            $result
        ) {

            $label =
                'ارزیابی نشد';

            $background =
                '#f6f7f7';

            $border =
                '#ccd0d4';

            $color =
                '#646970';

            ?>
            <span
                style="
                    display:inline-block;
                    padding:3px 8px;
                    border:1px solid <?php echo esc_attr( $border ); ?>;
                    background:<?php echo esc_attr( $background ); ?>;
                    color:<?php echo esc_attr( $color ); ?>;
                    border-radius:3px;
                    font-size:12px;
                    line-height:1.5;
                "
            >
                <?php echo esc_html( $label ); ?>
            </span>
            <?php

            return;
        }

        $this->render_result_badge(
            $result
        );
    }

    /**
     * Render result badge.
     *
     * @param mixed $success Result.
     *
     * @return void
     */
    private function render_result_badge(
        $success
    ) {

        if (
            null ===
            $success
        ) {

            $label =
                'نتیجه ثبت نشده';

            $background =
                '#f6f7f7';

            $border =
                '#ccd0d4';

            $color =
                '#646970';

        } elseif (
            $success
        ) {

            $label =
                '✓ موفق';

            $background =
                '#edfaef';

            $border =
                '#68a56d';

            $color =
                '#176b1f';

        } else {

            $label =
                '✕ ناموفق';

            $background =
                '#fcf0f1';

            $border =
                '#d63638';

            $color =
                '#8a1c1f';
        }
        ?>

        <span
            style="
                display:inline-block;
                padding:5px 10px;
                background:<?php echo esc_attr( $background ); ?>;
                border:1px solid <?php echo esc_attr( $border ); ?>;
                color:<?php echo esc_attr( $color ); ?>;
                font-weight:600;
                white-space:nowrap;
            "
        >
            <?php
            echo esc_html(
                $label
            );
            ?>
        </span>

        <?php
    }

    /**
     * Format execution duration.
     *
     * @param mixed $duration_ms Duration in milliseconds.
     *
     * @return string
     */
    private function format_duration(
        $duration_ms
    ) {

        $duration_ms =
            absint(
                $duration_ms
            );

        if (
            0 ===
            $duration_ms
        ) {

            return 'کمتر از 0.001 ثانیه';
        }

        if (
            $duration_ms < 1000
        ) {

            return (
                number_format(
                    $duration_ms,
                    0
                ) .
                ' ms'
            );
        }

        $seconds =
            $duration_ms /
            1000;

        if (
            $seconds < 60
        ) {

            return (
                number_format(
                    $seconds,
                    2
                ) .
                ' ثانیه'
            );
        }

        $minutes =
            floor(
                $seconds /
                60
            );

        $remaining_seconds =
            $seconds -
            (
                $minutes *
                60
            );

        return (
            number_format(
                $minutes,
                0
            ) .
            ' دقیقه و ' .
            number_format(
                $remaining_seconds,
                2
            ) .
            ' ثانیه'
        );
    }

    /**
     * Render summary card.
     *
     * @param string $label        Card label.
     * @param int    $value        Card value.
     * @param string $border_color Card accent color.
     *
     * @return void
     */
    private function render_summary_card(
        $label,
        $value,
        $border_color
    ) {
        ?>

        <div
            style="
                min-width:180px;
                padding:15px 18px;
                background:#fff;
                border:1px solid #ccd0d4;
                border-right:4px solid <?php echo esc_attr( $border_color ); ?>;
                box-sizing:border-box;
            "
        >

            <div
                style="
                    color:#646970;
                    font-size:13px;
                    margin-bottom:5px;
                "
            >

                <?php
                echo esc_html(
                    $label
                );
                ?>

            </div>

            <strong
                style="
                    display:block;
                    font-size:24px;
                    line-height:1.2;
                "
            >

                <?php
                echo esc_html(
                    $value
                );
                ?>

            </strong>

        </div>

        <?php
    }

    /**
     * Get execution policy label.
     *
     * @param string $policy Policy key.
     *
     * @return string
     */
    private function get_policy_label(
        $policy
    ) {

        $labels = array(
            'all' =>
                'اجرای همه اتوماسیون‌های منطبق',

            'first_match' =>
                'فقط اولین اتوماسیون منطبق',

            'first_success' =>
                'اولین اتوماسیون موفق',
        );

        return isset(
            $labels[
                $policy
            ]
        )
            ? $labels[
                $policy
            ]
            : $policy;
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
     * Get order status label.
     *
     * @param string $status_slug Status slug.
     *
     * @return string
     */
    private function get_order_status_label(
        $status_slug,
        $default_label = null
    ) {

        $status_slug =
            sanitize_key(
                (string) $status_slug
            );

        $status_slug =
            preg_replace(
                '/^wc-/',
                '',
                $status_slug
            );

        if (
            '' ===
            $status_slug
        ) {

            return is_null( $default_label )
                ? ''
                : wp_strip_all_tags(
                    (string) $default_label
                );
        }

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

            'draft' =>
                'پیش‌نویس',
        );

        if (
            function_exists(
                'wc_get_order_statuses'
            )
        ) {

            foreach (
                wc_get_order_statuses()
                as $status_key =>
                $status_label
            ) {

                $woocommerce_slug =
                    sanitize_key(
                        str_replace(
                            'wc-',
                            '',
                            (string) $status_key
                        )
                    );

                if (
                    $woocommerce_slug ===
                    $status_slug
                ) {

                    return wp_strip_all_tags(
                        $status_label
                    );
                }
            }
        }

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
            ! is_null( $default_label )
        ) {

            return wp_strip_all_tags(
                (string) $default_label
            );
        }

        return $status_slug;
    }

    /**
     * Get execution status presentation data.
     *
     * @param string $status Execution status.
     *
     * @return array
     */
    private function get_status_data(
        $status
    ) {

        $statuses = array(
            'running' => array(
                'label' =>
                    'در حال اجرا',

                'background' =>
                    '#f0f6fc',

                'border' =>
                    '#72aee6',

                'color' =>
                    '#135e96',
            ),

            'completed' => array(
                'label' =>
                    'موفق',

                'background' =>
                    '#edfaef',

                'border' =>
                    '#68a56d',

                'color' =>
                    '#176b1f',
            ),

            'failed' => array(
                'label' =>
                    'ناموفق',

                'background' =>
                    '#fcf0f1',

                'border' =>
                    '#d63638',

                'color' =>
                    '#8a1c1f',
            ),

            'conditions_failed' => array(
                'label' =>
                    'شرایط برقرار نبود',

                'background' =>
                    '#fff8e5',

                'border' =>
                    '#dba617',

                'color' =>
                    '#6d5200',
            ),
        );

        return isset(
            $statuses[
                $status
            ]
        )
            ? $statuses[
                $status
            ]
            : array(
                'label' =>
                    $status,

                'background' =>
                    '#f6f7f7',

                'border' =>
                    '#ccd0d4',

                'color' =>
                    '#3c434a',
            );
    }
}
