# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin designed to allow store administrators to create rule-based automations without writing code.

The long-term goal is to turn WooSmart Automation into a complete, reliable, predictable, extensible, and user-friendly automation platform for WooCommerce stores.

---

# Project Vision

The core concept of WooSmart Automation is:

**WHEN → IF → THEN**

- **WHEN** something happens
- **IF** certain conditions are satisfied
- **THEN** perform one or more actions

Example:

    WHEN
    Order Created

    IF
    Order Total > 100,000 تومان

    THEN
    Notify Store Administrator

Another example:

    WHEN
    Order Created

    IF
    Order Total > 500,000 تومان

    THEN
    Notify Store Administrator
    AND
    Change Order Status → Processing

The final goal is to allow store owners to build complex workflows from the WordPress admin without programming.

---

# Repository

GitHub:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the primary source of truth for the project.

The project is developed incrementally and every important milestone should be committed to Git.

---

# Current Project Status

Version:

    1.0.0

Current stage:

    MVP / Foundation

Current milestone:

    Modular Condition Registry + Action Registry architecture
    + Currency-aware WooSmart Admin UI
    + Multiple Automation diagnostics
    + Notification failure diagnostics

Current development focus:

    Mail Transport / SMTP Environment
    Action Registry completion
    Multiple Actions stabilization
    Conflict Detection
    Execution Priority
    Execution Policy
    Multiple Conditions
    More Conditions
    More Triggers

---

# Current Architecture

The current architecture is modular.

    WooCommerce
         ↓
    Trigger System
         ↓
    Execution Engine
         ↓
    Condition Engine
         ↓
    Action Engine
         ↓
    Execution Result
         ↓
    Logger

Condition architecture:

    Condition Registry
         ↓
    Condition Engine
         ↓
    Condition Result

Action architecture:

    Action Registry
         ↓
    Action Engine
         ↓
    Action Handler
         ↓
    Action Result

Currency architecture:

    WooCommerce Currency
         ↓
    WooSmart Currency Helper
         ↓
    WooSmart Admin Display

Administrative workflow:

    WordPress Admin
         ↓
    WooSmart Admin
         ↓
    Automation Manager
         ↓
    Automation Data
         ↓
    Execution Engine

The architecture uses shared Condition, Action, and Execution Engine instances instead of unnecessarily creating duplicate engine instances.

---

# Current File Structure

    woosmart-automation/
    │
    ├── woosmart-automation.php
    ├── README.md
    │
    └── includes/
        ├── class-woosmart-core.php
        ├── class-woosmart-logger.php
        ├── class-woosmart-currency.php
        ├── class-woosmart-admin.php
        ├── class-woosmart-automation.php
        ├── class-woosmart-triggers.php
        ├── class-woosmart-post-types.php
        ├── class-woosmart-automation-manager.php
        ├── class-woosmart-condition-registry.php
        ├── class-woosmart-condition-engine.php
        ├── class-woosmart-action-registry.php
        ├── class-woosmart-action-engine.php
        └── class-woosmart-execution-engine.php

---

# File Responsibilities

## woosmart-automation.php

Main plugin bootstrap.

Responsibilities:

- Load plugin classes.
- Initialize shared services.
- Load the Condition Registry.
- Initialize the Condition Engine.
- Load the Action Registry.
- Initialize the Action Engine.
- Initialize the Execution Engine.
- Pass the shared Execution Engine to the Trigger system.
- Initialize the Admin layer.
- Initialize the Post Type.
- Initialize the Automation Manager.
- Initialize the Currency helper.
- Handle plugin activation.
- Handle plugin deactivation.
- Flush rewrite rules when required.

The current architecture avoids unnecessary duplicate Condition, Action, and Execution Engine instances.

---

## class-woosmart-core.php

Core plugin functionality.

Responsibilities:

- Detect WooCommerce.
- Display WooCommerce dependency warnings.
- Provide WooCommerce availability status.
- Maintain WooCommerce compatibility behavior.

Important currency rule:

WooCommerce remains the source of truth for store currency.

WooSmart must not replace or override the store's currency system.

WooSmart must not modify:

- Product prices.
- Order totals.
- Payment amounts.
- Gateway currency logic.
- WooCommerce currency settings.

The current development store uses:

    IRT

WooCommerce therefore controls the store's تومان presentation.

---

## class-woosmart-currency.php

Display-only currency helper.

Responsibilities:

- Read the current WooCommerce currency.
- Detect `IRT`.
- Detect `IRR`.
- Return the user-facing display unit.
- Normalize numeric values without changing their currency.
- Format values for WooSmart UI without performing currency conversion.

Current behavior:

    IRT
        ↓
    تومان

    IRR
        ↓
    ریال

Other currencies use the WooCommerce currency symbol where available.

Critical architectural rule:

    WooSmart does NOT convert:
        Rial → Toman
        Toman → Rial

The class does not modify WooCommerce prices or order totals.

The conversion factor is intentionally:

    1

This is intentional.

WooCommerce itself remains responsible for the interpretation and storage of monetary values.

---

## class-woosmart-logger.php

Central logging system.

Responsibilities:

- Store automation events.
- Store execution context.
- Retrieve logs.
- Clear logs.
- Keep the latest 100 log entries in the current MVP.

Current storage:

    WordPress Options

Future storage:

    Dedicated database table

---

## class-woosmart-admin.php

Main WooSmart administration interface.

Current sections:

- داشبورد
- اتوماسیون‌ها
- افزودن اتوماسیون
- گزارش‌ها

The current interface is:

    Persian
    RTL

The plugin name remains:

    WooSmart

and is intentionally not translated.

Internal identifiers remain English for stability and compatibility.

Examples:

    order_created
    order_total
    greater_than
    change_order_status
    notify_admin

Recommended user-facing terminology:

| Internal Term | UI Term |
|---|---|
| Trigger | رویداد |
| Condition | شرط |
| Action | عملیات |
| Automation | اتوماسیون |
| Execution | اجرا |
| Status | وضعیت |
| Active | فعال |
| Inactive | غیرفعال |

Example UI:

    رویداد:
    ایجاد سفارش

    شرط:
    مبلغ سفارش
    بیشتر از
    100,000 تومان

    عملیات:
    ارسال اعلان به مدیر فروشگاه

The Condition Builder is connected to the Condition Registry.

Condition fields and operators are no longer required to be duplicated in the Admin layer.

The current Admin Builder reads:

    Condition Definition
    Condition Label
    Operators
    Value Type

from:

    class-woosmart-condition-registry.php

The current Condition Builder dynamically updates the available operators when the selected condition changes.

