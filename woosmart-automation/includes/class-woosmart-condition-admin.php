<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WooSmart Multiple Conditions Admin Adapter.
 *
 * Extends the existing Automation Builder without replacing the large
 * WooSmart Admin class. The first condition continues to use the current
 * builder. Additional conditions are managed here and submitted as one
 * machine-readable payload.
 */
class WooSmart_Condition_Admin {

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
     * Pending conditions captured from the current admin request.
     *
     * @var array|null
     */
    private $pending_conditions = null;

    /**
     * Request start timestamp.
     *
     * @var int
     */
    private $request_started_at = 0;

    /**
     * Initialize Multiple Conditions support.
     */
    public function __construct() {

        $this->condition_registry =
            new WooSmart_Condition_Registry();

        $this->currency =
            new WooSmart_Currency();

        $this->request_started_at =
            time();

        add_action(
            'admin_post_woosmart_save_automation',
            array(
                $this,
                'capture_condition_payload',
            ),
            1
        );

        add_action(
            'admin_post_woosmart_update_automation',
            array(
                $this,
                'capture_condition_payload',
            ),
            1
        );

        add_action(
            'admin_footer',
            array(
                $this,
                'render_admin_script',
            ),
            100
        );
    }

    /**
     * Capture the Multiple Conditions payload before Automation Manager runs.
     *
     * The existing Automation Manager remains backward compatible and still
     * receives the first condition through its existing scalar fields. The
     * complete condition set is persisted at shutdown after the Manager has
     * finished its normal save/update work.
     *
     * @return void
     */
    public function capture_condition_payload() {

        if (
            ! isset(
                $_POST['woosmart_conditions_json']
            )
        ) {
            return;
        }

        $raw_payload =
            wp_unslash(
                $_POST['woosmart_conditions_json']
            );

        $conditions =
            json_decode(
                $raw_payload,
                true
            );

        if (
            ! is_array(
                $conditions
            )
        ) {
            return;
        }

        $conditions =
            $this->sanitize_condition_payload(
                $conditions
            );

        $this->pending_conditions =
            $conditions;

        /*
         * Keep the current Automation Manager fully compatible by populating
         * its existing single-condition request fields with the first valid
         * condition. Multiple conditions are persisted after the Manager has
         * completed its normal save/update process.
         */
        $first_condition =
            ! empty(
                $conditions
            )
                ? $conditions[0]
                : null;

        if (
            is_array(
                $first_condition
            )
        ) {

            $_POST['condition_field'] =
                $first_condition['field'];

            $_POST['condition_operator'] =
                $first_condition['operator'];

            if (
                'between' ===
                $first_condition['operator']
            ) {

                $_POST['condition_value_min'] =
                    isset(
                        $first_condition['value']['min']
                    )
                        ? $first_condition['value']['min']
                        : '';

                $_POST['condition_value_max'] =
                    isset(
                        $first_condition['value']['max']
                    )
                        ? $first_condition['value']['max']
                        : '';

                $_POST['condition_value'] =
                    '';

            } else {

                $_POST['condition_value'] =
                    isset(
                        $first_condition['value']
                    )
                        ? $first_condition['value']
                        : '';

                $_POST['condition_value_min'] =
                    '';

                $_POST['condition_value_max'] =
                    '';
            }

        } else {

            $_POST['condition_field'] =
                '';

            $_POST['condition_operator'] =
                '';

            $_POST['condition_value'] =
                '';

            $_POST['condition_value_min'] =
                '';

            $_POST['condition_value_max'] =
                '';
        }

        add_action(
            'shutdown',
            array(
                $this,
                'persist_pending_conditions',
            ),
            1
        );
    }

