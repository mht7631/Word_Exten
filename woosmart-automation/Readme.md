# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin designed to allow store administrators to create rule-based automations without writing code.

The long-term goal is to turn WooSmart Automation into a complete, reliable, extensible, predictable, and user-friendly automation platform for WooCommerce stores.

---

# Project Vision

The core concept of WooSmart Automation is:

```text
WHEN something happens
IF certain conditions are satisfied
THEN perform one or more actions
```

Example:

```text
WHEN
Order Created

IF
Order Total > 1,000,000 IRR

THEN
Notify Store Administrator
```

Another example:

```text
WHEN
Order Created

IF
Order Total > 5,000,000 IRR

THEN
Notify Store Administrator
AND
Change Order Status → Processing
```

The final goal is to allow store owners to build complex workflows from the WordPress admin without programming.

---

# Repository

GitHub:

```text
https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation
```

GitHub is the primary source of truth for the project.

The project is developed incrementally and every important milestone should be committed to Git.

---

# Current Project Status

Version:

```text
1.0.0
```

Current stage:

```text
MVP / Foundation
```

Current milestone:

```text
Core automation engine successfully tested
```

Current development focus:

```text
Notification System
Multiple Actions
Action Model
Conflict Detection
Execution Priority
Execution Policy
Multiple Conditions
```

---

# Current Architecture

The current architecture is modular.

```text
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
```

Administrative workflow:

```text
WordPress Admin
     ↓
WooSmart Admin
     ↓
Automation Manager
     ↓
Automation Data
     ↓
Execution Engine
```

The execution architecture is designed so that the main engines are shared instead of unnecessarily creating duplicate instances.

---

# Current File Structure

```text
woosmart-automation/
│
├── woosmart-automation.php
│
├── README.md
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
```

---

# File Responsibilities

## woosmart-automation.php

Main plugin bootstrap.

Responsibilities:

* Load plugin classes.
* Initialize shared services.
* Initialize the Condition Engine.
* Initialize the Action Engine.
* Initialize the Execution Engine.
* Pass the shared Execution Engine to the Trigger system.
* Initialize the Admin layer.
* Initialize the Post Type.
* Initialize the Automation Manager.
* Handle plugin activation.
* Handle plugin deactivation.
* Flush rewrite rules when required.

The current architecture avoids unnecessary duplicate Condition, Action, and Execution Engine instances.

---

## class-woosmart-core.php

Core plugin functionality.

Responsibilities:

* Detect WooCommerce.
* Display WooCommerce dependency warnings.
* Provide WooCommerce availability status.
* Control Iranian Rial frontend price formatting when the WooCommerce currency is `IRR`.

For Iranian stores, WooSmart currently formats WooCommerce prices as:

```text
1,500,000 ریال
```

instead of:

```text
﷼1,500,000.00
```

The actual store currency remains controlled by WooCommerce.

WooSmart does not change the numeric value of products or orders.

---

## class-woosmart-logger.php

Central logging system.

Responsibilities:

* Store automation events.
* Store execution context.
* Retrieve logs.
* Clear logs.
* Keep the latest 100 log entries in the current MVP.

Current storage:

```text
WordPress Options
```

Future storage:

```text
Dedicated database table
```

---

## class-woosmart-admin.php

Main WooSmart administration interface.

Current sections:

* داشبورد
* اتوماسیون‌ها
* افزودن اتوماسیون
* گزارش‌ها

The current interface is:

```text
Persian
RTL
```

Internal identifiers remain English for stability and compatibility.

Examples:

```text
order_created
order_total
greater_than
change_order_status
notify_admin
```

Recommended user-facing terminology:

| Internal Term | UI Term   |
| ------------- | --------- |
| Trigger       | رویداد    |
| Condition     | شرط       |
| Action        | عملیات    |
| Automation    | اتوماسیون |
| Execution     | اجرا      |
| Status        | وضعیت     |
| Active        | فعال      |
| Inactive      | غیرفعال   |

---

## class-woosmart-automation.php

Main automation foundation.

Current status:

```text
Foundation / Placeholder
```

Future responsibilities may include:

* Automation lifecycle.
* Automation registry.
* Automation-level services.
* Automation events.

---

## class-woosmart-triggers.php

Trigger system.

Current trigger:

```text
order_created
```

Current WooCommerce hook:

```text
woocommerce_new_order
```

The trigger creates an execution context containing the WooCommerce order ID.

