<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Execution history storage for WooSmart Automation.
 */
class WooSmart_Execution_History {

    /**
     * Database table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Runtime execution start times.
     *
     * @var array
     */
    private $runtime_start_times = array();

    /**
     * Initialize execution history storage.
     */
    public function __construct() {

        global $wpdb;

        $this->table_name =
            $wpdb->prefix . 'woosmart_executions';

        $this->maybe_create_table();
    }

    /**
     * Get database table name.
     *
     * @return string
     */
    public function get_table_name() {

        return $this->table_name;
    }

    /**
     * Create or upgrade execution history table.
     *
     * @return void
     */
    private function maybe_create_table() {

        global $wpdb;

        $installed_version = get_option(
            'woosmart_execution_history_db_version',
            ''
        );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate =
            $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            automation_id bigint(20) unsigned NOT NULL DEFAULT 0,
            automation_title text NULL,
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            trigger_key varchar(100) NOT NULL DEFAULT '',
            execution_policy varchar(50) NOT NULL DEFAULT 'all',
            status varchar(50) NOT NULL DEFAULT 'running',
            started_at datetime NOT NULL,
            completed_at datetime NULL DEFAULT NULL,
            duration_ms bigint(20) unsigned NOT NULL DEFAULT 0,
            actions_total int(11) unsigned NOT NULL DEFAULT 0,
            actions_successful tinyint(1) NOT NULL DEFAULT 0,
            condition_result tinyint(1) NULL DEFAULT NULL,
            conditions_json longtext NULL,
            actions_json longtext NULL,
            action_results_json longtext NULL,
            context_json longtext NULL,
            message text NULL,
            PRIMARY KEY  (id),
            KEY automation_id (automation_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY started_at (started_at),
            KEY duration_ms (duration_ms)
        ) {$charset_collate};";

        dbDelta(
            $sql
        );

        if (
            version_compare(
                $installed_version,
                '1.2.0',
                '<'
            )
        ) {

            update_option(
                'woosmart_execution_history_db_version',
                '1.2.0'
            );

            return;
        }

        if (
            '1.2.0' !==
            $installed_version
        ) {

            update_option(
                'woosmart_execution_history_db_version',
                '1.2.0'
            );
        }
    }

    /**
     * Start an execution record.
     *
     * @param int    $automation_id    Automation ID.
     * @param int    $order_id         Order ID.
     * @param string $trigger          Trigger key.
     * @param string $execution_policy Execution policy.
     * @param array  $context          Trigger context.
     * @param string $automation_title Automation title snapshot.
     * @param array  $conditions       Conditions snapshot.
     * @param array  $actions          Actions snapshot.
     *
     * @return int
     */
    public function start_execution(
        $automation_id,
        $order_id,
        $trigger,
        $execution_policy,
        $context,
        $automation_title = '',
        $conditions = array(),
        $actions = array()
    ) {

        global $wpdb;

        $started_at =
            current_time(
                'mysql'
            );

        $result =
            $wpdb->insert(
                $this->table_name,
                array(
                    'automation_id' =>
                        absint(
                            $automation_id
                        ),

                    'automation_title' =>
                        sanitize_text_field(
                            $automation_title
                        ),

                    'order_id' =>
                        absint(
                            $order_id
                        ),

                    'trigger_key' =>
                        sanitize_key(
                            $trigger
                        ),

                    'execution_policy' =>
                        sanitize_key(
                            $execution_policy
                        ),

                    'status' =>
                        'running',

                    'started_at' =>
                        $started_at,

                    'duration_ms' =>
                        0,

                    'condition_result' =>
                        null,

                    'conditions_json' =>
                        wp_json_encode(
                            $conditions,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        ),

                    'actions_json' =>
                        wp_json_encode(
                            $actions,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        ),

                    'action_results_json' =>
                        wp_json_encode(
                            array(),
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        ),

                    'context_json' =>
                        wp_json_encode(
                            $context,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        ),

                    'message' =>
                        'اجرای اتوماسیون آغاز شد.',
                ),
                array(
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

        if (
            false ===
            $result
        ) {

            return 0;
        }

        $execution_id =
            absint(
                $wpdb->insert_id
            );

        if (
            $execution_id
        ) {

            $this->runtime_start_times[
                $execution_id
            ] =
                microtime(
                    true
                );
        }

        return $execution_id;
    }

    /**
     * Finish an execution record.
     *
     * @param int        $execution_id       Execution ID.
     * @param string     $status             Final status.
     * @param int        $actions_total      Total Action count.
     * @param bool       $actions_successful Whether all Actions succeeded.
     * @param string     $message            Human-readable message.
     * @param bool|null  $condition_result   Overall Condition result.
     * @param array      $action_results     Per-Action results.
     *
     * @return void
     */
    public function finish_execution(
        $execution_id,
        $status,
        $actions_total,
        $actions_successful,
        $message,
        $condition_result = null,
        $action_results = array()
    ) {

        global $wpdb;

        $execution_id =
            absint(
                $execution_id
            );

        if (
            ! $execution_id
        ) {

            return;
        }

        $allowed_statuses = array(
            'completed',
            'failed',
            'conditions_failed',
        );

        if (
            ! in_array(
                $status,
                $allowed_statuses,
                true
            )
        ) {

            $status =
                'failed';
        }

        $completed_at =
            current_time(
                'mysql'
            );

        $duration_ms =
            0;

        $completed_microtime =
            microtime(
                true
            );

        if (
            isset(
                $this->runtime_start_times[
                    $execution_id
                ]
            )
        ) {

            $started_microtime =
                (float)
                $this->runtime_start_times[
                    $execution_id
                ];

            $elapsed_seconds =
                $completed_microtime -
                $started_microtime;

            if (
                $elapsed_seconds >= 0
            ) {

                $duration_ms =
                    (int)
                    round(
                        $elapsed_seconds *
                        1000
                    );
            }

            unset(
                $this->runtime_start_times[
                    $execution_id
                ]
            );

        } else {

            /*
             * Fallback only for safety.
             * Normal executions always use microtime().
             */
            $started_at =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT started_at FROM {$this->table_name} WHERE id = %d",
                        $execution_id
                    )
                );

            if (
                ! empty(
                    $started_at
                )
            ) {

                $started_timestamp =
                    strtotime(
                        $started_at
                    );

                $completed_timestamp =
                    strtotime(
                        $completed_at
                    );

                if (
                    false !==
                    $started_timestamp &&
                    false !==
                    $completed_timestamp &&
                    $completed_timestamp >=
                    $started_timestamp
                ) {

                    $duration_ms =
                        (
                            $completed_timestamp -
                            $started_timestamp
                        ) *
                        1000;
                }
            }
        }

        $action_results_json =
            wp_json_encode(
                is_array(
                    $action_results
                )
                    ? $action_results
                    : array(),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

        $condition_result_value =
            null;

        if (
            null !==
            $condition_result
        ) {

            $condition_result_value =
                $condition_result
                    ? 1
                    : 0;
        }

        $wpdb->update(
            $this->table_name,
            array(
                'status' =>
                    $status,

                'completed_at' =>
                    $completed_at,

                'duration_ms' =>
                    absint(
                        $duration_ms
                    ),

                'actions_total' =>
                    absint(
                        $actions_total
                    ),

                'actions_successful' =>
                    $actions_successful
                        ? 1
                        : 0,

                'condition_result' =>
                    $condition_result_value,

                'action_results_json' =>
                    $action_results_json,

                'message' =>
                    sanitize_textarea_field(
                        $message
                    ),
            ),
            array(
                'id' =>
                    $execution_id,
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
            ),
            array(
                '%d',
            )
        );
    }

    /**
     * Get one execution record.
     *
     * @param int $execution_id Execution ID.
     *
     * @return array|null
     */
    public function get_execution(
        $execution_id
    ) {

        global $wpdb;

        $execution_id =
            absint(
                $execution_id
            );

        if (
            ! $execution_id
        ) {

            return null;
        }

        $execution =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE id = %d",
                    $execution_id
                ),
                ARRAY_A
            );