Numeric conditions use formatted numeric input.

The currency label displayed in the numeric input is obtained from the current WooCommerce currency context through:

    WooSmart_Currency

No numeric currency conversion is performed by the Admin UI.

The amount field is designed to keep the numeric value readable while displaying the currency unit separately.

---

## class-woosmart-automation.php

Main automation foundation.

Current status:

    Foundation / Placeholder

Future responsibilities may include:

- Automation lifecycle.
- Automation registry.
- Automation-level services.
- Automation events.

---

## class-woosmart-triggers.php

Trigger system.

Current trigger:

    order_created

Current WooCommerce hook:

    woocommerce_new_order

The trigger creates an execution context containing the WooCommerce order ID.

Example context:

    {
        "order_id": 45
    }

Future versions will expand the Trigger system.

---

## class-woosmart-post-types.php

Registers the internal Automation post type.

Post type:

    woosmart_automation

The default WordPress UI for this post type is intentionally hidden because WooSmart provides its own administration interface.

---

## class-woosmart-automation-manager.php

Handles Automation CRUD and configuration validation.

Current operations:

- Create
- Update
- Enable / Disable
- Delete
- Duplicate

Current validation includes:

- Trigger validation.
- Condition validation.
- Condition field validation through Condition Registry.
- Condition operator validation through Condition Registry.
- Condition value validation.
- Action type validation.
- Order status validation.
- Notification configuration validation.
- Prevention of empty Action sets.
- Activation-time validation of existing Automation data.

The Condition validation layer no longer relies on a duplicated hard-coded list of Condition fields and operators.

The Manager resolves Condition definitions through:

    WooSmart_Condition_Registry

Future validation will include:

- Conflict detection.
- Condition overlap analysis.
- Action conflict analysis.
- Execution policy validation.

---

## class-woosmart-condition-registry.php

Central registry for Condition definitions.

Current responsibilities:

- Register Condition definitions.
- Check whether a Condition exists.
- Retrieve a Condition definition.
- Retrieve all public Condition definitions.
- Retrieve the allowed operators for a Condition.
- Execute the registered evaluator for a Condition.

Current registered Condition:

    order_total

Current operators:

    is_equal
    is_not_equal
    greater_than
    greater_than_or_equal
    less_than
    less_than_or_equal

Current registered metadata includes:

    label
    value_type
    operators
    evaluator

Current value type:

    number

This Registry is now the central source of truth for the Condition layer.

Future Condition definitions should be added through the Registry rather than duplicated across the Engine, Manager, and Admin UI.

---

## class-woosmart-condition-engine.php

Evaluates Automation Conditions.

Current field:

    order_total

Current operators:

    is_equal
    is_not_equal
    greater_than
    greater_than_or_equal
    less_than
    less_than_or_equal

The Condition Engine resolves Condition definitions through:

    WooSmart_Condition_Registry

Current condition behavior:

    All configured conditions must pass.

The current data model already uses an array structure, allowing future expansion to multiple Conditions.

Future versions will support:

    Multiple Conditions
    AND
    OR
    Nested Groups

---

## class-woosmart-action-registry.php

Central registry for Action definitions.

Current responsibilities:

- Register Action definitions.
- Check whether an Action exists.
- Retrieve Action definitions.
- Retrieve all Action definitions.
- Resolve an Action handler.
- Provide Action field metadata.

Current registered Actions:

    change_order_status
    notify_admin

Current Action definitions include:

    label
    handler
    fields

Example handler mapping:

    change_order_status
        ↓
    change_order_status()

    notify_admin
        ↓
    notify_admin()

The Action Registry is now the central source of truth for Action type registration.

The Action Engine no longer requires a hard-coded Action type switch for handler discovery.

---

## class-woosmart-action-engine.php

Executes Automation Actions.

Current Action architecture:

    Action Type
        ↓
    Action Registry
        ↓
    Registered Handler
        ↓
    Action Result
        ↓
    Logger

Current actions:

    change_order_status
    notify_admin

### change_order_status

Changes the WooCommerce order status.

Current implementation supports WooCommerce order statuses such as:

    pending
    processing
    on-hold
    completed
    cancelled
    refunded
    failed

The user-facing UI displays localized Persian labels where appropriate.

### notify_admin

Sends an email notification to the WordPress store administrator using:

    wp_mail()

Supported placeholders:

    {order_id}
    {order_total}
    {order_status}
    {customer_name}

The Action Engine does not implement SMTP itself.

SMTP configuration remains a WordPress-level / environment-level responsibility.

The Action Engine captures `wp_mail_failed` errors during its own notification attempt so the actual WordPress / PHPMailer failure can be stored in WooSmart Logs.

The current development implementation also explicitly controls the sender address during the WooSmart notification attempt.

This was introduced to diagnose and avoid the local XAMPP default sender:

    wordpress@localhost

The current log data confirms that the WooSmart sender value is now:

    mht7631@gmail.com

The remaining problem is mailer initialization in the local environment.

---

# Current Action System

The Action System is functional.

Current architecture:

    Automation
        ↓
    Execution Engine
        ↓
    Action Engine
        ↓
    Action Registry
        ↓
    Action Type
        ↓
    Action Handler
        ↓
    Action Result
        ↓
    Logger

Current Actions:

    1. change_order_status
    2. notify_admin

Current Action data is stored inside:

    _woosmart_actions

Example Action configuration:

    [
        {
            "type": "change_order_status",
            "status": "processing"
        }
    ]

Example Automation with notification and status change:

    [
        {
            "type": "notify_admin",
            "subject": "اعلان سفارش جدید در WooSmart",
            "message": "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد."
        },
        {
            "type": "change_order_status",
            "status": "processing"
        }
    ]

The current system has already demonstrated that more than one Action can be stored in an Automation configuration.

The current Action Engine can execute multiple Actions sequentially.

The next development step is to make Multiple Actions a fully controlled and predictable first-class feature in the UI and execution system.

---

## class-woosmart-execution-engine.php

Central execution orchestrator.

Responsibilities:

1. Receive a Trigger.
2. Find active Automations matching the Trigger.
3. Evaluate Conditions.
4. Execute Actions.
5. Determine Action success or failure.
6. Determine Automation success or failure.
7. Log the execution result.

Current execution model:

    Trigger
       ↓
    Active Automations
       ↓
    Conditions
       ↓
    Actions
       ↓
    Execution Result
       ↓
    Logger

The Execution Engine currently supports:

- Trigger processing.
- Active Automation lookup.
- Condition evaluation.
- Action execution.
- Action success detection.
- Action failure detection.
- Automation success logging.
- Automation failure logging.

