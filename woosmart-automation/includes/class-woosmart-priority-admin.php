<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooSmart Automation Priority Admin.
 */
class WooSmart_Priority_Admin {

    /**
     * Initialize Priority admin UI.
     */
    public function __construct() {

        add_action(
            'admin_menu',
            array(
                $this,
                'add_admin_menu',
            )
        );

        add_action(
            'admin_post_woosmart_save_priorities',
            array(
                $this,
                'save_priorities',
            )
        );
    }

    /**
     * Add Priority management page.
     *
     * @return void
     */
    public function add_admin_menu() {

        add_submenu_page(
            'woosmart-automation',
            'اولویت اجرا',
            'اولویت اجرا',
            'manage_options',
            'woosmart-priorities',
            array(
                $this,
                'render_page',
            )
        );
    }

    /**
     * Save automation priorities.
     *
     * @return void
     */
    public function save_priorities() {

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
            'woosmart_save_priorities',
            'woosmart_priorities_nonce'
        );

        $priorities =
            isset(
                $_POST['priority']
            ) &&
            is_array(
                $_POST['priority']
            )
                ? wp_unslash(
                    $_POST['priority']
                )
                : array();

        foreach (
            $priorities as $automation_id =>
            $priority
        ) {

            $automation_id =
                absint(
                    $automation_id
                );

            if (
                ! $automation_id
            ) {
                continue;
            }

            if (
                'woosmart_automation' !==
                get_post_type(
                    $automation_id
                )
            ) {
                continue;
            }

            $priority =
                absint(
                    $priority
                );

            if (
                $priority < 1
            ) {

                $priority =
                    1;
            }

            update_post_meta(
                $automation_id,
                '_woosmart_priority',
                $priority
            );
        }

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' =>
                        'woosmart-priorities',

                    'saved' =>
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
     * Render Priority management page.
     *
     * @return void
     */
    public function render_page() {

        $automations =
            get_posts(
                array(
                    'post_type' =>
                        'woosmart_automation',

                    'post_status' =>
                        'publish',

                    'posts_per_page' =>
                        -1,

                    'orderby' =>
                        'date',

                    'order' =>
                        'DESC',
                )
            );

        /*
         * Calculate display priority.
         *
         * Explicit priorities are preserved.
         * Missing priorities receive a temporary fallback
         * based on the current newest-to-oldest order.
         */
        $max_explicit_priority =
            0;

        foreach (
            $automations as $automation
        ) {

            $stored_priority =
                get_post_meta(
                    $automation->ID,
                    '_woosmart_priority',
                    true
                );

            if (
                '' !==
                $stored_priority
            ) {

                $stored_priority =
                    absint(
                        $stored_priority
                    );

                if (
                    $stored_priority >
                    $max_explicit_priority
                ) {

                    $max_explicit_priority =
                        $stored_priority;
                }
            }
        }

        $fallback_base =
            max(
                0,
                $max_explicit_priority
            ) + 10;

        $fallback_counter =
            0;
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                اولویت اجرای اتوماسیون‌ها
            </h1>

            <p
                style="
                    max-width:900px;
                "
            >
                هرچه عدد اولویت کمتر باشد، اتوماسیون زودتر بررسی می‌شود.
                اگر دو اتوماسیون اولویت یکسان داشته باشند، اتوماسیون جدیدتر زودتر بررسی می‌شود.
            </p>

            <div
                class="notice notice-info"
                style="max-width:900px;"
            >

                <p>
                    اتوماسیون‌هایی که هنوز اولویت ذخیره‌شده ندارند، فعلاً به‌ترتیب جدیدترین به قدیمی‌ترین اجرا می‌شوند. با تعیین Priority، ترتیب اجرای آن‌ها کاملاً تحت کنترل شما قرار می‌گیرد.
                </p>

            </div>

            <?php if ( isset( $_GET['saved'] ) ) : ?>

                <div
                    class="notice notice-success is-dismissible"
                    style="max-width:900px;"
                >

                    <p>
                        اولویت‌ها با موفقیت ذخیره شدند.
                    </p>

                </div>

            <?php endif; ?>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                style="max-width:1100px;"
            >

                <input
                    type="hidden"
                    name="action"
                    value="woosmart_save_priorities"
                >

                <?php
                wp_nonce_field(
                    'woosmart_save_priorities',
                    'woosmart_priorities_nonce'
                );
                ?>

                <table
                    class="widefat fixed striped"
                    style="margin-top:20px;"
                >

                    <thead>

                        <tr>

                            <th style="width:80px;">
                                شناسه
                            </th>

                            <th>
                                اتوماسیون
                            </th>

                            <th style="width:180px;">
                                Priority
                            </th>

                            <th style="width:180px;">
                                وضعیت
                            </th>

                            <th style="width:180px;">
                                تاریخ ایجاد
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ( empty( $automations ) ) : ?>

                            <tr>

                                <td colspan="5">

                                    هنوز هیچ اتوماسیونی ساخته نشده است.

                                </td>

                            </tr>

                        <?php else : ?>

                            <?php foreach (
                                $automations
                                as $automation
                            ) : ?>

                                <?php

                                $automation_id =
                                    absint(
                                        $automation->ID
                                    );

                                $stored_priority =
                                    get_post_meta(
                                        $automation_id,
                                        '_woosmart_priority',
                                        true
                                    );

                                $has_explicit_priority =
                                    '' !==
                                    $stored_priority;

                                if (
                                    $has_explicit_priority
                                ) {

                                    $display_priority =
                                        absint(
                                            $stored_priority
                                        );

                                } else {

                                    $display_priority =
                                        $fallback_base +
                                        $fallback_counter;

                                    $fallback_counter += 10;
                                }

                                $status =
                                    get_post_meta(
                                        $automation_id,
                                        '_woosmart_status',
                                        true
                                    );

                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            #<?php echo esc_html( $automation_id ); ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $automation->post_title
                                            );
                                            ?>
                                        </strong>

