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

The project is being developed as a real commercial product, not as a collection of unrelated WooCommerce hooks. The goal is a reliable, predictable, explainable, customer-friendly automation engine that store administrators can use without programming knowledge.

The core product promise is simple:

```text
Create an automation
        ↓
Understand what it will do
        ↓
Let WooSmart execute it predictably
        ↓
Receive the intended notification/action
        ↓
See exactly what happened
```

---

# 1. Project Identity

**Product:** WooSmart Automation  
**Platform:** WordPress + WooCommerce  
**UI:** Persian / RTL  
**Version:** `1.0.0`  
**Stage:** MVP / Foundation → Execution Reliability → Product Hardening

Repository:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the project source of truth. Local XAMPP is the development and real WooCommerce test environment.

---

# 2. Mission

WooSmart started with a practical problem:

```text
When an order is created
If the order matches a rule
Then notify the store administrator
```

The mission is to turn that simple rule into a dependable automation product without making the normal store owner deal with technical complexity.

The product therefore prioritizes:

- Reliable notification delivery through WordPress mail infrastructure.
- Predictable Automation execution.
- Clear handling of multiple Actions.
- Clear handling of multiple matching Automations.
- Useful Execution History.
- Human-readable diagnostics.
- WooCommerce compatibility.
- Minimal configuration complexity.

A feature should only be added when it improves the real store workflow.

---

# 3. Product Vision

Long-term Automation Builder:

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

The normal user should understand the rule from the UI without seeing internal PHP, hook names, database metadata, or provider-specific details.

Long-term product qualities:

```text
Easy for beginners
Powerful for advanced users
Predictable in execution
Transparent after execution
Safe around WooCommerce side effects
Independent from email providers
Extensible for future Actions and Triggers
Suitable for commercial distribution
```

---

# 4. Project History — From Start to Current State

This section explains how the project evolved and why important architectural decisions were made. It is intentionally more useful than a raw changelog so that a new developer or another AI can understand the project without reconstructing the entire development history.

## Phase 1 — Foundation

The first MVP proved the basic path:

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

The foundation included plugin bootstrap, WooCommerce detection, the internal Automation post type, Persian Admin UI, Automation CRUD, one WooCommerce Trigger, a Condition Engine, Actions, and logging.

## Phase 2 — Condition Registry

Conditions were moved into a central Registry instead of being duplicated across the Engine, Manager, and Admin UI.

A Condition definition contains concepts such as:

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

## Phase 3 — WooCommerce-Aware Currency UI

The development store uses WooCommerce `IRT` and WooSmart displays this as `تومان` in the Persian UI.

WooCommerce remains the source of truth.

WooSmart does **not** perform an independent Rial/Toman conversion.

```text
IRT → تومان
IRR → ریال
```

No silent `×10` or `÷10` conversion is performed.

The amount field was improved with thousands separators, correct LTR numeric rendering inside RTL, and a separate currency label.

This is a compatibility rule, not merely a visual choice.

## Phase 4 — Action Registry

Actions were moved into a central Action Registry so that adding an Action does not require rewriting the Execution Engine.

Current Actions:

```text
change_order_status
notify_admin
```

The registry provides Action metadata and handler resolution.

## Phase 5 — Multiple Actions

The product evolved from a single-action rule into a real workflow model:

```text
THEN
    Action 1
    AND
    Action 2
    AND
    Action 3
```

The current UI and data model support multiple Actions, execution order, Action reordering, individual Action results, and an overall Automation result.

Real test workflows included:

```text
1. Change status → Processing
2. Change status → Completed
3. Notify administrator
```

## Phase 6 — Real Email Delivery

The notification architecture was deliberately kept provider-neutral:

```text
WooSmart
    ↓
wp_mail()
    ↓
WordPress Mail Transport
    ↓
SMTP / Email Provider
    ↓
Inbox
```

WooSmart controls the notification recipient through its own setting. The active WordPress Mail Transport controls the sender and delivery mechanism.

Real email delivery was successfully tested in the development environment.

A provider-side recipient restriction was also encountered during testing. WooSmart now captures `wp_mail_failed` / `WP_Error` information during its own notification attempt, classifies common failures generically, and preserves the original provider message.

## Phase 7 — Multiple Matching Automations

Real orders proved that more than one Automation can match the same Trigger.

Example:

```text
Automation A
    Order Total > 1,000,000

Automation B
    Order Total < 3,000,000
```

