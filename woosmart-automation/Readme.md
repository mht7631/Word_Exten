# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin designed to allow store administrators to create rule-based automations without writing code.

The long-term goal is to turn WooSmart Automation into a complete, reliable, extensible, and user-friendly automation platform for WooCommerce stores.

---

# Project Vision

The core concept of WooSmart Automation is:

    WHEN something happens
    IF certain conditions are satisfied
    THEN perform one or more actions

Example:

    WHEN
    ایجاد سفارش

    IF
    مبلغ سفارش > 1,000,000 ریال

    THEN
    ارسال اعلان به مدیر فروشگاه

Another example:

    WHEN
    ایجاد سفارش

    IF
    مبلغ سفارش > 5,000,000 ریال

    THEN
    ارسال اعلان به مدیر فروشگاه
    AND
    تغییر وضعیت سفارش → در حال پردازش

The final goal is to allow store owners to build complex workflows from the WordPress admin without programming.

---

# Repository

GitHub:

    https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the primary source of truth for the project.

---

# Current Project Status

Version:

    1.0.0

Current stage:

    MVP / Foundation

Current status:

    Core automation engine:
        Functional and tested

    Admin:
        Persian / RTL

    Trigger system:
        Functional

    Condition system:
        Functional

    Action system:
        Functional

    Multiple Actions:
        Implemented and tested

    Notification Action:
        Implemented

    Failure handling:
        Implemented and tested

    Conflict Detection:
        Planned

    Execution Priority:
        Planned

    Execution Policy:
        Planned

    Multiple Conditions:
        Planned

---

# Current Development Focus

The project has successfully moved beyond the basic single-action MVP.

Current priorities:

1. Reliable notification delivery
2. Conflict Detection
3. Execution Priority
4. Execution Policy
5. Better execution planning
6. Multiple Conditions
7. More Order Conditions
8. More Triggers
9. More Actions
10. Execution History

---

# Architecture

Current architecture:

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
    Logger

Administrative architecture:

    WordPress Admin
         ↓
    WooSmart Admin
         ↓
    Automation Manager
         ↓
    Automation Data
         ↓
    Execution Engine

Planned production execution architecture:

    Trigger
         ↓
    Automation Discovery
         ↓
    Condition Evaluation
         ↓
    Conflict Analysis
         ↓
    Execution Planning
         ↓
    Action Engine
         ↓
    Execution Result
         ↓
    Logging / Monitoring

---

# File Structure

    woosmart-automation/
    │
    ├── woosmart-automation.php
    │
    └── includes/
        ├── class-woosmart-core.php
        ├── class-woosmart-logger.php
        ├── class-woosmart-admin.php
        ├── class-woosmart-automation.php
        ├── class-woosmart-triggers.php
        ├── class-woosmart-post-types.php
        ├── class-woosmart-automation-manager.php
        ├── class-woosmart-condition-engine.php
        ├── class-woosmart-action-engine.php
        └── class-woosmart-execution-engine.php

---

# File Responsibilities

## woosmart-automation.php

Main plugin bootstrap.

Responsibilities:

* Load plugin classes
* Initialize shared services
* Initialize the execution engine
* Initialize the trigger system
* Handle activation
* Handle deactivation
* Flush rewrite rules when required

The architecture uses shared Condition and Action engines instead of unnecessary duplicate engine instances.

---

## class-woosmart-core.php

Core plugin functionality.

Responsibilities:

* Detect WooCommerce
* Display WooCommerce dependency warnings
* Provide WooCommerce availability status
* Handle WooCommerce-related environment functionality
* Format Iranian Rial display when WooCommerce currency is IRR

For Iranian stores, WooSmart displays:

    1,500,000 ریال

instead of:

    ﷼1,500,000.00

WooCommerce remains the source of truth for the actual store currency.

WooSmart does not replace WooCommerce's currency system.

---

## class-woosmart-logger.php

Central logging system.

Responsibilities:

* Store automation events
* Store execution context
* Store Action-level results
* Store Automation-level results
* Retrieve logs
* Clear logs
* Keep the latest 100 log entries in the current MVP

Current storage:

    WordPress Options

Future storage:

    Dedicated database table

---

## class-woosmart-admin.php

Main WooSmart administration interface.

Current sections:

* داشبورد
* اتوماسیون‌ها
* افزودن اتوماسیون
* گزارش‌ها

The interface is:

    Persian
    RTL

Internal identifiers remain English for stability and compatibility.

