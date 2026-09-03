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
**Stage:** MVP / Execution Reliability, Conflict Detection & Execution Planning → Multiple Conditions Design  

Repository:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the source of truth. Local XAMPP is the development and real WooCommerce test environment.

---

# 2. Product Vision

WooSmart is intended to make powerful WooCommerce automation feel simple:

```text
WHEN
    Order Created

IF
    Order Total is between 1,700,000 and 7,000,000 تومان

THEN
    Change Order Status → Processing
    AND
    Notify Store Administrator
```

Long-term product qualities:

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

The product must explain both configuration and runtime behavior without exposing unnecessary implementation details to normal store users.

---

# 3. Project History — From Start to Current State

## Phase 1 — Foundation

WooSmart started as a small WooCommerce automation MVP using `WHEN → IF → THEN`.

The first runtime path was:

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

The foundation includes plugin bootstrap, WooCommerce detection, the internal Automation post type, Persian RTL Admin UI, Automation CRUD, the current WooCommerce Trigger, Condition Engine, Actions, and technical logging.

## Phase 2 — Condition Registry

Conditions were moved into a central **Condition Registry** so definitions are not duplicated across the Engine, Manager, and Admin UI.

A Condition definition provides metadata such as:

```text
label
value_type
operators
evaluator
```

Current primary Condition:

```text
order_total
```

Current scalar operators:

```text
is_equal
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

The current MVP does not reintroduce a separate `class-woosmart-condition-admin.php`.

## Phase 3 — Currency-Aware WooCommerce UI

The development store uses WooCommerce `IRT`, while the Persian UI displays `تومان`.

WooCommerce remains the monetary source of truth.

WooSmart does not implement an independent Rial/Toman conversion system and never silently multiplies or divides money values because of a display label.

```text
IRT → تومان
IRR → ریال
```

The amount field uses thousands separators, correct LTR numeric rendering inside RTL, and a separate currency label.

## Phase 4 — Action Registry

Actions were moved into a central **Action Registry** so the Execution Engine does not contain scattered Action knowledge.

Current Actions:

```text
change_order_status
notify_admin
```

The Registry resolves Action definitions and handlers.

## Phase 5 — Multiple Actions

The product evolved from a single-action rule into a real workflow model.

```text
THEN
    Action 1
    AND
    Action 2
    AND
    Action 3
```

Multiple Actions are supported, ordered, executed sequentially, and recorded independently in Execution History.

A real tested workflow included:

```text
1. Change status → Processing
2. Change status → Completed
3. Notify administrator
```

## Phase 6 — Real Email Delivery and Provider-Neutral Diagnostics

The notification architecture is deliberately provider-neutral:

```text
WooSmart
    ↓
wp_mail()
    ↓
WordPress Mail Transport
    ↓
SMTP / Email Provider
```

WooSmart controls the notification recipient. The active WordPress Mail Transport controls sender and delivery.

Real notification delivery was successfully tested in development. WooSmart also captures `wp_mail_failed` / `WP_Error` information during its own notification attempts and preserves useful provider error information where available.

Provider-specific credentials and secrets must never be exposed in WooSmart diagnostics.

## Phase 7 — Multiple Matching Automations

Real WooCommerce orders proved that more than one Automation can match the same Trigger.

Example:

```text
Automation #91
    Order Total > 1,600,000

Automation #77
    Order Total between 1,700,000 and 7,000,000
```

An order may satisfy both, creating the need for explicit execution ordering and policy behavior.

## Phase 8 — Execution Policy

Three execution policies are implemented and tested:

```text
all
first_match
first_success
```

### `all`

All matching Automations may execute.

### `first_match`

Execution stops after the first Automation whose Conditions pass.

### `first_success`

Execution continues until an Automation completes successfully. A matching Automation whose Actions fail does not stop the policy.

A real test demonstrated the important distinction:

```text
Automation #68
Condition ✓
Action 1 ✓
Action 2 ✓
Action 3 ✕
→ Automation FAILED

