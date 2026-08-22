# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin designed to allow store administrators to create rule-based automations without writing code.

The long-term goal is to build a reliable, predictable, secure, extensible, and user-friendly WooCommerce automation platform.

---

# Core Concept

WooSmart is built around:

**WHEN → IF → THEN**

```text
WHEN
    something happens

IF
    conditions are satisfied

THEN
    execute one or more actions
```

Example:

```text
WHEN
    Order Created

IF
    Order Total > 100,000 تومان

THEN
    Notify Store Administrator
```

---

# Repository

GitHub:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the primary source of truth for the project.

---

# Current Project Status

Version:

```text
1.0.0
```

Stage:

```text
MVP / Foundation
```

Current milestone:

```text
Condition Registry
Action Registry
WooCommerce currency-aware Admin UI
Multiple Automation diagnosis
Notification diagnostics
```

Current development focus:

```text
Mail Transport / SMTP Environment
Action Registry completion
Multiple Actions stabilization
Conflict Detection
Execution Priority
Execution Policy
Multiple Conditions
More Conditions
More Triggers
```

---

# Architecture

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

Condition architecture:

```text
Condition Registry
    ↓
Condition Engine
    ↓
Condition Result
```

Action architecture:

```text
Action Registry
    ↓
Action Engine
    ↓
Action Handler
    ↓
Action Result
```

Currency architecture:

```text
WooCommerce Currency
    ↓
WooSmart Currency Helper
    ↓
WooSmart Admin Display
```

WooSmart uses shared Condition, Action, and Execution Engine instances.

---

# Current File Structure

```text
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
```

---

# File Responsibilities

## woosmart-automation.php

Plugin bootstrap.

Responsibilities:

- Load plugin classes.
- Initialize shared services.
- Initialize Condition and Action Registries.
- Initialize Condition, Action, and Execution Engines.
- Initialize Triggers, Admin, Post Type, and Automation Manager.
- Handle activation/deactivation.
- Flush rewrite rules when required.

## class-woosmart-core.php

Core WooCommerce dependency and compatibility functionality.

WooSmart does not replace WooCommerce's currency system.

## class-woosmart-logger.php

Central MVP logger.

Current storage:

```text
WordPress Options
```

Current retention:

```text
Latest 100 log entries
```

## class-woosmart-currency.php

Display-only WooCommerce currency helper.

Responsibilities:

- Read the WooCommerce currency code.
- Detect `IRT` and `IRR`.
- Return a user-facing display unit.
- Normalize/format numeric values without currency conversion.

Current behavior:

```text
IRT → تومان
IRR → ریال
Other currencies → WooCommerce currency symbol when available
```

Important:

```text
No Rial/Toman conversion
No product price modification
No order total modification
No WooCommerce currency modification
No payment gateway modification
```

## class-woosmart-admin.php

Persian RTL administration interface.

Current sections:

- داشبورد
- اتوماسیون‌ها
- افزودن اتوماسیون
- گزارش‌ها

The Condition Builder uses the Condition Registry.

The amount field uses the current WooCommerce currency display unit.

Current store configuration:

```text
WooCommerce Currency:
IRT

WooSmart Admin display:
تومان
```

The UI does not perform a 10x or 0.1x conversion.

## class-woosmart-automation.php

Foundation / placeholder for future automation lifecycle services.

## class-woosmart-triggers.php

Current Trigger:

```text
order_created
```

WooCommerce hook:

```text
woocommerce_new_order
```

Context example:

```json
{
    "order_id": 45
}
```

## class-woosmart-post-types.php

Registers:

```text
woosmart_automation
```

The default WordPress UI is hidden because WooSmart has its own Admin UI.

## class-woosmart-automation-manager.php

Current operations:

- Create
- Update
- Enable / Disable
- Delete
- Duplicate

Validation covers Trigger, Condition, Action, Order Status, notification configuration, and existing Automation data.

## class-woosmart-condition-registry.php

Central Condition definition registry.

Current Condition:

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

## class-woosmart-condition-engine.php

Evaluates Conditions through the Condition Registry.

Current behavior:

```text
All configured conditions must pass.
```

## class-woosmart-action-registry.php

