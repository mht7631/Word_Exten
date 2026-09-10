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
**Stage:** MVP / Execution Reliability, Conflict Detection, Execution Planning, Multiple Conditions & Condition Groups → Next: Expanded Conditions / Actions / Triggers  

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

An order may satisfy multiple Automations, creating the need for explicit execution ordering and policy behavior.

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
Condition evaluation snapshot
Action snapshot
Action results
Message
```

Each execution has a human-readable detail page.

## Phase 10 — Historical Snapshot Integrity

Historical executions must describe the Automation **as it existed at execution time**.

If an Automation later changes, the old Execution must still show its original configuration.

The same rule applies to Actions and Conditions.

Execution History therefore stores historical snapshots rather than reading the current Automation configuration when an old execution is viewed.

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

## Phase 13 — Order Total Range / Between

The `order_total` Condition supports a dedicated `between` operator.

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

Range behavior is inclusive.

Real WooCommerce testing confirmed the complete Range path, including Condition evaluation, Action execution, Execution History, and administrator notification.

## Phase 14 — Deterministic Priority Ordering

Priority is an explicit execution-order mechanism, not another Condition system.

Rule:

```text
Lower number = higher priority = earlier evaluation
```

Current runtime ordering is:

```text
1. Explicit Priority ASC
2. Newest-to-oldest source order for equal Priority
3. Stable Automation ID tie-breaker if necessary
```

Automations without an explicit Priority are placed after explicit priorities while preserving source order.

A dedicated same-Priority regression test has been completed.

## Phase 15 — WooCommerce Deferred Transactional Emails

Performance testing showed that synchronous WooCommerce transactional email delivery could dominate an order-status transition.

Deferred Transactional Emails were enabled so WooCommerce can queue transactional email work through Action Scheduler instead of blocking the transaction path.

Recommended development/MVP configuration:

```text
WooCommerce transactional emails     ✅ Enabled
WooCommerce Deferred Emails          ✅ Enabled
WordPress Mail Transport / SMTP      ✅ Enabled
WooSmart Automation                 ✅ Enabled
```

WooSmart does not remove WooCommerce email callbacks to achieve this behavior.

## Phase 16 — Cross-Automation Conflict Detection

The project progressed from Action-level conflict warnings to a dedicated cross-Automation Conflict Detector.

Current detected patterns include:

```text
overlapping_automation_conditions
duplicate_cross_automation_status_target
cross_automation_status_transition
```

The Conflict Detector is advisory and non-blocking. It explains potential conflicts and exposes Priority information, but it never silently disables, modifies, or deletes another Automation.

## Phase 17 — Formal Execution Planning

Execution planning was formalized so the runtime builds a plan before Actions create side effects.

Current flow:

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

Planning and runtime evaluation are separate stages. Planning does not execute Actions or duplicate runtime Condition logs.

The formal plan records matching, Priority, Conditions, Actions, and execution decisions before side effects occur.

## Phase 18 — Currency-Aware Notification Placeholder

Notification placeholders were aligned with the WooSmart Currency layer.

The `{order_total}` placeholder uses the same display formatter as the Currency helper rather than hardcoding `تومان` inside the Action Engine.

Current display behavior:

```text
IRT → تومان
IRR → ریال
Other currencies → WooCommerce currency symbol
```

No monetary conversion is performed.

Real notification testing confirmed successful delivery with the formatted order amount and current store display unit.

## Phase 19 — Multiple Conditions with AND

The Condition model was extended from one active Condition per Automation to an ordered list of Conditions while preserving backward compatibility with the existing stored Condition format.

Current AND semantics:

```text
Condition 1
    AND
Condition 2
    AND