    /**
     * Persist Multiple Conditions after the Automation Manager completes.
     *
     * @return void
     */
    public function persist_pending_conditions() {

        if (
            ! is_array(
                $this->pending_conditions
            )
        ) {
            return;
        }

        $automation_id =
            isset(
                $_POST['automation_id']
            )
                ? absint(
                    $_POST['automation_id']
                )
                : 0;

        /*
         * Update request already contains a trusted Automation ID after the
         * nonce/capability checks performed by Automation Manager.
         */
        if (
            ! $automation_id &&
            ! empty(
                $_POST['automation_name']
            )
        ) {

            $automation_name =
                sanitize_text_field(
                    wp_unslash(
                        $_POST['automation_name']
                    )
                );

            $automation_id =
                $this->find_newly_created_automation(
                    $automation_name
                );
        }

        if (
            ! $automation_id ||
            'woosmart_automation' !==
            get_post_type(
                $automation_id
            )
        ) {
            return;
        }

        update_post_meta(
            $automation_id,
            '_woosmart_conditions',
            $this->pending_conditions
        );
    }

    /**
     * Find the Automation created by the current request.
     *
     * @param string $automation_name Automation title.
     *
     * @return int
     */
    private function find_newly_created_automation(
        $automation_name
    ) {

        $posts =
            get_posts(
                array(
                    'post_type' =>
                        'woosmart_automation',

                    'post_status' =>
                        'publish',

                    'posts_per_page' =>
                        1,

                    'orderby' =>
                        'ID',

                    'order' =>
                        'DESC',

                    's' =>
                        '',
                )
            );

        if (
            empty(
                $posts
            )
        ) {
            return 0;
        }

        foreach (
            $posts as $post
        ) {

            if (
                $post->post_title !==
                $automation_name
            ) {
                continue;
            }

            if (
                ! empty(
                    $post->post_date_gmt
                )
            ) {

                $created_timestamp =
                    strtotime(
                        $post->post_date_gmt .
                        ' GMT'
                    );

                if (
                    false !==
                    $created_timestamp &&
                    $created_timestamp +
                        30 <
                    $this->request_started_at
                ) {
                    continue;
                }
            }

            return absint(
                $post->ID
            );
        }

        return 0;
    }

    /**
     * Sanitize incoming condition payload.
     *
     * @param array $conditions Raw conditions.
     *
     * @return array
     */
    private function sanitize_condition_payload(
        $conditions
    ) {

        $sanitized =
            array();

        foreach (
            $conditions as $condition
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

            if (
                empty(
                    $field
                ) ||
                empty(
                    $operator
                ) ||
                ! $this->condition_registry->has(
                    $field
                )
            ) {
                continue;
            }

            $operators =
                $this->condition_registry->get_operators(
                    $field
                );

            if (
                ! isset(
                    $operators[
                        $operator
                    ]
                )
            ) {
                continue;
            }

            $definition =
                $this->condition_registry->get(
                    $field
                );

            $value_type =
                is_array(
                    $definition
                ) &&
                isset(
                    $definition[
                        'value_type'
                    ]
                )
                    ? sanitize_key(
                        $definition[
                            'value_type'
                        ]
                    )
                    : 'text';

            if (
                'between' ===
                $operator
            ) {

                $minimum =
                    isset(
                        $condition['value']['min']
                    )
                        ? $this->normalize_numeric(
                            $condition['value']['min']
                        )
                        : '';

                $maximum =
                    isset(
                        $condition['value']['max']
                    )
                        ? $this->normalize_numeric(
                            $condition['value']['max']
                        )
                        : '';

                if (
                    '' === $minimum ||
                    '' === $maximum
                ) {
                    continue;
                }

                $sanitized[] =
                    array(
                        'field' =>
                            $field,

                        'operator' =>
                            $operator,

                        'value' =>
                            array(
                                'min' =>
                                    $minimum,

                                'max' =>
                                    $maximum,
                            ),
                    );

                continue;
            }

            if (
                'number' ===
                $value_type
            ) {

                $value =
                    $this->normalize_numeric(
                        isset(
                            $condition['value']
                        )
                            ? $condition['value']
                            : ''
                    );

            } else {

                $value =
                    sanitize_text_field(
                        isset(
                            $condition['value']
                        )
                            ? $condition['value']
                            : ''
                    );
            }

            if (
                '' ===
                $value
            ) {
                continue;
            }

            $sanitized[] =
                array(
                    'field' =>
                        $field,

                    'operator' =>
                        $operator,

                    'value' =>
                        $value,
                );
        }

        return array_values(
            $sanitized
        );
    }

