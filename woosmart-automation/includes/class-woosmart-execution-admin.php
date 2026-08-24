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

        $policy = isset(
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
     * Render Execution History page.
     *
     * @return void
     */
    public function render_executions_page() {

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
                    $summary['all'],
                    '#2271b1'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'موفق',
                    $summary['completed'],
                    '#008a20'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'ناموفق',
                    $summary['failed'],
                    '#b32d2e'
                );
                ?>

                <?php
                $this->render_summary_card(
                    'شرایط برقرار نبود',
                    $summary['conditions_failed'],
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

                            <tr>

                                <td>
                                    #<?php echo esc_html( $execution['id'] ); ?>
                                </td>

                                <td>

                                    <strong>
                                        #<?php echo esc_html( $execution['automation_id'] ); ?>
                                    </strong>

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
                                            $execution['trigger_key']
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo esc_html(
                                        $this->get_policy_label(
                                            $execution['execution_policy']
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    $status_data =
                                        $this->get_status_data(
                                            $execution['status']
                                        );
                                    ?>

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
                                        $execution['actions_total']
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
                                        $execution['started_at']
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
                                        $execution['message']
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
                                            '' !== $status_filter
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
            0 === $duration_ms
        ) {

            return 'کمتر از 0.001 ثانیه';
        }

        if (
            $duration_ms < 1000
        ) {

            return number_format(
                $duration_ms,
                0
            ) . ' ms';
        }

        $seconds =
            $duration_ms / 1000;

        if (
            $seconds < 60
        ) {

            return number_format(
                $seconds,
                2
            ) . ' ثانیه';
        }

        $minutes =
            floor(
                $seconds / 60
            );

        $remaining_seconds =
            $seconds -
            (
                $minutes * 60
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
            $labels[ $policy ]
        )
            ? $labels[ $policy ]
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
            $labels[ $trigger ]
        )
            ? $labels[ $trigger ]
            : $trigger;
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
            $statuses[ $status ]
        )
            ? $statuses[ $status ]
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