An order of 2,000,000 can match both.

This introduced the need to explicitly control:

```text
Which Automation runs first?
Should execution continue?
What happens if an Action fails?
What happens if two Automations modify the same WooCommerce state?
```

## Phase 8 — Execution Policy

Three execution policies were implemented and tested:

```text
all
first_match
first_success
```

### ALL

All matching Automations are allowed to execute.

### FIRST_MATCH

Execution stops after the first Automation whose Conditions match.

### FIRST_SUCCESS

Execution continues until a matching Automation completes all of its Actions successfully.

A real test proved the important distinction:

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

Therefore `FIRST_SUCCESS` is based on the real Automation result, not merely a successful Condition.

## Phase 9 — Execution History

The technical Logger was not enough for a store owner, so a separate Execution History layer was introduced.

```text
Logger
    = technical event stream

Execution History
    = human-readable execution record
```

Execution records contain concepts such as:

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

Each execution can be opened in a detail view so the administrator can understand what happened without reading raw logs.

## Phase 10 — Historical Snapshot Integrity

A critical requirement was discovered: historical executions must describe the Automation **as it existed at execution time**.

If an Automation changes later from:

```text
Order Total < 5,000,000
```

to:

```text
Order Total < 3,000,000
```

the old Execution must still show 5,000,000.

The same applies to Actions.

This behavior was tested and confirmed.

## Phase 11 — Execution Timing

The first Execution History timing model could appear to show much longer durations than the real work because unrelated log timing gaps were being interpreted as execution time.

The model was corrected so:

```text
Execution duration
    = measured execution start → measured execution end
```

Each Action can also have an individual measured duration.

Permanent requirement:

> **Displayed duration must represent measured execution time.**

## Phase 12 — Action-Level Conflict Warnings

Multiple Actions exposed a first class of potentially unsafe configuration inside a single Automation:

```text
Action 1 → Processing
Action 2 → Completed
```

WooSmart now warns when multiple order-status changes are defined in one Automation and identifies the possible WooCommerce side effects.

These warnings are intentionally non-blocking in the current MVP.

The final product may later expand conflict analysis across Automations, but it should only be added when it materially improves user safety and understanding.

## Phase 13 — Price Range Condition

The `order_total` Condition was expanded with a dedicated Range operator:

```text
between
```

The UI supports:

```text
حداقل
حداکثر
```

The stored structure is:

```php
array(
    'field'    => 'order_total',
    'operator' => 'between',
    'value'    => array(
        'min' => '1700000',
        'max' => '3000000',
    ),
)
```

Range behavior is **inclusive**:

```text
1,700,000  → match
2,000,000  → match
3,000,000  → match
```

This was verified using real WooCommerce orders.

A final real test with Order #87 confirmed the complete path:

```text
Condition:
1,700,000 ≤ Order Total ≤ 3,000,000
        ↓
Condition Passed
        ↓
3 Actions
        ↓
All Actions Successful
        ↓
Admin Email Delivered
```

Therefore **Range is currently considered implemented and end-to-end tested**.

During Range development, stale test configuration in Automation #77 caused an old `is_equal` condition to remain in the database. This was identified as test/configuration state rather than a Range evaluation defect and corrected by saving the Automation with the intended Range configuration.

---

# 5. Current Architecture

Runtime:

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
Action Results
     ├── Execution History
     └── Technical Logger
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

Current execution concepts:

```text
Trigger
    ↓
Matching Automations
    ↓
Priority / ordering information
    ↓
Conditions
    ↓
Execution Policy
    ↓
Actions
    ↓
Results
    ↓
Execution History
```

The architecture uses shared Condition, Action, and Execution services to avoid unnecessary duplicate engine instances.

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
| `woosmart-automation.php` | Plugin bootstrap, shared services, activation/deactivation |
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
| `class-woosmart-action-engine.php` | Action execution, result collection, mail diagnostics |
| `class-woosmart-execution-engine.php` | Runtime orchestration and Execution Policy |
| `class-woosmart-execution-history.php` | Historical execution records and snapshots |
| `class-woosmart-execution-admin.php` | Execution History and Policy UI |
| `class-woosmart-priority-admin.php` | Priority configuration UI / persistence |

---

# 8. Current Features

## Foundation — ✅

- Plugin bootstrap.
- Activation/deactivation.
- WooCommerce dependency detection.
- Internal Automation post type.
- Persian RTL Admin UI.

## Automation Management — ✅

