# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation platform built around one simple concept:

**WHEN → IF → THEN**

```text
WHEN something happens
        ↓
IF conditions are satisfied
        ↓
THEN execute one or more actions
```

The long-term goal is not merely a rule plugin. WooSmart is being designed as a reliable, predictable, explainable, extensible, and customer-friendly automation engine for WooCommerce stores.

A store administrator should be able to create useful workflows without programming knowledge, understand what will happen before activation, and understand exactly what happened after execution.

---

# Repository / Source of Truth

GitHub:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the project source of truth.

Local XAMPP is the development/test environment.

Development workflow:

```text
Review current GitHub file
        ↓
Modify complete file
        ↓
Replace local file
        ↓
Test Admin UI
        ↓
Test real WooCommerce order
        ↓
Inspect Logger + Execution History
        ↓
Fix / retest
        ↓
Commit stable milestone
        ↓
Update README
```

---

# Current Status

Version:

```text
1.0.0
```

Stage:

```text
MVP / Foundation
```

The current foundation has been tested against real WooCommerce orders.

Implemented core:

```text
Plugin bootstrap
WooCommerce integration
Persian RTL Admin UI
Automation CRUD
Condition Registry
Condition Engine
Action Registry
Action Engine
Multiple Actions
Multiple Automation execution
Execution Policy MVP
Execution History
Execution Details
Notification Settings
wp_mail() integration
Provider-neutral mail diagnostics
WooCommerce-aware currency display
```

Current focus:

```text
Execution timing accuracy
Conflict Detection
Execution Priority
Formal Execution Planning
Multiple Conditions
Range / Between conditions
More Conditions / Actions / Triggers
```

---

# Product Vision

Example:

```text
WHEN
    Order Created

IF
    Order Total > 1,000,000 تومان

THEN
    Notify Store Administrator
    AND
    Change Order Status → Processing
```

Long-term target:

```text
WHEN
    Order Created

IF
    Order Total > 5,000,000 تومان
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

The UX should remain simple even when the engine becomes powerful.

---

# Current Architecture

Core execution flow:

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
Execution History + Logger
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
Registered Handler
       ↓
Action Result
```

Notification architecture:

```text
WooSmart Notification Settings
       ↓
Recipient Resolution
       ↓
wp_mail()
       ↓
WordPress Mail Transport
       ↓
SMTP / Email Provider
```

The bootstrap uses shared Condition, Action, and Execution services rather than unnecessary duplicate engines.

---

# File Structure

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
    ├── class-woosmart-notification-settings.php
    ├── class-woosmart-automation.php
    ├── class-woosmart-triggers.php
    ├── class-woosmart-post-types.php
    ├── class-woosmart-automation-manager.php
    ├── class-woosmart-condition-registry.php
    ├── class-woosmart-condition-engine.php
    ├── class-woosmart-action-registry.php
    ├── class-woosmart-action-engine.php
    ├── class-woosmart-execution-engine.php
    ├── class-woosmart-execution-history.php
    └── class-woosmart-execution-admin.php
```

---

# Current Capabilities

## Trigger

```text
[x] order_created
```

WooCommerce hook:

```text
woocommerce_new_order
```

Execution context contains the order ID.

## Conditions

Current condition:

```text
order_total
```

Operators:

```text
is_equal
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

The Condition Registry is the source of truth for definitions, labels, operators, value types, and evaluators.

Current condition model:

```text
All configured conditions must pass.
```

The data structure is already array-based for future Multiple Conditions and groups.

## Actions

Current Actions:

```text
change_order_status
notify_admin
```

Multiple Actions execute sequentially.

Example:

```text
Action 1 → Processing
Action 2 → Completed
Action 3 → Notify Administrator
```

Each Action produces an independent result and the Automation receives an overall result.

## Automation Management

```text
[x] Create
[x] Edit
[x] Enable / Disable
[x] Duplicate
[x] Delete
[x] Validation
```

Stored metadata:

```text
_woosmart_status
_woosmart_trigger
_woosmart_conditions
_woosmart_actions
```

Internal identifiers remain English for stability.

---