Condition 3
```

The UI supports adding, deleting, editing, and reordering Conditions.

Real WooCommerce testing confirmed positive and negative multi-Condition behavior.

## Phase 20 — Condition Groups / OR

The Condition model was extended to support explicit grouped boolean logic without changing the meaning of existing flat Conditions.

Grouped structure:

```php
array(
    'version' => 1,
    'groups' => array(
        array(
            'conditions' => array(
                array(
                    'field'    => 'order_total',
                    'operator' => 'greater_than',
                    'value'    => '1000000',
                ),
                array(
                    'field'    => 'order_total',
                    'operator' => 'less_than',
                    'value'    => '5000000',
                ),
            ),
        ),
        array(
            'conditions' => array(
                array(
                    'field'    => 'order_total',
                    'operator' => 'greater_than',
                    'value'    => '10000000',
                ),
                array(
                    'field'    => 'order_total',
                    'operator' => 'less_than',
                    'value'    => '20000000',
                ),
            ),
        ),
    ),
)
```

Semantics are:

```text
Conditions inside one group = AND
Groups = OR
```

Therefore the current grouped model represents:

```text
(A AND B) OR (C AND D)
```

Legacy flat Conditions remain strict AND and do not require a forced migration.

The current MVP intentionally does not yet implement arbitrary nested groups or full boolean-expression syntax.

## Phase 21 — Detailed Condition Evaluation History

Execution History was extended with a dedicated `condition_evaluation_json` snapshot and database version `1.3.0`.

The stored evaluation mirrors the actual Condition Engine result:

```text
condition_evaluation
├── matched
├── format
├── conditions
└── groups
    ├── group 1
    │   ├── matched
    │   └── conditions
    │       ├── condition 1 → passed
    │       └── condition 2 → passed
    └── group 2
        ├── matched
        └── conditions
            ├── condition 1 → passed
            └── condition 2 → passed
```

This data is stored separately from the historical Condition configuration snapshot.

`conditions_failed` executions now also create a persistent Execution History record instead of disappearing at the planning stage.

The History UI distinguishes between:

```text
✓ موفق
✕ ناموفق
ارزیابی نشد
نتیجه ثبت نشده
```

`ارزیابی نشد` is used when a Condition or Group was intentionally skipped because of logical short-circuiting. `نتیجه ثبت نشده` remains available for genuinely missing result data, especially Action results when an Action never ran.

## Phase 22 — Condition Groups Real-Order Validation

Automation `#138` — `تست OR گروهی` — was used for real WooCommerce validation.

Configuration:

```text
Group 1
    Order Total > 1,000,000
    AND
    Order Total < 5,000,000

OR

Group 2
    Order Total > 10,000,000
    AND
    Order Total < 20,000,000
```

Real tests confirmed:

```text
Order #144 → Group 1 succeeds
Order #145 → Group 1 fails, Group 2 succeeds
Order #146 → both Groups fail
```

Expected logical outcomes were reflected in Execution History, including per-Group and per-Condition results.

The tests also confirmed short-circuit behavior:

```text
Successful Group 1 in an OR expression
    ↓
Later Group is not evaluated
    ↓
UI shows: ارزیابی نشد
```

For a failed AND Group, once an earlier Condition fails, later Conditions in that same Group may remain unevaluated and are shown as `ارزیابی نشد` rather than being treated as failed.

The `conditions_failed` test confirmed that the complete Condition evaluation snapshot is available in logs and that a persistent History record is created without executing Actions.

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
Legacy Conditions / Condition Groups
     ↓
AND / OR Evaluation
     ↓
Detailed Condition Evaluation
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
Planning Condition Evaluation
     ↓
