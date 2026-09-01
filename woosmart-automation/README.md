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
**Stage:** MVP / Execution Reliability → Execution Planning

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
    AND
    Payment Method = Bank Transfer

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

Example:

```text
Automation #91
    Order Total > 1,600,000

Automation #77
    Order Total between 1,700,000 and 7,000,000
```

An order may satisfy both, creating the need for explicit execution ordering and policy behavior.

This exposed the following engine questions:

```text
Which Automation runs first?
Should another Automation also run?
What happens if an Action fails?
What happens if two Automations modify the same WooCommerce state?
```

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

If an Automation later changes from:

```text
Order Total < 5,000,000
```

to:

```text
Order Total < 3,000,000
```

the old Execution must still show the original configuration.

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

This is only the first layer of conflict analysis.

## Phase 13 — Order Total Range / Between

The `order_total` Condition now supports a dedicated `between` operator.

Target structure:

```text
operator: between
min: 1700000
max: 7000000
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
2. Original database date order as the primary stable tie-breaker
3. Automation ID as the final deterministic tie-breaker when necessary
```

Automations without an explicit Priority are placed after explicit priorities while preserving their original newest-to-oldest order.

The comparator follows the normal `usort()` contract and does not rely on accidental database ordering as the permanent business rule.

Real multi-Automation tests have shown Priority information in the execution scan, for example:

```text
Automation #91 → Priority 10
Automation #77 → Priority 20
```

The remaining validation requirement is dedicated same-Priority testing at the final production-hardening stage.

## Phase 15 — WooCommerce Deferred Transactional Emails

Performance testing exposed that synchronous WooCommerce transactional email delivery could dominate the duration of an order-status transition.

Before Deferred Emails were enabled, the transition path could take several seconds and, in one test, more than 30 seconds.

After enabling WooCommerce Deferred Emails, WooCommerce changed from the blocking callback:

```text
WC_Emails::send_transactional_email
```

to the queued callback:

```text
WC_Emails::queue_transactional_email
```

A real checkout test then measured approximately:

```text
pending → processing
≈ 106 ms
```

The transactional email is now queued through Action Scheduler instead of blocking the checkout request.

Recommended development / MVP configuration:

```text
WooCommerce transactional emails     ✅ Enabled
WooCommerce Deferred Emails          ✅ Enabled
WordPress Mail Transport / SMTP     ✅ Enabled
WooSmart Automation                 ✅ Enabled
```

WooSmart does not remove or replace WooCommerce email callbacks to achieve this performance improvement.

## Phase 16 — Action Engine Cleanup After Performance Diagnosis

Temporary callback-level status-hook profiling was used during diagnosis of the slow checkout behavior.

After the root cause was identified, those heavy diagnostic wrappers were removed from the clean MVP Action Engine.

The current Action Engine remains responsible for:

```text
Action dispatch
Sequential execution
Fail-fast behavior
Per-Action result collection
Action duration measurement
Mail failure capture
Order-status execution
Order-status duplicate protection
```

A same-status request is now treated as a successful skip rather than causing an unnecessary WooCommerce status transition.

Example verified behavior:

```text
Current status = processing
Requested status = processing
        ↓
Action skipped
        ↓
No duplicate status transition
        ↓
No unnecessary transition hook chain
```

---

# 4. Current Architecture

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

Current execution flow:

```text
Trigger
    ↓
Candidate Automations
    ↓
Priority / deterministic ordering
    ↓
Condition Evaluation
    ↓
Execution Policy
    ↓
Action Execution
    ↓
Action Results
    ↓
Execution History
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

Long-term execution-planning architecture:

```text
Trigger
     ↓
Candidate Automations
     ↓
Condition Evaluation
     ↓
Conflict Detection
     ↓
Priority
     ↓
Execution Policy
     ↓
Action Registry
     ↓
Action Engine
     ↓
Execution History
     ↓
Trace / Monitoring
```

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
    └── class-woosmart-priority-admin.php
```