# Execution Policy

Execution Policy is implemented as an MVP feature.

Current policies:

```text
all
first_match
first_success
```

### ALL

All matching Automations may execute.

### FIRST_MATCH

Evaluation stops after the first Automation whose Conditions pass.

### FIRST_SUCCESS

Evaluation continues until an Automation completes successfully. A matching Automation whose Actions fail does not stop the policy.

These policies have been tested with real orders.

Important architectural rule:

```text
Database query order must not become the permanent execution-order mechanism.
```

Priority will become the explicit ordering mechanism.

---

# Execution History

Execution History is now a user-facing feature and is intentionally separate from the technical Logger.

```text
Logger
    = technical event stream

Execution History
    = human-readable execution record
```

Current Execution information includes:

```text
Execution ID
Automation ID
Order ID / context
Trigger
Execution Policy
Status
Action counts
Start Time
End Time
Duration
Message
Condition results
Action results
```

Example:

```text
Execution #8
Automation: #68 — زیر 3,000,000
Order: #74
Trigger: ایجاد سفارش
Policy: اولین اتوماسیون موفق
Result: موفق

Condition
✓ مبلغ سفارش کمتر از 3,000,000 تومان

Actions
1. تغییر وضعیت سفارش → در حال پردازش
   ✓ موفق

2. تغییر وضعیت سفارش → تکمیل‌شده
   ✓ موفق

3. ارسال اعلان به مدیر
   ✓ موفق
```

The customer should be able to understand an execution without opening raw logs.

---

# Execution Timing Requirement

Execution History must use measured execution timestamps rather than simply calculating the gap between unrelated log entries.

Required model:

```text
Execution Start
    ↓
Action 1 Start / End
    ↓
Action 2 Start / End
    ↓
Action N Start / End
    ↓
Execution End
```

A historical issue exposed misleading long durations caused by timestamp gaps while actual work completed much faster. This is now an explicit requirement:

```text
Displayed duration = measured execution duration
```

Per-Action duration should also remain available for diagnosis.

---

# Multiple Automations

Multiple active Automations can match the same Trigger.

Example:

```text
Automation A
    Order Total > 1,000,000

Automation B
    Order Total < 3,000,000
```

An order worth 2,000,000 can match both.

Current behavior depends on the selected Execution Policy:

```text
ALL
    Both may execute.

FIRST_MATCH
    First matching Automation executes.

FIRST_SUCCESS
    Continue until one succeeds.
```

This behavior has been tested with real orders.

---

# Current Limitation: Competing Automations

Example:

```text
Automation A
    Order Total > 500,000
    → Status = Processing

Automation B
    Order Total < 10,000,000
    → Status = Completed
```

For a 2,000,000 order both Conditions are true and both Automations target `Order Status`.

Under `all`, one result can overwrite another.

This is not the desired final behavior.

The long-term solution is:

```text
Conflict Detection
+
Execution Priority
+
Execution Policy
+
Execution Trace
```

---

# Conflict Detection

Current development already has Action-level warnings for multiple status changes inside one Automation.

Example:

```text
Action 1 → Processing
Action 2 → Completed
```

These warnings are currently advisory and non-blocking.

The future Conflict Engine is broader and must analyze:

```text
Trigger compatibility
Condition overlap
Action target
Action effect
Priority
Execution Policy
```

## Conflict Levels

```text
🟢 Safe
    No meaningful interaction expected.

🟡 Potential Conflict
    Automations may react to the same event but do not clearly overwrite the same state.

🔴 Conflict
    Automations can modify the same target with incompatible outcomes.
```

Warnings should explain:

```text
Which Automation
Which Conditions overlap
Which Action conflicts
Which property is affected
Why the conflict matters
```

Never silently rewrite or disable another Automation.

---

# Execution Priority

Priority is the next major architectural stage.

Target:

```text
Priority 10
Priority 20
Priority 30
```

Lower number = earlier execution.

Example:

```text
Priority 10
    ↓
Automation A

Priority 20
    ↓
Automation B
```

Priority creates deterministic ordering, but Priority alone is not enough. It must work with Conflict Detection and Execution Policy.