Central Action definition registry.

Current Actions:

```text
change_order_status
notify_admin
```

## class-woosmart-action-engine.php

Executes registered Actions.

Current Actions:

```text
change_order_status
notify_admin
```

The Action Engine captures `wp_mail_failed` diagnostics when its mail Action fails.

## class-woosmart-execution-engine.php

Central execution orchestrator.

Responsibilities:

1. Find active Automations matching a Trigger.
2. Evaluate Conditions.
3. Execute Actions.
4. Determine success/failure.
5. Log results.

A temporary `automation_scan` diagnostic records the number and IDs of matching Automations and should be removed when deterministic execution planning is finalized.

---

# Current Admin Terminology

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

---

# Currency Architecture Decision

This is a permanent design rule for the current product architecture.

## WooCommerce Owns Currency

WooCommerce is the source of truth for:

```text
Currency
Product prices
Order totals
Payment amounts
```

WooSmart must not create a parallel store currency system.

WooSmart must not:

- Change WooCommerce currency.
- Change product prices.
- Change order totals.
- Change payment amounts.
- Modify gateway currency logic.
- Add an independent Rial/Toman conversion layer.

## Current Development Store

The development store uses:

```text
IRT
```

WooCommerce therefore displays prices in Toman.

WooSmart follows that same currency context:

```text
Store:
تومان

WooSmart Admin:
تومان
```

No numeric conversion is performed by WooSmart.

## Currency Display Example

```text
WooCommerce amount:
150,000

WooCommerce currency:
IRT

WooSmart display:
150,000 تومان
```

The numeric amount remains exactly in WooCommerce's own representation.

---

# Current Data Model

Automation post type:

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

Example Condition:

```json
[
    {
        "field": "order_total",
        "operator": "greater_than",
        "value": "1000000"
    }
]
```

Example Actions:

```json
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
```

The monetary value is interpreted according to WooCommerce's currency representation. WooSmart does not silently convert the stored value because of a UI decision.

---

# Current Actions

## change_order_status

Changes the WooCommerce order status.

Supported statuses include:

```text
pending
processing
on-hold
completed
cancelled
refunded
failed
```

## notify_admin

Uses:

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

SMTP is not implemented inside WooSmart core.

---

# Current Execution Model

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

Multiple active Automations can currently respond to the same Trigger.

This behavior has been verified in real WooCommerce testing.

---

# Confirmed Multiple Automation Test

A real order produced:

```json
{
    "trigger": "order_created",
    "context": {
        "order_id": 56
    },
    "found_count": 3,
    "automation_ids": [48, 43, 35]
}
```

This confirmed that the Execution Engine can find multiple active matching Automations.

---

# Confirmed Condition Tests

A real order above the threshold produced:

```text
condition_passed
```

A real order that did not meet an equality rule produced:

```text
condition_failed
automation_conditions_failed
```

No Action was executed when the Condition failed.

---

# Confirmed Automation 43 Test

Automation 43 has been verified through a real WooCommerce order:

```text
Active
↓
order_created
↓
Order Total > configured value
↓
condition_passed
↓
notify_admin
```

The earlier issue where Automation 43 appeared not to be discovered was diagnosed with `automation_scan` and confirmed to be a multiple-Automation interpretation issue rather than a Condition or Query failure.

---

# Notification Diagnostics

The notification system has been implemented and tested through the real WooCommerce workflow.

## First Mail Error

The initial failure was:

```text
نشانی نامعتبر: (From): wordpress@localhost
```

WooSmart then explicitly used the administrator email as the development sender.

The logs began reporting:

```text
from:
mht7631@gmail.com
```

Therefore the original invalid localhost From-address problem was isolated and bypassed for development.

## Current Mail Error

After correcting the From address, the local environment produced:

```text
نمی‌توان تابع ایمیل را نمونه‌سازی کرد.
```

with:

```text
mail_error_code:
2
```

Current diagnosis:

```text
Condition = Passed
Action = notify_admin
From = Valid
wp_mail() = Called
Mailer initialization = Failed
```

The current blocker is therefore the local WordPress/PHP mail transport in XAMPP, not the Condition Engine or Action Registry.

