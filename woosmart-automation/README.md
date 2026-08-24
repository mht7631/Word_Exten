# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation platform built around one simple idea:

**WHEN → IF → THEN**

```text
WHEN something happens
        ↓
IF conditions are satisfied
        ↓
THEN execute one or more actions
```

The project is being built as a real product, not merely as a collection of WooCommerce hooks. The long-term goal is a reliable, predictable, explainable, extensible, and customer-friendly automation engine that store administrators can use without programming knowledge.

A user should eventually be able to create a workflow, understand what it will do before activation, know when another automation may interfere with it, and see exactly what happened after execution.

---

# 1. Project Identity

**Product:** WooSmart Automation  
**Platform:** WordPress + WooCommerce  
**Language / UI:** Persian, RTL  
**Version:** `1.0.0`  
**Stage:** MVP / Foundation → Execution Planning  

Repository:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the source of truth. Local XAMPP is the development and test environment.

---

# 2. Why This Project Exists

The project started from a simple need: WooCommerce stores often need small business rules such as:

```text
When an order is created
If the order is above a certain amount
Then notify the administrator
```

A useful automation product quickly becomes more complex:

- More than one Condition may be needed.
- More than one Action may be needed.
- Several Automations may match the same order.
- Two Automations may modify the same WooCommerce state.
- An Action may succeed while a later Action fails.
- Mail delivery may fail for reasons outside WooSmart.
- Administrators need to know why an Automation did or did not run.

WooSmart is therefore designed around **predictability, safety, explainability, and usability**, not merely around adding more hooks.

---

# 3. Product Vision

The final product should make a powerful automation engine feel simple:

```text
WHEN
    Order Created

IF
    Order Total is between 1,000,000 and 5,000,000 تومان
    AND
    Payment Method = Bank Transfer
    AND
    Customer Role = Wholesale

THEN
    Change Order Status → Processing
    AND
    Notify Store Administrator
    AND
    Add Order Note
```

A normal store owner should be able to understand the rule as a readable sentence rather than seeing technical internals.

Long-term product promise:

```text
Easy for beginners
Powerful for advanced users
Predictable in execution
Clear about conflicts
Transparent after execution
Compatible with WooCommerce
Independent from email vendors
Commercially useful
Extensible for future integrations
```

---

# 4. Project History — From Start to Current State

This section is intentionally descriptive rather than a raw changelog. Its purpose is to let a new developer or another AI understand **where the project started, why the architecture evolved, what has already been solved, and what the next architectural goal is**.

## Phase 1 — Foundation

WooSmart began as a small WooCommerce automation MVP using `WHEN → IF → THEN`.

The first goal was simply to prove a full path:

```text
WooCommerce Order
    ↓
Trigger
    ↓
Condition
    ↓
Action
    ↓
Log
```

The foundation included the plugin bootstrap, WooCommerce detection, internal Automation post type, Admin UI, Automation CRUD, one Trigger, one Condition family, Actions, and basic logging.

## Phase 2 — Condition Registry

Conditions were moved from scattered hard-coded logic into a central **Condition Registry**.

A Condition definition now provides metadata such as:

```text
label
value_type
operators
evaluator
```

The Condition Engine, Automation Manager, and Admin UI all use the Registry as their source of truth.

This was a major architectural step because future Conditions can be added centrally instead of rewriting several unrelated classes.

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

## Phase 3 — Currency-Aware WooCommerce UI

The development store uses WooCommerce `IRT`, while the Persian UI should display `تومان`.

The project explicitly decided **not** to create an independent currency conversion system.

WooCommerce remains the source of truth.

WooSmart only presents the value appropriately:

```text
IRT → تومان
IRR → ریال
```

No hidden `×10` or `÷10` conversion is performed.

The Admin amount field was then improved with thousands separators, correct LTR numeric presentation inside RTL, and a separate currency label.

This is a compatibility rule, not merely a UI preference.

## Phase 4 — Action Registry

Actions were moved into a central **Action Registry** so that the Execution Engine does not need to know every Action implementation.

Current Actions:

```text
change_order_status
notify_admin
```

The registry resolves the Action definition and handler, which gives the project a clear extension point for future Actions.

## Phase 5 — Multiple Actions