Examples:

    order_created
    order_total
    greater_than
    change_order_status
    notify_admin

Recommended user terminology:

| Internal Term | Persian UI |
|---|---|
| Trigger | رویداد |
| Condition | شرط |
| Action | عملیات |
| Automation | اتوماسیون |
| Execution | اجرا |
| Status | وضعیت |
| Active | فعال |
| Inactive | غیرفعال |

---

## class-woosmart-automation.php

Main Automation foundation.

Current status:

    Foundation / Placeholder

Future responsibilities may include:

* Automation lifecycle
* Automation registry
* Automation-level services
* Automation events

---

## class-woosmart-triggers.php

Trigger system.

Current trigger:

    order_created

Current WooCommerce hook:

    woocommerce_new_order

The trigger creates an execution context containing the WooCommerce order ID.

Example:

    {
        "order_id": 45
    }

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

* Create
* Update
* Enable / Disable
* Duplicate
* Delete

Current validation includes:

* Trigger validation
* Condition validation
* Condition value validation
* Action type validation
* Order status validation
* Notification configuration validation
* Prevention of empty Action sets
* Activation-time validation of existing Automation data

---

## class-woosmart-condition-engine.php

Evaluates Automation conditions.

Current field:

    order_total

Current operators:

    is_equal
    is_not_equal
    greater_than
    greater_than_or_equal
    less_than
    less_than_or_equal

Current condition model:

    All configured conditions must pass.

Future condition model:

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

---

## class-woosmart-action-engine.php

Executes Automation Actions.

Current Actions:

    change_order_status
    notify_admin

The Action Engine is connected to the Execution Engine.

Responsibilities:

* Receive Action configurations
* Execute Actions
* Return success/failure information
* Log successful Actions
* Log failed Actions

The Execution Engine does not implement individual Action logic.

This separation is intentional so new Actions can be added without rewriting the core execution system.

---

# Current Actions

## change_order_status

Changes the WooCommerce order status.

Current supported statuses:

    pending
    processing
    on-hold
    completed
    cancelled
    refunded
    failed

The Action is stored in the Automation configuration.

Example:

    [
        {
            "type": "change_order_status",
            "status": "processing"
        }
    ]

---

## notify_admin

Sends an email notification to the WordPress store administrator using:

    wp_mail()

Supported placeholders:

    {order_id}
    {order_total}
    {order_status}
    {customer_name}

Example Action:

    {
        "type": "notify_admin",
        "subject": "اعلان سفارش جدید در WooSmart",
        "message": "یک سفارش جدید با شرایط اتوماسیون مطابقت دارد."
    }

The Action Engine does not implement SMTP itself.

SMTP configuration remains a WordPress-level responsibility.

---

# Multiple Actions

Multiple Actions have now been implemented.

An Automation can contain more than one Action.

Example:

    [
        {
            "type": "notify_admin",
            "subject": "سفارش مناسب جدید",
            "message": "سفارش {order_id} دارای شرایط موردنظر است."
        },
        {
            "type": "change_order_status",
            "status": "processing"
        }
    ]

This allows an Automation to perform multiple operations after its Conditions are satisfied.

Example:

    WHEN
        ایجاد سفارش

    IF
        مبلغ سفارش > 1,000,000 ریال

    THEN
        ارسال اعلان به مدیر فروشگاه
        AND
        تغییر وضعیت سفارش → در حال پردازش

Multiple Actions are now part of the implemented MVP functionality.

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

---

# Currency Handling

WooCommerce remains the source of truth for store currency.

For an Iranian store:

    Currency:
        IRR

WooSmart displays amounts as:

    1,000,000 ریال

The condition engine stores and compares numeric values rather than formatted display strings.

Example:

    Display:
        1,000,000 ریال

    Stored value:
        1000000

This prevents formatting from affecting condition evaluation.

The amount input in the Automation interface supports thousand separators so the user can clearly understand the entered amount.

---

# Execution Engine

## class-woosmart-execution-engine.php

Central execution orchestrator.

Current responsibilities:

1. Receive a Trigger
2. Find active Automations matching the Trigger
3. Evaluate Conditions
4. Execute Actions
5. Determine execution success/failure
6. Log the result

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

---

# Execution Results

The execution system distinguishes between:

## Successful Condition

    Trigger
       ↓
    Conditions Passed
       ↓
    Actions Executed
       ↓
    Automation Success

---