---

# SMTP / Mail Transport Rule

WooSmart uses:

```text
wp_mail()
```

The WooSmart core does not implement its own SMTP engine.

Production email transport should be supplied by:

```text
WordPress SMTP configuration
Server SMTP
External mail transport
```

The next notification milestone is to configure and verify a valid mail transport in the development environment.

A dedicated configurable sender setting is planned for the future.

---

# Current Logging Events

Current events include:

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
condition_passed
condition_failed
automation_scan
```

User-facing labels are localized to Persian.

Internal event identifiers remain English.

---

# Current Tests

Confirmed:

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
- [x] Action Registry resolution
- [x] Action execution
- [x] Failed Action detection
- [x] Failed Automation detection
- [x] Persian Admin UI
- [x] RTL interface
- [x] WooCommerce IRT configuration
- [x] WooSmart تومان display for IRT
- [x] No WooSmart currency conversion
- [x] Formatted amount input
- [x] Real WooCommerce order execution
- [x] Automation logging
- [x] Action logging
- [x] Condition pass/failure logging
- [x] `wp_mail_failed` diagnostics
- [x] Multiple Automation detection

Pending:

- [ ] Local mail transport configuration
- [ ] Real SMTP delivery test
- [ ] Dedicated Notification Settings
- [ ] Full Multiple Actions UI stabilization
- [ ] Conflict Detection
- [ ] Execution Priority
- [ ] Execution Policy
- [ ] Multiple Conditions

---

# Important Current Limitation: Multiple Automations

Example:

```text
Automation A:
    Order Total > 500,000
    → Processing

Automation B:
    Order Total < 10,000,000
    → Completed