Conflict Detection
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
Results
```

Conflict Detection is advisory and does not silently change Automation configuration.

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

Planned Admin UI decomposition is intentionally deferred. See the dedicated refactor process in Section 30.

---

# 6. File Responsibilities

| File | Responsibility |
| --- | --- |
| `woosmart-automation.php` | Plugin bootstrap, shared services, initialization, activation/deactivation |
| `class-woosmart-core.php` | WooCommerce dependency and compatibility foundation |
| `class-woosmart-logger.php` | Technical event logging |
| `class-woosmart-currency.php` | WooCommerce-aware display-only currency handling |
| `class-woosmart-admin.php` | Main Persian RTL Admin UI and current Automation Builder facade |
| `class-woosmart-notification-settings.php` | Notification recipient configuration and test email |
| `class-woosmart-automation.php` | Automation foundation |
| `class-woosmart-triggers.php` | WooCommerce Trigger integration |
| `class-woosmart-post-types.php` | Internal Automation post type |
| `class-woosmart-automation-manager.php` | CRUD, validation, persistence |
| `class-woosmart-condition-registry.php` | Central Condition definitions |
| `class-woosmart-condition-engine.php` | Condition evaluation, including legacy AND and grouped AND/OR evaluation |
| `class-woosmart-action-registry.php` | Central Action definitions / handlers |
| `class-woosmart-action-engine.php` | Action execution and results |
| `class-woosmart-execution-engine.php` | Runtime orchestration, Conflict Detection, Priority, Execution Policy, Formal Execution Planning, and History handling |
| `class-woosmart-execution-history.php` | Persistent Execution records, historical snapshots, and detailed Condition evaluation snapshots |
| `class-woosmart-execution-admin.php` | Execution History / Policy UI and Condition/Action result presentation |
| `class-woosmart-priority-admin.php` | Priority UI and Priority persistence |
| `class-woosmart-conflict-detector.php` | Cross-Automation conflict analysis and advisory UI |

---

# 7. Current Features — Status and Meaning

## Plugin Foundation — ✅

The plugin can be activated in WordPress, detect WooCommerce, register its Automation type, and initialize the runtime.

## Automation CRUD — ✅

Administrators can create, edit, enable/disable, duplicate, and delete Automations.

## Condition Registry — ✅

Condition definitions are centralized.

## Order Total Condition — ✅

`order_total` supports scalar comparison operators and inclusive `between` range handling.

## Action Registry — ✅

Actions are registered centrally and resolved through the Action Engine.

## Multiple Actions — ✅

An Automation can contain multiple sequential Actions with independent Action results.

## Multiple Action Reordering — ✅

The UI allows Action order to change.

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

## Execution History — ✅ Tested

Executions have persistent records, filters, summaries, and detail pages.

## Historical Snapshots — ✅ Tested

Past Executions retain the Conditions and Actions that existed at execution time.

## Execution Timing — ✅ Tested

Measured execution timing is used instead of inferred gaps between unrelated logger events.

## Execution Detail Range Display — ✅ Tested

Structured `between` values are displayed as a readable Persian range.

## Multiple Conditions with AND — ✅ Tested

Multiple ordered Conditions are supported with explicit AND semantics while retaining compatibility with legacy flat configurations.

## Condition Groups / OR — ✅ Tested

Condition Groups are supported with:

```text
Conditions within Group → AND
Groups → OR
```

Current grouped model supports `(A AND B) OR (C AND D)`-style logic.

## Detailed Condition Evaluation History — ✅ Tested

Execution History persists the actual Condition Engine evaluation separately from the historical configuration snapshot.

## `conditions_failed` History — ✅ Tested

An Automation that fails during planning now creates a persistent History record with its Condition evaluation snapshot and without executing Actions.

## Short-Circuit Result Presentation — ✅ Tested

Intentionally unevaluated Conditions are displayed as `ارزیابی نشد`.

## Execution Detail Condition Results — ✅ Tested

Grouped and legacy executions display actual Condition outcomes where available.

## Notification Settings — ✅

WooSmart can store a dedicated notification recipient and fall back to WordPress `admin_email` when appropriate.

## Real Email Delivery — ✅ Tested

Real test mail successfully passed through the configured WordPress Mail Transport in development.

## Provider-Neutral Mail Diagnostics — ✅

WooSmart captures useful `wp_mail_failed` / `WP_Error` information without coupling the core to one provider.

## Currency-Aware UI — ✅

`IRT` is displayed as `تومان` in WooSmart's UI without numeric conversion.

## Currency-Aware Notification Placeholder — ✅ Tested

`{order_total}` uses the WooSmart Currency formatter.

## Repeated Status Protection — ✅ Tested

Redundant status transitions are skipped.

## Deferred WooCommerce Emails — ✅ Verified

WooCommerce transactional emails remain enabled while Deferred Emails move their work to Action Scheduler.

## Priority — ✅ Tested

Priority is implemented in runtime ordering, including deterministic same-Priority tie handling.

## Cross-Automation Conflict Engine — ✅ MVP / Tested

The dedicated Conflict Detector identifies supported cross-Automation overlap and status-transition conflicts. Findings are advisory and non-blocking.

## Formal Execution Planning — ✅ Tested

The runtime builds a formal Execution Plan before Actions create side effects.

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
[x] multiple conditions with AND
[x] condition groups with AND inside groups and OR between groups
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
Condition logic matches
    ↓
Execute Actions sequentially
    ↓
Determine Automation result
```