    /**
     * Normalize numeric value.
     *
     * @param mixed $value Numeric value.
     *
     * @return string
     */
    private function normalize_numeric(
        $value
    ) {

        $value =
            str_replace(
                ',',
                '',
                (string) $value
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
     * Render Multiple Conditions admin UI enhancement.
     *
     * @return void
     */
    public function render_admin_script() {

        if (
            ! is_admin() ||
            ! current_user_can(
                'manage_options'
            )
        ) {
            return;
        }

        $page =
            isset(
                $_GET['page']
            )
                ? sanitize_key(
                    wp_unslash(
                        $_GET['page']
                    )
                )
                : '';

        if (
            'woosmart-add-automation' !==
            $page
        ) {
            return;
        }

        $edit_id =
            isset(
                $_GET['edit']
            )
                ? absint(
                    $_GET['edit']
                )
                : 0;

        $stored_conditions =
            array();

        if (
            $edit_id &&
            'woosmart_automation' ===
            get_post_type(
                $edit_id
            )
        ) {

            $stored_conditions =
                get_post_meta(
                    $edit_id,
                    '_woosmart_conditions',
                    true
                );

            if (
                ! is_array(
                    $stored_conditions
                )
            ) {
                $stored_conditions =
                    array();
            }
        }

        $definitions =
            $this->condition_registry->get_all();

        $currency_unit =
            $this->currency->get_display_unit();
        ?>

        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const definitions =
                    <?php
                    echo wp_json_encode(
                        $definitions,
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

                const storedConditions =
                    <?php
                    echo wp_json_encode(
                        $stored_conditions,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    );
                    ?>;

                const form =
                    document.querySelector(
                        '#condition_field'
                    )
                        ? document.querySelector(
                            '#condition_field'
                        ).form
                        : null;

                const legacyField =
                    document.getElementById(
                        'condition_field'
                    );

                const legacyOperator =
                    document.getElementById(
                        'condition_operator'
                    );

                const legacyValue =
                    document.getElementById(
                        'condition_value'
                    );

                const legacyMin =
                    document.getElementById(
                        'condition_value_min'
                    );

                const legacyMax =
                    document.getElementById(
                        'condition_value_max'
                    );

                if (
                    ! form ||
                    ! legacyField ||
                    ! legacyOperator ||
                    ! legacyValue
                ) {
                    return;
                }

                let existingConditions =
                    Array.isArray(
                        storedConditions
                    )
                        ? storedConditions
                        : [];

                const heading =
                    legacyField.closest(
                        'table'
                    );

                if (
                    ! heading
                ) {
                    return;
                }

                const builder =
                    document.createElement(
                        'div'
                    );

                builder.id =
                    'woosmart-multiple-conditions';

                builder.setAttribute(
                    'dir',
                    'rtl'
                );

                builder.style.cssText =
                    'max-width:900px;margin:0 0 25px 0;';

                heading.parentNode.insertBefore(
                    builder,
                    heading.nextSibling
                );

                const intro =
                    document.createElement(
                        'div'
                    );

                intro.style.cssText =
                    'margin:0 0 12px 0;padding:12px 14px;background:#f6f7f7;border:1px solid #dcdcde;color:#3c434a;';

                intro.innerHTML =
                    '<strong>منطق شرایط:</strong> همه شرایط زیر باید برقرار باشند.' +
                    ' هر شرط با <strong>AND</strong> به شرط بعدی متصل می‌شود.';

                builder.appendChild(
                    intro
                );

                const conditionsContainer =
                    document.createElement(
                        'div'
                    );

                conditionsContainer.id =
                    'woosmart-condition-extra-container';

                builder.appendChild(
                    conditionsContainer
                );

                const addButton =
                    document.createElement(
                        'button'
                    );

                addButton.type =
                    'button';

                addButton.className =
                    'button';

                addButton.textContent =
                    '+ افزودن شرط';

                addButton.style.marginTop =
                    '10px';

                builder.appendChild(
                    addButton
                );

                const help =
                    document.createElement(
                        'p'
                    );

                help.className =
                    'description';

                help.textContent =
                    'با افزودن شرط، اتوماسیون فقط زمانی اجرا می‌شود که تمام شرایط برقرار باشند.';

                builder.appendChild(
                    help
                );

                const payloadInput =
                    document.createElement(
                        'input'
                    );

                payloadInput.type =
                    'hidden';

                payloadInput.name =
                    'woosmart_conditions_json';

                payloadInput.value =
                    '';

                form.appendChild(
                    payloadInput
                );

                function normalizeNumber(
                    value
                ) {

                    value =
                        String(
                            value ||
                            ''
                        )
                            .replace(
                                /,/g,
                                ''
                            )
                            .replace(
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
                        '' ===
                        value
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
                    return definitions[
                        field
                    ] || null;
                }

                function createSelect(
                    className
                ) {

                    const select =
                        document.createElement(
                            'select'
                        );

                    select.className =
                        className;

                    select.style.minWidth =
                        '250px';

                    return select;
                }

                function populateFieldOptions(
                    fieldSelect,
                    selectedField
                ) {

                    fieldSelect.innerHTML =
                        '';

                    Object.keys(
                        definitions
                    ).forEach(
                        function(
                            key
                        ) {

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                key;

                            option.textContent =
                                definitions[
                                    key
                                ].label ||
                                key;

                            fieldSelect.appendChild(
                                option
                            );
                        }
                    );

                    if (
                        selectedField &&
                        definitions[
                            selectedField
                        ]
                    ) {
                        fieldSelect.value =
                            selectedField;
                    }
                }

                function populateOperatorOptions(
                    operatorSelect,
                    field,
                    selectedOperator
                ) {

                    operatorSelect.innerHTML =
                        '';

                    const definition =
                        getDefinition(
                            field
                        );

                    if (
                        ! definition ||
                        ! definition.operators
                    ) {
                        return;
                    }

                    Object.keys(
                        definition.operators
                    ).forEach(
                        function(
                            key
                        ) {

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                key;

                            option.textContent =
                                definition.operators[
                                    key
                                ];

                            operatorSelect.appendChild(
                                option
                            );
                        }
                    );

                    if (
                        selectedOperator &&
                        operatorSelect.querySelector(
                            'option[value="' +
                            CSS.escape(
                                selectedOperator
                            ) +
                            '"]'
                        )
                    ) {
                        operatorSelect.value =
                            selectedOperator;
                    }
                }

                function createValueArea(
                    row,
                    value
                ) {

                    const valueCell =
                        row.querySelector(
                            '.woosmart-extra-condition-value'
                        );

                    valueCell.innerHTML =
                        '';

                    const definition =
                        getDefinition(
                            row.querySelector(
                                '.woosmart-extra-condition-field'
                            ).value
                        );

                    const operator =
                        row.querySelector(
                            '.woosmart-extra-condition-operator'
                        ).value;

                    const valueType =
                        definition &&
                        definition.value_type
                            ? definition.value_type
                            : 'text';

                    if (
                        'between' ===
                        operator
                    ) {

                        const range =
                            isPlainObject(
                                value
                            )
                                ? value
                                : {};

                        const wrapper =
                            document.createElement(
                                'div'
                            );

                        wrapper.style.cssText =
                            'display:flex;align-items:center;gap:8px;flex-wrap:wrap;';

                        const minInput =
                            createMoneyInput(
                                'حداقل',
                                range.min ||
                                ''
                            );

                        const sep =
                            document.createElement(
                                'span'
                            );

                        sep.textContent =
                            'تا';

                        const maxInput =
                            createMoneyInput(
                                'حداکثر',
                                range.max ||
                                ''
                            );

                        minInput.dataset.role =
                            'min';

                        maxInput.dataset.role =
                            'max';

                        wrapper.appendChild(
                            minInput.wrapper
                        );
                        wrapper.appendChild(
                            sep
                        );
                        wrapper.appendChild(
                            maxInput.wrapper
                        );

                        valueCell.appendChild(
                            wrapper
                        );

                        return;
                    }

                    if (
                        'number' ===
                        valueType
                    ) {

                        valueCell.appendChild(
                            createMoneyInput(
                                'مقدار',
                                value ||
                                ''
                            ).wrapper
                        );

                        return;
                    }

                    const input =
                        document.createElement(
                            'input'
                        );

                    input.type =
                        'text';

                    input.className =
                        'regular-text woosmart-extra-condition-text';

                    input.value =
                        value ||
                        '';

                    valueCell.appendChild(
                        input
                    );
                }

                function createMoneyInput(
                    labelText,
                    value
                ) {

                    const wrapper =
                        document.createElement(
                            'div'
                        );

                    wrapper.style.cssText =
                        'position:relative;min-width:220px;';

                    const input =
                        document.createElement(
                            'input'
                        );

                    input.type =
                        'text';

                    input.inputMode =
                        'decimal';

                    input.dir =
                        'ltr';

                    input.className =
                        'regular-text woosmart-extra-condition-number';

                    input.value =
                        formatNumber(
                            value
                        );

                    input.placeholder =
                        '1,000,000';

                    input.style.cssText =
                        'width:100%;box-sizing:border-box;padding:8px 12px 8px 90px;direction:ltr;text-align:left;font-variant-numeric:tabular-nums;';

                    const label =
                        document.createElement(
                            'span'
                        );

                    label.textContent =
                        labelText;

                    label.dir =
                        'rtl';

                    label.style.cssText =
                        'position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#646970;pointer-events:none;font-size:13px;font-weight:600;white-space:nowrap;';

                    wrapper.appendChild(
                        input
                    );

                    wrapper.appendChild(
                        label
                    );

                    input.addEventListener(
                        'input',
                        function() {
                            input.value =
                                formatNumber(
                                    input.value
                                );
                        }
                    );

                    return {
                        wrapper:
                            wrapper,
                        input:
                            input,
                    };
                }

                function isPlainObject(
                    value
                ) {
                    return (
                        value !== null &&
                        typeof value ===
                            'object' &&
                        ! Array.isArray(
                            value
                        )
                    );
                }

                function createRow(
                    condition,
                    index
                ) {

                    condition =
                        condition ||
                        {};

                    const row =
                        document.createElement(
                            'div'
                        );

                    row.className =
                        'woosmart-extra-condition-row';

                    row.style.cssText =
                        'margin:0 0 12px 0;padding:16px;border:1px solid #ccd0d4;background:#fff;';

                    const top =
                        document.createElement(
                            'div'
                        );

                    top.style.cssText =
                        'display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;';

                    const title =
                        document.createElement(
                            'strong'
                        );

                    title.textContent =
                        'شرط ' +
                        String(
                            index + 1
                        );

                    top.appendChild(
                        title
                    );

                    const remove =
                        document.createElement(
                            'button'
                        );

                    remove.type =
                        'button';

                    remove.className =
                        'button-link-delete';

                    remove.textContent =
                        'حذف شرط';

                    remove.addEventListener(
                        'click',
                        function() {
                            row.remove();
                            renumberRows();
                        }
                    );

                    top.appendChild(
                        remove
                    );

                    row.appendChild(
                        top
                    );

                    const grid =
                        document.createElement(
                            'div'
                        );

                    grid.style.cssText =
                        'display:grid;grid-template-columns:minmax(200px,1fr) minmax(200px,1fr);gap:12px;align-items:start;';

                    const fieldWrap =
                        document.createElement(
                            'div'
                        );

                    const fieldLabel =
                        document.createElement(
                            'label'
                        );

                    fieldLabel.textContent =
                        'فیلد';

                    fieldLabel.style.display =
                        'block';

                    fieldLabel.style.marginBottom =
                        '6px';

                    const field =
                        createSelect(
                            'woosmart-extra-condition-field'
                        );

                    populateFieldOptions(
                        field,
                        condition.field ||
                        Object.keys(
                            definitions
                        )[0]
                    );

                    fieldWrap.appendChild(
                        fieldLabel
                    );

                    fieldWrap.appendChild(
                        field
                    );

                    const operatorWrap =
                        document.createElement(
                            'div'
                        );

                    const operatorLabel =
                        document.createElement(
                            'label'
                        );

                    operatorLabel.textContent =
                        'مقایسه';

                    operatorLabel.style.display =
                        'block';

                    operatorLabel.style.marginBottom =
                        '6px';

                    const operator =
                        createSelect(
                            'woosmart-extra-condition-operator'
                        );

                    populateOperatorOptions(
                        operator,
                        field.value,
                        condition.operator ||
                        ''
                    );

                    operatorWrap.appendChild(
                        operatorLabel
                    );

                    operatorWrap.appendChild(
                        operator
                    );

                    grid.appendChild(
                        fieldWrap
                    );

                    grid.appendChild(
                        operatorWrap
                    );

                    row.appendChild(
                        grid
                    );

                    const valueWrap =
                        document.createElement(
                            'div'
                        );

                    valueWrap.style.marginTop =
                        '12px';

                    const valueLabel =
                        document.createElement(
                            'label'
                        );

                    valueLabel.textContent =
                        'مقدار';

                    valueLabel.style.display =
                        'block';

                    valueLabel.style.marginBottom =
                        '6px';

                    const valueCell =
                        document.createElement(
                            'div'
                        );

                    valueCell.className =
                        'woosmart-extra-condition-value';

                    valueWrap.appendChild(
                        valueLabel
                    );

                    valueWrap.appendChild(
                        valueCell
                    );

                    row.appendChild(
                        valueWrap
                    );

                    field.addEventListener(
                        'change',
                        function() {
                            populateOperatorOptions(
                                operator,
                                field.value,
                                ''
                            );

                            createValueArea(
                                row,
                                ''
                            );
                        }
                    );

                    operator.addEventListener(
                        'change',
                        function() {
                            createValueArea(
                                row,
                                ''
                            );
                        }
                    );

                    createValueArea(
                        row,
                        condition.value ||
                        ''
                    );

                    return row;
                }

                function renumberRows() {

                    const rows =
                        conditionsContainer.querySelectorAll(
                            '.woosmart-extra-condition-row'
                        );

                    rows.forEach(
                        function(
                            row,
                            index
                        ) {

                            const title =
                                row.querySelector(
                                    'strong'
                                );

                            if (
                                title
                            ) {
                                title.textContent =
                                    'شرط ' +
                                    String(
                                        index + 2
                                    );
                            }
                        }
                    );
                }

                function readMoneyInput(
                    input
                ) {
                    return normalizeNumber(
                        input
                            ? input.value
                            : ''
                    );
                }

                function readExtraRows() {

                    const rows =
                        Array.from(
                            conditionsContainer.querySelectorAll(
                                '.woosmart-extra-condition-row'
                            )
                        );

                    return rows.map(
                        function(
                            row
                        ) {

                            const field =
                                row.querySelector(
                                    '.woosmart-extra-condition-field'
                                );

                            const operator =
                                row.querySelector(
                                    '.woosmart-extra-condition-operator'
                                );

                            const definition =
                                getDefinition(
                                    field.value
                                );

                            if (
                                'between' ===
                                operator.value
                            ) {

                                const numberInputs =
                                    row.querySelectorAll(
                                        '.woosmart-extra-condition-number'
                                    );

                                return {
                                    field:
                                        field.value,

                                    operator:
                                        operator.value,

                                    value:
                                        {
                                            min:
                                                readMoneyInput(
                                                    numberInputs[0]
                                                ),

                                            max:
                                                readMoneyInput(
                                                    numberInputs[1]
                                                ),
                                        },
                                };
                            }

                            if (
                                definition &&
                                'number' ===
                                definition.value_type
                            ) {

                                const input =
                                    row.querySelector(
                                        '.woosmart-extra-condition-number'
                                    );

                                return {
                                    field:
                                        field.value,

                                    operator:
                                        operator.value,

                                    value:
                                        readMoneyInput(
                                            input
                                        ),
                                };
                            }

                            const text =
                                row.querySelector(
                                    '.woosmart-extra-condition-text'
                                );

                            return {
                                field:
                                    field.value,

                                operator:
                                    operator.value,

                                value:
                                    text
                                        ? text.value
                                        : '',
                            };
                        }
                    );
                }

                function readFirstCondition() {

                    const field =
                        legacyField.value ||
                        '';

                    const operator =
                        legacyOperator.value ||
                        '';

                    if (
                        ! field ||
                        ! operator
                    ) {
                        return null;
                    }

                    const definition =
                        getDefinition(
                            field
                        );

                    if (
                        'between' ===
                        operator
                    ) {

                        return {
                            field:
                                field,

                            operator:
                                operator,

                            value:
                                {
                                    min:
                                        normalizeNumber(
                                            legacyMin
                                                ? legacyMin.value
                                                : ''
                                        ),

                                    max:
                                        normalizeNumber(
                                            legacyMax
                                                ? legacyMax.value
                                                : ''
                                        ),
                                },
                        };
                    }

                    if (
                        definition &&
                        'number' !==
                        definition.value_type
                    ) {

                        const textInput =
                            document.getElementById(
                                'condition_value_text'
                            );

                        return {
                            field:
                                field,

                            operator:
                                operator,

                            value:
                                textInput
                                    ? textInput.value
                                    : '',
                        };
                    }

                    return {
                        field:
                            field,

                        operator:
                            operator,

                        value:
                            normalizeNumber(
                                legacyValue.value
                            ),
                    };
                }

                function preparePayload() {

                    const conditions =
                        [];

                    const first =
                        readFirstCondition();

                    if (
                        first
                    ) {
                        if (
                            'between' ===
                            first.operator
                        ) {

                            if (
                                first.value.min ||
                                first.value.max
                            ) {
                                conditions.push(
                                    first
                                );
                            }

                        } else if (
                            '' !==
                            String(
                                first.value ||
                                ''
                            )
                        ) {
                            conditions.push(
                                first
                            );
                        }
                    }

                    readExtraRows().forEach(
                        function(
                            condition
                        ) {

                            if (
                                'between' ===
                                condition.operator
                            ) {

                                if (
                                    condition.value.min &&
                                    condition.value.max
                                ) {
                                    conditions.push(
                                        condition
                                    );
                                }

                                return;
                            }

                            if (
                                '' !==
                                String(
                                    condition.value ||
                                    ''
                                )
                            ) {
                                conditions.push(
                                    condition
                                );
                            }
                        }
                    );

                    payloadInput.value =
                        JSON.stringify(
                            conditions
                        );
                }

                addButton.addEventListener(
                    'click',
                    function() {

                        const currentCount =
                            conditionsContainer.querySelectorAll(
                                '.woosmart-extra-condition-row'
                            ).length;

                        const row =
                            createRow(
                                {
                                    field:
                                        Object.keys(
                                            definitions
                                        )[0],

                                    operator:
                                        '',

                                    value:
                                        '',
                                },
                                currentCount +
                                1
                            );

                        conditionsContainer.appendChild(
                            row
                        );

                        renumberRows();
                    }
                );

                /*
                 * Restore conditions after the first one on edit.
                 */
                if (
                    existingConditions.length >
                    1
                ) {

                    existingConditions
                        .slice(1)
                        .forEach(
                            function(
                                condition,
                                index
                            ) {

                                const row =
                                    createRow(
                                        condition,
                                        index + 1
                                    );

                                conditionsContainer.appendChild(
                                    row
                                );
                            }
                        );

                    renumberRows();
                }

                form.addEventListener(
                    'submit',
                    function() {
                        preparePayload();
                    }
                );
            }
        );
        </script>
        <?php
    }
}