A temporary diagnostic event:

    automation_scan

was added during development to verify how many active matching Automations are returned for a Trigger.

Example:

    {
        "trigger": "order_created",
        "context": {
            "order_id": 56
        },
        "found_count": 3,
        "automation_ids": [48, 43, 35]
    }

This confirmed that multiple active Automations can be discovered and processed.

The diagnostic event is temporary and should eventually be removed or replaced by a formal Execution Planning system.

---

# Current Data Model

Each Automation is stored as:

    woosmart_automation

Current metadata:

    _woosmart_status
    _woosmart_trigger
    _woosmart_conditions
    _woosmart_actions

Example:

    Automation:
        سفارش مناسب

    Status:
        active

    Trigger:
        order_created

    Conditions:
        [
            {
                "field": "order_total",
                "operator": "greater_than",
                "value": "1000000"
            }
        ]

    Actions:
        [
            {
                "type": "notify_admin",
                "subject": "سفارش مناسب جدید",
                "message": "..."
            },
            {
                "type": "change_order_status",
                "status": "processing"
            }
        ]

The numeric monetary value must remain in the same representation used by WooCommerce.

WooSmart does not convert stored monetary values when changing their UI display unit.

---

# Current User Interface

The administrative interface is Persian and RTL.

The plugin name remains:

    WooSmart

Recommended terminology:

| Internal Term | Persian UI |
|---|---|
| Trigger | رویداد |
| Condition | شرط |
| Action | عملیات |
| Automation | اتوماسیون |
| Execution | اجرا |
| Status | وضعیت |
| Order Created | ایجاد سفارش |
| Order Total | مبلغ سفارش |
| Notify Admin | ارسال اعلان به مدیر فروشگاه |
| Change Order Status | تغییر وضعیت سفارش |

The purpose of this terminology is to make the plugin understandable to store administrators without requiring technical knowledge.

---

# Current Currency Handling

WooCommerce remains the source of truth for store currency.

This is an important architectural decision.

WooSmart does not create an independent currency system.

For the current development store:

    WooCommerce Currency:
        IRT

WooCommerce therefore controls the store-level monetary representation.

WooSmart reads the current WooCommerce currency and displays the corresponding user-facing unit.

For:

    IRT

WooSmart displays:

    تومان

For:

    IRR

WooSmart displays:

    ریال

Other currencies use the WooCommerce currency symbol where available.

Example current development environment:

    WooCommerce:
        IRT

    Product:
        150,000 تومان

    WooSmart:
        150,000 تومان

No numeric conversion is performed.

The system intentionally does NOT do:

    1,500,000 ریال
        ↓
    150,000 تومان

inside WooSmart.

Instead:

    WooCommerce
        ↓
    provides the monetary value
        ↓
    WooSmart
        ↓
    displays the same numeric value using the WooCommerce currency context

This prevents conflicts with:

- WooCommerce
- Payment Gateways
- Product pricing plugins
- Shipping plugins
- Tax plugins
- Reporting plugins
- Iranian WooCommerce plugins
- Third-party integrations

WooSmart must never modify WooCommerce currency merely to change its own UI.

---

# Currency Input Behavior

The Automation Condition Builder contains a numeric input for monetary Conditions.

Example:

    مبلغ سفارش

    بیشتر از

    [ 100,000 ] تومان

The numeric input:

- Adds thousands separators for readability.
- Keeps the actual submitted numeric value clean.
- Uses the current WooCommerce currency display unit.
- Does not perform currency conversion.
- Does not alter the WooCommerce order value.

The current UI separates the numeric value from the currency label visually.

Example:

    100,000        تومان

The number and currency label are intentionally displayed as separate visual elements to avoid RTL/LTR rendering problems.

---

# Important Currency Safety Rule

A user-interface display choice must never silently modify a stored monetary value.

Therefore:

    UI unit
        ≠
    currency conversion

WooSmart only changes the display language of the currency unit.

WooCommerce remains responsible for actual monetary interpretation.

---

# What Has Been Implemented

## Foundation

- [x] Plugin bootstrap
- [x] Plugin activation
- [x] Plugin deactivation
- [x] WooCommerce dependency detection
- [x] WooCommerce admin notice
- [x] Internal Automation Custom Post Type

## Admin

- [x] WooSmart admin menu
- [x] Dashboard
- [x] Automation list
- [x] Create Automation
- [x] Edit Automation
- [x] Enable / Disable Automation
- [x] Duplicate Automation
- [x] Delete Automation
- [x] Logs page
- [x] Persian interface
- [x] RTL interface
- [x] Persian terminology
- [x] Persian action labels
- [x] Persian order-status labels
- [x] WooCommerce-aware currency display
- [x] IRT → تومان display
- [x] Numeric input formatting
- [x] Thousands separators in amount input
- [x] Separate currency label inside amount field

## Automation Management

- [x] Create
- [x] Update
- [x] Toggle
- [x] Duplicate
- [x] Delete
- [x] Validation
- [x] Trigger validation
- [x] Condition validation
- [x] Condition Registry validation
- [x] Action validation
- [x] Notification configuration validation
- [x] Existing Automation validation

## Trigger System

- [x] Trigger infrastructure
- [x] Order Created trigger
- [x] WooCommerce order context

## Condition System

- [x] Condition Registry
- [x] Condition Engine
- [x] Order Total
- [x] Equal
- [x] Not Equal
- [x] Greater Than
- [x] Greater Than or Equal
- [x] Less Than
- [x] Less Than or Equal
- [x] Registry-driven operators
- [x] Registry-driven field metadata

## Action System

- [x] Action Registry
- [x] Action Engine
- [x] Change Order Status
- [x] Notify Store Administrator
- [x] Action validation
- [x] Action success detection
- [x] Action failure detection
- [x] Action execution logging
- [x] Multiple Action configuration storage
- [x] Multiple Action sequential execution

## Execution

- [x] Trigger processing
- [x] Active Automation lookup
- [x] Multiple Automation detection
- [x] Condition evaluation
- [x] Action execution
- [x] Successful execution logging
- [x] Failed Action logging
- [x] Failed Automation logging
- [x] Execution result handling
- [x] Diagnostic Automation scan

## Logging

- [x] Event logging
- [x] Context logging
- [x] Action-level failure logging
- [x] Automation-level failure logging
- [x] Persian log labels
- [x] Condition pass/failure logging
- [x] Notification failure diagnostics
- [x] Latest 100 log entries retention

---

# End-to-End Tests Already Confirmed

The following behavior has been tested using real WooCommerce orders.

## Successful Condition