                                        <div
                                            style="
                                                color:#646970;
                                                margin-top:4px;
                                                font-size:12px;
                                            "
                                        >
                                            رویداد:
                                            <?php
                                            echo esc_html(
                                                $this->get_trigger_label(
                                                    get_post_meta(
                                                        $automation_id,
                                                        '_woosmart_trigger',
                                                        true
                                                    )
                                                )
                                            );
                                            ?>
                                        </div>

                                    </td>

                                    <td>

                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            name="priority[<?php echo esc_attr( $automation_id ); ?>]"
                                            value="<?php echo esc_attr( $display_priority ); ?>"
                                            style="
                                                width:120px;
                                            "
                                        >

                                    </td>

                                    <td>

                                        <?php if ( 'active' === $status ) : ?>

                                            <span
                                                style="
                                                    display:inline-block;
                                                    padding:4px 8px;
                                                    background:#edfaef;
                                                    border:1px solid #68a56d;
                                                    color:#176b1f;
                                                    font-weight:600;
                                                "
                                            >
                                                فعال
                                            </span>

                                        <?php else : ?>

                                            <span
                                                style="
                                                    display:inline-block;
                                                    padding:4px 8px;
                                                    background:#fff8e5;
                                                    border:1px solid #dba617;
                                                    color:#6d5200;
                                                    font-weight:600;
                                                "
                                            >
                                                غیرفعال
                                            </span>

                                        <?php endif; ?>

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

                        <?php endif; ?>

                    </tbody>

                </table>

                <?php if ( ! empty( $automations ) ) : ?>

                    <?php
                    submit_button(
                        'ذخیره اولویت‌ها'
                    );
                    ?>

                <?php endif; ?>

            </form>

            <div
                style="
                    max-width:900px;
                    margin-top:25px;
                    background:#fff;
                    border:1px solid #ccd0d4;
                    padding:18px;
                "
            >

                <h2
                    style="margin-top:0;"
                >
                    مثال
                </h2>

                <table
                    class="widefat"
                    style="max-width:700px;"
                >

                    <thead>

                        <tr>
                            <th>
                                Priority
                            </th>

                            <th>
                                ترتیب
                            </th>

                            <th>
                                Automation
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>
                            <td>
                                1
                            </td>

                            <td>
                                اول
                            </td>

                            <td>
                                Automation A
                            </td>
                        </tr>

                        <tr>
                            <td>
                                10
                            </td>

                            <td>
                                دوم
                            </td>

                            <td>
                                Automation B
                            </td>
                        </tr>

                        <tr>
                            <td>
                                20
                            </td>

                            <td>
                                سوم
                            </td>

                            <td>
                                Automation C
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

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
            $labels[
                $trigger
            ]
        )
            ? $labels[
                $trigger
            ]
            : $trigger;
    }
}