`class-woosmart-condition-admin.php` is intentionally not part of the current MVP and must not be reintroduced unless the product direction explicitly changes.

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
| `class-woosmart-action-registry.php` | Central Action definitions and handlers |
| `class-woosmart-action-engine.php` | Action execution, result collection, mail failure capture |
| `class-woosmart-execution-engine.php` | Runtime orchestration, deterministic ordering, and Execution Policy |
| `class-woosmart-execution-history.php` | Historical Execution records and snapshots |
| `class-woosmart-execution-admin.php` | Execution History, Detail, and Policy UI |
| `class-woosmart-priority-admin.php` | Priority UI and Priority persistence |

---

# 7. Current Features — Status

```text
Plugin Foundation                         ✅ Complete
WooCommerce Detection                     ✅ Complete
Automation CRUD                           ✅ Complete
Persian RTL Admin                         ✅ Complete
Currency-Aware Display                   ✅ Complete
Condition Registry                        ✅ Complete
Order Total Condition                    ✅ Complete
Order Total Range / Between              ✅ Tested
Range Execution Detail                  ✅ Tested
Action Registry                           ✅ Complete
Change Order Status                       ✅ Complete
Notify Admin                              ✅ Tested
Multiple Actions                          ✅ Tested
Action Ordering                           ✅ Complete
Same-Status Duplicate Protection          ✅ Tested
Action-Level Conflict Warnings            ✅ Complete / MVP
Multiple Automation Matching              ✅ Tested
Execution Policy                          ✅ Tested
FIRST_SUCCESS behavior                    ✅ Tested
Execution History                         ✅ Tested
Execution Detail                          ✅ Tested
Historical Condition Snapshot             ✅ Tested
Historical Action Snapshot                ✅ Tested
Measured Execution Duration               ✅ Tested
Measured Action Duration                  ✅ Tested
Notification Settings                     ✅ Complete
Provider-Neutral Mail Diagnostics         ✅ Complete
Real wp_mail() Delivery                   ✅ Tested
WooCommerce Deferred Transactional Email  ✅ Enabled / Verified
Priority Storage / UI                     ✅ Complete
Priority Runtime Ordering                 ✅ Tested
Priority Same-Value Tie-Breaker           🟡 Final targeted validation
Cross-Automation Conflict Engine          🟡 Planned
Multiple Conditions                       🔴 Planned
Condition Groups (AND / OR)              🔴 Planned
More Conditions                           🔴 Planned
More Actions                              🔴 Planned
More Triggers                             🔴 Planned
Scheduling / Delayed Actions              🔴 Planned
Retry / Failure Queue                     🔴 Planned
External Integrations                     🔴 Planned
Automated Regression Suite                🟡 Required for release hardening
Marketplace / Production Hardening        🟡 Required before commercial release
```

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

Range values are stored structurally as `min` / `max`.

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

All configured Conditions in the current model must pass.

Action execution is currently **fail-fast**:

```text
Action 1 ✓
Action 2 ✕
Action 3
   ↓
NOT EXECUTED
```

Important behavior:

> **Automation failure does not automatically roll back previously successful Actions.**

Example:

```text
Action 1 → status changed ✓
Action 2 → status changed ✓
Action 3 → email failed ✕
```

The Automation becomes failed, but earlier successful changes remain.

Generic rollback is intentionally not implemented because WooCommerce state changes and external side effects are not universally reversible.

When an Action requests the order status that is already active, the Action is treated as a successful skip and does not create a duplicate status transition.

---

# 10. Execution Policy

The current MVP supports:

## `all`

All matching Automations may execute.

## `first_match`

The first Automation whose Conditions pass wins and later Automations are not evaluated further.

## `first_success`

The engine continues through matching Automations until one completes successfully.

Priority and Policy are separate concepts:

```text
Priority
    = which matching Automation is considered first

Execution Policy
    = what happens after matching Automations are evaluated
```

The permanent business rule must never be accidental database query order.

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

Range Conditions must also remain historically accurate, including their structured `min` and `max` values.

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

The displayed duration must be measured from Execution timestamps, not inferred from unrelated Logger timestamps.

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

# 14. Execution Priority

Priority is the explicit ordering mechanism.

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

Current implementation guarantees deterministic ordering through:

```text
Explicit Priority ASC
→ original fetched order as the stable secondary ordering
→ Automation ID as the final deterministic tie-breaker
```

