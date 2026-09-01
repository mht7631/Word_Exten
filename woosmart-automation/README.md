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

The project is being developed as a real commercial product. The goal is a reliable, predictable, explainable, customer-friendly automation engine that store administrators can use without programming knowledge.

The core product promise is:

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
**Stage:** MVP / Execution Reliability → Product Hardening

Repository:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the project source of truth. Local XAMPP is the real development and WooCommerce test environment.

---

# 2. Mission

WooSmart started with a practical problem:

```text
When an order is created
If the order matches a rule
Then notify the store administrator
```

The mission is to turn that simple rule into a dependable automation product without forcing a normal store owner to deal with technical complexity.

Current product priorities:

```text
Reliable execution
Predictable ordering
Reliable notifications
Useful Execution History
Safe WooCommerce interaction
Minimal configuration complexity
Low support burden
```

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

This section records the architectural evolution and the important lessons discovered during real WooCommerce testing.

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

The foundation includes plugin bootstrap, WooCommerce detection, the internal Automation post type, Persian RTL Admin UI, Automation CRUD, the current WooCommerce Trigger, Condition Engine, Actions, and technical logging.

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

The amount field uses thousands separators, correct LTR numeric rendering inside RTL, and a separate currency label.

## Phase 4 — Action Registry

Actions were moved into a central Action Registry so that adding an Action does not require rewriting the Execution Engine.

Current Actions:

```text
change_order_status
notify_admin
```

## Phase 5 — Multiple Actions

The product evolved from a single-action rule into a workflow model:

```text
THEN
    Action 1
    AND
    Action 2
    AND
    Action 3
```

Multiple Actions are supported, ordered, executed sequentially, and recorded independently in Execution History.

A real workflow has been tested:

```text
1. Change status → Processing
2. Change status → Completed
3. Notify administrator
```

## Phase 6 — Real Email Delivery

The notification architecture is deliberately provider-neutral:

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

WooSmart controls the notification recipient. The active WordPress Mail Transport controls the sender and delivery mechanism.

Real notification delivery has been successfully tested.

WooSmart captures `wp_mail_failed` / `WP_Error` details during its own notification attempt and preserves useful provider error information.

## Phase 7 — Multiple Matching Automations

Real orders proved that more than one Automation can match the same Trigger.

This introduced the need to control:

```text
Which Automation runs first?
Should execution continue?
What happens if an Action fails?
What happens if multiple Automations modify the same WooCommerce state?
```

## Phase 8 — Execution Policy

Three execution policies are implemented:

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

`FIRST_SUCCESS` is based on the complete Automation result, not merely a successful Condition.

## Phase 9 — Execution History

The technical Logger is not enough for a store owner, so a separate Execution History layer was introduced.

```text
Logger
    = technical event stream

Execution History
    = human-readable execution record
```

Execution records contain:

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

Each execution can be opened in a Detail view.

## Phase 10 — Historical Snapshot Integrity

Historical executions must describe the Automation **as it existed at execution time**.

The stored Condition and Action configuration used by an execution is treated as an execution snapshot. Later edits to the Automation must not rewrite what the old execution claims happened.

The Execution Engine now normalizes legacy Condition data only in memory for the current one-Condition MVP and does not mutate the Automation metadata during runtime.

## Phase 11 — Execution Timing

Execution timing uses measured runtime boundaries rather than unrelated logger timestamp gaps.

```text
Execution duration
    = measured execution start → measured execution end
```

Each Action also stores an individual measured duration in Execution History.

Temporary diagnostic timing instrumentation was used during performance investigation and has now been removed from the production MVP Action Engine.

## Phase 12 — Action-Level Conflict Warnings

Multiple Actions exposed potentially unsafe configuration such as:

```text
Action 1 → Processing
Action 2 → Completed
```

The current MVP warns about repeated/sequential order-status changes inside an Automation without blocking the user.

Broader cross-Automation conflict analysis remains future work.

## Phase 13 — Price Range Condition

The `order_total` Condition supports a dedicated Range operator:

```text
between
```