The original MVP was too close to a single-action rule. The product vision required real workflows:

```text
THEN
    Action 1
    AND
    Action 2
    AND
    Action 3
```

The data model and UI were expanded to support multiple Actions, sequential execution, Action reordering, individual Action results, and an overall Automation result.

Real tests included workflows such as:

```text
1. Change status → Processing
2. Change status → Completed
3. Notify administrator
```

## Phase 6 — Real Email Delivery and Provider-Neutral Diagnostics

The notification Action uses:

```text
WooSmart
    ↓
wp_mail()
    ↓
WordPress Mail Transport
    ↓
SMTP / Email Provider
```

WooSmart does not implement its own SMTP system and does not depend on a particular vendor.

The recipient is configured by WooSmart, while the WordPress Mail Transport controls sender and delivery.

Real mail delivery was confirmed during development. A real provider-side recipient restriction also exposed the need for better diagnostics. WooSmart now captures `wp_mail_failed` / `WP_Error` information during its own notification attempts, classifies common failures generically, and preserves the original provider message.

## Phase 7 — Multiple Matching Automations

Real WooCommerce orders proved that several active Automations can match the same Trigger.

For example:

```text
Automation A
    Order Total > 1,500,000

Automation B
    Order Total < 5,000,000
```

An order worth 2,000,000 can satisfy both.

That exposed a larger engine problem:

```text
Which Automation runs first?
Should another Automation also run?
What if the first one fails?
What if two Automations change the same state?
```

This became the reason for Execution Policy, Conflict Detection, Priority, and formal Execution Planning.

## Phase 8 — Execution Policy

Three policies were implemented and tested with real orders:

```text
ALL
FIRST_MATCH
FIRST_SUCCESS
```

### ALL

All matching Automations may execute.

### FIRST_MATCH

Execution stops after the first Automation whose Conditions pass.

### FIRST_SUCCESS

Execution continues until an Automation completes successfully. A matching Automation whose Actions fail does not stop the policy.

A real test demonstrated:

```text
#68
Condition ✓
Action 1 ✓
Action 2 ✓
Action 3 ✕
→ Automation FAILED

#43
Condition ✓
Action 1 ✓
Action 2 ✓
→ Automation SUCCESS
→ FIRST_SUCCESS stops
```

This proved that `FIRST_SUCCESS` uses the real Automation result, not merely the Condition result.

## Phase 9 — Execution History

The technical Logger was useful for development but was not enough for a store administrator.

A separate **Execution History** layer was introduced:

```text
Logger
    = technical event stream

Execution History
    = customer-readable execution record
```

An Execution record contains information such as:

```text
Execution ID
Automation ID
Order ID
Trigger
Execution Policy
Status
Action counts
Start time
End time
Duration
Condition snapshot
Action snapshot
Action results
Message
```

Each Execution also has a human-readable detail page.

This lets a user answer:

```text
What ran?
Why did it run?
What Conditions passed?
Which Actions ran?
Which Action failed?
How long did it take?
What was the final result?
```

## Phase 10 — Historical Snapshot Integrity

A critical requirement was discovered while developing Execution History: a historical Execution must describe the Automation **as it existed at execution time**.

If an Automation later changes from:

```text
Order Total < 5,000,000
```

to:

```text
Order Total < 3,000,000
```

the old Execution must still show the original 5,000,000 rule.

The same applies to its Actions.

This was tested successfully. Execution History is therefore a historical snapshot, not a live view of today's Automation configuration.

## Phase 11 — Execution Timing

The first Execution History implementation exposed a timing problem: a duration could be inflated by gaps between unrelated log entries even though the actual Execution finished much faster.

The timing model was corrected so that:

```text
Execution duration
    = measured Execution start → measured Execution end
```

and each Action can also have its own measured duration.

A real test confirmed that the sum of Action durations closely tracks the overall Execution duration.

Permanent requirement:

> **Displayed duration must represent measured execution time.**

## Phase 12 — Action-Level Conflict Warnings

Multiple Actions revealed an important first-level conflict class inside a single Automation:

```text
Action 1 → Processing
Action 2 → Completed
```

The UI now shows non-blocking warnings for repeated or sequential order-status changes and explains possible WooCommerce hooks and side effects.