## Failed Condition

    Trigger
       ↓
    Conditions Failed
       ↓
    Actions Skipped
       ↓
    automation_conditions_failed

---

## Failed Action

    Trigger
       ↓
    Conditions Passed
       ↓
    Action Failed
       ↓
    automation_failed

This distinction is important because an Automation can have valid Conditions but still fail because one of its Actions could not be executed.

---

# Logging

Current logging supports:

    order_created
    automation_created
    automation_updated
    automation_executed
    automation_conditions_failed
    automation_failed
    action_executed
    action_failed
    automation_status_changed

Examples of successful execution:

    اجرای اتوماسیون
    اتوماسیون با موفقیت پردازش شد.

Example Action success:

    اجرای عملیات
    عملیات با موفقیت اجرا شد.

Example Condition failure:

    شرایط برقرار نبود
    شرایط اتوماسیون برقرار نبود.

Example Action failure:

    خطای عملیات
    اجرای عملیات با خطا مواجه شد.

---

# Confirmed End-to-End Tests

The following behavior has been tested with real WooCommerce orders.

## Order Created Trigger

Confirmed:

    WooCommerce Order
        ↓
    order_created
        ↓
    WooSmart Execution Engine

---

## Condition Pass

Example:

    Order Total > 999,999.99

Result:

    Condition Passed
        ↓
    Action Executed
        ↓
    Order Status Changed
        ↓
    Execution Logged

Confirmed example:

    pending → on-hold

---

## Condition Failure

Example:

    Order Total > 999,999.99

When the order amount is below the threshold:

    order_created
        ↓
    automation_conditions_failed
        ↓
    No Action executed

This behavior has been confirmed.

---

## Multiple Actions

A real Automation was created containing:

    notify_admin
    +
    change_order_status

Example:

    Order Total = 1,000,000

Actions:

    Notify Store Administrator
    AND
    Change Order Status → Processing

The Action Engine successfully processed the status-change Action.

---

## Action Failure

The notification Action was tested in the local XAMPP environment.

When wp_mail() could not deliver the email:

    notify_admin
        ↓
    action_failed
        ↓
    automation_failed

The failure was correctly logged.

This confirms that Action-level failure is propagated to Automation-level failure.

---

# SMTP Limitation

The current local XAMPP environment does not have a working SMTP configuration.

Therefore:

    WooSmart
        ↓
    notify_admin
        ↓
    wp_mail()
        ↓
    Local mail transport
        ↓
    Delivery failure

This is currently considered an environment / mail transport limitation, not a failure in the Automation condition or execution logic.

Next step:

    Configure WordPress SMTP
        ↓
    Test real email delivery

SMTP should remain outside the WooSmart Action Engine.

WooSmart should call WordPress mail functionality rather than embedding an SMTP implementation into the core plugin.

---

# Important Current Limitation: Automation Conflicts

The current engine allows multiple active Automations to respond to the same Trigger.

Example:

    Automation A:
        Order Total > 500,000
        → Status = Processing

    Automation B:
        Order Total < 10,000,000
        → Status = Completed

A 2,000,000 order satisfies both Automations.

The current implementation can therefore execute both.

Example observed behavior:

    pending
       ↓
    Automation B
       ↓
    completed
       ↓
    Automation A
       ↓
    processing

The final state can therefore depend on execution order.

This behavior is currently understood and intentionally identified as a future architecture problem.

The solution should not simply rely on the incidental order returned by the database.

---

# Planned Conflict Management

The long-term solution is:

    Conflict Detection
    +
    Execution Priority
    +
    Execution Policy

These three components should work together.

---

# Conflict Detection

When a user creates or edits an Automation, WooSmart should analyze other active Automations sharing the same Trigger.

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

---

# Conflict Levels

Future conflict analysis should distinguish between:

## Safe

No meaningful overlap.

    🟢 Safe

---

## Potential Conflict

Conditions may overlap but Actions are not directly contradictory.

    🟡 Potential Conflict

Example:

    Automation A → Send Email
    Automation B → Add Order Note

These can normally coexist.

---

## Conflict

Multiple Automations can modify the same property with different outcomes.

    🔴 Conflict

Example:

    Automation A → Processing
    Automation B → Completed

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

---

# Planned Execution Priority

Each Automation will eventually have a Priority.

Example:

    Priority 10
    Priority 20
    Priority 30

Lower number:

    Executes earlier

The execution system must explicitly sort Automations by Priority.

It must not rely on incidental database query order.