- Create Automation.
- Edit Automation.
- Enable/Disable.
- Duplicate.
- Delete.
- Validation.

## Trigger — ✅

Current Trigger:

```text
order_created
```

Implemented through the WooCommerce new-order execution path.

## Conditions — ✅

Current primary Condition:

```text
order_total
```

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

Range is inclusive and tested with real orders.

Current MVP intentionally keeps Condition configuration simple. Multiple Conditions / AND-OR groups remain future functionality rather than a current complexity requirement.

## Actions — ✅

Current:

```text
change_order_status
notify_admin
```

Multiple Actions are supported and executed sequentially.

## Multiple Actions — ✅

The UI supports:

- Add Action.
- Remove Action.
- Reorder Action.
- Configure each Action.
- Observe individual Action result.

## Conflict Warnings — ✅ MVP

Current non-blocking warnings cover repeated/sequential order-status changes inside a single Automation.

## Execution Policy — ✅ Tested

```text
all
first_match
first_success
```

## Execution History — ✅ Tested

Execution list, filtering, summaries, detail pages, Action results, Conditions, timing, and historical snapshots are implemented.

## Notification Settings — ✅

WooSmart stores a dedicated notification recipient and falls back to WordPress `admin_email` when the WooSmart recipient is empty.

## Real Email Delivery — ✅ Tested

Successful real delivery was confirmed through the configured WordPress Mail Transport.

## Mail Diagnostics — ✅

`wp_mail_failed` / `WP_Error` information is captured during WooSmart notification attempts.

## Currency Display — ✅

WooCommerce `IRT` is presented as `تومان` in the current Persian UI without independent numeric conversion.

## Priority — 🟡 Present / Validation Stage

Priority metadata and Admin UI exist and execution diagnostics expose priorities. The commercial MVP should keep Priority simple and avoid turning it into a complex rule system. Its practical purpose is deterministic ordering when several Automations match the same Trigger.

---

# 9. Current End-to-End Test Evidence

The following have been verified against real WooCommerce orders in the development environment.

### Automation execution

```text
Order Created
    ↓
Trigger detected
    ↓
Condition evaluated
    ↓
Action(s) executed
    ↓
Execution logged
```

### Multiple matching Automations

Real orders have produced multiple matching Automation IDs and demonstrated the behavior of `ALL`, `FIRST_MATCH`, and `FIRST_SUCCESS`.

### Failed Action → FIRST_SUCCESS fallback

A failed email Action correctly caused the Automation to fail. Under `FIRST_SUCCESS`, execution continued to a later matching Automation and stopped only after a complete successful Automation.

### Range

Order #87 confirmed:

```text
between 1,700,000 and 3,000,000
        ↓
condition_passed
        ↓
3 / 3 Actions successful
        ↓
Admin email successful
```

This is the current strongest end-to-end Range proof.

---

# 10. Notification Architecture

WooSmart deliberately separates recipient configuration from mail transport configuration.

WooSmart controls:

```text
Notification Recipient
```

WordPress / the configured Mail Transport controls:

```text
From address
SMTP/API credentials
Transport
Provider
Delivery
```

Possible environments include:

```text
SMTP hosting
Resend
Brevo
Gmail
Microsoft 365
Amazon SES
Other WordPress-compatible mail transports
```

WooSmart core should remain provider-neutral.

---

# 11. Current Logging Model

The technical logger contains events such as:

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
action_result
action_side_effect
condition_passed
condition_failed
automation_scan
automation_conflict_detected
```

The technical Logger is intended for diagnostics and development.

Execution History is intended for administrator-facing execution understanding.

---

# 12. What Is Intentionally NOT Part of the Simple MVP

The project should not become complicated merely because a feature is technically possible.

The following are intentionally postponed until they have a clear product benefit:

- Multiple nested Condition groups.
- Complex AND/OR visual logic.
- Large-scale visual workflow builder.
- Advanced conflict graphs.
- Complicated Priority rules.
- Provider-specific SMTP configuration inside WooSmart.
- Large integration marketplace.
- Background scheduling before a real use case requires it.
- Retry queues before reliable failure scenarios justify them.

The rule is:

> **Do not add architecture merely to make the architecture look sophisticated.**

---

# 13. Next Development Roadmap

The remaining work should be completed in practical stages.

## Stage 1 — Execution History Final Polish

Goal:

```text
User clicks an Execution
        ↓