This is only the first layer of Conflict Detection. The final goal is a broader cross-Automation Conflict Engine.

## Current Phase — Execution Planning

The project is now moving from isolated MVP features toward explicit execution planning:

```text
Trigger
    ↓
Candidate Automations
    ↓
Condition Evaluation
    ↓
Conflict Analysis
    ↓
Priority Ordering
    ↓
Execution Policy
    ↓
Action Execution
    ↓
Results
    ↓
Execution History
    ↓
Technical Logger
```

This is the transition from a simple rule runner to a real automation platform.

---

# 5. Current Architecture

Core runtime:

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
     ├── Execution History
     └── Technical Logger
```

Condition system:

```text
Condition Registry
     ↓
Condition Engine
     ↓
Condition Result
```

Action system:

```text
Action Registry
     ↓
Action Engine
     ↓
Registered Handler
     ↓
Action Result
```

Notification system:

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

Future execution-planning system:

```text
Trigger
     ↓
Candidate Automations
     ↓
Conditions
     ↓
Conflict Detection
     ↓
Priority
     ↓
Execution Policy
     ↓
Actions
     ↓
Results
```

Shared Condition, Action, and Execution services are used to avoid unnecessary duplicate engine instances.

---

# 6. Current File Structure

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
    ├── class-woosmart-execution-admin.php
    └── class-woosmart-priority-admin.php
```

---

# 7. File Responsibilities

| File | Responsibility |
| --- | --- |
| `woosmart-automation.php` | Plugin bootstrap, shared services, initialization, activation/deactivation |
| `class-woosmart-core.php` | WooCommerce dependency and compatibility foundation |
| `class-woosmart-logger.php` | Technical event logging |
| `class-woosmart-currency.php` | WooCommerce-aware display-only currency handling |
| `class-woosmart-admin.php` | Main Persian RTL Admin UI and Automation Builder |
| `class-woosmart-notification-settings.php` | Notification recipient configuration and test email |
| `class-woosmart-automation.php` | Automation foundation |
| `class-woosmart-triggers.php` | WooCommerce Trigger integration |
| `class-woosmart-post-types.php` | Internal Automation post type |
| `class-woosmart-automation-manager.php` | CRUD, validation, persistence |
| `class-woosmart-condition-registry.php` | Central Condition definitions |
| `class-woosmart-condition-engine.php` | Condition evaluation |
| `class-woosmart-action-registry.php` | Central Action definitions / handlers |
| `class-woosmart-action-engine.php` | Action execution and results |
| `class-woosmart-execution-engine.php` | Runtime orchestration and Policy |
| `class-woosmart-execution-history.php` | Historical Execution records and snapshots |
| `class-woosmart-execution-admin.php` | Execution History / Policy UI |
| `class-woosmart-priority-admin.php` | Priority UI and Priority persistence; runtime integration is being validated |

---

# 8. Current Features — Status and Meaning

## Plugin Foundation — ✅

The plugin can be activated in WordPress, detect WooCommerce, register its Automation type, and initialize the runtime.

## Automation CRUD — ✅

Administrators can create, edit, enable/disable, duplicate, and delete Automations.

## Condition Registry — ✅

Condition definitions are centralized, preventing duplicated Condition knowledge across Engine, Manager, and UI.

## Order Total Condition — ✅

`order_total` is currently the main Condition and supports equality and greater/less-than comparisons.

## Action Registry — ✅

Actions are registered centrally and resolved through the Action Engine.

## Multiple Actions — ✅

An Automation can contain multiple sequential Actions. Each Action has an independent result, while the Automation has an overall result.

## Multiple Action Reordering — ✅

The UI allows Action order to change because execution order can affect WooCommerce state.

## Action-Level Conflict Warning — ✅

The UI warns about repeated and sequential order-status changes inside one Automation.

## Multiple Automation Matching — ✅

The engine can find multiple active Automations for the same Trigger.

## Execution Policy — ✅ Tested

```text
ALL
FIRST_MATCH
FIRST_SUCCESS
```

All three have been tested with real orders.

## Execution History — ✅ Tested

Executions have persistent records, filters, summaries, and detail pages.

## Historical Snapshots — ✅ Tested

Past Executions retain the Conditions and Actions that existed at execution time.

## Execution Timing — ✅ Tested

