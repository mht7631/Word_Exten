<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooSmart Notification Settings.
 *
 * Provides a dedicated recipient email setting for WooSmart notifications
 * without changing the WordPress administrator email or SMTP configuration.
 */
class WooSmart_Notification_Settings {

    /**
     * Option name used for the WooSmart notification recipient.
     *
     * @var string
     */
    const RECIPIENT_OPTION = 'woosmart_notification_email';

    /**
     * Initialize settings functionality.
     */
    public function __construct() {

        add_action(
            'admin_menu',
            array(
                $this,
                'add_settings_page',
            )
        );

        add_action(
            'admin_post_woosmart_save_notification_settings',
            array(
                $this,
                'save_settings',
            )
        );

        add_action(
            'admin_post_woosmart_send_test_notification',
            array(
                $this,
                'send_test_notification',
            )
        );
    }

    /**
     * Add the Notifications submenu.
     *
     * @return void
     */
    public function add_settings_page() {

        add_submenu_page(
            'woosmart-automation',
            'تنظیمات اعلان‌ها',
            'تنظیمات اعلان‌ها',
            'manage_options',
            'woosmart-notification-settings',
            array(
                $this,
                'render_settings_page',
            )
        );
    }

    /**
     * Get the configured notification recipient.
     *
     * Falls back to the WordPress administrator email when no custom
     * WooSmart recipient has been configured.
     *
     * @return string
     */
    public static function get_recipient_email() {

        $configured_email = sanitize_email(
            get_option(
                self::RECIPIENT_OPTION,
                ''
            )
        );

        if (
            ! empty( $configured_email ) &&
            is_email( $configured_email )
        ) {

            return $configured_email;
        }

        return sanitize_email(
            get_option(
                'admin_email',
                ''
            )
        );
    }

    /**
     * Render the notification settings page.
     *
     * @return void
     */
    public function render_settings_page() {

        if (
            ! current_user_can( 'manage_options' )
        ) {
            return;
        }

        $configured_email = sanitize_email(
            get_option(
                self::RECIPIENT_OPTION,
                ''
            )
        );

        $fallback_email = sanitize_email(
            get_option(
                'admin_email',
                ''
            )
        );

        $active_email = self::get_recipient_email();

        $message = isset( $_GET['woosmart_notice'] )
            ? sanitize_key( wp_unslash( $_GET['woosmart_notice'] ) )
            : '';
        ?>

        <div
            class="wrap"
            dir="rtl"
        >

            <h1>
                تنظیمات اعلان‌ها
            </h1>

            <hr>

            <?php if ( 'saved' === $message ) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        تنظیمات اعلان‌ها با موفقیت ذخیره شد.
                    </p>
                </div>

            <?php elseif ( 'test_sent' === $message ) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        ایمیل آزمایشی با موفقیت به آدرس تنظیم‌شده ارسال شد.
                    </p>
                </div>

            <?php elseif ( 'invalid_email' === $message ) : ?>

                <div class="notice notice-error is-dismissible">
                    <p>
                        آدرس ایمیل واردشده معتبر نیست.
                    </p>
                </div>

            <?php elseif ( 'test_failed' === $message ) : ?>

                <div class="notice notice-error is-dismissible">
                    <p>
                        ارسال ایمیل آزمایشی ناموفق بود. تنظیمات SMTP و گزارش خطای ایمیل را بررسی کنید.
                    </p>
                </div>

            <?php endif; ?>

            <h2>
                ایمیل دریافت اعلان‌ها
            </h2>

            <p>
                اعلان‌های WooSmart به این آدرس ارسال می‌شوند. سرویس ارسال ایمیل توسط WordPress و Mail Transport سایت تعیین می‌شود و WooSmart تنظیمات SMTP را مدیریت نمی‌کند.
            </p>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="woosmart_save_notification_settings"
                >

                <?php
                wp_nonce_field(
                    'woosmart_save_notification_settings',
                    'woosmart_notification_nonce'
                );
                ?>

                <table class="form-table">

                    <tr>

                        <th scope="row">
                            <label for="woosmart_notification_email">
                                ایمیل دریافت اعلان
                            </label>
                        </th>

                        <td>

                            <input
                                type="email"
                                id="woosmart_notification_email"
                                name="woosmart_notification_email"
                                class="regular-text"
                                value="<?php echo esc_attr( $configured_email ); ?>"
                                placeholder="<?php echo esc_attr( $fallback_email ); ?>"
                            >

                            <p class="description">
                                اگر این فیلد خالی باشد، WooSmart از ایمیل مدیر وردپرس استفاده می‌کند:
                                <strong><?php echo esc_html( $fallback_email ); ?></strong>
                            </p>

                        </td>

                    </tr>

                </table>

                <?php submit_button( 'ذخیره تنظیمات' ); ?>

            </form>

            <hr>

            <h2>
                ارسال ایمیل آزمایشی
            </h2>

            <p>
                با این گزینه می‌توانید قبل از استفاده از اتوماسیون‌ها، ارتباط WooSmart با سیستم ارسال ایمیل WordPress را آزمایش کنید.
            </p>

            <p>
                <strong>
                    مقصد فعلی:
                </strong>
                <?php echo esc_html( $active_email ); ?>
            </p>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="woosmart_send_test_notification"
                >

                <?php
                wp_nonce_field(
                    'woosmart_send_test_notification',
                    'woosmart_notification_test_nonce'
                );
                ?>

                <?php
                submit_button(
                    'ارسال ایمیل آزمایشی',
                    'secondary',
                    'submit',
                    false
                );
                ?>

            </form>

            <p class="description" style="margin-top:12px;">
                WooSmart فقط از <code>wp_mail()</code> استفاده می‌کند. فرستنده، SMTP و سرویس‌دهنده ایمیل توسط Mail Transport سایت مدیریت می‌شوند.
            </p>

        </div>

        <?php
    }