The UI supports:

```text
حداقل
حداکثر
```

Stored structure:

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

Real WooCommerce tests proved the complete Range path, including successful Action execution and administrator notification.

Execution Detail now renders the Range correctly instead of attempting to cast the structured `min` / `max` value to a scalar.

## Phase 14 — Deterministic Priority Ordering

Priority is treated as an ordering mechanism, not another Condition system.

Current runtime behavior:

```text
1. Explicit Priority ASC
2. Newer Automation first when Priority is equal
3. Automation ID as the final deterministic tie-breaker
```

Automations without explicit Priority are placed after explicit priorities while preserving their original newest-to-oldest order.

The comparator follows the normal `usort()` contract and returns `0` when the compared Automation IDs are equal.

Priority remains intentionally simple for the MVP.

## Phase 15 — WooCommerce Deferred Transactional Emails

Real performance testing exposed an important WooCommerce behavior.

Before Deferred Emails were enabled, `WC_Emails::send_transactional_email` consumed most of the runtime of the `pending → processing` status transition. Tests reached several seconds and, in one case, more than 30 seconds.

WooCommerce Deferred Emails were then enabled.

The callback changed from:

```text
WC_Emails::send_transactional_email
```

to:

```text
WC_Emails::queue_transactional_email
```

A real checkout test then showed:

```text
pending → processing
≈ 106 ms
```

with the transactional email queued asynchronously through Action Scheduler instead of blocking the checkout request.

This is now part of the recommended WooCommerce configuration for the development and MVP test environment:

```text
WooCommerce transactional emails     ✅ Enabled
WooCommerce Deferred Emails          ✅ Enabled
WordPress Mail Transport / SMTP     ✅ Enabled
WooSmart Automation                 ✅ Enabled
```

WooSmart does not remove or replace WooCommerce's own email Hook behavior.

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
Actions
    ↓
Action Results
    ↓
Execution History
```

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

`class-woosmart-condition-admin.php` is intentionally **not** part of the current MVP and must not be reintroduced unless the product direction explicitly changes.

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
| `class-woosmart-action-engine.php` | Action execution, result collection, mail failure capture |
| `class-woosmart-execution-engine.php` | Runtime orchestration, deterministic ordering, Execution Policy |
| `class-woosmart-execution-history.php` | Historical execution records and snapshots |
| `class-woosmart-execution-admin.php` | Execution History, Detail, and Policy UI |
| `class-woosmart-priority-admin.php` | Simple Priority configuration and persistence |

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

Range is inclusive and has been tested with real WooCommerce orders.

The current MVP intentionally keeps Condition configuration simple. Multiple Condition groups and complex AND/OR logic are future functionality.

## Actions — ✅

Current:

```text
change_order_status
notify_admin
```

Multiple Actions are supported and executed sequentially.

## Multiple Actions — ✅ Tested

The UI supports:

- Add Action.
- Remove Action.
- Reorder Action.
- Configure each Action.
- Observe individual Action result.

## Conflict Warnings — ✅ MVP

Current non-blocking warnings cover repeated or sequential order-status changes inside one Automation.

## Execution Policy — ✅ Tested

```text
all
first_match
first_success
```

## Execution History — ✅ Tested

Implemented:

- Execution list.
- Status filtering.
- Summary counters.
- Execution Detail.
- Condition display.
- Range display.
- Action results.
- Historical snapshots.
- Start/end timestamps.
- Execution duration.
- Action durations.
- Execution Policy display.

## Notification Settings — ✅

WooSmart stores a dedicated notification recipient and falls back to WordPress `admin_email` when the WooSmart recipient is empty.

## Real Email Delivery — ✅ Tested

Successful real delivery was confirmed through the configured WordPress Mail Transport.

## Mail Diagnostics — ✅

`wp_mail_failed` / `WP_Error` information is captured during WooSmart notification attempts.

## Currency Display — ✅

WooCommerce `IRT` is presented as `تومان` in the current Persian UI without independent numeric conversion.

## Priority — ✅ Runtime / 🟡 UX Hardening

Priority is implemented as a deterministic ordering mechanism.

Current runtime ordering:

```text
Priority ASC
    ↓