Measured execution timing is used instead of inferred gaps between unrelated logger events.

## Notification Settings — ✅

WooSmart can store a dedicated notification recipient and fall back to WordPress `admin_email` when appropriate.

## Real Email Delivery — ✅ Tested

Real test mail successfully passed through the configured WordPress Mail Transport in development.

## Provider-Neutral Mail Diagnostics — ✅

WooSmart captures useful `wp_mail_failed` / `WP_Error` data without coupling the core to one provider.

## Currency-Aware UI — ✅

`IRT` is displayed as `تومان` in WooSmart's UI without numeric currency conversion.

## Priority — 🟡 In Progress

Priority UI and persistence exist, while end-to-end priority-based runtime ordering is still being validated with real orders.

## Cross-Automation Conflict Engine — 🟡 Next Major Stage

The current Action-level warnings are not yet the final overlap-aware Conflict Engine.

---

# 9. Current Trigger / Condition / Action Inventory

## Triggers

Implemented:

```text
[x] order_created
```

Planned:

```text
[ ] order_paid
[ ] order_status_changed
[ ] order_completed
[ ] order_cancelled
[ ] order_failed
[ ] order_refunded
[ ] order_on_hold
[ ] customer_registered
[ ] customer_login
[ ] customer_role_changed
[ ] product_created
[ ] product_updated
[ ] stock_changed
[ ] becomes_in_stock
[ ] becomes_out_of_stock
[ ] checkout_started
[ ] checkout_completed
[ ] abandoned_cart
```

## Conditions

Implemented:

```text
[x] order_total
[x] is_equal
[x] is_not_equal
[x] greater_than
[x] greater_than_or_equal
[x] less_than
[x] less_than_or_equal
```

Planned order Conditions:

```text
[ ] order_subtotal
[ ] order_status
[ ] payment_method
[ ] shipping_method
[ ] coupon
[ ] customer
[ ] billing_country
[ ] shipping_country
[ ] item_count
[ ] product
[ ] product_category
[ ] product_quantity
```

Planned customer Conditions:

```text
[ ] customer_role
[ ] customer_email
[ ] customer_order_count
[ ] customer_total_spent
[ ] customer_registration_date
```

Planned product Conditions:

```text
[ ] product_price
[ ] stock_quantity
[ ] stock_status
[ ] category
[ ] sku
[ ] product_type
```

## Actions

Implemented:

```text
[x] change_order_status
[x] notify_admin
```

Planned order Actions:

```text
[ ] add_order_note
[ ] modify_order_metadata
[ ] apply_coupon
[ ] modify_order_items
[ ] add_product_to_order
[ ] remove_product_from_order
```

Planned notifications / integrations:

```text
[ ] customer_email
[ ] additional_admin_email
[ ] SMS
[ ] WhatsApp
[ ] Telegram
[ ] push_notification
[ ] webhook
[ ] HTTP request
[ ] REST API
[ ] Slack
[ ] Discord
[ ] Google Sheets
[ ] CRM integrations
```

---

# 10. Current Execution Semantics

For one Automation:

```text
Trigger
    ↓
Evaluate Conditions
    ↓
If Conditions pass
    ↓
Execute Actions sequentially
    ↓
Determine Automation result
```

All current Conditions must pass.

Important behavior:

> **Automation failure does not automatically roll back previously successful Actions.**

Example:

```text
Action 1 → status changed ✓
Action 2 → status changed ✓
Action 3 → email failed ✕
```

The Automation becomes failed, but earlier successful changes remain.

This is intentional for the current MVP. Generic rollback is not safe because WooCommerce state changes and external side effects are not universally reversible.

Future Retry / Rollback / Transaction features must explicitly account for this.

---

# 11. Execution Policy

The current MVP supports:

## `all`

All matching Automations may execute.

## `first_match`

The first Automation whose Conditions pass wins and later Automations are not evaluated further.

## `first_success`

The engine continues through matching Automations until one completes successfully.

Important future rule:

> Execution Policy and Priority must work together. Database query order must never remain the permanent definition of business priority.

---

# 12. Execution History

Execution History is intentionally different from the technical Logger.

```text
Logger
    = technical diagnosis

Execution History
    = customer-readable execution truth
```

A detail page should explain:

```text
Execution #N
Automation #N
Order #N
Trigger
Policy
Result
Duration

Conditions
✓ / ✕

Actions
1. Action → result → duration
2. Action → result → duration
3. Action → result → duration
```

Historical records must remain stable even when the source Automation changes later.

---

# 13. Execution Timing Requirement

The correct timing model is:

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

The displayed duration must be measured from Execution timestamps, not inferred from unrelated logs.

Per-Action durations should remain available for diagnosis.

This requirement exists because an earlier implementation showed misleadingly long history durations even though the actual execution was much shorter.

---

# 14. Conflict Model

The project deliberately separates two Conflict layers.

## A. Action-Level Conflict — Current

Current examples:

```text
multiple_order_status_changes
sequential_order_status_transitions
duplicate_order_status_target
```

These warnings are advisory and non-blocking.

## B. Cross-Automation Conflict Engine — Future

Future analysis must consider:

```text
Trigger compatibility
Condition overlap
Action target
Action effect
Priority
Execution Policy
```

Example:

```text
Automation A
    IF Total > 500,000
    THEN Status = Processing

Automation B
    IF Total < 10,000,000
    THEN Status = Completed
```

For 2,000,000 both Conditions are true and both Actions target the same property.

Target severity:

```text
🟢 Safe
🟡 Potential Conflict
🔴 Conflict
```

The system should explain the conflict and must never silently modify another Automation.

---

# 15. Execution Priority

Priority is the explicit ordering mechanism being introduced.

Rule:

```text
Lower number = higher priority = earlier evaluation
```

Example:

```text
Priority 1
    ↓
Automation A

Priority 10
    ↓
Automation B

Priority 20
    ↓
Automation C
```

Equal Priority must have a deterministic secondary ordering rule, preferably creation date / stable ID.

Current state:

```text
Priority UI / storage        🟡 Implemented / being validated
Runtime Priority ordering    🟡 In validation
Production-stable Priority   🔴 Not yet declared complete
```

---

# 16. Condition Range / Between

A high-value next Condition feature is a first-class numeric range operator:

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

This should later help both UX and Conflict Detection.

---

# 17. Multiple Conditions

Current:

```text
All configured conditions must pass.
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

Target:

```text
AND
OR
Nested Groups
Optional Negation
Human-readable summaries
```

The data model should be designed before implementation to avoid destructive future migrations.

---

# 18. Customer-Focused Product Features

The product must eventually feel easy enough for a non-technical store owner while remaining powerful enough for agencies and advanced WooCommerce users.

## Quick Start

```text
Choose Trigger
    ↓
Choose Condition
    ↓
Choose Action
    ↓
Preview
    ↓
Activate
```

## Safe Activation

Before activation show:

```text
Trigger
Conditions
Actions
Priority
Execution Policy
Possible conflicts
Expected effects
```

## Preview / Dry Run

Allow previewing a rule against an existing order without modifying it.

Example:

```text
Condition: ✓ Pass
Action 1: Would execute
Action 2: Would execute
Conflict: None
```

## Human-Readable Summary

Generate a natural-language explanation of the Automation configuration.

## Automation Templates

Potential built-in recipes:

```text
سفارش‌های گران
سفارش پرداخت‌نشده
سفارش ویژه
اعلان سفارش جدید
هشدار موجودی کم
VIP customer workflow
Failed payment alert
Refund notification
```

## Import / Export

Future Automation JSON export/import for:

```text
Backups
Agency migration
Staging → Production
Template packs
Support diagnostics
```

Imports must validate compatibility and never blindly activate unsafe configurations.

## Execution Trace

Eventually, a user should be able to open an order and understand its automation history:

```text
Order #42

Automation Trace

#40
Condition: Passed
Action: Status → Completed

#35
Condition: Passed
Action: Status → Processing

Final Status: Processing
Reason: #35 executed later under the selected execution plan.
```

---

# 19. Future Reliability Features

## Retry

Potential model:

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

Potential configuration:

```text
Maximum retries
Retry delay
Exponential backoff
Failure notification
```

## Scheduling / Delayed Actions

Examples:

```text
Wait 1 hour
Wait 24 hours
Execute at specific time
Execute after a condition remains true
```

Likely future infrastructure:

```text
WP-Cron / Action Scheduler / Background Queue
```

The exact implementation should be chosen when delayed execution becomes an active milestone.

## Safe Failure

The user should clearly distinguish:

```text
Condition failed
```

from:

```text
Condition passed
Action 1 succeeded
Action 2 failed
```

The current MVP already preserves this distinction and future features must retain it.

---

# 20. Future Platform Architecture

Long-term target:

```text
Trigger Registry
       ↓