---

# Execution Planning

Long-term execution architecture:

```text
Trigger
    ↓
Candidate Automations
    ↓
Condition Evaluation
    ↓
Conflict Analysis
    ↓
Execution Plan
    ↓
Priority Ordering
    ↓
Execution Policy
    ↓
Action Execution
    ↓
Action Results
    ↓
Execution History
    ↓
Technical Logger
```

The engine should become:

```text
Predictable
Deterministic
Explainable
Debuggable
Extensible
```

---

# Multiple Actions

Current foundation:

```text
[x] Multiple Action storage
[x] Multiple Action execution
[x] Action Registry
[x] Individual Action results
[x] Reordering UI
[x] Action-level conflict warnings
```

Target UX:

```text
THEN

[ Action 1 ]
[ Action 2 ]
[ Action 3 ]

+ افزودن عملیات
```

Users should be able to add, remove, reorder, configure, validate, preview, and understand every Action independently.

---

# Condition Range / Between

A planned high-value UX improvement is a first-class range condition.

Instead of:

```text
Order Total > 1,000,000
AND
Order Total < 5,000,000
```

the user should eventually be able to select:

```text
مبلغ سفارش
بین
1,000,000
تا
5,000,000 تومان
```

Target model:

```text
operator: between
min: 1000000
max: 5000000
```

This improves usability and makes monetary range analysis easier for Conflict Detection.

---

# Multiple Conditions

Current:

```text
All conditions must pass.
```

Next:

```text
Condition 1
AND
Condition 2
AND
Condition 3
```

Later:

```text
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

Target capabilities:

```text
AND
OR
Nested groups
Grouping
Negation where useful
Human-readable summaries
```

The data model must be designed before implementation to avoid destructive future migrations.

---

# Human-Friendly UX

Core product principle:

```text
Powerful engine
+
Simple UI
```

Eventually an Automation should be explainable in natural language:

```text
وقتی سفارش ایجاد شد
اگر مبلغ سفارش بین 1,000,000 تا 5,000,000 تومان بود
و روش پرداخت کارت‌به‌کارت بود
آنگاه:
    وضعیت را در حال پردازش کن
    و به مدیر اعلان بده
```

WooSmart should hide internal technical details unless they help the customer make a decision.

---

# Customer-Focused Features

These are product features, not just engineering tasks.

## Quick Start

```text
Choose Trigger
    ↓
Choose Condition
    ↓
Choose Action
    ↓
Test
    ↓
Activate
```

## Safe Activation

Before activation show:

```text
Trigger
Conditions
Actions
Execution Policy
Possible conflicts
Expected effects
```

## Preview / Dry Run

Example:

```text
Preview this Automation against Order #73
```

Result should say:

```text
Condition: Pass
Action 1: Would execute
Action 2: Would execute
Conflict: None
```

Dry Run must not mutate the order.

## Human-readable Summary

Generate a readable sentence/paragraph from the Automation configuration.

## Automation Templates

Potential built-in recipes:

```text
سفارش‌های گران
سفارش‌های پرداخت‌نشده
تغییر خودکار وضعیت سفارش
هشدار موجودی کم
اعلان سفارش ویژه
VIP customer workflow
Failed payment alert
Refund notification
```

## Import / Export

Future:

```text
Export Automation
    ↓
WooSmart JSON
    ↓
Import on another site
```

Useful for backups, agencies, migration, and template packs.

Imports must validate compatibility and never blindly activate unsafe configurations.

---

# Planned Execution Trace

For one order:

```text
Order #42

Automation Trace

#40
Conditions: Passed
Action: Status → Completed

#35
Conditions: Passed
Action: Status → Processing