Creation date DESC
    ↓
Automation ID DESC
```

The UI should remain simple and must not turn Priority into a second rule engine.

---

# 9. Current End-to-End Test Evidence

The current MVP has been tested with real WooCommerce orders in the development environment.

## Range

A real test of Automation #77 verified:

```text
order_total
between
1,700,000 and 3,000,000
        ↓
condition_passed
        ↓
3 Actions successful
        ↓
Admin notification successful
```

Execution Detail correctly renders the structured Range as a readable Persian amount range.

## Multiple Automations

Real orders have produced multiple matching Automation IDs and demonstrated the execution policy behavior.

Example tested ordering:

```text
Automation #91 → Priority 10
Automation #77 → Priority 20
```

The scan log exposes the resolved ordering information.

## Repeated Status Protection

A real test demonstrated:

```text
existing status: processing
requested status: processing
        ↓
Action skipped
        ↓
No duplicate status transition
        ↓
No unnecessary WooCommerce status hooks
```

This prevents an unnecessary WooCommerce status transition when the desired status is already active.

## WooCommerce Deferred Email Performance

A real diagnostic sequence identified the root cause of slow checkout:

```text
woocommerce_order_status_pending_to_processing
        ↓
WC_Emails::send_transactional_email
        ↓
majority of transition time
```

After enabling WooCommerce Deferred Emails:

```text
WC_Emails::queue_transactional_email
        ↓
Action Scheduler
        ↓
checkout request remains fast
```

A real order test then measured roughly `106 ms` for `pending → processing`.

The temporary callback-level diagnostics used to find this cause have been removed from the clean MVP Action Engine.

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

WooSmart core remains provider-neutral.

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
action_skipped
condition_passed
condition_failed
automation_scan
automation_conflict_detected
```

Diagnostic-only events used during performance investigation are **not part of the clean production MVP Action Engine**.

Execution History is intended for administrator-facing execution understanding.

---

# 12. What Is Intentionally NOT Part of the Simple MVP

The project should not become complicated merely because a feature is technically possible.

The following are intentionally postponed:

- Multiple nested Condition groups.
- Complex AND/OR visual logic.
- Large-scale visual workflow builder.
- Advanced cross-Automation conflict graphs.
- Complicated Priority rules.
- Provider-specific SMTP configuration inside WooSmart.
- Large integration marketplace.
- Scheduling before a real delayed-execution use case requires it.
- Retry queues before reliable failure scenarios justify them.
- Automatic rollback.
- `class-woosmart-condition-admin.php` in the MVP.

The rule is:

> **Do not add architecture merely to make the architecture look sophisticated.**

---

# 13. Next Development Roadmap

The remaining work should stay practical and MVP-focused.

## Stage 1 — Execution History Final Polish

Goal:

```text
User clicks an Execution
        ↓
Immediately understands what happened
```

Remaining refinement:

- Final visual polish.
- Final snapshot validation.
- Useful failure messages.
- Automation / Order links where useful.
- Verify edge cases around missing historical data.

## Stage 2 — Priority Final Validation

Goal:

```text
Multiple matching Automations
        ↓
Predictable execution order
```

Priority must remain a simple ordering mechanism.

## Stage 3 — Additional High-Value Conditions

Candidate order:

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

## Stage 4 — Additional High-Value Actions

Candidate order:

```text
Add Order Note
Customer Email
Additional Admin Notification
Modify Order Metadata
```

## Stage 5 — Additional Triggers

Potential next Triggers:

```text
Order Paid
Order Status Changed
Order Completed
Order Cancelled
Order Refunded
```

## Stage 6 — Product Hardening

Before public commercial release:

- Permission review.
- Nonce review.
- Input/output validation review.
- Error-message review.
- Compatibility testing.
- Performance testing with more Automations.
- Clean activation/deactivation behavior.
- Upgrade/migration safety.
- Clean uninstall strategy.
- Automated regression tests for critical execution paths.
- Marketplace packaging and documentation.

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