        if (
            ! is_array(
                $execution
            )
        ) {

            return null;
        }

        $execution[
            'conditions'
        ] =
            $this->decode_json_array(
                isset(
                    $execution['conditions_json']
                )
                    ? $execution['conditions_json']
                    : ''
            );

        $execution[
            'actions'
        ] =
            $this->decode_json_array(
                isset(
                    $execution['actions_json']
                )
                    ? $execution['actions_json']
                    : ''
            );

        $execution[
            'action_results'
        ] =
            $this->decode_json_array(
                isset(
                    $execution['action_results_json']
                )
                    ? $execution['action_results_json']
                    : ''
            );

        $execution[
            'context'
        ] =
            $this->decode_json_array(
                isset(
                    $execution['context_json']
                )
                    ? $execution['context_json']
                    : ''
            );

        if (
            '' !==
            $execution['condition_result'] &&
            null !==
            $execution['condition_result']
        ) {

            $execution[
                'condition_result'
            ] =
                (bool)
                absint(
                    $execution[
                        'condition_result'
                    ]
                );

        } else {

            $execution[
                'condition_result'
            ] =
                null;
        }

        return $execution;
    }

    /**
     * Decode a JSON array safely.
     *
     * @param string $json JSON string.
     *
     * @return array
     */
    private function decode_json_array(
        $json
    ) {

        if (
            empty(
                $json
            )
        ) {

            return array();
        }

        $decoded =
            json_decode(
                $json,
                true
            );

        return is_array(
            $decoded
        )
            ? $decoded
            : array();
    }

    /**
     * Get execution records.
     *
     * @param int    $page     Current page.
     * @param int    $per_page Rows per page.
     * @param string $status   Optional status filter.
     *
     * @return array
     */
    public function get_executions(
        $page = 1,
        $per_page = 25,
        $status = ''
    ) {

        global $wpdb;

        $page =
            max(
                1,
                absint(
                    $page
                )
            );

        $per_page =
            max(
                1,
                min(
                    100,
                    absint(
                        $per_page
                    )
                )
            );

        $offset =
            (
                $page - 1
            ) *
            $per_page;

        if (
            ! empty(
                $status
            )
        ) {

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY started_at DESC, id DESC LIMIT %d OFFSET %d",
                    sanitize_key(
                        $status
                    ),
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY started_at DESC, id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Count execution records.
     *
     * @param string $status Optional status filter.
     *
     * @return int
     */
    public function count_executions(
        $status = ''
    ) {

        global $wpdb;

        if (
            ! empty(
                $status
            )
        ) {

            return absint(
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
                        sanitize_key(
                            $status
                        )
                    )
                )
            );
        }

        return absint(
            $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name}"
            )
        );
    }

    /**
     * Get summary counts.
     *
     * @return array
     */
    public function get_summary() {

        return array(
            'all' =>
                $this->count_executions(),

            'completed' =>
                $this->count_executions(
                    'completed'
                ),

            'failed' =>
                $this->count_executions(
                    'failed'
                ),

            'conditions_failed' =>
                $this->count_executions(
                    'conditions_failed'
                ),
        );
    }
}