Final Status: Processing
Reason: Automation #35 executed later under the selected plan.
```

This becomes especially important after Priority and Conflict Detection.

---

# Planned Trigger Expansion

## Orders

```text
[x] Order Created
[ ] Order Paid
[ ] Order Status Changed
[ ] Order Completed
[ ] Order Cancelled
[ ] Order Failed
[ ] Order Refunded
[ ] Order On Hold
```

## Customers

```text
[ ] Customer Registered
[ ] Customer Login
[ ] Customer Role Changed
```

## Products

```text
[ ] Product Created
[ ] Product Updated
[ ] Product Stock Changed
[ ] Product Becomes In Stock
[ ] Product Becomes Out of Stock
```

## Cart / Checkout

```text
[ ] Cart Updated
[ ] Checkout Started
[ ] Checkout Completed
[ ] Abandoned Cart
```

---

# Planned Condition Expansion

## Orders

```text
[x] Order Total
[ ] Order Subtotal
[ ] Order Status
[ ] Payment Method
[ ] Shipping Method
[ ] Coupon
[ ] Customer
[ ] Billing Country
[ ] Shipping Country
[ ] Order Item Count
[ ] Product
[ ] Product Category
[ ] Product Quantity
[ ] Order Total Between
```

## Customers

```text
[ ] Customer Role
[ ] Customer Email
[ ] Customer Order Count
[ ] Customer Total Spent
[ ] Customer Registration Date
```

## Products

```text
[ ] Product Price
[ ] Stock Quantity
[ ] Stock Status
[ ] Category
[ ] SKU
[ ] Product Type
```

All future Conditions should be added through the Condition Registry.

---

# Planned Action Expansion

## Order Actions

```text
[x] Change Order Status
[ ] Add Order Note
[ ] Modify Order Metadata
[ ] Apply Coupon
[ ] Modify Order Items
[ ] Add Product to Order
[ ] Remove Product from Order
```

## Notifications

```text
[x] Notify Store Administrator by Email
[ ] Customer Email
[ ] Additional Admin Email
[ ] SMS
[ ] Telegram
[ ] WhatsApp
[ ] Push Notification
```

## External / Integration Actions

```text
[ ] Webhook
[ ] HTTP Request
[ ] REST API
[ ] Slack
[ ] Discord
[ ] Google Sheets
[ ] CRM integrations
```

External HTTP functionality must include endpoint validation, authentication, timeouts, SSRF protection, and rate limiting.

---

# Notification Architecture

WooSmart is provider-neutral.

```text
WooSmart
    ↓
wp_mail()
    ↓
WordPress Mail Transport
    ↓
Any compatible provider
```

Possible environments:

```text
SMTP hosting
Resend
Brevo
Gmail
Microsoft 365
Amazon SES
Other transactional providers
```

WooSmart manages recipient preference.

The active WordPress Mail Transport manages sender and delivery.

WooSmart does not implement SMTP.

---

# Mail Diagnostics

WooSmart captures `wp_mail_failed` / `WP_Error` information where available.

Generic categories may include:

```text
خطای محدودیت گیرنده
خطای احراز هویت سرویس ایمیل
خطای اتصال به سرویس ایمیل
خطای آدرس فرستنده
خطای SSL / TLS
خطای شبکه
خطای عمومی سرویس ارسال ایمیل
```

Original provider diagnostics should be preserved.

Sensitive credentials must never appear in diagnostics.

A real provider-side recipient restriction was successfully captured during development, confirming the provider-neutral diagnostic path.

---

# Currency Architecture

WooCommerce is the source of truth.

Current development currency:

```text
IRT
```

WooSmart display:

```text
تومان
```

For `IRR`:

```text
ریال
```

Rules:

```text
No parallel currency engine
No ×10 conversion
No ÷10 conversion
No modification of product prices
No modification of order totals
No modification of payment amounts
```

Formatting is presentation only.

---

# Logging vs Execution History

## Logger

```text
Technical events
Diagnostics
Developer investigation
Raw event history
```

## Execution History

```text
Store administrator view
Execution overview
Condition results
Action results
Duration
Human-readable explanation
```

A normal store owner should not need to understand raw logs to know what happened to an order.

---

# Security Principles

Current controls:

```text
Capability checks
Nonces
Input sanitization
Output escaping
Trigger validation
Condition validation
Action validation
Automation validation
```

Before external features are introduced:

```text
REST authentication
Webhook authentication
Secure credential storage
Endpoint validation
SSRF protection
Timeouts
Rate limiting
Permission separation
Action-level permissions
```

Security must be designed before external HTTP features are shipped.

---

# Performance / Production Evolution

The MVP favors clarity and safe behavior over premature optimization.

Future production improvements may include:

```text
Automation caching
Optimized candidate queries
Trigger filtering
Dedicated execution/log tables
Background execution
Execution queue
Job batching
Large-store optimization
```

Potential tables:

```text
wp_woosmart_automations
wp_woosmart_executions
wp_woosmart_logs
wp_woosmart_jobs
```

Migration should be driven by real scale requirements.

---

# Scheduling / Delayed Actions

Future:

```text
Wait 1 hour
Wait 24 hours
Execute at a specific time
Execute after a condition remains true
```

Example:

```text
Order Created
    ↓