Example:

```json
{
    "order_id": 45
}
```

Future versions will expand the Trigger system.

---

## class-woosmart-post-types.php

Registers the internal Automation post type.

Post type:

```text
woosmart_automation
```

The default WordPress UI for this post type is intentionally hidden because WooSmart provides its own administration interface.

---

## class-woosmart-automation-manager.php

Handles Automation CRUD and configuration validation.

Current operations:

* Create
* Update
* Enable / Disable
* Delete
* Duplicate

Validation is also performed here.

Current validation includes:

* Trigger validation.
* Condition validation.
* Condition value validation.
* Action type validation.
* Order status validation.
* Notification configuration validation.
* Prevention of empty Action sets.
* Activation-time validation of existing Automation data.

---

## class-woosmart-condition-engine.php

Evaluates Automation Conditions.

Current field:

```text
order_total
```

Current operators:

```text
is_equal
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

Current condition behavior:

```text
All configured conditions must pass.
```

The current data model already uses an array structure, allowing future expansion to multiple Conditions.

Future versions will support:

```text
Multiple Conditions
AND
OR
Nested Groups
```

---

## class-woosmart-action-engine.php

Executes Automation Actions.

Current actions:

```text
change_order_status
notify_admin
```

### change_order_status

Changes the WooCommerce order status.

Current supported behavior uses WooCommerce order statuses.

### notify_admin

Sends an email notification to the WordPress store administrator using:

```text
wp_mail()
```

Supported placeholders:

```text
{order_id}
{order_total}
{order_status}
{customer_name}
```

The Action Engine does not implement SMTP itself.

SMTP configuration remains a WordPress-level / environment-level responsibility.

---

## class-woosmart-execution-engine.php

Central execution orchestrator.

Responsibilities:

1. Receive a Trigger.
2. Find active Automations matching that Trigger.
3. Evaluate Conditions.
4. Execute Actions.
5. Determine execution success or failure.
6. Log the result.

Current execution flow:

```text
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
```

The Execution Engine now distinguishes between:

```text
automation_executed
```

and:

```text
automation_failed
```

This prevents a failed Action from being reported as a successful Automation.

---

# Current Data Model

Each Automation is stored as:

```text
woosmart_automation
```

Current metadata:

```text
_woosmart_status
_woosmart_trigger
_woosmart_conditions
_woosmart_actions
```

Example:

```text
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
        }
    ]
```

---

# Current User Interface

The user-facing administration terminology is Persian.

Example:

```text
رویداد:
ایجاد سفارش

شرط:
مبلغ سفارش
بیشتر از
1,000,000 ریال

عملیات:
ارسال اعلان به مدیر فروشگاه
```

The plugin name remains:

```text
WooSmart
```

and is intentionally not translated.

---

# Current Currency Handling

WooCommerce remains the source of truth for the store currency.

For an Iranian store:

```text
Currency:
    IRR
```

WooSmart displays amounts in the admin UI as:

```text
1,000,000 ریال
```

The condition engine works with numeric values instead of formatted display strings.

Example:

```text
Display:
    1,000,000 ریال

Stored value:
    1000000
```

This keeps number formatting separate from condition evaluation.

---

# Current Amount Input

The Automation Builder currently supports:

```text
Automatic thousand separators
```

Example:

```text
User enters:

1500000

Displayed:

1,500,000 ریال
```

The backend receives:

```text
1500000
```

This prevents display formatting from affecting numeric comparison.

---

# What Has Been Implemented

## Foundation

* [x] Plugin bootstrap.
* [x] Plugin activation.
* [x] Plugin deactivation.
* [x] WooCommerce dependency detection.
* [x] WooCommerce admin notice.
* [x] Internal Automation Custom Post Type.
* [x] Shared Execution Engine architecture.
* [x] Shared Condition Engine architecture.
* [x] Shared Action Engine architecture.

---

# Admin

* [x] WooSmart admin menu.
* [x] Dashboard.
* [x] Automation list.
* [x] Create Automation.
* [x] Edit Automation.
* [x] Enable / Disable Automation.
* [x] Duplicate Automation.
* [x] Delete Automation.
* [x] Logs page.
* [x] Persian interface.
* [x] RTL interface.
* [x] Persian event labels.
* [x] Persian condition labels.
* [x] Persian action labels.
* [x] Persian order-status labels.
* [x] Rial amount display.
* [x] Automatic thousand separators in amount input.
* [x] Action selection UI.
* [x] Notification configuration UI.

---

# Automation Management

* [x] Create.
* [x] Update.
* [x] Toggle.
* [x] Duplicate.
* [x] Delete.
* [x] Configuration validation.
* [x] Trigger validation.
* [x] Condition validation.
* [x] Condition value validation.
* [x] Action validation.
* [x] Order status validation.
* [x] Notification validation.
* [x] Prevention of empty Action sets.
* [x] Activation-time validation.

---

# Trigger System

* [x] Trigger infrastructure.
* [x] Order Created trigger.
* [x] WooCommerce order execution context.
* [x] Real WooCommerce trigger testing.

---

# Condition System

* [x] Condition Engine.
* [x] Order Total.
* [x] Equal.
* [x] Not Equal.
* [x] Greater Than.
* [x] Greater Than or Equal.
* [x] Less Than.
* [x] Less Than or Equal.
* [x] Real-world condition pass testing.
* [x] Real-world condition failure testing.

---

# Action System

* [x] Action Engine.
* [x] Change Order Status.
* [x] Notify Store Administrator.
* [x] Action-level success logging.
* [x] Action-level failure logging.
* [x] Support for order placeholders in notifications.

---

# Execution System

* [x] Trigger processing.
* [x] Active Automation lookup.
* [x] Condition evaluation.
* [x] Action execution.
* [x] Successful execution logging.
* [x] Failed Action detection.
* [x] Failed Automation detection.
* [x] Real WooCommerce execution testing.

---

# Logging

* [x] Event logging.
* [x] Context logging.
* [x] Action-level failure logging.
* [x] Automation-level failure logging.
* [x] Persian event labels.
* [x] Persian messages.
* [x] Latest 100 log entries retained in the MVP.

Current log events include:

```text
order_created
automation_created
automation_updated
automation_status_changed
automation_deleted
automation_duplicated
automation_skipped
automation_conditions_failed
automation_executed
automation_failed
action_executed
action_failed
```

---

# End-to-End Tests Already Confirmed

WooSmart has already been tested against real WooCommerce orders.

---

## Successful Condition

Example:

```text
Order Total > 999,999.99
```

Expected path:

```text
Order Created
    ↓
Condition Passed
    ↓
Action Executed
    ↓
Execution Logged
```

Confirmed behavior:

```text
pending → on-hold
```

and:

```text
pending → processing
```

---

## Failed Condition

Example:

```text
Order Total > 999,999.99
```

with an order below the threshold.

Confirmed behavior:

```text
order_created
    ↓
automation_conditions_failed
    ↓
No Action executed
```

---

## Successful Status Action

Confirmed:

```text
Action:
    change_order_status

Result:
    pending → processing
```

---

## Multiple Active Automations

Multiple Automations using the same Trigger have already been tested.

Example:

```text
Automation #40
Order Total < 10,000,000
→ Completed

Automation #35
Order Total > 499,999.99
→ Processing
```

For a 2,000,000 order both Conditions pass.

The observed execution was:

```text
pending
   ↓
Automation #40
   ↓
completed
   ↓
Automation #35
   ↓
processing
```

This confirmed an important architectural requirement:

> WooSmart needs deterministic Automation execution planning.

The final result must not depend on incidental database query order.

---

## Failed Notification Action

Confirmed:

```text
Action:
    notify_admin
```

Current local XAMPP environment:

```text
wp_mail()
    ↓
Mail transport unavailable
    ↓
action_failed
    ↓
automation_failed
```

This confirmed that the WooSmart execution engine correctly detects Action failures.

The remaining issue is real SMTP / mail transport configuration.

---

# Current Known Limitations

## 1. SMTP Is Not Yet Configured

The `notify_admin` Action is implemented and correctly reaches `wp_mail()`.

However, the local XAMPP environment does not yet have a working SMTP transport.

Current state:

```text
WooSmart
    ↓
notify_admin
    ↓
wp_mail()
    ↓
Local mail transport
    ↓
Failure
```

The intended architecture is:

```text
WooSmart
    ↓
wp_mail()
    ↓
WordPress SMTP configuration
    ↓
Mail Provider
    ↓
Store Administrator
```

WooSmart should not contain its own SMTP implementation.

---

## 2. Multiple Actions Are Not Yet Available in the Builder

The Action Engine already loops through an Action array, but the current builder configures one Action per Automation.

Future:

```text
THEN