A real WooCommerce order above the configured threshold produced:

    condition_passed

and the matching Automation continued to Action execution.

---

## Failed Condition

A real WooCommerce order that did not satisfy a condition produced:

    condition_failed

and:

    automation_conditions_failed

No Action was executed for the failed Automation.

---

## Successful Status Action

Confirmed:

    Action:
        change_order_status

Result examples:

    pending → processing

    pending → on-hold

Other WooCommerce status transitions were also tested during development.

---

## Multiple Automation Detection

Multiple active Automations can respond to the same Trigger.

A real test produced:

    automation_scan

with:

    found_count: 3

and:

    automation_ids:
        48
        43
        35

This confirms that the Execution Engine can discover multiple matching Automations.

---

## Automation 43

A real order above the configured threshold for Automation 43 produced:

    condition_passed

for:

    order_total
    greater_than
    1000000

This confirmed that:

- Automation 43 is active.
- Automation 43 is discovered by the Trigger execution path.
- The Condition Engine correctly evaluates the order total.
- The Action phase is reached.

---

# Notification System

The `notify_admin` Action has been implemented.

The Action Engine calls:

    wp_mail()

The notification Action supports:

    recipient
    subject
    message

Supported placeholders:

    {order_id}
    {order_total}
    {order_status}
    {customer_name}

Example:

    subject:
        اعلان سفارش جدید در WooSmart

    message:
        یک سفارش جدید با شرایط اتوماسیون مطابقت دارد.

        شناسه سفارش: {order_id}
        مبلغ سفارش: {order_total}
        وضعیت سفارش: {order_status}
        نام مشتری: {customer_name}

---

# SMTP Limitation

The local XAMPP environment does not currently provide a working mail transport for the WooSmart notification Action.

The first observed error was:

    نشانی نامعتبر: (From): wordpress@localhost

This was traced to the default local WordPress sender.

WooSmart was then changed during development to explicitly use the configured WordPress administrator email as the development sender.

The logs subsequently showed:

    from:
        mht7631@gmail.com

This confirmed that the original:

    wordpress@localhost

problem was bypassed.

The current error is different:

    نمی‌توان تابع ایمیل را نمونه‌سازی کرد.

with:

    mail_error_code:
        2

Therefore the remaining issue is now the local mailer / transport initialization.

Current execution path:

    Condition Passed
        ↓
    notify_admin
        ↓
    Sender Address Valid
        ↓
    wp_mail()
        ↓
    Mailer Initialization
        ↓
    Failure

This is considered an environment / WordPress mail transport issue rather than a Condition Engine issue.

---

# SMTP Architectural Decision

SMTP is not part of the WooSmart core.

WooSmart uses:

    wp_mail()

Email delivery should eventually be provided by:

- WordPress SMTP configuration
- Server-level mail transport
- External SMTP provider
- Other WordPress-compatible mail transport

WooSmart should not implement its own SMTP engine.

The current development sender is temporary and should eventually be replaced by configurable Notification Settings.

---

# Confirmed Failure Logging

A failed notification Action has already been observed in the logs.

Example:

    خطا در عملیات
    اجرای عملیات با خطا مواجه شد.

Context includes:

    action_type
    recipient
    from
    order_id
    mail_error
    mail_error_code

The Execution Engine then records:

    automation_failed

with:

    automation_id
    trigger
    context
    actions_successful: false

This confirms that the Automation execution logic correctly distinguishes between:

    successful Action
    failed Action
    successful Automation
    failed Automation

---

# Current Logging Events

The project currently uses events such as:

    order_created
    automation_created
    automation_updated
    automation_deleted
    automation_status_changed
    automation_duplicated
    automation_skipped
    automation_conditions_failed
    automation_executed
    automation_failed
    action_executed
    action_failed
    condition_passed
    condition_failed
    automation_scan

The user-facing log labels are localized to Persian.

Internal event identifiers remain stable English identifiers.

---

# Important Current Limitation: Multiple Automations

The current engine intentionally allows multiple active Automations to respond to the same Trigger.

This is currently functional, but it creates a potential conflict when two or more Automations modify the same target property.

Example:

    Automation A:
        Order Total > 500,000
        → Status = Processing

    Automation B:
        Order Total < 10,000,000
        → Status = Completed

For an order worth 2,000,000:

    Both Conditions = TRUE

Therefore both Automations may execute.

Because both modify:

    Order Status

the second execution can overwrite the first result.

This behavior is not considered the final product behavior.

It is an identified architectural limitation that will be solved through Conflict Detection, Execution Priority, and Execution Policy.

---

# Planned Conflict Management

The long-term solution is not simply adding Priority.

WooSmart will use a combination of:

    Conflict Detection
    +
    Execution Priority
    +
    Execution Policy

These three concepts will work together.

---

# Conflict Detection

When a user creates or edits an Automation, WooSmart should analyze active Automations that share the same Trigger.

Conflict analysis should consider:

    Trigger
    +
    Condition overlap
    +
    Action target
    +
    Action effect

Example:

    Automation A:
        Order Total > 500,000
        → Status = Processing

    Automation B:
        Order Total < 10,000,000
        → Status = Completed

The condition ranges overlap.

Both Automations modify:

    Order Status

Therefore:

    Conflict = TRUE

The system should detect this before the user accidentally creates an unpredictable workflow.

---

# Conflict Levels

Future conflict analysis will support at least three levels.

## Safe

No meaningful overlap exists.

    🟢 Safe

Example:

    Automation A:
        Order Total > 5,000,000
        → Send Email

    Automation B:
        Product Stock < 5
        → Send Notification

---

## Potential Conflict

Conditions may overlap, but Actions do not directly produce contradictory results.

    🟡 Potential Conflict

Example:

    Automation A
        → Send Email

    Automation B
        → Add Order Note

Both can safely execute, although they may both respond to the same order.

---

## Conflict

Multiple Automations can act on the same property with different outcomes.

    🔴 Conflict

Example:

    Automation A
        → Processing

    Automation B
        → Completed

---

# Planned Conflict UX

When a conflict is detected, the user should receive a clear warning.

Example:

    ⚠️ هشدار تداخل

    این اتوماسیون با یک اتوماسیون فعال دیگر
    هم‌پوشانی دارد.

    هر دو اتوماسیون ممکن است برای یک سفارش
    اجرا شوند و وضعیت سفارش را تغییر دهند.

Possible choices:

    ویرایش شرایط
    ذخیره به‌صورت غیرفعال
    فعال‌سازی با پذیرش هشدار

A conflict should never silently modify or delete another Automation.