Legacy Conditions:

```text
A AND B AND C
```

Grouped Conditions:

```text
(A AND B) OR (C AND D)
```

Conditions inside a Group use AND. Groups use OR.

The Condition Engine uses short-circuit evaluation where applicable:

```text
AND:
    first failed Condition can stop the current Group

OR:
    first matched Group can stop later Group evaluation
```

Intentionally skipped evaluation is not treated as a false result.

Important behavior:

> **Automation failure does not automatically roll back previously successful Actions.**

Generic rollback is not safe because WooCommerce state changes and external side effects are not universally reversible.

---

# 10. Execution Policy

The current MVP supports:

## `all`

All matching Automations may execute.

## `first_match`

The first Automation whose Conditions pass wins and later Automations are not executed.

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
Conditions / Groups
✓ / ✕ / ارزیابی نشد
Actions
1. Action → result → duration
2. Action → result → duration
```

Detailed Condition evaluation is stored separately from the historical configuration snapshot.

For grouped Conditions, the History page displays the actual result of each evaluated Group and Condition.

For Conditions intentionally skipped through short-circuiting, the UI shows:

```text
ارزیابی نشد
```

Historical records must remain stable even when the source Automation changes later.

`conditions_failed` Executions are persistent History records. Actions are not executed in that status.

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

Per-Action durations remain available for diagnosis.

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

The dedicated Conflict Detector provides advisory cross-Automation analysis.

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

The current implementation does not block execution or silently modify another Automation.

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

Automations without an explicit Priority are assigned a runtime fallback after all explicit priorities while retaining source order.

Priority does not decide whether an Automation matches. It decides ordering among candidate Automations.

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

This is part of the working MVP.

---

# 16. Multiple Conditions and Condition Groups

## Legacy / flat model

Existing Automations may continue using a flat list:

```text
A AND B AND C
```

No forced migration is required.

## Grouped model

New or edited Automations may use:

```text
Group 1
    A AND B

OR

Group 2
    C AND D
```

This represents:

```text
(A AND B) OR (C AND D)
```

The grouped model is explicitly versioned and currently uses:

```text
version = 1
```

The Condition Engine returns a detailed evaluation object containing:

```text
matched
format
conditions
groups
```

This result is used by Execution History without changing the existing boolean-compatible `evaluate()` API.

Current limitations intentionally remain:

```text
No arbitrary nested groups yet
No general boolean expression parser
No NOT / negation operator yet
```

Future extensions must preserve the meaning of existing flat AND Automations.

---

# 17. Customer-Focused Product Features

The product must eventually feel easy enough for a non-technical store owner while remaining powerful enough for agencies and advanced WooCommerce users.

## Quick Start

```text
Choose Trigger
    ↓