Wait 24 hours
    ↓
Send Reminder
```

Potential infrastructure:

```text
WP-Cron
Action Queue
Background Processing
Retry Mechanism
Failed Job Handling
```

Scheduling should follow a stable synchronous execution model.

---

# Retry System

Future model:

```text
Action Failed
    ↓
Retry #1
    ↓
Retry #2
    ↓
Retry #3
    ↓
Permanent Failure
```

Target controls:

```text
Maximum retries
Retry delay
Exponential backoff
Permanent failure
Failure notification
```

Retry semantics should depend on Action type; a local state mutation and an external HTTP call should not automatically share the same retry policy.

---

# Extensibility / Developer API

Long-term third-party extensibility should allow registration of:

```text
Triggers
Conditions
Actions
Notification channels
Data providers
Execution policies
```

The current Registry architecture is the foundation for this.

---

# Testing Strategy

Development cycle:

```text
Build
  ↓
Replace Complete File
  ↓
Check syntax / activation
  ↓
Test Admin UI
  ↓
Test real WooCommerce order
  ↓
Check Logger
  ↓
Check Execution History
  ↓
Fix
  ↓
Retest existing behavior
  ↓
Commit stable milestone
  ↓
Update README
```

A feature is not complete because the UI works. Runtime behavior must also be tested.

---

# Confirmed Tests

```text
[x] Create Automation
[x] Edit Automation
[x] Enable / Disable
[x] Duplicate
[x] Delete
[x] Order Created trigger
[x] Order Total condition
[x] Condition pass
[x] Condition failure
[x] Change Order Status
[x] Multiple Action storage
[x] Multiple Action execution
[x] Action Registry resolution
[x] Action success detection
[x] Action failure detection
[x] Automation failure detection
[x] Persian RTL Admin UI
[x] WooCommerce IRT display
[x] تومان display without conversion
[x] Notification Settings
[x] Notification recipient fallback
[x] wp_mail() notification
[x] Real notification delivery in development
[x] wp_mail_failed diagnostics
[x] Provider-neutral mail classification
[x] Multiple matching Automations
[x] ALL policy
[x] FIRST_MATCH policy
[x] FIRST_SUCCESS policy
[x] Execution History list
[x] Execution Details
[x] Per-Action result display
```

---

# Known Limitations

```text
Only one Trigger is implemented.
Only one Condition family is implemented.
No full Multiple Conditions / groups yet.
Priority is not yet implemented.
Full cross-Automation Conflict Engine is not yet implemented.
Current Action conflict warnings are advisory.
Advanced external Actions are not implemented.
Scheduling is not implemented.
Retry queue is not implemented.
Automated test suite is not complete.
```

These limitations are deliberate roadmap items, not forgotten requirements.

---

# Product Roadmap

## Stage A — Foundation ✅

```text
[x] Plugin bootstrap
[x] WooCommerce integration
[x] Automation CRUD
[x] Condition Registry
[x] Action Registry
[x] Basic Trigger
[x] Basic Condition
[x] Basic Actions
[x] Persian RTL Admin UI
```

## Stage B — Execution Intelligence 🟡

```text
[x] Multiple Actions
[x] Multiple Automation detection
[x] Execution Policy MVP
[x] Execution History
[x] Execution Details
[ ] Accurate final timing model
[ ] Conflict Detection Engine
[ ] Execution Priority
[ ] Formal Execution Planning
```

## Stage C — Rule Power

```text
[ ] Multiple Conditions
[ ] AND / OR groups
[ ] Nested groups
[ ] Between / Range conditions
[ ] More Order Conditions
[ ] More Customer Conditions
[ ] More Product Conditions
```

## Stage D — Action Power

```text
[ ] Order Note
[ ] Order Metadata
[ ] Coupon
[ ] Customer Email
[ ] Telegram
[ ] Webhook
[ ] HTTP Request
[ ] Integration framework
```

## Stage E — User Experience

```text
[ ] Human-readable Automation summary
[ ] Preview / Dry Run
[ ] Safe Activation
[ ] Better Conflict UX
[ ] Automation Templates
[ ] Quick Start
[ ] Import / Export
[ ] Recipe Library
```

## Stage F — Reliability

```text
[ ] Structured errors
[ ] Retry system
[ ] Scheduling
[ ] Background execution
[ ] Execution queue
[ ] Dedicated production tables
[ ] Performance optimization
[ ] Automated tests
```

## Stage G — Platform

```text
[ ] Developer API
[ ] Third-party Trigger Registry
[ ] Third-party Condition Registry
[ ] Third-party Action Registry
[ ] Notification channel abstraction
[ ] Advanced Automation Builder
```

---

# Commercial Product Direction

The plugin should not be positioned as:

```text
"A plugin with many technical features"
```

The customer value proposition should be:

```text
"Automate WooCommerce work without coding."
```

Customer-facing strengths should be:

```text
Easy setup
Predictable execution
Clear warnings
Useful templates
Readable history
Safe activation
No vendor lock-in
WordPress mail compatibility
Extensible architecture
```

Potential commercial layers:

```text
Core plugin
    ↓