Immediately understands what happened
```

Remaining refinement:

- Clearer condition presentation.
- Clearer Action presentation.
- Correct historical snapshots.
- Correct measured durations.
- Useful failure messages.
- Links between Automation, Order, and Execution.

This is a high-value customer-facing feature and should be finished before adding large new capabilities.

## Stage 2 — Deterministic Automation Ordering

Goal:

```text
Multiple matching Automations
        ↓
Predictable order
```

Priority should remain simple: it is an ordering mechanism, not another Condition engine.

The product should clearly document the tie-breaking behavior when priorities are equal or absent.

## Stage 3 — Cross-Automation Conflict Analysis

Only after deterministic ordering is stable should WooSmart consider broader conflict analysis.

The useful minimum is:

```text
Does another Automation match the same Trigger?
Does its Condition overlap?
Does it modify the same important WooCommerce state?
Could its result overwrite the first result?
```

The system should warn rather than silently alter another Automation.

## Stage 4 — Additional High-Value Conditions

Add Conditions that store owners actually need, in roughly this order:

```text
Order Status
Payment Method
Shipping Method
Product / Category
Customer Role
Coupon
Order Item Count
```

Each new Condition should use the Registry architecture.

## Stage 5 — Additional High-Value Actions

Prioritize actions that support the core notification/commerce workflow:

```text
Add Order Note
Customer Email
Additional Admin Notification
Modify Order Metadata
```

Avoid adding external integrations before the core Action model is stable.

## Stage 6 — Additional Triggers

Potential next Triggers:

```text
Order Paid
Order Status Changed
Order Completed
Order Cancelled
Order Refunded
```

After that:

```text
Customer Registered
Product Stock Changed
Product Becomes Out of Stock
```

## Stage 7 — Product Hardening

Before public commercial release:

- Permission review.
- Nonce review.
- Input/output validation review.
- Error-message review.
- Compatibility testing with common WooCommerce versions.
- Performance testing with more Automations.
- Clean activation/deactivation behavior.
- Upgrade/migration safety.
- Clean uninstall strategy.
- Automated regression tests for critical execution paths.

---

# 14. Future Product Ideas

These ideas are retained so they are not forgotten, but they are **not current implementation requirements**.

## More Actions

```text
Add Order Note
Modify Order Metadata
Apply Coupon
Modify Order Items
Customer Email
Additional Admin Email
Webhook
HTTP Request
Telegram
WhatsApp
SMS
```

## More Triggers

```text
Order Paid
Order Status Changed
Order Completed
Order Cancelled
Order Failed
Order Refunded
Customer Registered
Customer Login
Product Created
Product Updated
Product Stock Changed
```

## More Conditions

```text
Order Status
Payment Method
Shipping Method
Coupon
Customer Role
Customer Order Count
Customer Total Spent
Product
Product Category
Product Quantity
Stock Quantity
Stock Status
SKU
```

## Scheduling

Future use cases may include:

```text
Wait 1 hour
Wait 24 hours
Run at a specific time
Reminder after payment failure
```

Possible infrastructure later:

```text
WP-Cron
Action Scheduler
Background Processing
Retry Queue
```

This should be introduced only when a real delayed-execution requirement exists.

## Integrations

Long-term possibilities:

```text
Telegram
WhatsApp
Slack
Discord
Google Sheets
CRM
REST API
Webhooks
```

These should remain outside the core business logic as much as possible.

---

# 15. Security Principles

Current security foundation includes:

```text
Capability checks
Nonces
Input sanitization
Output escaping
Validation of Trigger/Condition/Action configuration
```

Before introducing external HTTP requests or Webhooks, security must additionally cover:

```text
Authentication
Authorization
SSRF protection
URL validation
Rate limiting
Secret storage
Credential protection
```

---

# 16. Performance Principles

The current MVP is designed for real store testing rather than premature high-scale optimization.

Future optimization may include:

```text
Automation query optimization
Caching
Reduced database queries
Dedicated Execution tables
Better Trigger filtering
Background execution
```

Optimization should follow measured requirements.

---

# 17. Data Model

Current Automation storage uses WordPress:

```text
Post Type:
woosmart_automation
```

Current metadata includes:

```text
_woosmart_status
_woosmart_trigger
_woosmart_conditions
_woosmart_actions
```

Priority-related metadata may also exist in the current implementation.

The Range Condition is stored as structured data rather than a formatted display string.

Example:

```php
array(
    'field'    => 'order_total',
    'operator' => 'between',
    'value'    => array(
        'min' => '1700000',
        'max' => '3000000',
    ),
)
```

Future database tables remain an optimization and scalability decision, not a prerequisite for the current MVP.

---

# 18. Testing Strategy

Development is performed incrementally:

```text
Inspect current file
    ↓