    /**
     * Save notification settings.
     *
     * @return void
     */
    public function save_settings() {

        if (
            ! current_user_can( 'manage_options' )
        ) {

            wp_die(
                esc_html__(
                    'دسترسی غیرمجاز.',
                    'woosmart-automation'
                )
            );
        }

        check_admin_referer(
            'woosmart_save_notification_settings',
            'woosmart_notification_nonce'
        );

        $email = isset( $_POST['woosmart_notification_email'] )
            ? sanitize_email(
                wp_unslash(
                    $_POST['woosmart_notification_email']
                )
            )
            : '';

        if (
            '' !== $email &&
            ! is_email( $email )
        ) {

            $this->redirect_with_notice(
                'invalid_email'
            );
        }

        if ( '' === $email ) {

            delete_option(
                self::RECIPIENT_OPTION
            );

        } else {

            update_option(
                self::RECIPIENT_OPTION,
                $email,
                false
            );
        }

        $this->redirect_with_notice(
            'saved'
        );
    }

    /**
     * Send a test notification through WordPress mail transport.
     *
     * @return void
     */
    public function send_test_notification() {

        if (
            ! current_user_can( 'manage_options' )
        ) {

            wp_die(
                esc_html__(
                    'دسترسی غیرمجاز.',
                    'woosmart-automation'
                )
            );
        }

        check_admin_referer(
            'woosmart_send_test_notification',
            'woosmart_notification_test_nonce'
        );

        $recipient = self::get_recipient_email();

        if (
            empty( $recipient ) ||
            ! is_email( $recipient )
        ) {

            $this->redirect_with_notice(
                'invalid_email'
            );
        }

        $subject =
            'تست ارسال ایمیل WooSmart Automation';

        $message =
            "این یک ایمیل آزمایشی از WooSmart Automation است.\n\n" .
            "اگر این پیام را دریافت کرده‌اید، سیستم ارسال ایمیل WordPress سایت به‌درستی کار می‌کند.";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        $sent = wp_mail(
            $recipient,
            $subject,
            $message,
            $headers
        );

        $this->redirect_with_notice(
            $sent
                ? 'test_sent'
                : 'test_failed'
        );
    }

    /**
     * Redirect back to the settings page with a notice.
     *
     * @param string $notice Notice key.
     *
     * @return void
     */
    private function redirect_with_notice(
        $notice
    ) {

        $url = add_query_arg(
            array(
                'page' => 'woosmart-notification-settings',
                'woosmart_notice' => sanitize_key( $notice ),
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $url );
        exit;
    }
}