The system should explain:

    Which Automation conflicts
    Which Conditions overlap
    Which Action conflicts
    What property may be overwritten

---

# Planned Execution Priority

Each Automation will eventually support:

    Priority

Example:

    Priority 10
    Priority 20
    Priority 30

The lower number will execute earlier.

Example:

    Priority 10
        ↓
    Automation A

    Priority 20
        ↓
    Automation B

This makes execution deterministic.

WooSmart must not rely on incidental database query order.

Priority alone is not considered sufficient conflict management.

---

# Planned Execution Policy

Execution Policy determines what happens after an Automation executes successfully.

The intended model is:

    Continue
    or
    Stop

Example:

    Automation A
        ↓
    Action Successful
        ↓
    STOP

This can prevent lower-priority Automations from overriding an earlier result.

The exact UI and policy options will be designed when Execution Planning is implemented.

The current intended default policy for the future system is:

    Continue

unless a conflict or explicit Automation configuration requires stopping execution.

This is a planned design decision, not yet implemented.

---

# Planned Execution Planning

The future execution architecture should become:

    Trigger
        ↓
    Find Matching Automations
        ↓
    Evaluate Conditions
        ↓
    Conflict Analysis
        ↓
    Sort by Priority
        ↓
    Apply Execution Policy
        ↓
    Execute Actions
        ↓
    Collect Results
        ↓
    Logger

The purpose is to make Automation execution:

    Predictable
    Deterministic
    Explainable
    Debuggable
    Extensible

---

# Planned Multiple Actions

The current Action system already supports Action configurations in an array and has been tested with Automations containing more than one Action configuration.

The Action Engine can execute multiple Actions sequentially.

However, full Multiple Actions support remains a development milestone.

The target model is:

    THEN

    Action 1
    AND
    Action 2
    AND
    Action 3

Example:

    Order Total > 5,000,000

    THEN

    Send Notification
    AND
    Change Status → Processing
    AND
    Add Order Note

The UI should eventually allow users to:

- Add an Action.
- Remove an Action.
- Reorder Actions.
- Configure each Action independently.
- Validate every Action.
- See the result of each Action separately.

---

# Planned Action Architecture

The Action system should remain extensible.

Adding a new Action should not require rewriting the Execution Engine.

Target architecture:

    Execution Engine
        ↓
    Action Engine
        ↓
    Action Registry
        ↓
    Action Type
        ↓
    Action Handler

The Execution Engine should be responsible for orchestration.

The Action Engine should be responsible for Action execution.

Individual Actions should contain their own Action-specific logic.

This allows future Actions to be added without tightly coupling them to the Automation core.

---

# Planned Action Expansion

## Order Actions

Currently implemented:

- [x] Change Order Status

Future:

- [ ] Add Order Note
- [ ] Modify Order Metadata
- [ ] Apply Coupon
- [ ] Modify Order Items
- [ ] Add Product to Order
- [ ] Remove Product from Order

---

## Notification Actions

Currently implemented:

- [x] Notify Store Administrator by Email

Future:

- [ ] Customer Email
- [ ] Additional Admin Email
- [ ] SMS
- [ ] WhatsApp
- [ ] Telegram
- [ ] Push Notification

---

## External Actions

Future:

- [ ] Webhook
- [ ] HTTP Request
- [ ] REST API
- [ ] Slack
- [ ] Discord
- [ ] Google Sheets
- [ ] CRM integrations

---

# Planned Trigger Expansion

## Orders

Currently implemented:

- [x] Order Created

Future:

- [ ] Order Paid
- [ ] Order Status Changed
- [ ] Order Completed
- [ ] Order Cancelled
- [ ] Order Failed
- [ ] Order Refunded
- [ ] Order On Hold

---

## Customers

Future:

- [ ] Customer Registered
- [ ] Customer Login
- [ ] Customer Role Changed

---

## Products

Future:

- [ ] Product Created
- [ ] Product Updated
- [ ] Product Stock Changed
- [ ] Product Becomes In Stock
- [ ] Product Becomes Out of Stock

---

## Cart / Checkout

Future:

- [ ] Cart Updated
- [ ] Checkout Started
- [ ] Checkout Completed
- [ ] Abandoned Cart

---

# Planned Condition Expansion

## Order Conditions

Currently implemented:

- [x] Order Total

Future:

- [ ] Order Subtotal
- [ ] Order Status
- [ ] Payment Method
- [ ] Shipping Method
- [ ] Coupon
- [ ] Customer
- [ ] Billing Country
- [ ] Shipping Country
- [ ] Order Item Count
- [ ] Product
- [ ] Product Category
- [ ] Product Quantity

---

## Customer Conditions

Future:

- [ ] Customer Role
- [ ] Customer Email
- [ ] Customer Order Count
- [ ] Customer Total Spent
- [ ] Customer Registration Date

---

## Product Conditions

Future:

- [ ] Product Price
- [ ] Stock Quantity
- [ ] Stock Status
- [ ] Category
- [ ] SKU
- [ ] Product Type

---

# Planned Multiple Conditions

Current condition execution concept:

    All configured conditions must pass.

Future:

    Condition 1
    AND
    Condition 2
    AND
    Condition 3

Later:

    Group 1
        Condition
        AND
        Condition

    OR

    Group 2
        Condition
        AND
        Condition

The future condition system should support:

    AND
    OR
    Nested Groups

The exact data structure will be designed before implementation so that the model remains extensible.

---

# Planned Notification System

The notification architecture should eventually support multiple channels.

    Notification
    │
    ├── Email
    ├── SMS
    ├── Telegram
    ├── WhatsApp
    └── Webhook

The core Automation Engine should not be tightly coupled to a single messaging provider.

Notification providers should be replaceable and extensible.

---

# Planned Execution History

The current Logs page provides basic event logging.

A future dedicated Execution History system will track:

    Execution ID
    Automation ID
    Trigger
    Start Time
    End Time
    Status
    Conditions Result
    Actions Result
    Error
    Context

Possible statuses:

    pending
    running
    completed
    failed
    skipped
    cancelled

This will eventually be separate from the lightweight event Logger.

---

# Planned Automation Trace

WooSmart should eventually explain why an Automation executed.

Example:

    Order #42

    Automation Trace

    #40
    Conditions: Passed
    Action: Status → Completed

    #35
    Conditions: Passed
    Action: Status → Processing

    Final Status:
    Processing

    Reason:
    Automation #35 executed later due to priority.

This feature will be important for debugging complex Automation workflows.

---

# Planned Delayed and Scheduled Actions

Future versions may support:

    Wait 1 hour
    Wait 24 hours
    Execute at a specific time
    Execute after a condition remains true