Condition Registry
       ↓
Conflict Engine
       ↓
Execution Planner
       ↓
Priority
       ↓
Execution Policy
       ↓
Action Registry
       ↓
Execution
       ↓
Execution History
       ↓
Trace / Monitoring
```

Potential background infrastructure:

```text
Execution Queue
Scheduled Jobs
Retry Queue
Failure Queue
```

Potential future dedicated tables:

```text
wp_woosmart_automations
wp_woosmart_executions
wp_woosmart_logs
wp_woosmart_jobs
```

The current MVP intentionally uses WordPress Custom Post Type + Post Meta + WordPress Options where appropriate. Dedicated tables are a future scale decision, not an immediate requirement.

---

# 21. Security / Compatibility Principles

Current security measures:

```text
Capability checks
Nonces
Input sanitization
Output escaping
Trigger validation
Condition validation
Action validation
Automation configuration validation
```

Before external requests are implemented, security work must include:

```text
REST API authentication
Webhook authentication
Credential security
External request validation
SSRF protection
Action-level permissions
Rate limiting
```

WooSmart must remain compatible with normal WooCommerce behavior.

---

# 22. Email / Notification Architecture

WooSmart uses:

```text
wp_mail()
```

It does not implement SMTP itself.

Possible site transports include:

```text
Resend
Brevo
Gmail
Microsoft 365
Amazon SES
SMTP hosting
Other WordPress-compatible mail transports
```

WooSmart manages the recipient preference. The WordPress Mail Transport controls sender and delivery.

WooSmart captures `wp_mail_failed` information for its own notifications and should preserve the original provider message whenever possible.

Provider-specific credentials and secrets must never be shown in diagnostics.

---

# 23. Current Data Model

Automation:

```text
post_type:
    woosmart_automation
```

Current metadata:

```text
_woosmart_status
_woosmart_trigger
_woosmart_conditions
_woosmart_actions
```

Priority property:

```text
_woosmart_priority
```

Execution History is stored separately and contains a historical snapshot of the relevant configuration.

Important invariant:

> Changing an Automation later must not rewrite historical Execution records.

---

# 24. Testing Philosophy

The project is developed through real WooCommerce behavior, not UI-only testing.

```text
Review current source
      ↓
Change complete file
      ↓
Replace local file
      ↓
Check PHP / activation
      ↓
Test Admin UI
      ↓
Create real WooCommerce order
      ↓
Inspect Logger
      ↓
Inspect Execution History
      ↓
Inspect Execution Detail
      ↓
Fix / retest
      ↓
Commit stable milestone
      ↓