Automation #43
Condition ✓
Action 1 ✓
Action 2 ✓
→ Automation SUCCESS
→ FIRST_SUCCESS stops
```

Therefore `first_success` is based on the complete Automation result, not merely on a successful Condition.

## Phase 9 — Execution History

The technical Logger is not enough for a store owner, so a separate Execution History layer was introduced.

```text
Logger
    = technical diagnosis

Execution History
    = customer-readable execution truth
```

Execution records include:

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

Each execution has a human-readable detail page.

## Phase 10 — Historical Snapshot Integrity

Historical executions must describe the Automation **as it existed at execution time**.

If an Automation later changes, the old Execution must still show its original configuration.

The same rule applies to Actions.

Execution History therefore stores historical snapshots rather than reading the current Automation configuration when an old execution is viewed.

The Execution Engine also contains MVP protection for stale legacy Condition data.

## Phase 11 — Execution Timing

The first Execution History timing model could show inflated durations because unrelated Logger timestamp gaps were being interpreted as execution time.

The model was corrected so:

```text
Execution duration
    = measured execution start → measured execution end
```

Each Action also has an individual measured duration.

Permanent requirement:

> **Displayed duration must represent measured execution time.**

## Phase 12 — Action-Level Conflict Warnings

Multiple Actions exposed potentially unsafe configuration such as:

```text
Action 1 → Processing
Action 2 → Completed
```

The current MVP warns about repeated or sequential order-status changes inside one Automation without blocking execution.

The warning explains possible WooCommerce hooks and side effects.

This is the first layer of conflict analysis.

## Phase 13 — Order Total Range / Between

The `order_total` Condition now supports a dedicated `between` operator.

Stored structure:

```php
array(
    'field'    => 'order_total',
    'operator' => 'between',
    'value'    => array(
        'min' => '1700000',
        'max' => '7000000',
    ),
)
```

Range behavior is inclusive:

```text
1,699,999 → fail
1,700,000 → pass
2,000,000 → pass
7,000,000 → pass
7,000,001 → fail
```

Real WooCommerce testing confirmed the complete Range path, including successful Condition evaluation, Action execution, Execution History, and administrator notification.

Execution Detail correctly renders structured `min` / `max` values as a readable Persian range.

## Phase 14 — Deterministic Priority Ordering

Priority is an explicit execution-order mechanism, not another Condition system.

Rule:

```text
Lower number = higher priority = earlier evaluation
```

Current runtime ordering is:

```text
1. Explicit Priority ASC
2. Original date order as the primary stable tie-breaker
3. Automation ID as the final deterministic tie-breaker when necessary
```

Automations without an explicit Priority are placed after explicit priorities while preserving their original newest-to-oldest order.

The comparator uses explicit comparison results and does not rely on accidental database ordering as the permanent business rule.

Real multi-Automation tests have shown Priority information in the execution scan, for example:

```text
Automation #91 → Priority 10
Automation #77 → Priority 20
```

A dedicated same-Priority regression test has also been completed. Three active Automations with the same explicit Priority were executed in the deterministic order generated by the runtime, and the Execution Plan preserved that same order.

## Phase 15 — WooCommerce Deferred Transactional Emails

Performance testing exposed that synchronous WooCommerce transactional email delivery could dominate the duration of an order-status transition.

Diagnostic testing identified:

```text
woocommerce_order_status_pending_to_processing
        ↓
WC_Emails::send_transactional_email
        ↓
majority of transition time
```

WooCommerce Deferred Emails were then enabled.

The transaction path changed to queued email processing:

```text
woocommerce_order_status_pending_to_processing
        ↓
WC_Emails::queue_transactional_email
        ↓