Modify complete file
    ↓
Replace full file in XAMPP
    ↓
Activate / refresh plugin
    ↓
Test Admin UI
    ↓
Create real WooCommerce order
    ↓
Inspect Logger
    ↓
Inspect Execution History
    ↓
Verify email delivery
    ↓
Fix
    ↓
Stable checkpoint
    ↓
Update README
    ↓
Continue
```

A feature is not considered complete merely because the Admin UI appears correct. The complete runtime path must be tested.

---

# 19. Important Testing Lessons

### Email configuration can invalidate a correct test

A previous failed email test was caused by a manually changed recipient address that had been forgotten. The WooSmart execution path was actually correct.

Lesson:

```text
Check plugin behavior
AND
Check external test configuration
```

### Range boundaries must be tested

For `between 1,700,000 and 3,000,000`, test:

```text
1,699,999 → fail
1,700,000 → pass
2,000,000 → pass
3,000,000 → pass
3,000,001 → fail
```

### Execution Policy must be tested with failure

A policy such as `FIRST_SUCCESS` is meaningful only when both success and failure paths are tested.

### Historical data must be protected

Editing an Automation after execution must not rewrite what the old Execution claims happened.

---

# 20. Current Known Limitations

- Only one Trigger is currently implemented.
- The current Condition UI is intentionally simple.
- Multiple Condition groups are not part of the current MVP.
- Only two Action types are currently available.
- Cross-Automation Conflict Detection is not yet a complete product feature.
- Priority exists but its final product UX and deterministic tie behavior require final hardening.
- Scheduling and retry infrastructure are not implemented.
- External integrations are not implemented.
- The technical Logger remains an MVP diagnostic layer.
- The project has not yet completed the full production compatibility/security/test pass required for public marketplace release.

---

# 21. Current Status Summary

```text
Plugin Foundation                 🟢 Complete
WooCommerce Detection             🟢 Complete
Automation CRUD                   🟢 Complete
Persian RTL Admin                 🟢 Complete
Currency-Aware Display            🟢 Complete
Condition Registry                🟢 Complete
Order Total Condition             🟢 Complete
Order Total Range                 🟢 Complete / Tested
Action Registry                   🟢 Complete
Change Order Status               🟢 Complete
Notify Admin                      🟢 Complete / Tested
Multiple Actions                  🟢 Complete / Tested
Action Ordering                   🟢 Complete
Action-Level Conflict Warnings    🟢 Complete / MVP
Multiple Automation Matching      🟢 Tested
Execution Policy                  🟢 Tested
FIRST_SUCCESS behavior            🟢 Tested
Execution History                 🟢 Complete / Tested
Historical Snapshots              🟢 Tested
Execution Timing                  🟢 Tested
Notification Settings             🟢 Complete
Provider-Neutral Diagnostics      🟢 Complete
Priority                          🟡 Hardening / Validation
Cross-Automation Conflicts        🟡 Planned
More Conditions                   🔴 Planned
More Actions                      🔴 Planned
More Triggers                     🔴 Planned
Scheduling                        🔴 Planned
Retry Queue                       🔴 Planned
Integrations                      🔴 Planned
Automated Regression Tests        🟡 Required for release
Marketplace Hardening             🔴 Required before commercial release
```

---

# 22. Immediate Development Target

The current project should not jump into a large feature set.

The next practical sequence is:

```text
1. Finish Execution History polish
        ↓
2. Validate deterministic Priority ordering
        ↓
3. Validate / simplify execution behavior for multiple Automations
        ↓
4. Add only the highest-value Conditions
        ↓
5. Add only the highest-value Actions
        ↓
6. Expand Triggers
        ↓
7. Product hardening
        ↓
8. Marketplace preparation
```

The primary product mission remains:

```text
WooCommerce Event
        ↓
Correct Condition
        ↓
Correct Automation
        ↓
Correct Action
        ↓
Notification reaches the intended administrator
        ↓