Action 1
AND
Action 2
AND
Action 3
```

---

## 3. Multiple Conditions Are Not Yet Available in the Builder

The data model and Condition Engine are designed around arrays, but the current UI configures one Condition.

Future:

```text
Condition 1
AND
Condition 2
AND
Condition 3
```

and later:

```text
Group 1
    Condition
    AND
    Condition

OR

Group 2
    Condition
    AND
    Condition
```

---

## 4. Conflict Detection Is Not Yet Implemented

Multiple active Automations can currently act on the same Order.

This can produce contradictory outcomes.

Example:

```text
Automation A
→ Processing

Automation B
→ Completed
```

Both may apply to the same order.

WooSmart must eventually detect such conflicts before activation.

---

## 5. Execution Priority Is Not Yet Implemented

The current Execution Engine does not yet have an explicit priority system.

Execution should eventually be deterministic:

```text
Priority 10
    ↓
Priority 20
    ↓
Priority 30
```

---

## 6. Execution Policy Is Not Yet Implemented

Future Automations may define whether later Automations continue after successful execution.

Possible policies:

```text
Continue
```

or:

```text
Stop after successful execution
```

---

# Planned Action Architecture

The Action system will eventually use a more structured model.

Instead of treating Actions only as simple commands, each Action should expose information such as:

```text
Action Type
Target Entity
Target Property
Effect
Side Effects
Compatibility
```

Example:

```text
change_order_status

Target:
    Order

Property:
    Status

Effect:
    Set Status = Processing
```

Another example:

```text
notify_admin

Target:
    Store Administrator

Property:
    Notification Channel

Effect:
    Send Email
```

This model will later make Conflict Detection much more reliable.

---

# Planned Conflict Management

WooSmart should not solve conflicts only with Priority.

The intended system is:

```text
Conflict Detection
+
Execution Priority
+
Execution Policy
```

---

# Conflict Detection

When an Automation is created or edited, WooSmart should analyze other active Automations that share the same Trigger.

Conflict analysis should consider:

```text
Trigger
+
Condition Overlap
+
Action Target
+
Action Effect
```

Example:

```text
Automation A:

Order Total > 500,000
→ Order Status = Processing
```

and:

```text
Automation B:

Order Total < 10,000,000
→ Order Status = Completed
```

Condition ranges overlap and both modify:

```text
Order Status
```

Therefore:

```text
Conflict = TRUE
```

---

# Conflict Levels

## Safe

No meaningful overlap.

```text
🟢 Safe
```

---

## Potential Conflict

Conditions overlap but the Actions are not directly contradictory.

Example:

```text
Automation A
→ Send Email

Automation B
→ Add Order Note
```

Result:

```text
🟡 Potential Conflict
```

---

## Conflict

Multiple Automations may modify the same property with different outcomes.

Example:

```text
Automation A
→ Processing

Automation B
→ Completed
```

Result:

```text
🔴 Conflict
```

---

# Conflict UX

When a conflict is detected, the user should see a clear warning.

Example:

```text
⚠️ هشدار تداخل

این اتوماسیون با یک اتوماسیون فعال دیگر
هم‌پوشانی دارد.

هر دو اتوماسیون ممکن است برای یک سفارش
اجرا شوند و وضعیت سفارش را تغییر دهند.
```

Possible choices:

```text
ویرایش شرایط
ذخیره به‌صورت غیرفعال
فعال‌سازی با پذیرش هشدار
```

WooSmart should never silently delete or modify another Automation.

---

# Planned Condition Overlap Analysis

For simple numeric Conditions such as `order_total`, WooSmart can eventually model Conditions as logical ranges.

Example:

```text
Order Total > 500,000

Range:

(500000, +∞)
```

Another Condition:

```text
Order Total < 10,000,000

Range:

(-∞, 10000000)
```

Intersection:

```text
(500000, 10000000)
```

Therefore the Conditions overlap.

This will form the basis of the first Conflict Analyzer implementation.

---

# Planned Execution Priority

Each Automation will eventually have:

```text
Priority
```

Example:

```text
Priority 10
Priority 20
Priority 30
```

Lower number:

```text
Executes earlier
```

Execution should be deterministic and independent of incidental database ordering.

---

# Planned Execution Policy

Future Automations may support:

```text
Continue
```

or:

```text
Stop after successful execution
```

Example:

```text
Automation A
    ↓
Successful Action
    ↓