Premium automation features
    ↓
Premium integrations
    ↓
Template / recipe packs
    ↓
Advanced execution features
```

The architecture should allow commercial expansion without making the core engine fragile.

---

# Important Product Principles

1. **Simple for the customer** — hide unnecessary technical complexity.
2. **Predictable for the store** — ordering, policy, and side effects must be understandable.
3. **Explainable after execution** — the customer must see what happened and why.
4. **Safe by default** — warn before dangerous or confusing behavior.
5. **Extensible internally** — prefer Registries over scattered hard-coded type lists.
6. **Provider-neutral** — do not depend on one mail or integration provider.
7. **WooCommerce remains the source of truth** — do not create parallel semantics unnecessarily.
8. **No silent destructive behavior** — never silently rewrite or disable another Automation.
9. **Measure before optimizing** — optimize observed bottlenecks.
10. **Stabilize before expanding** — do not build a huge feature layer on unstable execution semantics.

---

# Important Architectural Decisions

## Currency

WooCommerce controls monetary semantics. WooSmart only formats/display values for its own UI and never silently converts Rial/Toman.

## Mail

WooSmart uses `wp_mail()` and respects the active WordPress Mail Transport. SMTP is not part of the core engine.

## Recipient vs Sender

WooSmart manages the notification recipient preference. The Mail Transport controls sender and delivery.

## Registry Architecture

Conditions and Actions should be defined centrally and consumed by Admin, Manager, and Execution layers.

## Deterministic Execution

The final system must explicitly determine which Automation runs first, whether execution continues, whether execution stops, and which result is authoritative.

## Conflict Safety

Conflict analysis must explain risk, not silently rewrite customer configuration.

## Execution History

Execution History is the human-facing record; technical Logger entries are diagnostic evidence.

## Root-Cause Diagnostics

When WordPress / PHPMailer provides a concrete error, preserve it rather than reducing everything to `action_failed`.

---

# Future Decision Register

Ideas remain here until they are **Implemented**, **Rejected**, or **Replaced by a better design**.

```text
Conflict Detection
Execution Priority
Formal Execution Planning
Advanced Execution Policy
Multiple Conditions
AND / OR groups
Between / Range conditions
Human-readable summaries
Safe Activation
Dry Run
Automation Trace
Import / Export
Automation Templates
Recipe Library
Scheduling
Retry System
Background Processing
Execution Queue
More Triggers
More Conditions
More Actions
External Integrations
Developer API
Dedicated production tables
Automated tests
Advanced Automation Builder
```

---

# Recommended Version Strategy

Version numbers represent milestones, not promises.

Possible structure:

```text
1.0.x  Foundation stabilization
1.1.x  Execution Intelligence
1.2.x  Conflict + Priority
1.3.x  Multiple Conditions
1.4.x  Action expansion
1.5.x  UX / Templates / Dry Run
1.6.x  Reliability / Retry / Scheduling
2.x    Advanced Builder + Platform APIs
```

Actual versioning may change with product scope.

---

# Project Rules for Future Sessions

1. GitHub and current project files are the source of truth.
2. Review the actual current file before modifying it.
3. Never assume an old version is still current.
4. Preserve existing working behavior unless a change is intentional.
5. Work incrementally.
6. Test major changes with real WooCommerce behavior.
7. Check both Logger and Execution History after execution changes.
8. When a file changes, provide the complete file for replacement.
9. Do not implement future features before the current milestone is stable.
10. Update README after meaningful milestones.
11. Keep postponed features documented.
12. Keep Persian UI terminology consistent.
13. Keep internal identifiers English and stable.
14. Preserve runtime diagnostics whenever possible.
15. Do not introduce independent currency conversion logic.
16. Do not couple WooSmart core to a specific email provider.
17. Do not silently alter or disable another Automation.
18. Verify complete-file integrity before committing large replacements.
19. Commit stable milestones rather than every experiment.
20. Prefer measured runtime evidence over assumptions.

---

# Current Development Target

The next sequence is:

```text
1. Finalize Execution timing accuracy
        ↓