```

For a 2,000,000 order both Conditions are true.

Both Automations may execute and the second status Action may overwrite the first.

This is not final product behavior.

The final architecture will use:

```text
Conflict Detection
+
Execution Priority
+
Execution Policy
```

---

# Planned Conflict Management

Conflict analysis will consider:

```text
Trigger
+
Condition overlap
+
Action target
+
Action effect
```

Future conflict levels:

```text
🟢 Safe
🟡 Potential Conflict
🔴 Conflict
```

Existing Automations must never be silently modified or disabled because of a newly detected conflict.

---

# Planned Execution Priority

Each Automation will eventually support a Priority.

Example:

```text
Priority 10
Priority 20
Priority 30
```

Lower numbers execute earlier.

Priority alone is not sufficient; it will work with Conflict Detection and Execution Policy.

---

# Planned Execution Policy

Future policy examples:

```text
Continue
Stop
```

The future default is intended to be:

```text
Continue
```

unless explicit configuration or conflict handling requires stopping.

---

# Planned Multiple Conditions

Current behavior:

```text
All configured conditions must pass.
```

Future:

```text
Condition 1
AND
Condition 2
AND
Condition 3
```

Later:

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

Planned logic:

```text
AND
OR
Nested Groups
```

---

# Planned Multiple Actions

The current data model and execution engine can already store and execute multiple Actions.

The target UI will support:

- Add Action.
- Remove Action.
- Reorder Action.
- Configure each Action independently.
- Validate each Action.
- Show each Action result separately.

---

# Planned Trigger Expansion

Orders:

- [x] Order Created
- [ ] Order Paid
- [ ] Order Status Changed
- [ ] Order Completed
- [ ] Order Cancelled
- [ ] Order Failed
- [ ] Order Refunded
- [ ] Order On Hold

Customers:

- [ ] Customer Registered
- [ ] Customer Login
- [ ] Customer Role Changed

Products:

- [ ] Product Created
- [ ] Product Updated
- [ ] Product Stock Changed
- [ ] Product In Stock
- [ ] Product Out of Stock

Cart / Checkout:

- [ ] Cart Updated
- [ ] Checkout Started
- [ ] Checkout Completed
- [ ] Abandoned Cart

---

# Planned Condition Expansion

Order conditions:

- [x] Order Total
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

Customer conditions:

- [ ] Customer Role
- [ ] Customer Email
- [ ] Customer Order Count
- [ ] Customer Total Spent
- [ ] Customer Registration Date

Product conditions:

- [ ] Product Price
- [ ] Stock Quantity
- [ ] Stock Status
- [ ] Category
- [ ] SKU
- [ ] Product Type

---

# Planned Action Expansion

Order Actions:

- [x] Change Order Status
- [ ] Add Order Note
- [ ] Modify Order Metadata
- [ ] Apply Coupon
- [ ] Modify Order Items
- [ ] Add Product to Order
- [ ] Remove Product from Order

Notification Actions:

- [x] Notify Store Administrator by Email
- [ ] Customer Email
- [ ] Additional Admin Email
- [ ] SMS
- [ ] WhatsApp
- [ ] Telegram
- [ ] Push Notification

External Actions:

- [ ] Webhook
- [ ] HTTP Request
- [ ] REST API
- [ ] Slack
- [ ] Discord
- [ ] Google Sheets
- [ ] CRM integrations

---

# Future Execution History

The current Logger is an event logger.

A future dedicated Execution History system should track:

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

Potential statuses:

```text
pending
running
completed
failed
skipped
cancelled
```

---

# Future Automation Trace

WooSmart should eventually explain why an Automation executed and why a final value was produced.

Example:

```text
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
```

---

# Future Scheduling / Retry

Future versions may support:

```text
Wait 1 hour
Wait 24 hours
Execute at a specific time
Retry failed action
```

Potential infrastructure:

- [ ] WP-Cron
- [ ] Action Queue
- [ ] Background Processing
- [ ] Retry Mechanism
- [ ] Failed Job Handling
- [ ] Exponential Backoff

---

# Security

Current:

- [x] Capability checks
- [x] Nonces
- [x] Input sanitization
- [x] Output escaping
- [x] Action validation
- [x] Trigger validation
- [x] Condition validation
- [x] Automation configuration validation

Future:

- [ ] REST API authentication
- [ ] Webhook authentication
- [ ] Secure credential storage
- [ ] External request validation
- [ ] SSRF protection
- [ ] Permission separation
- [ ] Action-level permissions
- [ ] Rate limiting

---

# Performance / Database Roadmap

Current:

```text
Custom Post Type
+
Post Meta
+
WordPress Options Logger
```

Future possibilities:

```text
wp_woosmart_automations
wp_woosmart_executions
wp_woosmart_logs
wp_woosmart_jobs
```

Potential improvements:

- [ ] Automation caching
- [ ] Optimized queries
- [ ] Dedicated Logs table
- [ ] Background execution
- [ ] Execution queue
- [ ] Better large-volume handling
- [ ] Efficient Trigger filtering

---

# Immediate Development Roadmap

```text
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
More Actions
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
```

---

# First Major Product Goal

```text
WHEN
    ایجاد سفارش

IF
    مبلغ سفارش > 100,000 تومان

THEN
    ارسال اعلان به مدیر فروشگاه

AND

    تغییر وضعیت سفارش → در حال پردازش
```

Target flow:

```text
WooCommerce Order Created
    ↓
Trigger Detected
    ↓
Matching Automation Found
    ↓
Conditions Passed
    ↓
Actions Executed
    ↓
Results Collected
    ↓
