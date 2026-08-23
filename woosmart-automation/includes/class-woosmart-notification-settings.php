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
     * Transient prefix used for temporary mail test diagnostics.
     *
     * @var string
     */
    const TEST_ERROR_TRANSIENT_PREFIX =
        'woosmart_test_mail_error_';

    /**
     * Whether the current request is capturing
     * a WooSmart test mail failure.
     *
     * @var bool
     */
    private $capturing_test_mail_error = false;

    /**
     * Last captured WordPress mail error.
     *
     * @var WP_Error|null
     */
    private $last_mail_error = null;

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

        /*
         * Capture the actual WordPress / PHPMailer error
         * only while WooSmart is sending its own test email.
         */
        add_action(
            'wp_mail_failed',
            array(
                $this,
                'capture_mail_error',
            ),
            10,
            1
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
     * Capture wp_mail() failure details.
     *
     * This is intentionally provider-agnostic.
     * WooSmart stores only the WordPress WP_Error object and
     * later presents its safe diagnostic information.
     *
     * @param WP_Error $error WordPress mail error.
     *
     * @return void
     */
    public function capture_mail_error(
        $error
    ) {

        if (
            ! $this->capturing_test_mail_error
        ) {

            return;
        }

        if (
            $error instanceof WP_Error
        ) {

            $this->last_mail_error =
                $error;

            return;
        }

        $this->last_mail_error =
            new WP_Error(
                'wp_mail_failed',
                'خطای نامشخص در سیستم ارسال ایمیل.'
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

        $active_email =
            self::get_recipient_email();

        $message = isset(
            $_GET['woosmart_notice']
        )
            ? sanitize_key(
                wp_unslash(
                    $_GET['woosmart_notice']
                )
            )
            : '';

        $test_error =
            $this->get_test_error();

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

                <div
                    class="notice notice-success is-dismissible"
                >

                    <p>
                        تنظیمات اعلان‌ها با موفقیت ذخیره شد.
                    </p>

                </div>

            <?php elseif ( 'test_sent' === $message ) : ?>

                <div
                    class="notice notice-success is-dismissible"
                >

                    <p>
                        ایمیل آزمایشی با موفقیت به آدرس تنظیم‌شده ارسال شد.
                    </p>

                </div>

            <?php elseif ( 'invalid_email' === $message ) : ?>

                <div
                    class="notice notice-error is-dismissible"
                >

                    <p>
                        آدرس ایمیل واردشده معتبر نیست.
                    </p>

                </div>

            <?php elseif ( 'test_failed' === $message ) : ?>

                <div
                    class="notice notice-error"
                >

                    <p>
                        <strong>
                            ارسال ایمیل آزمایشی ناموفق بود.
                        </strong>
                    </p>

                    <p>
                        سیستم ارسال ایمیل WordPress یا سرویس ایمیل تنظیم‌شده، ارسال پیام را رد کرده است.
                    </p>

                </div>

                <?php if ( ! empty( $test_error ) ) : ?>

                    <div
                        class="notice notice-warning"
                        style="
                            max-width:900px;
                            padding:12px 16px;
                        "
                    >

                        <p>
                            <strong>
                                تشخیص خطا
                            </strong>
                        </p>

                        <table
                            style="
                                width:100%;
                                max-width:850px;
                            "
                        >

                            <?php if ( ! empty( $test_error['category'] ) ) : ?>

                                <tr>

                                    <td
                                        style="
                                            width:160px;
                                            font-weight:600;
                                            padding:6px 8px 6px 0;
                                        "
                                    >
                                        نوع خطا:
                                    </td>

                                    <td
                                        style="
                                            padding:6px 0;
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $test_error['category']
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endif; ?>

                            <?php if ( ! empty( $test_error['code'] ) ) : ?>

                                <tr>

                                    <td
                                        style="
                                            width:160px;
                                            font-weight:600;
                                            padding:6px 8px 6px 0;
                                        "
                                    >
                                        کد خطا:
                                    </td>

                                    <td
                                        style="
                                            padding:6px 0;
                                            direction:ltr;
                                            text-align:left;
                                        "
                                    >
                                        <code>
                                            <?php
                                            echo esc_html(
                                                $test_error['code']
                                            );
                                            ?>
                                        </code>
                                    </td>

                                </tr>

                            <?php endif; ?>

                            <?php if ( ! empty( $test_error['message'] ) ) : ?>

                                <tr>

                                    <td
                                        style="
                                            width:160px;
                                            font-weight:600;
                                            vertical-align:top;
                                            padding:6px 8px 6px 0;
                                        "
                                    >
                                        جزئیات:
                                    </td>

                                    <td
                                        style="
                                            padding:6px 0;
                                            direction:ltr;
                                            text-align:left;
                                            word-break:break-word;
                                        "
                                    >
                                        <code>
                                            <?php
                                            echo esc_html(
                                                $test_error['message']
                                            );
                                            ?>
                                        </code>
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </table>

                        <p
                            class="description"
                            style="margin-top:10px;"
                        >
                            این خطا مستقیماً از سیستم ارسال ایمیل WordPress دریافت شده است. WooSmart به سرویس‌دهنده خاصی وابسته نیست و جزئیات نمایش‌داده‌شده می‌تواند مربوط به SMTP، احراز هویت، فرستنده، گیرنده، اتصال شبکه یا سرویس ایمیل مورد استفاده باشد.
                        </p>

                    </div>

                <?php endif; ?>

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

                            <label
                                for="woosmart_notification_email"
                            >
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

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $fallback_email
                                    );
                                    ?>
                                </strong>

                            </p>

                        </td>

                    </tr>

                </table>

                <?php
                submit_button(
                    'ذخیره تنظیمات'
                );
                ?>

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

                <?php
                echo esc_html(
                    $active_email
                );
                ?>

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

            <p
                class="description"
                style="margin-top:12px;"
            >
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

        $email =
            isset(
                $_POST['woosmart_notification_email']
            )
                ? sanitize_email(
                    wp_unslash(
                        $_POST['woosmart_notification_email']
                    )
                )
                : '';

        if (
            '' !== $email &&
            ! is_email(
                $email
            )
        ) {

            $this->redirect_with_notice(
                'invalid_email'
            );
        }

        if (
            '' === $email
        ) {

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

        $recipient =
            self::get_recipient_email();

        if (
            empty( $recipient ) ||
            ! is_email(
                $recipient
            )
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

        /*
         * Reset previous diagnostic information.
         */
        $this->last_mail_error =
            null;

        /*
         * Capture only errors produced by this specific
         * WooSmart test email.
         */
        $this->capturing_test_mail_error =
            true;

        $sent =
            wp_mail(
                $recipient,
                $subject,
                $message,
                $headers
            );

        $this->capturing_test_mail_error =
            false;

        if (
            $sent
        ) {

            $this->redirect_with_notice(
                'test_sent'
            );
        }

        /*
         * Build a provider-agnostic diagnostic.
         */
        $diagnostic =
            $this->build_mail_error_diagnostic();

        /*
         * Store the diagnostic temporarily so it survives
         * the admin redirect without polluting permanent options.
         */
        $this->store_test_error(
            $diagnostic
        );

        $this->redirect_with_notice(
            'test_failed'
        );
    }

    /**
     * Build a generic mail diagnostic from WP_Error.
     *
     * @return array
     */
    private function build_mail_error_diagnostic() {

        $diagnostic = array(
            'category' =>
                'خطای عمومی سیستم ارسال ایمیل',

            'code' =>
                '',

            'message' =>
                'wp_mail() بدون ارائه جزئیات بیشتر، مقدار false برگرداند.',
        );

        if (
            ! $this->last_mail_error
            instanceof WP_Error
        ) {

            return $diagnostic;
        }

        $error_code =
            $this->last_mail_error
                ->get_error_code();

        $error_message =
            $this->last_mail_error
                ->get_error_message();

        $error_data =
            $this->last_mail_error
                ->get_error_data();

        if (
            ! empty(
                $error_code
            )
        ) {

            $diagnostic['code'] =
                (string)
                $error_code;
        }

        if (
            ! empty(
                $error_message
            )
        ) {

            $diagnostic['message'] =
                (string)
                $error_message;
        }

        /*
         * PHPMailer may expose a more specific exception code.
         */
        if (
            is_array(
                $error_data
            ) &&
            isset(
                $error_data[
                    'phpmailer_exception_code'
                ]
            )
        ) {

            $diagnostic['code'] =
                (string)
                $error_data[
                    'phpmailer_exception_code'
                ];
        }

        $diagnostic['category'] =
            $this->classify_mail_error(
                $diagnostic['message'],
                $diagnostic['code']
            );

        return $diagnostic;
    }

    /**
     * Classify a mail error without depending on a specific provider.
     *
     * This method intentionally uses generic mail-related
     * error patterns only.
     *
     * @param string $message Error message.
     * @param string $code    Error code.
     *
     * @return string
     */
    private function classify_mail_error(
        $message,
        $code
    ) {

        $haystack =
            strtolower(
                $message . ' ' . $code
            );

        if (
            false !== strpos(
                $haystack,
                'authenticate'
            ) ||
            false !== strpos(
                $haystack,
                'authentication'
            ) ||
            false !== strpos(
                $haystack,
                'auth failed'
            ) ||
            false !== strpos(
                $haystack,
                'credentials'
            )
        ) {

            return 'خطای احراز هویت سرویس ایمیل';
        }

        if (
            false !== strpos(
                $haystack,
                'connect'
            ) ||
            false !== strpos(
                $haystack,
                'connection'
            ) ||
            false !== strpos(
                $haystack,
                'timeout'
            ) ||
            false !== strpos(
                $haystack,
                'timed out'
            )
        ) {

            return 'خطای اتصال به سرویس ایمیل';
        }

        if (
            false !== strpos(
                $haystack,
                'sender'
            ) ||
            false !== strpos(
                $haystack,
                'from'
            ) ||
            false !== strpos(
                $haystack,
                'mailbox unavailable'
            )
        ) {

            return 'خطای آدرس فرستنده';
        }

        if (
            false !== strpos(
                $haystack,
                'recipient'
            ) ||
            false !== strpos(
                $haystack,
                'to address'
            ) ||
            false !== strpos(
                $haystack,
                'address rejected'
            )
        ) {

            return 'خطای آدرس گیرنده';
        }

        if (
            false !== strpos(
                $haystack,
                'ssl'
            ) ||
            false !== strpos(
                $haystack,
                'tls'
            ) ||
            false !== strpos(
                $haystack,
                'certificate'
            )
        ) {

            return 'خطای SSL / TLS';
        }

        if (
            false !== strpos(
                $haystack,
                'blocked'
            ) ||
            false !== strpos(
                $haystack,
                'firewall'
            ) ||
            false !== strpos(
                $haystack,
                'refused'
            )
        ) {

            return 'خطای اتصال یا محدودیت شبکه';
        }

        return 'خطای عمومی سرویس ارسال ایمیل';
    }

    /**
     * Store temporary mail test diagnostics.
     *
     * @param array $diagnostic Diagnostic data.
     *
     * @return void
     */
    private function store_test_error(
        $diagnostic
    ) {

        $user_id =
            get_current_user_id();

        if (
            ! $user_id
        ) {

            return;
        }

        set_transient(
            self::TEST_ERROR_TRANSIENT_PREFIX .
            $user_id,
            $diagnostic,
            5 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Retrieve and remove the temporary mail test diagnostic.
     *
     * @return array
     */
    private function get_test_error() {

        $user_id =
            get_current_user_id();

        if (
            ! $user_id
        ) {

            return array();
        }

        $transient_key =
            self::TEST_ERROR_TRANSIENT_PREFIX .
            $user_id;

        $diagnostic =
            get_transient(
                $transient_key
            );

        if (
            false === $diagnostic ||
            ! is_array(
                $diagnostic
            )
        ) {

            return array();
        }

        delete_transient(
            $transient_key
        );

        return $diagnostic;
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

        $url =
            add_query_arg(
                array(
                    'page' =>
                        'woosmart-notification-settings',

                    'woosmart_notice' =>
                        sanitize_key(
                            $notice
                        ),
                ),
                admin_url(
                    'admin.php'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }
}