Choose Condition / Group
    ↓
Add Conditions as needed
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
Conditions / Groups
Actions
Priority
Execution Policy
Possible conflicts
Expected effects
```

## Preview / Dry Run

Allow previewing a rule against an existing order without modifying it.

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

WooCommerce Deferred Emails already use Action Scheduler for transactional mail delivery. WooSmart's generalized delayed Automation execution remains future work.

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

WooCommerce transactional emails should remain enabled. Deferred Transactional Emails should be enabled when avoiding checkout latency is important and supported by the installed WooCommerce version.

WooSmart captures useful `wp_mail_failed` / `WP_Error` information for its own notifications without exposing provider credentials.

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

`_woosmart_conditions` supports both:

```text
Legacy flat condition list

and

Versioned grouped condition structure
```

Execution History table:

```text
wp_woosmart_executions
```

Important History fields include:

```text
condition_result
condition_evaluation_json
conditions_json
actions_json
action_results_json
context_json
```

Execution History DB version for detailed Condition evaluation is:

```text
1.3.0
```

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

For Condition Groups specifically, both positive and negative real-order paths must be tested.

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
[x] Multiple Conditions — 2-condition positive execution
[x] Multiple Conditions — negative execution
[x] Multiple Conditions — 3-condition persistence and reordering
[x] Multiple Conditions — between combination
[x] Condition Groups — AND inside Group
[x] Condition Groups — OR between Groups
[x] Condition Groups — Group 1 real-order success
[x] Condition Groups — Group 2 real-order success
[x] Condition Groups — complete failure path
[x] Condition Groups — short-circuit behavior
[x] Detailed Condition evaluation persistence
[x] `conditions_failed` Execution History persistence
[x] Execution History grouped Condition-result display
[x] Short-circuit `ارزیابی نشد` presentation
```

Current next development stage:

```text
[ ] More high-value Order Conditions
[ ] More high-value Order Actions
[ ] More Triggers
[ ] Nested Groups / Negation design
[ ] Professional Automation Builder improvements
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

The main implemented Condition is `order_total`, including scalar comparison operators and `between`. Multiple Conditions and Condition Groups are supported for this domain.

## Grouping Is Version 1 / Current MVP

Current grouped logic supports:

```text
Conditions in Group = AND
Groups = OR
```

Nested groups, NOT / negation, and arbitrary boolean expressions are not yet implemented.

## Generalized Scheduling Is Not Implemented

WooCommerce Deferred Emails use Action Scheduler, but WooSmart's own generalized delayed Automation execution is future infrastructure.

## Large Admin Class Is Still Monolithic

`class-woosmart-admin.php` remains a large facade containing multiple Admin responsibilities. A controlled decomposition process is documented in Section 30 and is intentionally deferred until the current runtime/condition milestones are stable.

---

# 26. Roadmap

The roadmap is intentionally ordered by architectural dependency and customer value:

```text
CURRENT
│
├── More high-value Order Conditions
│
├── More high-value Order Actions
│
├── More Triggers
│
├── Professional Automation Builder improvements
│
├── Nested Groups / Negation
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
✅ Multiple Conditions with AND semantics
✅ Multiple Conditions real-order validation
✅ Condition Groups with AND inside Groups / OR between Groups
✅ Condition Groups real-order validation
✅ Detailed Condition Evaluation History
✅ `conditions_failed` Execution History persistence
✅ Short-circuit result presentation
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
- Condition Groups / advanced boolean logic.
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

Advanced users should be able to use Conditions, Groups, Policy, Priority, Templates, Trace, and integrations.

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

### Distinguish failed from not evaluated

A short-circuited Condition is not a failed Condition. The UI must preserve that distinction.

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

## Multiple Conditions use explicit boolean semantics

Legacy flat conditions retain AND semantics. Grouped Conditions explicitly introduce AND inside Groups and OR between Groups. Future boolean extensions must not silently change existing Automation meaning.

