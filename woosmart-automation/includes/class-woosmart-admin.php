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
            'Dashboard',
            'Dashboard',
            'manage_options',
            'woosmart-automation',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'Automations',
            'Automations',
            'manage_options',
            'woosmart-automations',
            array( $this, 'render_automations_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'Add Automation',
            'Add Automation',
            'manage_options',
            'woosmart-add-automation',
            array( $this, 'render_add_automation_page' )
        );

        add_submenu_page(
            'woosmart-automation',
            'Logs',
            'Logs',
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
            'No status found'
        );
        ?>

        <div class="wrap">

            <h1>WooSmart Automation</h1>

            <p>
                افزونه WooSmart Automation با موفقیت اجرا شده است.
            </p>

            <hr>

            <h2>Plugin Status</h2>

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

        <div class="wrap">

            <h1 class="wp-heading-inline">
                Automations
            </h1>

            <a
                href="<?php echo esc_url( admin_url( 'admin.php?page=woosmart-add-automation' ) ); ?>"
                class="page-title-action"
            >
                Add Automation
            </a>

            <hr class="wp-header-end">

            <?php if ( empty( $automations ) ) : ?>

                <div class="notice notice-info">

                    <p>
                        هنوز هیچ Automationای ساخته نشده است.
                    </p>

                </div>

            <?php else : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Name</th>

                            <th>Trigger</th>

                            <th>Conditions</th>

                            <th>Status</th>

                            <th>Actions</th>

                            <th>Date</th>

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

                            if ( ! is_array( $conditions ) ) {
                                $conditions = array();
                            }

                            if ( 'active' === $status ) {

                                $status_label = 'Active';
                                $status_class = 'notice-success';
                                $toggle_label  = 'Disable';

                            } else {

                                $status_label = 'Inactive';
                                $status_class = 'notice-warning';
                                $toggle_label  = 'Enable';
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
                                        $trigger
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ( empty( $conditions ) ) : ?>

                                        <span>
                                            None
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

                                            <div>

                                                <?php
                                                echo esc_html(
                                                    $field
                                                );
                                                ?>

                                                <strong>
                                                    <?php
                                                    echo esc_html(
                                                        $operator
                                                    );
                                                    ?>
                                                </strong>

                                                <?php
                                                echo esc_html(
                                                    $value
                                                );
                                                ?>

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
                                        Edit
                                    </a>

                                    <a
                                        href="<?php echo esc_url( $duplicate_url ); ?>"
                                        class="button"
                                    >
                                        Duplicate
                                    </a>

                                    <a
                                        href="<?php echo esc_url( $delete_url ); ?>"
                                        class="button"
                                        onclick="return confirm('Are you sure you want to move this automation to trash?');"
                                    >
                                        Delete
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

        if ( $edit_id ) {

            if (
                'woosmart_automation' ===
                get_post_type( $edit_id )
            ) {

                $is_edit = true;

                $automation = get_post(
                    $edit_id
                );

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
            }
        }

        ?>

        <div class="wrap">

            <h1>

                <?php
                echo $is_edit
                    ? 'Edit Automation'
                    : 'Create Automation';
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
                                Automation Name
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="automation_name"
                                name="automation_name"
                                class="regular-text"
                                value="<?php echo esc_attr( $name ); ?>"
                                placeholder="مثلاً سفارش بالای یک میلیون"
                                required
                            >

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">

                            <label for="automation_trigger">
                                Trigger
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
                                    Order Created
                                </option>

                            </select>

                        </td>

                    </tr>

                </table>

                <h2>Conditions</h2>

                <p>
                    Automation فقط زمانی اجرا می‌شود که شرط برقرار باشد.
                </p>

                <table class="form-table">

                    <tr>

                        <th scope="row">

                            <label for="condition_field">
                                Field
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
                                    Order Total
                                </option>

                            </select>

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">

                            <label for="condition_operator">
                                Operator
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
                                    Is Equal
                                </option>

                                <option
                                    value="is_not_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'is_not_equal'
                                    ); ?>
                                >
                                    Is Not Equal
                                </option>

                                <option
                                    value="greater_than"
                                    <?php selected(
                                        $condition_operator,
                                        'greater_than'
                                    ); ?>
                                >
                                    Greater Than
                                </option>

                                <option
                                    value="greater_than_or_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'greater_than_or_equal'
                                    ); ?>
                                >
                                    Greater Than or Equal
                                </option>

                                <option
                                    value="less_than"
                                    <?php selected(
                                        $condition_operator,
                                        'less_than'
                                    ); ?>
                                >
                                    Less Than
                                </option>

                                <option
                                    value="less_than_or_equal"
                                    <?php selected(
                                        $condition_operator,
                                        'less_than_or_equal'
                                    ); ?>
                                >
                                    Less Than or Equal
                                </option>

                            </select>

                        </td>

                    </tr>

                    <tr>

                        <th scope="row">

                            <label for="condition_value">
                                Value
                            </label>

                        </th>

                        <td>

                            <input
                                type="number"
                                step="0.01"
                                id="condition_value"
                                name="condition_value"
                                class="regular-text"
                                value="<?php echo esc_attr( $condition_value ); ?>"
                                placeholder="1000000"
                            >

                        </td>

                    </tr>

                </table>

                <?php
                submit_button(
                    $is_edit
                        ? 'Update Automation'
                        : 'Save Automation'
                );
                ?>

            </form>

        </div>

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

        <div class="wrap">

            <h1>WooSmart Logs</h1>

            <p>
                رویدادهای ثبت‌شده توسط WooSmart Automation.
            </p>

            <hr>

            <?php if ( empty( $logs ) ) : ?>

                <div class="notice notice-info">

                    <p>
                        هنوز هیچ لاگی ثبت نشده است.
                    </p>

                </div>

            <?php else : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Message</th>
                            <th>Context</th>
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
                                        isset( $log['event'] )
                                            ? $log['event']
                                            : ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        isset( $log['message'] )
                                            ? $log['message']
                                            : ''
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
}