Execution Logged
```

---

# Long-Term Product Vision

Example future workflow:

```text
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
```

The long-term system should allow complex workflows without requiring store administrators to write code.

---

# Development Principles

## Modular Architecture

Each major responsibility should have a clear module.

## Do Not Break Existing Features

Before changing a working component:

```text
Understand
↓
Modify
↓
Test Existing Behavior
↓
Test New Behavior
```

## Incremental Development

```text
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
```

## Real WooCommerce Testing

Admin UI functionality alone is not sufficient. The real WooCommerce execution path must also be tested.

## Full File Replacement

When a file changes during collaborative development, provide the complete file for replacement rather than partial snippets.

## Review Current File Before Modification

Always review the current GitHub/local version before modifying an existing file.

## Git as Source of Truth

GitHub is the project source of truth. Stable milestones should be committed.

## Currency Compatibility

WooCommerce owns currency. WooSmart must not silently create a competing currency conversion system.

## Root-Cause Diagnostics

```text
Reproduce
↓
Read Logs
↓
Identify Root Cause
↓
Change Smallest Necessary Layer
↓
Retest
```

---

# README Maintenance Rule

README is part of the project state.

After every meaningful milestone, update:

- What has been implemented.
- What has been tested.
- What is currently working.
- What limitations remain.
- What is currently being developed.
- What has intentionally been postponed.
- What the next development step is.
- Important architectural decisions.
- Important diagnostic findings.
- Currency decisions.

---

# Future Decisions Register

Important postponed areas:

- Conflict Detection.
- Execution Priority.
- Execution Policy.
- Multiple Conditions.
- AND / OR groups.
- Advanced Multiple Actions UI.
- Full Action Registry-driven UI.
- Notification sender configuration.
- SMTP environment configuration.
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

A postponed item remains documented until it is implemented, rejected, or replaced by a better design.

---

# Important Architectural Decisions

## WooCommerce Controls Currency

WooCommerce is the source of truth for the store currency.

## No Independent Rial / Toman Conversion

WooSmart does not convert values merely because the UI displays تومان or ریال.

## SMTP Is Not Part of the Core

WooSmart uses `wp_mail()` and relies on the WordPress/server mail transport.

## Internal Identifiers Remain English

User-facing labels may be Persian; internal identifiers remain stable English identifiers.

## Registry Architecture Is the Source of Truth

Conditions and Actions should be registered centrally and not duplicated across multiple layers.

## Execution Order Must Become Deterministic

Future execution planning must explicitly determine ordering, continuation, stopping, conflicts, and authoritative results.

## Existing Automations Must Not Be Silently Modified

Conflict detection must inform the user rather than silently disabling or changing existing Automations.

## Mail Diagnostics Must Preserve Root Cause

When email delivery fails, WooSmart should preserve the underlying WordPress / PHPMailer error whenever available.

---

# Current Status Summary

```text
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

Multiple Actions Data/Execution:
    🟢 Supported

Multiple Actions UI:
    🟡 Stabilization planned

Notification:
    🟡 Implemented / mail transport pending

Execution Engine:
    🟢 Functional and tested

Validation:
    🟢 Implemented

Logging:
    🟢 Functional MVP

Mail Diagnostics:
    🟢 Implemented

Multiple Automation Detection:
    🟢 Confirmed

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
```

---

# Project Rule for Future Development

When continuing the project in a new development session:

1. Treat GitHub and current project files as the source of truth.
2. Review the current implementation before changing a file.
3. Do not assume an older version is still current.
4. Preserve existing functionality unless a change is intentional.
5. Work incrementally.
6. Test every major change with real WooCommerce behavior.
7. Check WooSmart Logs after execution tests.
8. When a file changes, provide the complete file for replacement.
9. Update README.md after meaningful milestones.
10. Keep postponed requirements documented.
11. Record important architectural decisions.
12. Do not implement future features prematurely.
13. Confirm the previous stage is stable before moving to the next major stage.
14. Git commits should represent stable milestones.
15. Do not modify files based on assumptions when current source can be reviewed.
16. Keep Persian Admin terminology consistent.
17. Document real runtime root causes before changing unrelated architecture.
18. Do not introduce a second currency system when WooCommerce already supplies the required currency context.
19. Never silently convert stored monetary values because of a UI display decision.
20. Verify complete-file integrity before committing large file replacements.

---

# End

WooSmart Automation is being developed as a real WooCommerce automation platform rather than a collection of unrelated features.

The core principle remains:

```text
WHEN
    something happens

IF
    conditions are satisfied

THEN
    execute predictable actions
```

The current foundation is functional.

The Condition and Action Registry architecture is established.

WooCommerce remains the currency authority. The current development store uses `IRT`, and WooSmart displays `تومان` without independent currency conversion.

The current known notification blocker is a local mail transport initialization failure:

```text
نمی‌توان تابع ایمیل را نمونه‌سازی کرد.
```

with error code:

```text
2
```

The next immediate task is to resolve the local mail transport and verify real email delivery without adding SMTP-specific logic to WooSmart core.

The next major architectural challenge remains making multiple Automations execute safely, deterministically, and transparently.