STOP
```

This can prevent later Automations from overriding an earlier result.

---

# Planned Multiple Actions

The current engine already accepts an array of Actions.

Future builder behavior:

```text
THEN

[ ارسال اعلان به مدیر فروشگاه ]

AND

[ تغییر وضعیت سفارش → در حال پردازش ]

AND

[ افزودن یادداشت سفارش ]
```

This is the next major expansion of the Action system.

---

# Planned Multiple Conditions

Future builder:

```text
IF

[ مبلغ سفارش ] [ بیشتر از ] [ 5,000,000 ریال ]

AND

[ روش پرداخت ] [ برابر با ] [ کارت به کارت ]

AND

[ نقش مشتری ] [ برابر با ] [ عمده‌فروش ]
```

Later:

```text
IF
    Group A
        Condition
        AND
        Condition

OR

    Group B
        Condition
        AND
        Condition
```

---

# Planned Trigger Expansion

## Orders

* [ ] Order Created
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

# Planned Action Expansion

## Order Actions

* [x] Change Order Status
* [ ] Add Order Note
* [ ] Modify Order Metadata
* [ ] Apply Coupon
* [ ] Modify Order Items

## Notification Actions

* [x] Notify Store Administrator by Email
* [ ] Notify Customer by Email
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
* [ ] CRM Integrations

---

# Planned Notification System

The long-term Notification layer should support multiple channels.

```text
Notification
│
├── Email
├── SMS
├── Telegram
├── WhatsApp
└── Webhook
```

The Core Automation Engine should not become tightly coupled to a specific provider.

---

# Planned Execution History

A dedicated execution history system will eventually track:

```text
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
```

Possible execution statuses:

```text
pending
running
completed
failed
skipped
cancelled
```

---

# Planned Automation Trace

WooSmart should eventually explain exactly why an Automation executed.

Example:

```text
Order #42

Automation Trace

#40
Conditions:
    Passed

Action:
    Status → Completed

#35
Conditions:
    Passed

Action:
    Status → Processing

Final Status:
    Processing

Reason:
    Automation #35 executed later according
    to the configured execution priority.
```

This will be a major debugging and monitoring feature.

---

# Planned Delayed and Scheduled Actions

Future versions may support:

```text
Wait 1 hour
Wait 24 hours
Execute at a specific time
Execute after a condition remains true
```

Examples:

```text
Order Created
    ↓
Wait 24 hours
    ↓
Send Reminder
```

or:

```text
Payment Pending
    ↓
Wait 2 hours
    ↓
Notify Store Administrator
```

Planned infrastructure:

* [ ] WP-Cron integration
* [ ] Action queue
* [ ] Background processing
* [ ] Retry mechanism
* [ ] Failed job handling

---

# Planned Error Handling

Production-grade error handling should include:

* [ ] Structured errors
* [ ] Action-level error details
* [ ] Automation-level error details
* [ ] Retry count
* [ ] Retry delay
* [ ] Failed execution queue
* [ ] Error notifications
* [ ] Debug mode

---

# Planned Performance Improvements

The current implementation is appropriate for MVP development.

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

```text
WordPress Custom Post Type
+
Post Meta
+
WordPress Options
```

Future architecture may introduce dedicated tables for:

```text
Automations
Executions
Logs
Scheduled Jobs
```

Potential tables:

```text
wp_woosmart_automations
wp_woosmart_executions
wp_woosmart_logs
wp_woosmart_jobs
```

The exact database architecture will be decided after performance requirements are established.

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
* [x] Activation-time validation

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

Every major feature should be verified through a real WooCommerce workflow whenever practical.

Standard development flow:

```text
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
```

---

# Current Testing Status

The following behavior has already been confirmed:

* [x] Create Automation
* [x] Edit Automation
* [x] Enable / Disable
* [x] Duplicate
* [x] Delete
* [x] Order Created Trigger
* [x] Order Total Condition
* [x] Condition Pass
* [x] Condition Failure
* [x] Change Order Status
* [x] Notification Action reaches `wp_mail()`
* [x] Action Failure Detection
* [x] Automation Failure Detection
* [x] Multiple Active Automation Execution
* [x] Persian Admin UI
* [x] RTL Admin UI
* [x] Rial Price Display
* [x] Thousand Separator Input
* [x] Validation Layer
* [x] Real WooCommerce Order Execution

Pending:

* [ ] Real SMTP Delivery
* [ ] Multiple Actions
* [ ] Conflict Detection
* [ ] Execution Priority
* [ ] Execution Policy
* [ ] Multiple Conditions

---

# Immediate Development Roadmap

The recommended development sequence is:

```text
STEP 1
Configure SMTP
    ↓