Priority is intended to make Automation execution deterministic.

---

# Planned Execution Policy

Future Automations may support an execution policy such as:

    Continue

or:

    Stop after successful execution

Example:

    Automation A
        ↓
    Action Successful
        ↓
    STOP

This can prevent a lower-priority Automation from overriding an earlier result.

The exact final policy design will be determined after Conflict Detection and Execution Priority are implemented and tested.

The current preferred direction is:

    Priority
        ↓
    Execute Automation
        ↓
    Check Policy
        ↓
    Continue or Stop

This is currently a planned feature, not implemented functionality.

---

# Planned Multiple Conditions

Current:

    One configured condition

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

This will require a more powerful condition representation and evaluator.

---

# Planned Condition Expansion

## Order Conditions

* [ ] Order Total
* [ ] Order Subtotal
* [ ] Order Status
* [ ] Payment Method
* [ ] Shipping Method
* [ ] Coupon
* [ ] Customer
* [ ] Billing Country
* [ ] Shipping Country
* [ ] Order Item Count
* [ ] Product
* [ ] Product Category
* [ ] Product Quantity

## Customer Conditions

* [ ] Customer Role
* [ ] Customer Email
* [ ] Customer Order Count
* [ ] Customer Total Spent
* [ ] Customer Registration Date

## Product Conditions

* [ ] Product Price
* [ ] Stock Quantity
* [ ] Stock Status
* [ ] Category
* [ ] SKU
* [ ] Product Type

---

# Planned Trigger Expansion

## Orders

* [x] Order Created
* [ ] Order Paid
* [ ] Order Status Changed
* [ ] Order Completed
* [ ] Order Cancelled
* [ ] Order Failed
* [ ] Order Refunded
* [ ] Order On Hold

## Customers

* [ ] Customer Registered
* [ ] Customer Login
* [ ] Customer Role Changed

## Products

* [ ] Product Created
* [ ] Product Updated
* [ ] Product Stock Changed
* [ ] Product Becomes In Stock
* [ ] Product Becomes Out of Stock

## Cart / Checkout

* [ ] Cart Updated
* [ ] Checkout Started
* [ ] Checkout Completed
* [ ] Abandoned Cart

---

# Current Action System

Implemented Actions:

* [x] Change Order Status
* [x] Notify Store Administrator

---

# Planned Action Expansion

## Order Actions

* [x] Change Order Status
* [ ] Add Order Note
* [ ] Modify Order Metadata
* [ ] Apply Coupon
* [ ] Modify Order Items

## Notification Actions

* [x] Notify Store Administrator by Email
* [ ] Customer Email
* [ ] Admin Notification
* [ ] SMS
* [ ] WhatsApp
* [ ] Telegram

## External Actions

* [ ] Webhook
* [ ] HTTP Request
* [ ] REST API
* [ ] Slack
* [ ] Discord
* [ ] Google Sheets
* [ ] CRM integrations

---

# Planned Notification System

The notification system should eventually support multiple channels.

    Notification
    │
    ├── Email
    ├── SMS
    ├── Telegram
    ├── WhatsApp
    └── Webhook

The core automation engine should not be tightly coupled to any single messaging provider.

---

# Planned Execution History

A dedicated execution history system will eventually track:

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

This will help administrators diagnose complex Automation behavior.

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

Or:

    Payment Pending
        ↓
    Wait 2 hours
        ↓
    Notify Administrator

Future infrastructure may include:

* [ ] WP-Cron
* [ ] Action Queue
* [ ] Background Processing
* [ ] Retry Mechanism
* [ ] Failed Job Handling

---

# Planned Error Handling

The production system should include:

* [ ] Structured Errors
* [ ] Action-level Error Details
* [ ] Automation-level Error Details
* [ ] Retry Count
* [ ] Retry Delay
* [ ] Failed Execution Queue
* [ ] Error Notifications
* [ ] Debug Mode

---

# Planned Performance Improvements

The current implementation is suitable for MVP development.

Production-scale improvements may include:

* [ ] Automation caching
* [ ] Optimized Automation queries
* [ ] Reduced database queries
* [ ] Dedicated logs table
* [ ] Background execution
* [ ] Execution queue
* [ ] Better handling of large Automation counts
* [ ] Better handling of large order volumes

---

# Planned Database Evolution

Current:

    WordPress Custom Post Type
    +
    Post Meta
    +
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

The exact database architecture will be decided after real-world performance requirements become clear.

---

# Security