Example:

    Order Created
        ↓
    Wait 24 hours
        ↓
    Send Reminder

Another example:

    Payment Pending
        ↓
    Wait 2 hours
        ↓
    Notify Administrator

Potential infrastructure:

- [ ] WP-Cron
- [ ] Action Queue
- [ ] Background Processing
- [ ] Retry Mechanism
- [ ] Failed Job Handling

---

# Planned Error Handling

The production system should eventually include:

- [ ] Structured errors
- [ ] Action-level error details
- [ ] Automation-level error details
- [ ] Retry count
- [ ] Retry delay
- [ ] Failed execution queue
- [ ] Error notifications
- [ ] Debug mode

The current MVP already distinguishes Action failure from Automation failure.

The current notification system also records the underlying `wp_mail_failed` information when WordPress exposes it.

---

# Planned Retry System

Future Action execution may support retry policies.

Example:

    Action Failed
        ↓
    Retry #1
        ↓
    Retry #2
        ↓
    Retry #3
        ↓
    Permanently Failed

The retry system should eventually support:

    Maximum retries
    Retry delay
    Exponential backoff
    Permanent failure
    Failure notification

This should be implemented together with the future execution queue / background processing architecture.

---

# Planned Performance Improvements

The current implementation is suitable for MVP development and real WooCommerce testing.

Production-scale improvements may include:

- [ ] Automation caching
- [ ] Optimized Automation queries
- [ ] Reduced database queries
- [ ] Dedicated Logs table
- [ ] Background execution
- [ ] Execution queue
- [ ] Better handling of large Automation counts
- [ ] Better handling of large order volumes
- [ ] Efficient Trigger filtering
- [ ] Execution batching where appropriate

Performance optimization should be driven by real requirements rather than premature architectural complexity.

---

# Planned Database Evolution

Current architecture:

    WordPress Custom Post Type
    +
    Post Meta
    +
    WordPress Options

Current Logger storage:

    WordPress Options

Future architecture may introduce dedicated tables for:

    Automations
    Executions
    Logs
    Scheduled Jobs

Potential tables:

    wp_woosmart_automations
    wp_woosmart_executions
    wp_woosmart_logs
    wp_woosmart_jobs

The exact database architecture will be decided after the feature and performance requirements become clear.

---

# Security

Current security measures:

- [x] Capability checks
- [x] Nonces
- [x] Input sanitization
- [x] Output escaping
- [x] Action validation
- [x] Trigger validation
- [x] Condition validation
- [x] Automation configuration validation

Future security work:

- [ ] REST API authentication
- [ ] Webhook authentication
- [ ] Secure credential storage
- [ ] External request validation
- [ ] SSRF protection
- [ ] Permission separation
- [ ] Action-level permissions
- [ ] Rate limiting for external requests

Security must be considered before implementing Webhooks, HTTP Requests, external APIs, or third-party integrations.

---

# Testing Strategy

WooSmart is being developed using incremental testing.

Every major feature should be tested through the real WooCommerce workflow.

Development cycle:

    Build
      ↓
    Replace Full File
      ↓
    Activate Plugin
      ↓
    Test Admin UI
      ↓
    Test Real WooCommerce Order
      ↓
    Check Logs
      ↓
    Fix
      ↓
    Commit Stable Version
      ↓
    Update README
      ↓
    Continue

---

# Current Testing Status

The following end-to-end behavior has been confirmed:

- [x] Create Automation
- [x] Edit Automation
- [x] Enable / Disable
- [x] Duplicate
- [x] Delete
- [x] Order Created trigger
- [x] Order Total condition
- [x] Condition pass
- [x] Condition failure
- [x] Change Order Status
- [x] Multiple Action configuration storage
- [x] Multiple Action execution
- [x] Condition Registry
- [x] Action Registry
- [x] Registry-driven Condition UI
- [x] Action execution
- [x] Failed Action detection
- [x] Failed Automation detection
- [x] Persian Admin UI
- [x] RTL interface
- [x] WooCommerce IRT configuration
- [x] WooSmart تومان display
- [x] WooCommerce-aware currency display
- [x] No independent currency conversion
- [x] Formatted amount input
- [x] Thousands separators in amount input
- [x] Real WooCommerce order execution
- [x] Automation execution logging
- [x] Action execution logging
- [x] Condition failure logging
- [x] Notification Action failure logging
- [x] Mail failure diagnostics
- [x] Multiple Automation detection

Pending:

- [ ] Local mail transport configuration
- [ ] Real SMTP delivery test
- [ ] Dedicated Notification Settings
- [ ] Formal Multiple Actions UI stabilization
- [ ] Conflict Detection
- [ ] Execution Priority
- [ ] Execution Policy
- [ ] Multiple Conditions

---

# Current Known Issues / Pending Tasks

## High Priority

- [ ] Resolve local XAMPP mail transport initialization failure.
- [ ] Verify `notify_admin` end-to-end with real SMTP configuration.
- [ ] Complete Multiple Actions UI.
- [ ] Implement Conflict Detection.
- [ ] Implement Execution Priority.
- [ ] Implement Execution Policy.
- [ ] Make execution planning deterministic.

## Medium Priority

- [ ] Multiple Conditions.
- [ ] AND / OR groups.
- [ ] More Order Conditions.
- [ ] More Order Actions.
- [ ] Better execution summaries.
- [ ] Better conflict warnings.
- [ ] Better Action-level error messages.
- [ ] Dedicated Notification Settings.

## Future

- [ ] Execution History.
- [ ] Automation Trace.
- [ ] Scheduled Actions.
- [ ] Retry Queue.
- [ ] Additional Triggers.
- [ ] Additional Conditions.
- [ ] Additional Actions.
- [ ] Integrations.
- [ ] Developer API.
- [ ] Advanced Automation Builder.
- [ ] Automated Test Suite.

---

# Immediate Development Roadmap

The recommended development order is:

    STEP 1
    Resolve local mail transport
        ↓
    STEP 2
    Verify real email delivery
        ↓
    STEP 3
    Complete Action Registry integration
        ↓
    STEP 4
    Stabilize Multiple Actions
        ↓
    STEP 5
    Conflict Detection
        ↓
    STEP 6
    Execution Priority
        ↓
    STEP 7
    Execution Policy
        ↓
    STEP 8
    Multiple Conditions
        ↓
    STEP 9
    More Conditions
        ↓
    STEP 10
    More Order Actions
        ↓
    STEP 11
    More Triggers
        ↓
    STEP 12
    Execution History
        ↓
    STEP 13
    Automation Trace
        ↓
    STEP 14
    Scheduling
        ↓
    STEP 15
    Retry System
        ↓
    STEP 16
    Integrations
        ↓
    STEP 17
    Professional Automation Builder