Action Scheduler
```

A real checkout test then reduced the measured `pending → processing` status transition to approximately `106 ms` while WooCommerce transactional emails remained enabled.

This is the recommended configuration for the current development/MVP environment:

```text
WooCommerce transactional emails     ✅ Enabled
WooCommerce Deferred Emails          ✅ Enabled
WordPress Mail Transport / SMTP     ✅ Enabled
WooSmart Automation                 ✅ Enabled
```

WooSmart does not remove WooCommerce email callbacks to achieve this behavior.

The temporary callback-level performance diagnostics used during investigation were removed from the clean MVP Action Engine after the root cause was confirmed.

## Phase 16 — Cross-Automation Conflict Detection

The project progressed from Action-level conflict warnings to a dedicated cross-Automation Conflict Detector.

The Conflict Detector evaluates active Automations that share the same Trigger and identifies supported overlap/conflict patterns without changing their configuration.

Current detected patterns include:

```text
overlapping_automation_conditions
duplicate_cross_automation_status_target
cross_automation_status_transition
```

The Conflict Detector is advisory and non-blocking. It explains potential conflicts and exposes Priority information, but it never silently disables, modifies, or deletes another Automation.

Real testing confirmed the detector can identify multiple warnings between active Automations, including condition overlap, shared status targets, and overlapping status transitions.

## Phase 17 — Formal Execution Planning

Execution planning was formalized so the runtime builds a plan before Actions create side effects.

The current flow is:

```text
Trigger
    ↓
Candidate Automations
    ↓
Planning Condition Evaluation
    ↓
Priority Ordering
    ↓
Execution Policy
    ↓
Formal Execution Plan
    ↓
Runtime Condition Evaluation
    ↓
Action Execution
    ↓
Execution Results
```

Planning Condition evaluation does not create duplicate `condition_passed` runtime logs. The selected Automation evaluates its Conditions again when actual execution begins.

Real order testing confirmed that the Execution Plan records the matching Automations, their Priority, Conditions, Actions, and execution decision before side effects occur.

Current architectural note:

> The current planner evaluates Conditions before Actions begin. Future Conditions that depend on mutable runtime state may require additional planning semantics so a planned match cannot become stale before Action execution.

## Phase 18 — Currency-Aware Notification Placeholder

Notification placeholders were aligned with the WooSmart Currency layer.

The `{order_total}` placeholder now uses the same display formatter as the Currency helper rather than hardcoding `تومان` inside the Action Engine.

Current display behavior remains:

```text
IRT → تومان
IRR → ریال
Other currencies → WooCommerce currency symbol
```

No monetary conversion is performed.

A real notification test confirmed successful delivery with the formatted order amount and current store display unit.

---

# 4. Current Architecture

Core runtime:

```text
WooCommerce
     ↓
Trigger System
     ↓
Candidate Automations
     ↓
Conflict Detection
     ↓
Execution Planning
     ↓
Priority
     ↓
Execution Policy
     ↓
Condition Evaluation
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

Current execution planning:

```text
Trigger
     ↓
Candidate Automations
     ↓
Condition Evaluation
     ↓
Conflict Detection
     ↓
Priority Ordering
     ↓
Execution Policy
     ↓
Formal Execution Plan
     ↓
Action Execution
     ↓
Results
```

Cross-Automation Conflict Detection is advisory and does not silently change Automation configuration.

Shared Condition, Action, and Execution services are used to avoid unnecessary duplicate engine instances.

---

# 5. Current File Structure

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
    ├── class-woosmart-priority-admin.php
    └── class-woosmart-conflict-detector.php
```

`class-woosmart-condition-admin.php` is intentionally not part of the current MVP.

---

# 6. File Responsibilities

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
| `class-woosmart-execution-engine.php` | Runtime orchestration, Conflict Detection integration, Priority, Execution Policy, and Formal Execution Planning |
| `class-woosmart-execution-history.php` | Historical Execution records and snapshots |
| `class-woosmart-execution-admin.php` | Execution History / Policy UI |
| `class-woosmart-priority-admin.php` | Priority UI and Priority persistence |
| `class-woosmart-conflict-detector.php` | Cross-Automation conflict analysis and advisory UI |

---

# 7. Current Features — Status and Meaning

## Plugin Foundation — ✅

The plugin can be activated in WordPress, detect WooCommerce, register its Automation type, and initialize the runtime.

## Automation CRUD — ✅

Administrators can create, edit, enable/disable, duplicate, and delete Automations.

## Condition Registry — ✅

Condition definitions are centralized, preventing duplicated Condition knowledge across Engine, Manager, and UI.

## Order Total Condition — ✅

`order_total` is the main implemented Condition.

Scalar operators:

```text
is_equal
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