Current security measures:

* [x] Capability checks
* [x] Nonces
* [x] Input sanitization
* [x] Output escaping
* [x] Action validation
* [x] Trigger validation
* [x] Condition validation

Future security work:

* [ ] REST API authentication
* [ ] Webhook authentication
* [ ] Secure credential storage
* [ ] External request validation
* [ ] SSRF protection
* [ ] Permission separation
* [ ] Action-level permissions

---

# Testing Strategy

WooSmart is being developed using incremental testing.

Every major feature should be tested through the real WooCommerce workflow.

Standard development cycle:

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

The following end-to-end behavior has already been confirmed:

* [x] Plugin activation
* [x] Plugin deactivation
* [x] WooCommerce dependency detection
* [x] Create Automation
* [x] Edit Automation
* [x] Enable / Disable Automation
* [x] Duplicate Automation
* [x] Delete Automation
* [x] Order Created trigger
* [x] Order Total condition
* [x] Equal operator
* [x] Not Equal operator
* [x] Greater Than operator
* [x] Greater Than or Equal operator
* [x] Less Than operator
* [x] Less Than or Equal operator
* [x] Condition pass
* [x] Condition failure
* [x] Change Order Status
* [x] Notify Store Administrator Action
* [x] Multiple Actions
* [x] Action success logging
* [x] Action failure logging
* [x] Automation failure logging
* [x] Persian Admin UI
* [x] RTL interface
* [x] Persian log labels
* [x] Persian order-status labels
* [x] Rial display
* [x] Thousand separators
* [x] Real WooCommerce order execution

SMTP delivery remains pending.

---

# Known Issues / Pending Tasks

## High Priority

* [ ] Configure SMTP for real email delivery
* [ ] Test notify_admin end-to-end with real email delivery
* [ ] Design Conflict Detection
* [ ] Implement Conflict Detection
* [ ] Design Execution Priority
* [ ] Implement Execution Priority
* [ ] Design Execution Policy
* [ ] Implement Execution Policy

## Medium Priority

* [ ] Multiple Conditions
* [ ] AND / OR groups
* [ ] More Order Conditions
* [ ] More Order Actions
* [ ] Better execution summaries
* [ ] Better Automation conflict messages

## Future

* [ ] Execution History
* [ ] Automation Trace
* [ ] Scheduled Actions
* [ ] Retry Queue
* [ ] Additional Triggers
* [ ] Additional Actions
* [ ] Integrations
* [ ] Developer API
* [ ] Advanced Automation Builder

---

# Immediate Development Roadmap

The recommended order of development is:

    STEP 1
    Configure SMTP
        ↓
    STEP 2
    Confirm notify_admin end-to-end
        ↓
    STEP 3
    Design Conflict Detection
        ↓
    STEP 4
    Implement Conflict Detection
        ↓
    STEP 5
    Design Execution Priority
        ↓
    STEP 6
    Implement Execution Priority
        ↓
    STEP 7
    Design Execution Policy
        ↓
    STEP 8
    Implement Execution Policy
        ↓
    STEP 9
    Multiple Conditions
        ↓
    STEP 10
    AND / OR Groups
        ↓
    STEP 11
    More Conditions
        ↓
    STEP 12
    More Triggers
        ↓
    STEP 13
    More Actions
        ↓
    STEP 14
    Execution History
        ↓
    STEP 15
    Automation Trace
        ↓
    STEP 16
    Scheduling
        ↓
    STEP 17
    Retry System
        ↓
    STEP 18
    Integrations
        ↓
    STEP 19
    Professional Automation Builder

The roadmap can change when development reveals a better architecture.

Any feature intentionally postponed during development must be added to the Future / Backlog section rather than being forgotten.

---

# First Major Product Goal

The next meaningful product milestone is a complete Automation such as:

    WHEN
    ایجاد سفارش

    IF
    مبلغ سفارش > 1,000,000 ریال

    THEN
    ارسال اعلان به مدیر فروشگاه

    AND

    تغییر وضعیت سفارش → در حال پردازش

The complete workflow should work reliably through the full WooCommerce lifecycle.

---

# Long-Term Product Vision

The final WooSmart platform should allow a store owner to build workflows such as:

    WHEN
        Order Created

    IF
        Order Total > 5,000,000
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

# Product Design Principles

## 1. Modular Architecture

Each major responsibility should have a clear module.

---

## 2. Do Not Break Existing Features

New functionality must preserve existing behavior.