Automations without explicit Priority are assigned normalized fallback priorities after explicit priorities while preserving the original newest-to-oldest order.

Same-Priority ordering has not yet been declared production-final until the dedicated edge-case test is completed.

---

# 15. WooCommerce Email Performance Requirement

The development/MVP environment should use WooCommerce Deferred Transactional Emails.

Recommended configuration:

```text
WooCommerce transactional emails = enabled
Deferred Transactional Emails     = enabled
WordPress Mail Transport         = configured
WooSmart                         = enabled
```

This keeps WooCommerce transactional email delivery asynchronous through Action Scheduler where supported and prevents unnecessary mail transport latency from blocking the checkout request.

WooSmart must not suppress WooCommerce email callbacks as a workaround.

---

# 16. Email / Notification Architecture

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

WooSmart manages the notification recipient. The WordPress Mail Transport controls sender and delivery.

WooSmart captures `wp_mail_failed` information for its own notifications and should preserve useful provider error information whenever possible.

Provider-specific credentials and secrets must never be shown in diagnostics.

---

# 17. Current Data Model

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

Priority metadata:

```text
_woosmart_priority
```

Range Conditions are stored as structured data, for example:

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

Execution History is stored separately and contains historical snapshots of the relevant configuration and results.

Important invariant:

> Changing an Automation later must not rewrite historical Execution records.

---

# 18. Testing Philosophy

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

Performance investigations must measure the correct layer. When a WooCommerce transition is slow, inspect the downstream Hook and callback chain before changing the automation orchestration itself.

---

# 19. Confirmed Test Areas

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
[x] Order Total between / Range
[x] Range boundary behavior
[x] Range Execution Detail rendering
[x] Change Order Status
[x] Same-status duplicate protection
[x] Multiple Actions
[x] Action ordering
[x] Action-level results
[x] Action failure detection
[x] Fail-fast Action chain
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
[x] WooCommerce Deferred Transactional Emails
[x] IRT → تومان presentation
[x] No independent currency conversion
[x] Action-level conflict warnings
[x] Priority persistence
[x] Priority-based runtime ordering
```

Currently under validation:

```text
[~] Same-Priority edge-case validation
[~] Cross-Automation Conflict Engine
[~] Production-hardening regression suite
[~] Marketplace compatibility / release hardening
```

---

# 20. Known Limitations

## No Generic Rollback

Successful Actions are not automatically reversed when a later Action fails.

## Current Condition Model Is Still Simple

`order_total` is the main implemented Condition family. Multiple Condition groups and complex logical composition are not yet part of the current MVP.

## Cross-Automation Conflict Engine Is Not Final

Current warnings mainly cover Action-level status-transition conflicts. The broader overlap-aware Conflict Engine is future work.

## Same-Priority Edge Cases Need Final Validation

Priority is implemented and real ordering has been observed, but same-Priority deterministic behavior still deserves a dedicated regression test before being declared fully production-stable.

## One Current Trigger

The implemented Trigger is `order_created`.

## Synchronous Action Engine

The Action Engine itself executes synchronously inside the current request. WooCommerce transactional emails can be deferred through WooCommerce's own Action Scheduler integration, but general WooSmart background execution is future infrastructure.

## External Integrations Not Implemented

SMS, Telegram, WhatsApp, webhooks, HTTP requests, REST integrations, Slack, Discord, Google Sheets, and CRM integrations remain future work.

## Production Hardening Not Finished

A commercial release still requires a dedicated security, compatibility, upgrade, uninstall, and automated regression pass.

---

# 21. Roadmap

The roadmap is ordered by architectural dependency and customer value:

```text
CURRENT
│
├── Same-Priority Priority validation
│
├── Cross-Automation Conflict Engine
│
├── Formal Execution Planning
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
├── Automated Regression Suite
│
└── Production / Marketplace Hardening
```

The exact ordering may change after real-world testing, but architectural dependencies should be respected.

---

# 22. Customer-Focused Product Features

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

## Human-Readable Summary

Generate a readable explanation of the Automation configuration.

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

Future Automation JSON export/import should support backups, agency migration, staging → production workflows, template packs, and support diagnostics.

Imports must validate compatibility and must never blindly activate unsafe configurations.

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
Reason: execution order followed the selected Priority and Execution Policy.
```