SMTP is an environment-level task and should be tested whenever the Notification Action is being validated, but SMTP itself is not intended to become part of the WooSmart core.

---

# First Major Product Goal

The first major complete Automation target is:

    WHEN
    ایجاد سفارش

    IF
    مبلغ سفارش > 100,000 تومان

    THEN
    ارسال اعلان به مدیر فروشگاه

    AND

    تغییر وضعیت سفارش → در حال پردازش

This workflow should work reliably through the full WooCommerce lifecycle.

The final behavior should be:

    WooCommerce Order Created
        ↓
    Trigger Detected
        ↓
    Matching Automation Found
        ↓
    Conditions Evaluated
        ↓
    Conditions Passed
        ↓
    Actions Executed
        ↓
    Results Collected
        ↓
    Execution Logged

---

# Long-Term Product Vision

The final WooSmart platform should allow a store owner to build workflows such as:

    WHEN
        Order Created

    IF
        Order Total > 500,000 تومان
        AND
        Payment Method = Bank Transfer
        AND
        Customer Role = Wholesale

    THEN
        Notify Store Administrator
        AND
        Change Order Status → Processing
        AND
        Add Order Note

And eventually:

    WHEN
        Product Stock Changes

    IF
        Stock < 5

    THEN
        Notify Store Administrator
        AND
        Send Telegram Alert
        AND
        Send Webhook

All of this should be configurable from the WordPress admin.

---

# Future Automation Builder

The long-term UI should evolve from the current basic form into a visual and user-friendly Automation Builder.

Target concept:

    WHEN
        [Trigger]

    IF
        [Condition]
        [+ Add Condition]

    THEN
        [Action]
        [+ Add Action]

The builder should eventually provide:

- Dynamic Trigger selection.
- Dynamic Condition selection.
- Dynamic Operator selection.
- Dynamic Action selection.
- Multiple Conditions.
- Multiple Actions.
- Condition Groups.
- AND / OR logic.
- Action ordering.
- Priority.
- Execution Policy.
- Conflict warnings.
- Human-readable Automation summaries.

The UI should hide unnecessary technical details from normal store administrators while retaining a stable internal data model.

---

# Development Principles

## 1. Modular Architecture

Each major responsibility should have a clear module.

The project should avoid placing unrelated responsibilities into a single class.

---

## 2. Do Not Break Existing Features

New functionality must preserve existing behavior.

Before changing a working component:

    Understand
    ↓
    Modify
    ↓
    Test Existing Behavior
    ↓
    Test New Behavior

---

## 3. Incremental Development

Develop one major feature at a time.

    Build
    ↓
    Test
    ↓
    Fix
    ↓
    Commit
    ↓
    Document
    ↓
    Continue

---

## 4. Real WooCommerce Testing

Core features must be verified against real WooCommerce orders.

A feature is not considered complete merely because the admin UI works.

The execution path must also be tested.

---

## 5. Full File Replacement

During collaborative development, when a file changes, the complete file should be provided for replacement rather than isolated snippets.

This is a deliberate project workflow because it reduces accidental merge errors and makes testing easier.

---

## 6. Review Current File Before Modification

Before modifying any existing project file:

- The current version of that file must be reviewed.
- Existing functionality must be preserved unless intentionally changed.
- Changes must be based on the actual current project state.
- Do not assume that an older version of a file is still present.

---

## 7. Git as Source of Truth

GitHub is the project source of truth.

Major milestones should be committed.

Suggested commit history:

    v1.0.0 - Initial MVP
    v1.1.0 - Action and Notification System
    v1.2.0 - Multiple Actions
    v1.3.0 - Conflict Detection
    v1.4.0 - Execution Priority
    v1.5.0 - Multiple Conditions
    v1.6.0 - Additional Triggers
    v1.7.0 - Execution History
    v1.8.0 - Scheduling
    v2.0.0 - Professional Automation Builder

Actual version numbers may change depending on the final release strategy.

---

# README Maintenance Rule

The README is part of the project state.

Whenever a major development milestone is completed, the README should be updated.

The README should record:

- What has been implemented.
- What has been tested.
- What is currently working.
- What limitations remain.
- What is currently being developed.
- What has intentionally been postponed.
- What the next development step is.
- Important architectural decisions.
- Important diagnostic findings.
- Important currency decisions.

This prevents the project roadmap from being lost between development sessions or separate conversations.

---

# Future Decisions Register

During development, some ideas may be discussed but intentionally postponed.

These must not be treated as forgotten requirements.

They should remain documented until one of the following happens:

    Implemented
    Rejected
    Replaced by a better design

Important postponed areas currently include:

- Conflict Detection.
- Execution Priority.
- Execution Policy.
- Multiple Conditions.
- AND / OR condition groups.
- Advanced Multiple Actions UI.
- Full Action Registry-driven UI.
- Execution History.
- Automation Trace.
- Scheduling.
- Retry System.
- Additional Triggers.
- Additional Conditions.
- Additional Actions.
- External Integrations.
- Developer API.
- Advanced Automation Builder.
- Dedicated database tables.
- Automated tests.
- Notification Settings.
- Production SMTP configuration.

---

# Important Architectural Decisions

## WooCommerce Controls Currency

WooCommerce does not receive its currency system from WooSmart.

WooCommerce remains the source of truth.

WooSmart reads the current WooCommerce currency and displays it appropriately in its own UI.

---

## No Independent Rial / Toman Conversion

WooSmart does not create a second monetary conversion layer.

If WooCommerce uses:

    IRT

WooSmart displays:

    تومان

If WooCommerce uses:

    IRR

WooSmart displays:

    ریال

No 10x or 0.1x conversion is performed by WooSmart.

---

## WooSmart Does Not Modify Store Prices

WooSmart does not:

- Change product prices.
- Change order totals.
- Change payment amounts.
- Change gateway behavior.
- Change WooCommerce currency settings.

This protects compatibility with existing WooCommerce configurations.

---

## SMTP Is Not Part of the Core

WooSmart uses:

    wp_mail()

SMTP delivery is considered an environment / WordPress configuration concern.

The core Action Engine should not implement an SMTP server or become dependent on a specific SMTP provider.

---

## Internal Identifiers Remain English

User-facing labels can be Persian.

Internal identifiers remain English.

Examples:

    order_created
    order_total
    greater_than
    notify_admin
    change_order_status

This improves maintainability, compatibility, and future internationalization.

---

## Execution Order Must Become Deterministic