Range operator:

```text
between
```

Range is inclusive and has been tested with real WooCommerce orders.

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

## Execution Detail Range Display — ✅ Tested

Structured `between` values are displayed as a readable min/max Persian range rather than as a scalar value.

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

## Currency-Aware Notification Placeholder — ✅ Tested

The `{order_total}` Notification Placeholder uses the WooSmart Currency formatter and therefore follows the current WooCommerce display unit without monetary conversion.

## Repeated Status Protection — ✅ Tested

When an Action requests the status that the order already has, WooSmart skips the redundant transition and therefore avoids unnecessary WooCommerce transition hooks.

## Deferred WooCommerce Emails — ✅ Verified

WooCommerce transactional emails remain enabled while Deferred Emails move their work to Action Scheduler so checkout is not blocked by synchronous transactional email delivery.

## Priority — ✅ Tested

Priority is implemented in the runtime and reflected in the Execution scan. Different-Priority and Same-Priority deterministic ordering have been tested with real Automations.

## Cross-Automation Conflict Engine — ✅ MVP / Tested

The dedicated Conflict Detector identifies supported cross-Automation overlap and status-transition conflicts. Findings are advisory and non-blocking.

## Formal Execution Planning — ✅ Tested

The runtime builds a formal Execution Plan before Actions create side effects and preserves the planned Automation order through actual execution.

---

# 8. Current Trigger / Condition / Action Inventory

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
[x] between
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

# 9. Current Execution Semantics

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

---

# 10. Execution Policy

The current MVP supports:

## `all`

All matching Automations may execute.

## `first_match`

The first Automation whose Conditions pass wins and later Automations are not evaluated further.

## `first_success`

The engine continues through matching Automations until one completes successfully.

Execution Policy and Priority are separate concerns:

```text
Priority
    = ordering

Execution Policy
    = stopping / continuation behavior
```

---

# 11. Execution History

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

# 12. Execution Timing Requirement

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

---

# 13. Conflict Model

The project deliberately separates two Conflict layers.

## A. Action-Level Conflict — Current

Current examples:

```text
multiple_order_status_changes
sequential_order_status_transitions
duplicate_order_status_target
```

These warnings are advisory and non-blocking.

## B. Cross-Automation Conflict Engine — Current MVP

The dedicated Conflict Detector now provides advisory cross-Automation analysis.

Current analysis considers:

```text
Trigger compatibility
Condition overlap
Action target
Action effect
Priority
Execution Policy
```

Current detected patterns include:

```text
overlapping_automation_conditions
duplicate_cross_automation_status_target
cross_automation_status_transition
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

For 2,000,000 both Conditions are true and both Actions target the same order state.

Target severity model:

```text
🟢 Safe
🟡 Potential Conflict
🔴 Conflict
```

The current implementation uses advisory warnings and does not block execution or silently modify another Automation.

Future conflict coverage can expand as additional Conditions and Actions are implemented.

---

# 14. Execution Priority

Priority is the explicit ordering mechanism.

Rule:

```text
Lower number = higher priority = earlier evaluation
```

Current runtime ordering:

```text
1. Explicit Priority ASC
2. Newest-to-oldest source order for equal Priority
3. Stable Automation ID tie-breaker if necessary
```

Automations without an explicit Priority are assigned a runtime fallback after all explicit priorities while retaining the source newest-to-oldest order.

Priority does not decide whether an Automation matches. It decides evaluation order after candidate Automations have been collected.

Current state:

```text
Priority storage / UI              ✅
Priority runtime ordering         ✅
Deterministic tie-break behavior  ✅
Same-Priority dedicated test      ✅
```

---

# 15. Condition Range / Between

Implemented numeric range for `order_total`:

```text
مبلغ سفارش
بین
1,700,000
تا
7,000,000 تومان
```

Stored model:

```text
operator: between
min: 1700000
max: 7000000
```

The comparison is inclusive.

This is already part of the working MVP and must not be treated as future functionality.

---

# 16. Multiple Conditions

Current MVP:

```text
The current product path is intentionally simple.
```

Next development stage:

```text
Design a backward-compatible Multiple Conditions model
without breaking the current single-condition data structure.
```

Target first level:

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

Backward compatibility with the current stored Condition structure is a requirement for this stage.

---

# 17. Customer-Focused Product Features

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

Eventually, a user should be able to open an order and understand its automation history.

---

# 18. Future Reliability Features

## Retry

Potential future model:

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

The current performance solution already uses WooCommerce Deferred Emails + Action Scheduler for transactional email delivery, but WooSmart's own generalized delayed Automation execution is still future work.

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

The current MVP preserves this distinction.

---

# 19. Future Platform Architecture

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

# 20. Security / Compatibility Principles

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

# 21. Email / Notification Architecture

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

WooCommerce transactional emails should remain enabled. On supported WooCommerce versions, Deferred Transactional Emails should be enabled when avoiding checkout latency is important. This keeps WooCommerce email behavior intact while moving mail work through Action Scheduler.

WooSmart captures `wp_mail_failed` information for its own notifications and should preserve the original provider message whenever possible.

Provider-specific credentials and secrets must never be shown in diagnostics.

Notification placeholders that contain monetary values should use the WooSmart Currency formatter rather than hardcoded currency labels.

---

# 22. Current Data Model

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
_woosmart_priority
```

Execution History is stored separately and contains a historical snapshot of the relevant configuration.

Important invariant:

> Changing an Automation later must not rewrite historical Execution records.

---

# 23. Testing Philosophy

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

# 24. Confirmed Test Areas

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
[x] order_total between / Range
[x] Range execution detail display
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
[x] Currency-aware Notification Placeholder
[x] Action-level conflict warnings
[x] Cross-Automation Conflict Detection MVP
[x] Priority persistence
[x] Priority runtime ordering
[x] Same-Priority deterministic ordering
[x] Repeated status protection
[x] WooCommerce Deferred Transactional Emails
[x] Checkout performance improvement after Deferred Emails
[x] Formal Execution Planning
```

Currently under final hardening / next development stage:

```text
[ ] Multiple Conditions backward-compatible data model
[ ] Multiple Conditions AND / OR execution
[ ] Condition Groups
```

---

# 25. Known Limitations

## No Generic Rollback

Successful Actions are not automatically reversed when a later Action fails.

## Cross-Automation Conflict Coverage Is Limited to Current MVP Semantics

The current Conflict Detector provides advisory overlap and order-status conflict analysis for the currently implemented Trigger, Conditions, and Actions. Coverage will expand as the platform gains additional Condition and Action types.

## One Current Trigger

The current Trigger system has `order_created` as its implemented Trigger.

## One Main Condition Domain

The main implemented Condition is `order_total`, including scalar comparison operators and `between`.

## Generalized Scheduling Is Not Implemented

WooCommerce Deferred Emails use Action Scheduler, but WooSmart's own generalized delayed Automation execution is future infrastructure.

## Multiple Conditions Are Not Yet Implemented

The current runtime remains centered on the existing Condition model. The next development stage is the backward-compatible Multiple Conditions data model and execution support.

---

# 26. Roadmap

The roadmap is intentionally ordered by architectural dependency and customer value:

```text
CURRENT
│
├── Multiple Conditions data model
│     ├── Backward compatibility
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

Completed architecture milestones now include:

```text
✅ Deterministic Priority Ordering
✅ Same-Priority regression validation
✅ Cross-Automation Conflict Detection MVP
✅ Formal Execution Planning
✅ Currency-aware Notification Placeholder
```

The exact ordering may change after real-world testing, but architectural dependencies should be respected.

---

# 27. Commercial / Product Direction

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

# 28. Product UX Principles

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

# 29. Important Architectural Decisions

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

## Deferred Transactional Emails are preferred over Hook suppression

When WooCommerce offers asynchronous transactional email delivery, WooSmart should preserve WooCommerce's own email behavior rather than removing email callbacks from status transitions.

## Registries are the source of truth

Conditions and Actions should be added through Registries rather than duplicated across classes.

## Execution must be deterministic

The system must explicitly decide:

```text
Which Automation runs first?
Which Actions run?
Does execution continue?
Does execution stop?
Is there a conflict?
Which result is authoritative?
```

## Execution planning must precede side effects

The runtime should build an explicit execution decision before Actions modify WooCommerce state or perform external side effects.

## Historical Executions are immutable snapshots

Execution details describe what happened at execution time.

## Conflicts must not silently rewrite configuration

WooSmart must never silently disable, modify, or delete another Automation because of a detected conflict.

---

# 30. Development Rules

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
23. Temporary diagnostics must be removed after the root cause of a production-path issue is established, unless diagnostics are needed again for a new investigation.
24. When solving WooCommerce email-performance problems, prefer WooCommerce-supported asynchronous mechanisms over custom Hook suppression.
25. Formal Execution Planning must remain separate from Action side effects.
26. Conflict detection must remain advisory unless a future explicit blocking policy is designed and documented.

---

# 31. Handoff Context for a New AI / Developer

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
Order Total Range / Between
   ↓
Priority runtime ordering
   ↓
Same-Priority deterministic ordering
   ↓
Cross-Automation Conflict Detection MVP
   ↓
Formal Execution Planning
   ↓
Deferred WooCommerce transactional emails verification
   ↓
Currency-aware Notification Placeholder
```

## What is confirmed working?

```text
Automation CRUD
order_created
order_total
Comparison operators
between / Range
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
Currency-aware Notification Placeholder
Action-level conflict warnings
Cross-Automation Conflict Detection MVP
Repeated status protection
Priority persistence
Priority runtime ordering
Same-Priority deterministic ordering
Deferred WooCommerce transactional emails
Checkout performance improvement after Deferred Emails
Formal Execution Planning
```

## What is not complete?

```text
Multiple Conditions backward-compatible data model
Multiple Conditions AND / OR execution
Condition Groups
More high-value Conditions / Actions / Triggers
Professional Builder
Preview / Dry Run
Safe Activation
Execution Trace / Monitoring
Scheduling
Retry Queue
External Integrations
Developer API
```

## What is the next task?

```text
1. Design the backward-compatible Multiple Conditions data model
2. Implement AND / multiple-condition evaluation
3. Add OR / Group structure without breaking existing Automations
4. Test Multiple Conditions with real WooCommerce orders
5. Update Execution History / conflict analysis to understand the expanded Condition model
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
- Do not reintroduce `class-woosmart-condition-admin.php` into the current MVP.
- Do not reintroduce temporary performance diagnostics into the clean Action Engine unless a new investigation requires them.
- Treat Cross-Automation Conflict Detection as advisory and non-blocking.
- Preserve deterministic Priority and Execution Planning behavior.
- Maintain backward compatibility while expanding the Condition model.
- Optimize for a product a store owner can actually understand and buy.

---

# 32. Final Product Architecture

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
                    ↙           ↘
          Execution History    Logger
                    ↓
               Execution Trace
                    ↓
              Monitoring / Reports
```

The current MVP already implements the Conflict Detection and Execution Planning layers for its supported semantics. The remaining roadmap expands their coverage as new Conditions, Actions, and Triggers are introduced.

The central promise is:

> **When something happens, WooSmart understands the rules, executes the right actions safely, and clearly explains the result.**

That is the product we are building.