2. Conflict Detection Engine
        ↓
3. Execution Priority
        ↓
4. Formal Execution Planning
        ↓
5. Multiple Conditions
        ↓
6. Between / Range conditions
        ↓
7. More Conditions
        ↓
8. More Actions
        ↓
9. Human-readable summaries + Dry Run
        ↓
10. Templates / Recipe Library
```

The immediate goal is not to add dozens of unrelated features. The goal is to make this execution pipeline trustworthy:

```text
One Trigger
    ↓
Candidate Automations
    ↓
Conditions
    ↓
Conflict Analysis
    ↓
Priority
    ↓
Execution Policy
    ↓
Actions
    ↓
Accurate Results
    ↓
Readable Execution History
```

---

# Final Product Architecture

```text
Trigger
    ↓
Candidate Selection
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
Action Registry
    ↓
Action Execution
    ↓
Action Results
    ↓
Automation Result
    ↓
Execution History
    ↓
Technical Logger
    ↓
Monitoring / Trace
```

Everything else — more Conditions, more Actions, integrations, templates, scheduling, notifications, APIs, and builder improvements — should plug into this architecture rather than bypass it.

---

# Definition of a Successful WooSmart

A successful WooSmart installation should make these questions easy to answer:

```text
What should happen?
Why should it happen?
Which Automation will run?
Which Automation will run first?
Will another Automation conflict with it?
What Actions will execute?
What happened during execution?
Did anything fail?
Why did it fail?
How long did it take?
What was the final result?
```

The customer should not need to read PHP, inspect raw database records, or understand WooCommerce hooks to answer these questions.

That is the product standard.

---

# End

WooSmart Automation is being developed as a real WooCommerce automation platform rather than a collection of unrelated features.

The core remains:

```text
WHEN
    Trigger

IF
    Conditions

THEN
    Actions
```

The product is now moving from basic execution toward **execution intelligence**:

```text
Multiple Automations
        ↓
Execution Policy
        ↓
Execution History
        ↓
Conflict Detection
        ↓
Priority
        ↓
Deterministic Execution Planning
```

Long-term objective:

```text
Powerful enough for advanced stores.
Simple enough for normal store owners.
Predictable enough to trust.
Explainable enough to support.
Extensible enough to grow.
```

WooSmart should eventually feel less like a technical rules engine and more like a dependable automation assistant built directly into WooCommerce.