STEP 2
Confirm Notify Admin end-to-end
    ↓
STEP 3
Multiple Actions
    ↓
STEP 4
Structured Action Model
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
More Triggers
    ↓
STEP 11
Execution History
    ↓
STEP 12
Scheduling
    ↓
STEP 13
Retry System
    ↓
STEP 14
Integrations
    ↓
STEP 15
Professional Automation Builder
```

---

# First Major Product Goal

The first major product goal is a complete automation such as:

```text
WHEN
ایجاد سفارش

IF
مبلغ سفارش > 1,000,000 ریال

THEN
ارسال اعلان به مدیر فروشگاه

AND

تغییر وضعیت سفارش → در حال پردازش
```

The full workflow should work reliably:

```text
WooCommerce Order
        ↓
Trigger
        ↓
Condition
        ↓
Conflict Analysis
        ↓
Execution Planning
        ↓
Action 1
        ↓
Action 2
        ↓
Execution Result
        ↓
Logging
```

---

# Long-Term Product Vision

The final WooSmart platform should allow a store owner to create workflows such as:

```text
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
```

And eventually:

```text
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
```

All of this should be configurable from the WordPress admin.

---

# Development Principles

## 1. Modular Architecture

Each major responsibility must have a clear module.

---

## 2. Predictable Execution

Execution results must not depend on accidental database ordering.

---

## 3. Do Not Break Existing Features

New functionality must preserve existing behavior.

---

## 4. Incremental Development

Develop one major feature at a time.

```text
Build
Test
Fix
Commit
Document
Continue
```

---

## 5. Real WooCommerce Testing

Important core features must be tested against real WooCommerce orders.

---

## 6. Full File Replacement

During collaborative development, when a file changes, the complete file should be provided for replacement rather than isolated snippets.

This reduces accidental merge errors.

---

## 7. Git as Source of Truth

GitHub is the project source of truth.

Major milestones should be committed.

Suggested commits:

```text
v1.0.0 - Initial MVP
v1.1.0 - Persian UI and Rial Support
v1.2.0 - Notification Action
v1.3.0 - Multiple Actions
v1.4.0 - Structured Action Model
v1.5.0 - Conflict Detection
v1.6.0 - Execution Priority
v1.7.0 - Execution Policy
v1.8.0 - Multiple Conditions
v1.9.0 - Additional Triggers
v1.10.0 - Execution History
v1.11.0 - Scheduling
v1.12.0 - Retry System
v2.0.0 - Professional Automation Builder
```

These version numbers are planning markers and may change as the architecture evolves.

---

# Versioning Strategy

Semantic versioning:

```text
MAJOR.MINOR.PATCH
```

Examples:

```text
1.0.0
1.1.0
1.1.1
```

Major:

```text
Breaking architecture or API changes
```

Minor:

```text
New features
```

Patch:

```text
Bug fixes and small improvements
```

---

# Current Status Summary

```text
Core:
    🟢 Functional

Admin:
    🟢 Persian / RTL MVP

WooCommerce Currency:
    🟢 IRR display implemented

Trigger Engine:
    🟡 One trigger

Condition Engine:
    🟡 Basic / one field

Action Engine:
    🟡 Two actions

Notification:
    🟡 Implemented / SMTP pending

Execution Engine:
    🟢 Functional and tested

Validation:
    🟢 Implemented

Logging:
    🟢 Functional MVP

Multiple Automations:
    🟡 Supported / conflict planning required

Conflict Detection:
    🔴 Planned

Execution Priority:
    🔴 Planned

Execution Policy:
    🔴 Planned

Multiple Actions:
    🔴 Builder support planned

Multiple Conditions:
    🔴 Builder support planned

Execution History:
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
```

---

# Development Rule

WooSmart should not become a collection of unrelated features.

The project must evolve as a coherent automation platform:

```text
Trigger
    ↓
Condition
    ↓
Conflict Analysis
    ↓
Execution Planning
    ↓
Actions
    ↓
Execution Result
    ↓
Logging
    ↓
Monitoring
```

The priority is not simply adding features.

The priority is creating a stable, predictable, debuggable, secure, and extensible WooCommerce automation engine.

---

# End