## Detailed condition evaluation is separate from configuration snapshots

`conditions_json` records what Conditions existed at execution time. `condition_evaluation_json` records what the Condition Engine actually evaluated and the result of that evaluation.

## Short-circuiting is a first-class runtime outcome

A skipped Condition is not equivalent to a failed Condition. History must preserve the distinction.

---

# 30. Deferred Refactor Process — `class-woosmart-admin.php`

`class-woosmart-admin.php` is currently a large monolithic Admin class. This is a known architecture issue, but **it must not be refactored casually** while core runtime milestones are still being stabilized.

The intended future structure is:

```text
includes/
├── class-woosmart-admin.php
└── admin/
    ├── class-woosmart-admin-dashboard.php
    ├── class-woosmart-admin-automations.php
    ├── class-woosmart-admin-automation-form.php
    ├── class-woosmart-admin-logs.php
    └── class-woosmart-admin-renderer.php

assets/
├── css/
│   └── woosmart-admin.css
└── js/
    ├── woosmart-conditions.js
    ├── woosmart-condition-groups.js
    └── woosmart-actions.js
```

## Refactor rules

1. **Do not start the refactor in the middle of another unstable milestone.** First finish the current feature, test it, and commit it.
2. **Review the exact current GitHub version before every extraction.** Do not work from an old local copy.
3. **Keep `class-woosmart-admin.php` as a compatibility facade initially.** Existing bootstrap and public hooks should continue to work.
4. **Extract one responsibility at a time.** Do not split the entire class in one large rewrite.
5. **Suggested extraction order:**
   ```text
   Dashboard
       ↓
   Automation list / CRUD UI
       ↓
   Automation Builder / Form
       ↓
   Logs
       ↓
   Shared rendering helpers
   ```
6. **No behavior change during extraction.** The goal of the first refactor passes is organization, not new features.
7. **Preserve method contracts and hook names whenever practical.** Any intentional contract change must be documented before implementation.
8. **Keep shared service instances controlled by the main bootstrap/facade.** Do not create duplicate Condition, Action, Execution, Logger, or Manager services unnecessarily.
9. **Move JavaScript only after PHP responsibilities are stable.** Condition, Group, and Action scripts should be separated without changing their current behavior.
10. **Run `php -l` on every extracted PHP file.**
11. **After each extraction, test at minimum:** plugin activation, Automation list, Add/Edit Automation, Save, Enable/Disable, Duplicate, Delete, Conditions, Condition Groups, Actions, Logs, and Execution History.
12. **Use real WooCommerce orders after each meaningful extraction.** Admin rendering changes can break runtime assumptions, field names, or saved metadata even when PHP syntax is valid.
13. **Do not delete the original implementation until the extracted replacement is proven equivalent.** Remove duplicated code only in a later cleanup step.
14. **Keep complete-file replacement workflow.** Every changed project file must be delivered in complete form for local replacement.
15. **Commit after each stable extraction milestone.** Avoid one giant refactor commit that is difficult to diagnose or revert.
16. **Update this README after each completed extraction.** Record what moved, what remained, and which tests passed.
17. **Never combine the Admin refactor with a new runtime feature unless there is a compelling dependency.** This keeps regressions attributable.

## Refactor acceptance criteria

The refactor is complete only when:

```text
Same external Admin behavior
Same saved metadata
Same hooks / actions
Same Automation CRUD behavior
Same Condition behavior
Same Condition Group behavior
Same Action behavior
Same Logs behavior
Same Execution History behavior
No PHP syntax errors
No activation errors
No new runtime regressions
```