Update README
```

A feature is not considered complete merely because its Admin UI works. Its runtime behavior must also be tested.

---

# 25. Confirmed Test Areas

```text
[x] Plugin activation
[x] WooCommerce detection
[x] Automation creation
[x] Automation editing
[x] Enable / Disable
[x] Duplicate
[x] Delete
[x] order_created trigger
[x] order_total condition
[x] Condition pass
[x] Condition failure
[x] Change Order Status
[x] Multiple Actions
[x] Action ordering
[x] Action-level results
[x] Action failure detection
[x] Automation failure detection
[x] Multiple matching Automations
[x] ALL policy
[x] FIRST_MATCH policy
[x] FIRST_SUCCESS policy
[x] Execution History
[x] Execution detail page
[x] Historical Condition snapshot
[x] Historical Action snapshot
[x] Measured Execution duration
[x] Measured Action duration
[x] Notification Settings
[x] Real wp_mail() delivery
[x] Provider-neutral mail failure diagnostics
[x] IRT → تومان presentation
[x] No independent currency conversion
[x] Action-level conflict warnings
```

Currently under validation:

```text
[~] Execution Priority
[~] Priority-based runtime ordering
[~] Cross-Automation Conflict Engine
```

---

# 26. Known Limitations

## No Generic Rollback

Successful Actions are not automatically reversed when a later Action fails.

## Cross-Automation Conflict Engine Is Not Final

Current warnings cover mainly Action-level status-transition conflicts. The broader overlap-aware engine is still future work.

## Priority Is Not Yet Production-Stable

Priority infrastructure exists but is still being validated end-to-end with real orders.

## One Current Trigger

The current Trigger system has `order_created` as its implemented Trigger.

## One Main Condition Domain

The main implemented Condition is `order_total`.

## Synchronous MVP

Scheduling, retries, background jobs, and heavy execution queues are future infrastructure.

---

# 27. Roadmap

The roadmap is intentionally ordered by architectural dependency and customer value:

```text
CURRENT
│
├── Priority stabilization
│
├── Cross-Automation Conflict Engine
│
├── Formal Execution Planning
│
├── Range / Between Conditions
│
├── Multiple Conditions
│     ├── AND
│     ├── OR
│     └── Groups
│
├── More high-value Order Conditions
│
├── More high-value Order Actions
│
├── More Triggers
│
├── Professional Automation Builder
│
├── Preview / Dry Run
│
├── Safe Activation
│
├── Execution Trace / Monitoring
│
├── Automation Templates
│
├── Import / Export
│
├── Scheduling / Delayed Actions
│
├── Retry / Failure Queue
│
├── External Integrations
│
├── Developer API
│
└── Dedicated database / scale improvements
```

The exact ordering may change after real-world testing, but architectural dependencies should be respected.

---

# 28. Commercial / Product Direction

WooSmart is intended to become a commercially useful WooCommerce product, initially suitable for the Persian WordPress market and potentially internationalized later.

Customer value should focus on:

```text
Less manual work
Fewer repetitive tasks
Safer automation
Clear explanations
Predictable execution
Easy configuration
Useful templates
Good diagnostics
```

Potential premium-value areas:

- Advanced Conditions.
- Multiple Condition Groups.
- Advanced Execution Planning.
- Template / Recipe Library.
- Dry Run and Preview.
- Execution Trace.
- Scheduling.
- Retry Rules.
- Webhooks.
- SMS / Telegram / WhatsApp integrations.
- Import / Export.
- Agency / staging workflows.
- Developer API.
- Advanced reporting.

The product must not become a random feature collection. Every major feature should solve a real store-owner problem.

---

# 29. Product UX Principles

### Simple by default

A beginner should be able to create a useful automation without understanding the architecture.

### Powerful when needed

Advanced users should be able to use Conditions, Policy, Priority, Groups, Templates, Trace, and integrations.

### Explain before execution

The user should know what the Automation is expected to do.

### Explain after execution

The user should know what actually happened.

### Never silently overwrite intent

Conflicts should be visible and understandable.

### Preserve historical truth

Old Executions must remain accurate even after an Automation is edited.

### Keep WooCommerce as the source of truth

Do not create unnecessary parallel systems for currency, order state, or other WooCommerce-owned concepts.

### Keep providers replaceable

Mail and future external integrations must not hard-code one vendor into the core engine.

---

# 30. Important Architectural Decisions

## WooCommerce controls currency

WooCommerce remains the monetary source of truth.

```text
IRT → تومان
IRR → ریال
```

These are display decisions, not hidden conversions.

## No independent currency conversion

WooSmart must never silently multiply or divide money values because of a display label.

## SMTP is not the core

WooSmart uses `wp_mail()` and the active WordPress Mail Transport.

## Recipient and sender are separate concerns

WooSmart owns the notification recipient setting. The WordPress Mail Transport owns sender and delivery.

## Registries are the source of truth

Conditions and Actions should be added through Registries rather than duplicated across classes.

## Execution must become deterministic

The system must explicitly decide:

```text
Which Automation runs first?
Which Actions run?
Does execution continue?
Does execution stop?
Is there a conflict?
Which result is authoritative?
```

## Historical Executions are immutable snapshots

Execution details describe what happened at execution time.

## Conflicts must not silently rewrite configuration

WooSmart must never silently disable, modify, or delete another Automation because of a detected conflict.

---

# 31. Development Rules

1. Treat GitHub as the source of truth.
2. Review the current file before modifying it.
3. Do not assume an older file version is still current.
4. Preserve existing working functionality unless a change is intentional.
5. When a project file changes, provide the complete file for replacement.
6. Test real WooCommerce behavior after major changes.
7. Inspect both technical Logger and Execution History when runtime behavior changes.
8. Commit stable milestones.
9. Update README after meaningful milestones.
10. Keep postponed ideas documented.
11. Do not implement distant architecture prematurely when the current milestone is not stable.
12. Keep Persian UI terminology consistent.
13. Keep internal identifiers in English.
14. Record the root cause of important runtime bugs before unrelated architecture changes.
15. Keep WooSmart independent from specific email providers.
16. Never introduce a second currency system when WooCommerce already supplies the required context.
17. Do not silently convert Rial/Toman values.
18. Do not use incidental database ordering as the permanent execution-order mechanism.
19. Do not silently modify another Automation because of a conflict.
20. Preserve historical Execution snapshots.
21. Prefer product clarity over unnecessary technical complexity.
22. Every major feature should have a customer-facing reason, not only an engineering reason.

---

# 32. Handoff Context for a New AI / Developer

This section is the compact project context that should be read first when continuing WooSmart in another conversation or with another AI.

## What are we building?

A WooCommerce automation engine based on:

```text
WHEN → IF → THEN
```

with a Persian RTL UI and a long-term goal of becoming a professional, commercially useful automation platform.

## Where did we start?

A simple WooCommerce order automation MVP:

```text
order_created
    ↓