Administrator can verify what happened
```

---

# 23. Commercial Product Direction

WooSmart is being designed for eventual commercial distribution through WordPress marketplaces such as ژاکت and راستچین.

That changes the engineering standard.

The product should prioritize:

```text
Simple installation
Simple first Automation
Clear Persian UI
Reliable email behavior
Predictable execution
Useful diagnostics
Safe WooCommerce interaction
Clear Execution History
Good documentation
Low support burden
```

A technically impressive feature that creates confusion or support requests is less valuable than a small feature that reliably solves a store owner's problem.

The product should therefore grow from the user's workflow outward, not from technical possibilities inward.

---

# 24. Development Rules

1. GitHub is the project source of truth.
2. The current file in the project must be reviewed before modification.
3. Do not assume an older file is still current.
4. Preserve existing working behavior unless the change is intentional.
5. When a file changes, provide the complete file for replacement.
6. Test real WooCommerce behavior after major changes.
7. Check both Technical Logs and Execution History.
8. Verify real notification delivery when notification logic changes.
9. Keep WooSmart independent from a specific mail provider.
10. Keep WooCommerce as the currency source of truth.
11. Do not perform silent Rial/Toman conversion.
12. Do not add architectural complexity without a concrete product requirement.
13. Keep historical Execution data immutable after execution.
14. Do not silently disable or modify another user's Automation because of a conflict.
15. Do not mark a feature complete because the Admin UI alone works.
16. Update this README after meaningful milestones.
17. Record important design decisions and discovered root causes.
18. Prefer predictable behavior over clever behavior.
19. Prefer customer-visible reliability over feature count.
20. Before marketplace release, perform a dedicated security, compatibility, upgrade, and regression pass.

---

# 25. Future Decisions Register

The following are ideas that must not be forgotten, but they are intentionally not current MVP requirements:

```text
Multiple Conditions
AND / OR Condition Groups
Advanced Conflict Engine
Advanced Priority UX
Additional Triggers
Additional Actions
Customer-facing notifications
SMS / Telegram / WhatsApp
Webhooks / HTTP Requests
Scheduling
Retry Queue
Background Processing
Advanced Automation Builder
Automation Trace
Developer API
Third-party integrations
Dedicated database tables
Automated regression suite
```

Each item should eventually be marked:

```text
Implemented
Rejected
Replaced
Postponed
```

---

# 26. Final Architecture Direction

The long-term execution architecture is:

```text
Trigger
    ↓
Candidate Automations
    ↓
Condition Evaluation
    ↓
Execution Ordering
    ↓
Conflict Awareness
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
```

The architecture should stay modular, but the user experience should stay simple.

The central principle remains:

```text
WHEN
    something happens

IF
    the rule matches

THEN
    perform the intended action

AND
    clearly explain the result
```

---

# 27. Current Project State for a New AI / Developer

A new development session should understand the project as follows:

```text
WooSmart Automation is a real WooCommerce automation MVP.

The foundation is already working.

The Registry architecture for Conditions and Actions exists.

Order creation is the current Trigger.

Order Total is the current main Condition.

Order Total supports scalar comparisons and an inclusive Range operator.

Multiple Actions work sequentially and return individual results.

Admin email notification works through wp_mail() and the active WordPress Mail Transport.

Multiple matching Automations are supported.

Execution Policy supports ALL, FIRST_MATCH, and FIRST_SUCCESS.

Execution History stores a historical snapshot of an execution.

Execution timing is measured rather than inferred from unrelated logger timestamps.

Action-level warnings exist for risky repeated order-status changes.

Priority exists as an ordering concept and is being hardened.

The next goal is not to add dozens of features.

The next goal is to make the current workflow extremely reliable,
understandable, and commercially ready.
```

Before changing any project file:

```text
Read current implementation
        ↓
Understand data flow
        ↓
Modify the smallest necessary architectural surface
        ↓
Provide the complete changed file
        ↓
Test with a real WooCommerce order
        ↓
Inspect Logs + Execution History
        ↓
Update README
```

---

# End

WooSmart Automation is evolving from a simple `WHEN → IF → THEN` WooCommerce rule runner into a dependable automation platform.

The most important milestone so far is not the number of features. It is that the complete path has been demonstrated with real orders:

```text
WooCommerce
    ↓
Trigger
    ↓
Condition
    ↓
Range / comparison
    ↓
Automation
    ↓
Multiple Actions
    ↓
Email delivery
    ↓
Execution Result
    ↓
Execution History
```

The current product direction is intentionally focused:

> **Make WooSmart reliable enough that a store owner can create an automation, trust it to perform the intended action, and immediately understand what happened.**