This process is deliberately documented here so the future refactor is treated as a controlled engineering milestone rather than an opportunistic rewrite.

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
23. Temporary diagnostics must be removed after the root cause of a production-path issue is established, unless diagnostics are needed again for a new investigation.
24. When solving WooCommerce email-performance problems, prefer WooCommerce-supported asynchronous mechanisms over custom Hook suppression.
25. Formal Execution Planning must remain separate from Action side effects.
26. Conflict detection must remain advisory unless a future explicit blocking policy is designed and documented.
27. Adding Conditions must preserve backward compatibility with existing single-condition Automations.
28. Existing flat AND semantics must not be silently changed when future boolean features are introduced.
29. Short-circuited Conditions must be represented as not evaluated, not as failed.
30. Detailed Condition evaluation must remain distinct from the historical Condition configuration snapshot.
31. `conditions_failed` must remain a first-class Execution History status when planning rejects an Automation.
32. Do not refactor the large Admin class casually; follow the controlled extraction process in Section 30.
33. Do not combine a large Admin refactor with unrelated runtime feature work unless a dependency requires it.
34. After every stable feature milestone, record the actual tested state in README.

---

# 32. Handoff Context for a New AI / Developer

This section is the compact project context that should be read first when continuing WooSmart in another conversation or with another AI.

## What are we building?

A WooCommerce automation engine based on:

```text
WHEN → IF → THEN
```

with a Persian RTL UI and a long-term goal of becoming a professional, commercially useful automation platform.

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
Deferred WooCommerce transactional email verification
   ↓
Currency-aware Notification Placeholder
   ↓
Multiple Conditions with AND semantics
   ↓
Condition Groups with AND/OR semantics
   ↓
Detailed Condition Evaluation History
   ↓
`conditions_failed` Execution History persistence
   ↓
Short-circuit result presentation
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
Multiple Conditions with AND semantics
Multiple Condition persistence / reordering
Multiple Condition positive and negative runtime behavior
Condition Groups
AND inside Groups
OR between Groups
Group 1 real-order success
Group 2 real-order success
Complete Group failure path
Condition Engine detailed evaluation
Execution History detailed Condition evaluation
`conditions_failed` History persistence
Short-circuit `ارزیابی نشد` display
```

## What is not complete?

```text
Nested Groups
Negation / NOT
More high-value Conditions / Actions / Triggers
Professional Builder improvements
Preview / Dry Run
Safe Activation
Execution Trace / Monitoring
Scheduling
Retry Queue
External Integrations
Developer API
Controlled refactor of class-woosmart-admin.php
```

## What is the next development focus?

```text
1. Expand high-value Order Conditions
2. Expand high-value Order Actions
3. Add additional Triggers based on customer value
4. Improve the Automation Builder
5. Design Nested Groups / Negation only after the current grouped model is stable
6. Extend Conflict Detection and Execution Planning as the Condition / Action model grows
7. Perform the documented Admin-class refactor as a separate controlled milestone
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
- Preserve deterministic Priority and Execution Planning behavior.
- Maintain backward compatibility while expanding the Condition model.
- Preserve legacy flat AND semantics.
- Preserve grouped AND-inside / OR-between semantics.
- Do not treat short-circuited Conditions as failed.
- Preserve `condition_evaluation_json` separately from `conditions_json`.
- Preserve `conditions_failed` Execution History records.
- Do not collapse the current grouped model into an implicit boolean-expression parser prematurely.
- Do not refactor `class-woosmart-admin.php` without following the controlled extraction process documented above.
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
                    ↙           ↘
          Execution History    Logger
                    ↓
               Execution Trace
                    ↓
              Monitoring / Reports
```

Current MVP implements Condition Registry, Condition Engine, grouped AND/OR evaluation for its supported semantics, Conflict Detection, Execution Planning, Priority, Execution Policies, Action Registry, Action Engine, Execution History, and detailed Condition evaluation persistence/presentation.

The remaining roadmap expands Condition depth, Actions, Triggers, UX, and scale while preserving backward compatibility.

The central promise is:

> **When something happens, WooSmart understands the rules, executes the right actions safely, and clearly explains the result.**

That is the product we are building.