order_total
    ↓
Action
    ↓
Log
```

## What has been built?

```text
Foundation
   ↓
Condition Registry
   ↓
Condition Engine
   ↓
Action Registry
   ↓
Action Engine
   ↓
Multiple Actions
   ↓
Multiple matching Automations
   ↓
Execution Policy
   ↓
Execution History
   ↓
Historical snapshots
   ↓
Measured timing
   ↓
Action-level conflict warnings
   ↓
Priority / Execution Planning
```

## What is confirmed working?

```text
Automation CRUD
order_created
order_total
Comparison operators
change_order_status
notify_admin
Multiple Actions
Multiple matching Automations
ALL
FIRST_MATCH
FIRST_SUCCESS
Execution History
Execution Details
Historical snapshots
Measured duration
Notification Settings
Real wp_mail() delivery in development
Provider-neutral mail diagnostics
IRT / تومان display handling
Action-level conflict warnings
```

## What is not complete?

```text
Cross-Automation Conflict Engine
Priority runtime validation
Formal Execution Planning
Multiple Conditions
Condition Groups
Range / Between
Professional Builder
Scheduling
Retry Queue
External Integrations
Developer API
```

## What is the next task?

```text
1. Validate Priority with real orders
2. Build broader Conflict Detection
3. Formalize Execution Planning
4. Add Between / Range
5. Add Multiple Conditions
6. Expand useful Conditions / Actions / Triggers
7. Build a professional, customer-friendly Builder
```

## What should another AI preserve?

- Do not undo working architecture without evidence.
- Review current files before modifying them.
- Treat GitHub as the source of truth.
- Provide complete changed files for local replacement.
- Test real WooCommerce orders.
- Preserve historical Execution snapshots.
- Keep Execution timing measured and accurate.
- Keep WooCommerce as currency/state source of truth.
- Keep mail transport provider-neutral.
- Do not silently change another Automation because of conflicts.
- Do not confuse technical Logger entries with customer-facing Execution History.
- Optimize for a product a store owner can actually understand and buy.

---

# 33. Final Product Architecture

Long-term target:

```text
                    WooCommerce
                         ↓
                   Trigger System
                         ↓
                Candidate Automations
                         ↓
                Condition Evaluation
                         ↓
                 Conflict Detection
                         ↓
                 Execution Planning
                         ↓
                      Priority
                         ↓
                 Execution Policy
                         ↓
                  Action Registry
                         ↓
                    Action Engine
                         ↓
                   Action Results
                     ↙         ↘
          Execution History   Logger
                     ↓
                Execution Trace
                     ↓
             Monitoring / Reports
```

The central promise is:

> **When something happens, WooSmart understands the rules, executes the right actions safely, and clearly explains the result.**

That is the product we are building.