WooSmart must not depend on incidental database ordering.

When multiple Automations match the same Trigger, future execution planning must explicitly determine:

    Which Automation runs first
    Which Action runs first
    Whether execution continues
    Whether execution stops
    Whether a conflict exists
    Which result becomes authoritative

---

## Conflict Detection Must Not Modify Existing Automations Silently

If a new Automation conflicts with an existing Automation:

- Existing Automations must not be silently changed.
- Existing Automations must not be silently disabled.
- The user must be informed.
- The user must decide how to proceed.

---

## Mail Diagnostics Must Preserve Root Cause

When mail delivery fails, WooSmart should preserve the underlying WordPress / PHPMailer error whenever available.

This makes troubleshooting possible without hiding environment-level errors behind generic messages.

---

## Registry Architecture Is the Source of Truth

Conditions should be defined in the Condition Registry.

Actions should be defined in the Action Registry.

Other layers should consume those definitions rather than duplicate them.

This is intended to make the system extensible and reduce inconsistent validation between UI, Manager, and Execution layers.

---

# Current Status Summary

    Core:
        🟢 Functional

    Admin:
        🟢 Persian / RTL MVP

    WooCommerce Currency:
        🟢 IRT in current development store

    WooSmart Currency Display:
        🟢 تومان for IRT

    Independent Currency Conversion:
        🟢 Not used

    Trigger Engine:
        🟡 One trigger implemented

    Condition Registry:
        🟢 Implemented

    Condition Engine:
        🟢 Functional

    Condition Builder:
        🟢 Registry-driven

    Action Registry:
        🟢 Implemented

    Action Engine:
        🟢 Functional

    Actions:
        🟢 Change Order Status
        🟢 Notify Store Administrator

    Multiple Action Data Model:
        🟢 Supported

    Multiple Action Execution:
        🟢 Supported

    Multiple Actions UI:
        🟡 Stabilization / improvement planned

    Notification:
        🟡 Implemented / Mail Transport pending

    Email From Diagnostics:
        🟢 Implemented

    Mail Failure Diagnostics:
        🟢 Implemented

    Execution Engine:
        🟢 Functional and tested

    Multiple Automation Detection:
        🟢 Confirmed

    Validation:
        🟢 Implemented

    Logging:
        🟢 Functional MVP

    Failure Handling:
        🟢 Implemented

    Conflict Detection:
        🔴 Planned

    Execution Priority:
        🔴 Planned

    Execution Policy:
        🔴 Planned

    Multiple Conditions:
        🔴 Planned

    Condition Groups:
        🔴 Planned

    Additional Triggers:
        🔴 Planned

    Additional Actions:
        🔴 Planned

    Execution History:
        🔴 Planned

    Automation Trace:
        🔴 Planned

    Scheduling:
        🔴 Planned

    Retry System:
        🔴 Planned

    Integrations:
        🔴 Planned

    Developer API:
        🔴 Planned

    Automated Tests:
        🔴 Future

---

# Current Development Target

The next development target is not to add random features.

The immediate objective is to complete and verify the Notification Action's mail transport in the development environment.

The next development sequence is:

    Local Mail Transport
        ↓
    Real Email Delivery
        ↓
    Multiple Actions Stabilization
        ↓
    Conflict Detection
        ↓
    Execution Priority
        ↓
    Execution Policy
        ↓
    Multiple Conditions
        ↓
    Expanded Conditions
        ↓
    Expanded Actions
        ↓
    Expanded Triggers

The central requirement is:

    One Trigger
        ↓
    Multiple Matching Automations
        ↓
    Predictable Execution
        ↓
    No Silent Conflicts
        ↓
    Clear Result
        ↓
    Complete Logging

---

# Final Product Architecture

The long-term WooSmart execution architecture should become:

    Trigger
        ↓
    Condition Evaluation
        ↓
    Conflict Analysis
        ↓
    Execution Planning
        ↓
    Priority Ordering
        ↓
    Execution Policy
        ↓
    Action Execution
        ↓
    Action Results
        ↓
    Automation Result
        ↓
    Execution History
        ↓
    Monitoring / Trace

The project priority is not simply adding more features.

The priority is creating a stable, predictable, debuggable, secure, and extensible WooCommerce automation engine.

---

# Project Rule for Future Development

When continuing the project in a new development session:

1. Treat the GitHub repository and current project files as the source of truth.
2. Review the current implementation before changing a file.
3. Do not assume an older file version is still current.
4. Preserve existing working functionality.
5. Work incrementally.
6. Test every major change with real WooCommerce behavior.
7. Check the WooSmart Logs after each execution test.
8. When a file must be changed, provide the complete file so it can be fully replaced.
9. Update README.md after every meaningful milestone.
10. Keep postponed features documented so they are not forgotten.
11. Record important architectural decisions in README.md.
12. Do not implement future features prematurely when the current milestone has not been fully tested.
13. Before moving to a new major stage, confirm that the previous stage is stable.
14. Git commits should represent stable development milestones.
15. Never replace a large project file from a truncated or incomplete source.
16. Verify the complete file before committing large file replacements.
17. When GitHub is available, review the current repository version before modifying an existing file.
18. Do not introduce a second currency system when WooCommerce already supplies the required currency context.
19. Do not perform silent currency conversions because of a display requirement.
20. Preserve real runtime error information in logs.
21. Keep Persian Admin terminology consistent across all UI components.
22. Treat GitHub as the canonical project state when synchronizing future development sessions.

---

# End

WooSmart Automation is being developed as a real, extensible WooCommerce automation platform rather than a collection of unrelated features.

The core principle remains:

    WHEN
        something happens

    IF
        conditions are satisfied

    THEN
        execute predictable actions

with the long-term architecture expanding toward:

    Trigger
        ↓
    Conditions
        ↓
    Conflict Analysis
        ↓
    Execution Planning
        ↓
    Actions
        ↓
    Results
        ↓
    Logging
        ↓
    Monitoring

The current foundation is functional.

The Condition Registry and Action Registry architectures are established.

Multiple matching Automations have been confirmed in real WooCommerce execution.

The current development store uses WooCommerce `IRT`.

WooSmart displays `تومان` for `IRT` without performing an independent Rial/Toman conversion.

The current known notification blocker is a local mail transport initialization failure:

    نمی‌توان تابع ایمیل را نمونه‌سازی کرد.

with:

    mail_error_code:
        2

The next immediate task is to resolve the local mail transport and verify real email delivery without adding SMTP-specific logic to the WooSmart core.

The next major architectural challenge remains making multiple Automations execute safely, deterministically, and transparently.