Existing working functionality must be tested after architectural changes.

---

## 3. Incremental Development

Develop one major feature at a time.

    Build
    Test
    Fix
    Commit
    Document
    Continue

---

## 4. Real WooCommerce Testing

Core features must be verified against real WooCommerce orders.

A feature is not considered complete only because the PHP code executes without an error.

The complete workflow should be tested.

---

## 5. Full File Replacement

During collaborative development, when a file changes, the complete file should be provided for replacement rather than isolated snippets.

This reduces accidental merge and placement errors.

---

## 6. Git as Source of Truth

GitHub is the project source of truth.

Major milestones should be committed.

Suggested commits:

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

The actual version numbers may change depending on the final release strategy.

---

# README Maintenance Rule

README.md is a living project document.

After every meaningful milestone:

    Feature Implemented
        ↓
    Feature Tested
        ↓
    README Updated
        ↓
    Git Commit
        ↓
    Next Feature

The README should always clearly indicate:

* What has been implemented
* What has been tested
* What is currently being developed
* What is intentionally postponed
* What is planned for the future
* What the next development step is
* Any important architectural decisions
* Any known limitations

This prevents project knowledge from being dependent on a single conversation.

---

# Future / Backlog Policy

During development, new ideas or features may be discussed that are intentionally not implemented immediately.

Whenever this happens, the feature must be classified as one of:

    Implemented
    In Progress
    Planned
    Future / Backlog
    Rejected

If a feature is postponed, it must remain documented in this README.

Examples of postponed items include:

* Conflict Detection
* Execution Priority
* Execution Policy
* Multiple Conditions
* AND / OR groups
* Additional Triggers
* Additional Actions
* Execution History
* Scheduling
* Retry System
* Integrations
* Developer API
* Advanced Automation Builder

The exact implementation order can change, but postponed functionality must not be forgotten.

---

# Current Status Summary

    Core:
        🟢 Functional

    Admin:
        🟢 Persian / RTL MVP

    Trigger Engine:
        🟡 One implemented trigger

    Condition Engine:
        🟡 Basic / one implemented field

    Action Engine:
        🟢 Functional

    Change Order Status:
        🟢 Implemented and tested

    Notify Store Administrator:
        🟢 Implemented
        🟡 Real SMTP delivery pending

    Multiple Actions:
        🟢 Implemented and tested

    Validation:
        🟢 Implemented

    Logging:
        🟢 Functional MVP

    Action Failure Detection:
        🟢 Implemented and tested

    Automation Failure Detection:
        🟢 Implemented and tested

    Conflict Detection:
        🔴 Planned

    Execution Priority:
        🔴 Planned

    Execution Policy:
        🔴 Planned

    Multiple Conditions:
        🔴 Planned

    AND / OR:
        🔴 Planned

    Execution History:
        🔴 Planned

    Automation Trace:
        🔴 Planned

    Scheduling:
        🔴 Planned

    Retry System:
        🔴 Planned

    Additional Triggers:
        🔴 Planned

    Additional Actions:
        🔴 Planned

    Integrations:
        🔴 Planned

    Developer API:
        🔴 Planned

    Automated Tests:
        🔴 Future

---

# Current Milestone

The project has successfully completed the foundational Action System milestone.

Current working flow:

    WooCommerce Order Created
            ↓
    Trigger: order_created
            ↓
    Find Active Automations
            ↓
    Evaluate Conditions
            ↓
    Execute Multiple Actions
            ↓
    Record Action Results
            ↓
    Record Automation Result
            ↓
    Display Logs

The system has been tested with real WooCommerce orders.

The next architectural milestone is:

    Conflict Detection
        +
    Execution Priority
        +
    Execution Policy

The purpose is to make multiple Automations predictable and prevent conflicting Actions from producing unexpected final order states.

---

# Development Rule

WooSmart must evolve as a coherent automation platform.

The core philosophy remains:

    WHEN
        Something Happens

    IF
        Conditions Are Satisfied

    THEN
        Execute One Or More Actions

The production execution model will eventually become:

    WHEN
        Trigger

    IF
        Conditions

    CHECK
        Conflicts

    PLAN
        Priority / Policy

    THEN
        Actions

    RESULT
        Success / Failure

    LOG
        Execution History

    MONITOR
        Automation Trace

The priority is not simply adding features.

The priority is creating a stable, predictable, debuggable, secure, maintainable, and extensible WooCommerce automation platform.

---

# End