The current MVP is designed for real store testing and measured reliability rather than premature high-scale optimization.

An important performance lesson has been verified:

```text
WooCommerce transactional emails
        ↓
may block the current checkout request
```

The recommended WooCommerce configuration for the current development/MVP environment is:

```text
Deferred Transactional Emails = enabled
```

This delegates transactional email work to Action Scheduler and prevents email transmission from unnecessarily blocking the checkout request.

WooSmart itself should not remove WooCommerce email callbacks to achieve this result.

Future optimization may include:

```text
Automation query optimization
Caching
Reduced database queries
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
_woosmart_priority
```

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

Execution History uses dedicated storage for execution records and historical snapshots.

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

WooSmart execution can be correct while provider or notification settings are wrong.

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

### Repeated status transitions should be tested

When an Action requests the status that the order already has:

```text
Current = processing
Requested = processing
        ↓
No transition
No duplicate WooCommerce status hooks
Action result remains successful
```

### Performance must be measured at the correct layer

When a WooCommerce status transition is slow, inspect the Hook/callback chain before changing WooSmart's orchestration logic.

In the current investigation, the actual root cause was:

```text
WC_Emails::send_transactional_email
```

not Range evaluation or Priority sorting.

---

# 20. Current Known Limitations

- Only one Trigger is currently implemented.
- The current Condition UI is intentionally simple.
- Multiple Condition groups are not part of the current MVP.
- Only two Action types are currently available.
- Cross-Automation Conflict Detection is not yet a complete product feature.
- Priority UX still needs final commercial polish, although runtime ordering is deterministic.
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
Range Execution Detail            🟢 Complete / Tested
Action Registry                   🟢 Complete
Change Order Status               🟢 Complete
Notify Admin                      🟢 Complete / Tested
Multiple Actions                  🟢 Complete / Tested
Action Ordering                   🟢 Complete
Action-Level Conflict Warnings    🟢 Complete / MVP
Repeated Status Protection        🟢 Complete / Tested
Multiple Automation Matching      🟢 Tested
Execution Policy                  🟢 Tested
FIRST_SUCCESS behavior            🟢 Tested
Execution History                 🟢 Complete / Tested
Execution Detail                  🟢 Complete / Tested
Historical Snapshots              🟢 Tested
Execution Timing                  🟢 Tested
Notification Settings             🟢 Complete
Provider-Neutral Diagnostics      🟢 Complete
Deferred Transactional Emails     🟢 Verified / Recommended
Priority Runtime Ordering         🟢 Implemented / Tested
Priority UX                       🟡 Final polish
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
1. Finalize Execution History polish
        ↓
2. Finalize Priority UX and validation
        ↓
3. Validate multiple-Automation behavior further
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
21. Do not reintroduce temporary performance diagnostics into the clean MVP without a specific debugging need.
22. Prefer WooCommerce-supported asynchronous infrastructure over custom Hook suppression when solving email-performance issues.

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
Dedicated database tables where justified
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

The foundation is working.

The Registry architecture for Conditions and Actions exists.

Order creation is the current Trigger.

Order Total is the current main Condition.

Order Total supports scalar comparisons and an inclusive Range operator.

Multiple Actions work sequentially and return individual results.

Admin email notification works through wp_mail() and the active WordPress Mail Transport.

Multiple matching Automations are supported.

Execution Policy supports ALL, FIRST_MATCH, and FIRST_SUCCESS.

Execution History stores a historical snapshot of an execution.

Execution Detail presents the execution in administrator-friendly form.

Priority is implemented as deterministic ordering and intentionally remains simple.

Repeated status transitions are skipped when the requested status is already active.

WooCommerce Deferred Emails have been verified as the correct way to prevent transactional email from blocking checkout in the current environment.

Temporary performance diagnostics have been removed from the clean MVP Action Engine.

The next goal is not to add dozens of features.

The next goal is to make the current workflow extremely reliable,
understandable, supportable, and commercially ready.
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