---

# 23. Future Reliability Features

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

The current MVP already preserves this distinction.

---

# 24. Future Platform Architecture

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

# 25. Security / Compatibility Principles

Current security foundation:

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

Before external requests are implemented, security work must additionally include:

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

HTTPS, secure production configuration, upgrade/migration safety, and uninstall behavior must be verified before public commercial release.

---

# 26. Important Architectural Decisions

## WooCommerce Controls Currency

WooCommerce remains the monetary source of truth.

```text
IRT → تومان
IRR → ریال
```

These are display decisions, not hidden conversions.

## No Independent Currency Conversion

WooSmart must never silently multiply or divide money values because of a display label.

## SMTP Is Not the Core

WooSmart uses `wp_mail()` and the active WordPress Mail Transport.

## Recipient and Sender Are Separate Concerns

WooSmart owns the notification recipient setting. The WordPress Mail Transport owns sender and delivery.

## Registries Are the Source of Truth

Conditions and Actions should be added through Registries rather than duplicated across classes.

## Execution Must Be Deterministic

The system must explicitly decide:

```text
Which Automation runs first?
Which Actions run?
Does execution continue?
Does execution stop?
Is there a conflict?
Which result is authoritative?
```

## Historical Executions Are Immutable Snapshots

Execution details describe what happened at execution time.

## Conflicts Must Not Silently Rewrite Configuration

WooSmart must never silently disable, modify, or delete another Automation because of a detected conflict.

## WooCommerce Deferred Email Infrastructure Is Preferred for WooCommerce Transactional Email Work

When WooCommerce provides an asynchronous email mechanism through Action Scheduler, WooSmart should cooperate with that mechanism rather than suppressing WooCommerce Hooks.

---

# 27. Development Rules

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
23. Do not reintroduce temporary performance diagnostics into the clean MVP without a specific debugging need.
24. Prefer WooCommerce-supported asynchronous infrastructure over custom Hook suppression when solving WooCommerce email-performance issues.
25. Do not mark a feature complete because the Admin UI alone works.
26. Before marketplace release, perform a dedicated security, compatibility, upgrade, uninstall, and regression pass.

---

# 28. Handoff Context for a New AI / Developer

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
Range / Between
   ↓
Priority ordering
   ↓
WooCommerce Deferred Email performance optimization
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
Action ordering
Fail-fast Action behavior
Same-status duplicate protection
Multiple matching Automations
ALL
FIRST_MATCH
FIRST_SUCCESS
Execution History
Execution Details
Historical snapshots
Measured execution duration
Measured Action duration
Notification Settings
Real wp_mail() delivery in development
Provider-neutral mail diagnostics
WooCommerce Deferred Transactional Emails
IRT / تومان display handling
Action-level conflict warnings
Priority persistence
Priority-based runtime ordering
```

## What is not complete?

```text
Same-Priority final regression validation
Cross-Automation Conflict Engine
Formal Execution Planning
Multiple Conditions
Condition Groups
More Conditions
More Actions
More Triggers
Professional Builder
Preview / Dry Run
Safe Activation analysis
Execution Trace / Monitoring
Scheduling
Retry Queue
External Integrations
Developer API
Automated Regression Suite
Marketplace / Production Hardening
```

## What should another AI preserve?

- Do not undo working architecture without evidence.
- Review current files before modifying them.
- Treat GitHub as the source of truth.
- Provide complete changed files for local replacement.
- Test real WooCommerce orders.
- Preserve historical Execution snapshots.
- Keep Execution timing measured and accurate.
- Keep WooCommerce as currency and order-state source of truth.
- Keep mail transport provider-neutral.
- Do not silently change another Automation because of conflicts.
- Do not confuse technical Logger entries with customer-facing Execution History.
- Do not reintroduce diagnostic complexity after the root cause has been isolated.
- Prefer WooCommerce-supported asynchronous infrastructure for WooCommerce transactional email performance.
- Optimize for a product a store owner can actually understand and buy.

---

# 29. Final Product Architecture

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
