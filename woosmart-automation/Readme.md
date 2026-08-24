# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin for building rule-based workflows without coding.

Core concept:

```text
WHEN something happens
IF conditions are satisfied
THEN execute one or more actions
```

The product goal is not simply to add many triggers and actions. WooSmart should make automation **predictable, explainable, safe, extensible, and easy for store administrators to use**.

---

## Repository

GitHub:

https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

GitHub is the project source of truth.

Development workflow:

```text
Review current file
    ↓
Implement one feature
    ↓
Replace full file locally
    ↓
Test real WooCommerce behavior
    ↓
Verify Logs / Execution History
    ↓
Commit stable milestone
    ↓
Update this README
```

When an existing project file is changed, the development workflow uses the **complete file** for replacement rather than partial snippets.

---

# Current Status

Version:

```text
1.0.0
```

Stage:

```text
MVP / Core Automation Engine
```

Current stable capabilities:

- WooCommerce-aware automation foundation.
- Persian / RTL Admin UI.
- Condition Registry and Condition Engine.
- Action Registry and Action Engine.
- Order Created trigger.
- Order Total condition with comparison operators.
- Multiple Actions.
- Action ordering controls.
- Action-level success/failure reporting.
- Conflict detection for risky repeated order-status transitions.
- Multiple Automation execution planning.
- Execution Policies: `all`, `first_match`, `first_success`.
- Execution History.
- Execution Detail pages.
- Condition and Action snapshots for historical integrity.
- Real WordPress mail delivery through `wp_mail()`.
- WooSmart notification recipient settings.
- Provider-neutral mail failure diagnostics.
- WooCommerce currency-aware display.
- IRT → تومان presentation without numeric currency conversion.

Current development focus:

```text
Execution Priority
    ↓
Condition Range / Between
    ↓
Multiple Conditions
    ↓
More Order Conditions
    ↓
More useful Actions
    ↓
More WooCommerce Triggers
    ↓
Professional Automation Builder
```

---

# Architecture

High-level execution flow:

```text
WooCommerce Event
      ↓
Trigger System
      ↓
Execution Engine
      ↓
Execution Planning
      ↓
Condition Engine
      ↓
Action Engine
      ↓
Action Results
      ↓
Automation Result
      ↓
Execution History + Logger
```

Condition architecture:

```text
Condition Registry
      ↓
Condition Definition
      ↓
Condition Engine
      ↓
Condition Result
```

Action architecture:

```text
Action Registry
      ↓
Action Definition
      ↓
Action Handler
      ↓
Action Result
```

Notification architecture:

```text
WooSmart Notification Settings
      ↓
wp_mail()
      ↓
WordPress Mail Transport
      ↓
SMTP / Transactional Email Provider
```

The system uses shared Condition, Action, and Execution History / Engine instances instead of creating unnecessary duplicate services.

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
    ├── class-woosmart-execution-engine.php
    ├── class-woosmart-execution-history.php
    ├── class-woosmart-execution-admin.php
    ├── class-woosmart-notification-settings.php
    └── class-woosmart-priority-admin.php
```

The Priority Admin file is part of the current development branch / local implementation and is being verified before Priority is marked stable.

---

# Core Components

## `woosmart-automation.php`

Main bootstrap.

Responsibilities:

- Load plugin classes.
- Initialize shared services.
- Initialize Condition Engine.
- Initialize Action Engine.
- Initialize Execution Engine.
- Initialize Trigger system.
- Initialize Admin interfaces.
- Initialize Automation Manager.
- Handle activation/deactivation.

The bootstrap intentionally wires shared services instead of creating duplicate engines.

---

## `class-woosmart-core.php`

Core WooCommerce compatibility layer.

Responsibilities:

- Detect WooCommerce availability.
- Provide dependency status.
- Maintain compatibility behavior.

WooCommerce remains the source of truth for store monetary values and currency settings.

---

## `class-woosmart-currency.php`

Display-only currency helper.

Current behavior:

```text
IRT → تومان
IRR → ریال
Other currencies → WooCommerce symbol where available
```

Critical rule:

```text
WooSmart never silently converts Rial ↔ Toman.
```

No multiplication or division is performed merely because the display unit is تومان or ریال.

WooSmart does not modify product prices, order totals, payment amounts, or WooCommerce currency settings.

---

## `class-woosmart-admin.php`

Main Persian RTL administration UI.

Current areas include:

- داشبورد
- اتوماسیون‌ها
- افزودن اتوماسیون
- گزارش‌ها
- Multiple Actions UI

The UI uses stable English internal identifiers while presenting Persian user-facing terminology.

Examples:

| Internal | UI |
|---|---|
| Trigger | رویداد |
| Condition | شرط |
| Action | عملیات |
| Automation | اتوماسیون |
| Execution | اجرا |
| Status | وضعیت |

The Condition Builder reads definitions and operators from the Condition Registry instead of duplicating them in the UI.

Numeric values are formatted for readability while the stored condition value remains numeric.

---

## `class-woosmart-automation-manager.php`

Handles Automation CRUD and validation.

Current operations:

- Create
- Update
- Enable / Disable
- Duplicate
- Delete

Validation covers:

- Trigger.
- Condition field.
- Condition operator.
- Condition value.
- Action type.
- Order status.
- Notification configuration.
- Empty Action sets.

Conflict detection is non-blocking in the current MVP: risky configurations generate a warning / diagnostic rather than silently changing user configuration.

---

# Condition System

## `class-woosmart-condition-registry.php`

Central source of truth for Conditions.

Current Condition:

```text
order_total
```

Current operators:

```text
is_equal
a
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

The first accidental `a` entry above is not a valid operator and must never exist in production data; the canonical operator list is:

```text
is_equal
is_not_equal
greater_than
greater_than_or_equal
less_than
less_than_or_equal
```

Condition metadata includes:

```text
label
value_type
operators
evaluator
```

Future Conditions should be registered here rather than duplicated across Admin, Manager, and Engine code.

---

## `class-woosmart-condition-engine.php`

Evaluates Conditions through the Registry.

Current behavior:

```text
All configured Conditions must pass.
```

The data structure is already array-based, allowing later support for:

```text
AND
OR
Condition Groups
Nested Groups
```

---

# Action System

## `class-woosmart-action-registry.php`

Central Action definition registry.

Current Actions:

```text
change_order_status
notify_admin
```

Each Action definition provides metadata such as:

```text
label
handler
fields
```

The Action Engine resolves handlers through this Registry rather than using an ever-growing hard-coded action switch.

---

## `class-woosmart-action-engine.php`

Executes Actions sequentially.

Current Actions:

### Change Order Status

Supports WooCommerce statuses such as:

```text
pending
processing
on-hold
completed
cancelled
refunded
failed
```

Status transitions can trigger WooCommerce hooks and transactional emails. WooSmart records these transitions as diagnostic side effects instead of trying to suppress WooCommerce behavior in the MVP.

### Notify Administrator

Uses:

```text
wp_mail()
```

Recipient resolution:

```text
WooSmart Notification Email
        ↓
WordPress admin_email fallback
```

Supported placeholders:

```text
{order_id}
{order_total}
{order_status}
{customer_name}
```

The Action Engine captures `wp_mail_failed` during WooSmart notification attempts so provider / PHPMailer diagnostics can be preserved.

---

# Multiple Actions

Multiple Actions are a core MVP feature.

Example:

```text
THEN
    Action 1: Change Status → Processing
    Action 2: Change Status → Completed
    Action 3: Notify Administrator
```

The system supports:

- Multiple Action storage.
- Sequential execution.
- Add / remove Action rows.
- Reordering Actions.
- Per-Action result reporting.
- Overall Automation success/failure based on Action results.

The UI currently provides basic Action conflict warnings for repeated order-status changes and potential WooCommerce side effects.

Important behavior:

> A failed later Action does not automatically roll back earlier successful Actions.

Rollback is deliberately not part of the current MVP because Actions can be non-reversible (for example, email delivery) and WooCommerce side effects can occur outside WooSmart.

---

# Conflict Detection

Current conflict detection is implemented for the most obvious Action-level risk: repeated order-status changes inside one Automation.

Current checks include:

```text
Multiple order-status changes
Duplicate target status
Sequential order-status transitions
```

Warnings are non-blocking.

Example:

```text
⚠ چند تغییر وضعیت سفارش

عملیات 1 → در حال پردازش
عملیات 2 → تکمیل‌شده
```

The system also logs an `automation_conflict_detected` diagnostic event.

Future conflict analysis will expand from Action-level conflicts to **cross-Automation conflicts**, including condition overlap and competing outcomes.

---

# Execution System

## `class-woosmart-execution-engine.php`

The Execution Engine orchestrates:

1. Trigger processing.
2. Active Automation discovery.
3. Execution ordering.
4. Condition evaluation.
5. Action execution.
6. Automation result determination.
7. Execution History recording.
8. Logger diagnostics.

The engine currently supports three Execution Policies.

---

# Execution Policies

## `all`

Run every active Automation whose Conditions match.

```text
Match → Execute
Match → Execute
Match → Execute
```

This is useful when Automations are intentionally independent.

## `first_match`

Stop after the first Automation whose Conditions match.

```text
Automation A → Condition ✕
Automation B → Condition ✓ → STOP
```

## `first_success`

Continue until one matching Automation completes all Actions successfully.

```text
Automation A
    Condition ✓
    Action 1 ✓
    Action 2 ✕
    ↓
    Automation failed

Automation B
    Condition ✓
    All Actions ✓
    ↓
    STOP
```

This policy has been tested with a real mail failure: the first matching Automation failed because its notification Action was rejected by the configured mail provider, and the next matching Automation executed successfully.

---

# Execution Ordering

Current stable ordering before Priority is finalized:

```text
Newest Automation → oldest Automation
```

The system records the discovered Automation IDs in `automation_scan` diagnostics.

Execution Priority is now being added so that production behavior no longer depends on incidental database order.

Target rule:

```text
Lower Priority number = earlier execution
```

Example:

```text
Priority 1  → first
Priority 10 → second
Priority 20 → third
```

If two Automations have the same Priority, creation date remains the deterministic tie-breaker.

Priority is considered part of the next stabilization milestone and should not be marked final until tested end-to-end.

---

# Execution History

Execution History has been implemented as a dedicated user-facing feature separate from lightweight technical Logs.

Current information includes:

```text
Execution ID
Automation ID
Order ID
Trigger
Execution Policy
Status
Action count
Start time
End time
Duration
Message
```

Supported statuses currently include:

```text
running
completed
failed
conditions_failed
```

Execution Detail pages provide:

- Execution summary.
- Condition results.
- Per-Action results.
- Action timing.
- Final outcome.
- Human-readable explanation.

Historical integrity has been tested: editing an Automation after an execution does not rewrite the stored Condition / Action snapshot for the old execution.

This is important because Execution History is intended to answer:

> “What actually happened at the time?”

rather than:

> “What is the Automation configured like now?”

---

# Execution Duration

Execution timing uses execution timestamps rather than page-load time.

Real tests confirmed that:

```text
Execution duration ≈ sum of Action durations + small orchestration overhead
```

Example measured execution:

```text
Action 1 ≈ 8.23 s
Action 2 ≈ 2.27 s
Action 3 ≈ 1.04 s
Execution ≈ 11.59 s
```

Condition-failed executions can correctly complete in milliseconds because no Action phase is entered.

This timing model is considered stable for the MVP.

---

# Logging

`class-woosmart-logger.php` is the lightweight technical event logger.

Important events include:

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

Current MVP logger storage uses WordPress Options and retains the latest log entries.

Execution History is intentionally separate from this technical event log.

---

# Notification System

WooSmart owns the **recipient setting**, not the SMTP transport.

```text
WooSmart Recipient
      ↓
wp_mail()
      ↓
WordPress Mail Transport
      ↓
Provider
```

Possible transports include SMTP hosting or transactional providers such as Resend, Brevo, Gmail, Microsoft 365, Amazon SES, and others.

WooSmart does not hard-code a provider and does not implement SMTP itself.

The sender / transport remains a WordPress environment concern.

---

# Notification Diagnostics

WooSmart captures `wp_mail_failed` while executing its own notification Action.

Generic categories can be used for common problems such as:

```text
Recipient restriction
Authentication failure
Connection failure
Sender address failure
SSL / TLS failure
Network / service limitation
Generic mail transport failure
```

The original provider error is preserved whenever WordPress supplies it.

Credentials such as SMTP passwords and API keys must never be exposed by WooSmart diagnostics.

A real provider restriction was successfully detected during development and caused `first_success` to continue to the next matching Automation.

---

# Currency Handling

WooCommerce is the monetary source of truth.

For the current development store:

```text
WooCommerce currency = IRT
WooSmart display      = تومان
```

The numeric value is not converted.

Example:

```text
Stored value: 100000
Displayed:    100,000 تومان
```

WooSmart does not change:

- Product prices.
- Order totals.
- Payment amounts.
- WooCommerce currency settings.
- Gateway currency behavior.

This separation is a compatibility requirement.

---

# Current User Experience Principles

WooSmart is intended for store administrators, not developers.

The UI should therefore:

- Use Persian / RTL terminology.
- Hide unnecessary implementation details.
- Explain warnings in plain language.
- Show what ran, what failed, and why.
- Preserve historical execution context.
- Avoid surprising side effects.
- Make complex automation understandable without code.

The long-term UX target is a builder that reads naturally as:

```text
WHEN
ایجاد سفارش

IF
مبلغ سفارش بین 1,000,000 تا 5,000,000 تومان

THEN
ارسال اعلان به مدیر
AND
تغییر وضعیت سفارش → در حال پردازش
```

---

# Data Model

Current Automation post type:

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

Execution History uses its own dedicated storage layer.

A future dedicated Automation / Execution / Log schema may be introduced when scale and feature requirements justify it.

---

# Security

Current baseline protections:

- Capability checks.
- Nonces.
- Input sanitization.
- Output escaping.
- Trigger validation.
- Condition validation.
- Action validation.
- Automation configuration validation.

Before adding Webhooks / HTTP Requests / external APIs, the project must also address:

- SSRF protection.
- Endpoint validation.
- Authentication.
- Credential storage.
- Rate limiting.
- Permission boundaries.

---

# Testing Status

Confirmed through real WooCommerce testing:

- [x] Create Automation
- [x] Edit Automation
- [x] Enable / Disable
- [x] Duplicate
- [x] Delete
- [x] Order Created trigger
- [x] Order Total condition
- [x] Condition pass/fail
- [x] Change Order Status
- [x] Multiple Actions
- [x] Action ordering
- [x] Action-level results
- [x] Action failure detection
- [x] Automation failure detection
- [x] Multiple Automation discovery
- [x] `all` policy
- [x] `first_match` policy
- [x] `first_success` policy
- [x] Real notification delivery
- [x] Mail failure diagnostics
- [x] Conflict warning UI
- [x] Execution History
- [x] Execution Detail
- [x] Execution duration
- [x] Historical Condition snapshot
- [x] Historical Action snapshot
- [x] IRT → تومان display without conversion

In progress:

- [ ] Execution Priority end-to-end validation
- [ ] Condition Range / Between
- [ ] Multiple Conditions
- [ ] Cross-Automation conflict analysis

---

# Known Limitations

The current MVP intentionally does **not** provide:

- Rollback / transaction semantics for Actions.
- Background job queue.
- Delayed execution.
- Retries.
- Nested condition groups.
- Advanced cross-Automation conflict resolution.
- Full visual Automation Builder.
- Large-scale dedicated database architecture.
- External integrations.

These are product roadmap items, not missing pieces of the current MVP definition.

---

# Product Roadmap

## Phase 1 — Execution Control

Current priority:

```text
1. Execution Priority
2. Condition Range / Between
3. Multiple Conditions
4. Cross-Automation Conflict Analysis
```

### Execution Priority

Make Automation order explicitly controlled by the user rather than database creation order.

### Between / Range

Add a first-class range operator for numeric Conditions:

```text
Order Total
between
1,000,000
and
5,000,000 تومان
```

This is preferable to forcing users to create two separate Automations just to describe a price range.

### Multiple Conditions

Target model:

```text
IF
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

---

# Phase 2 — Better WooCommerce Automation

Planned Conditions:

- Order Status.
- Payment Method.
- Shipping Method.
- Order Subtotal.
- Coupon.
- Customer Role.
- Customer Order Count.
- Customer Total Spent.
- Product.
- Category.
- SKU.
- Quantity.
- Stock.
- Billing / Shipping Country.

Planned Actions:

- Add Order Note.
- Modify Order Metadata.
- Apply Coupon.
- Customer Email.
- Additional Admin Email.
- Product / stock actions where safe.

Planned Triggers:

- Order Paid.
- Order Status Changed.
- Order Completed.
- Order Cancelled.
- Order Refunded.
- Product Created / Updated.
- Stock Changed.
- Customer Registered.

---

# Phase 3 — Professional Builder

Target UI:

```text
WHEN
[ Trigger ]

IF
[ Condition ]
[ + Add Condition ]

THEN
[ Action ]
[ + Add Action ]
```

The builder should support:

- Dynamic Trigger definitions.
- Registry-driven Conditions.
- Registry-driven Actions.
- Multiple Conditions.
- Multiple Actions.
- AND / OR groups.
- Ordering.
- Priority.
- Execution Policy.
- Conflict explanations.
- Human-readable summaries.

The builder should expose complexity progressively rather than overwhelming normal store administrators.

---

# Phase 4 — Execution Infrastructure

Future execution infrastructure may include:

```text
Scheduled Actions
Background Queue
Retry Policy
Exponential Backoff
Failure Queue
Execution Locks
Idempotency
```

Potential implementation technologies may include WP-Cron or an Action Scheduler-style queue, depending on final compatibility and scale requirements.

The architecture must avoid duplicate execution when the same trigger is delivered more than once.

---

# Phase 5 — Automation Intelligence

Potential future capabilities:

### Conflict Graph

Visualize which Automations can affect the same property.

```text
Automation A ──┐
               ├── Order Status
Automation B ──┘
```

### Automation Trace

Explain why an Automation did or did not run.

```text
Order #123

Automation #43
✓ Condition matched
✓ Action 1 succeeded
✓ Action 2 succeeded

Automation #68
✓ Condition matched
✕ Action 3 failed
→ Policy continued to next Automation
```

### Explainable Execution

Human-readable reasons should become a first-class feature rather than relying on technical logs.

---

# Phase 6 — Integrations

Potential integrations:

- Webhook.
- HTTP Request.
- REST API.
- Telegram.
- WhatsApp.
- Slack.
- Discord.
- Google Sheets.
- CRM systems.

These features require a stronger security model before implementation.

---

# Phase 7 — Developer Platform

Long-term platform ideas:

- Public Action API.
- Public Trigger API.
- Public Condition API.
- Developer hooks and filters.
- Extension packages.
- Integration SDK.
- Import / Export of Automation definitions.
- Automation templates.

The Registry architecture is intentionally designed to make this future possible without rewriting the core engine.

---

# Product Ideas for Future Versions

These are intentionally recorded so they remain visible but do not block MVP delivery.

## Automation Templates

Ready-to-use templates such as:

```text
High Value Order
Low Stock Alert
VIP Customer Order
Pending Payment Reminder
Wholesale Order Routing
```

A template should create a normal editable Automation, not a locked preset.

## Import / Export

Export Automation definitions to JSON and import them into another site.

This can eventually support:

```text
Backup
Migration
Template Marketplace
Agency Deployment
```

## Duplicate Detection

Detect near-duplicate Automations before users accidentally create several rules that do the same thing.

## Dry Run / Simulation

Let an administrator test an Automation against an existing Order without actually executing Actions:

```text
Simulation
✓ Trigger would match
✓ Conditions would pass
→ Action 1 would run
→ Action 2 would run
```

This would be especially valuable before enabling destructive or externally visible Actions.

## Safe Mode

A future Automation could support:

```text
Enabled
Disabled
Test / Dry Run
```

Test mode would evaluate Conditions and generate a planned result without applying external side effects.

## Idempotency / Duplicate Trigger Protection

Prevent repeated delivery of the same event from causing duplicate notifications or repeated state changes.

Potential model:

```text
Trigger Event ID
     ↓
Execution Key
     ↓
Already processed?
     ↓
Yes → Skip
No  → Execute
```

## Human-Readable Automation Summary

Automatically generate text such as:

```text
When an order is created,
if the total is between 1,000,000 and 5,000,000 تومان,
WooSmart changes the order to Processing and notifies the store administrator.
```

This would make complex configurations much easier to review.

## Safe Destructive Actions

Actions that can cause irreversible external effects should eventually declare metadata such as:

```text
safe
reversible
external_side_effect
requires_confirmation
```

This metadata could drive warnings and future Dry Run behavior.

---

# Important Architectural Decisions

## 1. WooCommerce Owns Currency

WooSmart reads WooCommerce currency context and does not create a parallel monetary system.

## 2. No Silent Rial / Toman Conversion

Display formatting must never silently change numeric values.

## 3. Registry-Driven Conditions and Actions

Conditions and Actions are defined centrally so new capabilities do not require duplicated logic across the engine, manager, and UI.

## 4. Execution Must Become Deterministic

The engine must explicitly know:

```text
Which Automation runs first
Which Action runs first
Whether execution continues
Whether execution stops
What failed
What succeeded
```

## 5. Policies Are Explicit

`all`, `first_match`, and `first_success` are intentional execution policies rather than accidental query behavior.

## 6. Priority Is Separate From Policy

Priority answers:

> “Which Automation gets evaluated first?”

Execution Policy answers:

> “When do we stop or continue?”

They must remain separate concepts.

## 7. Failed Actions Do Not Imply Rollback

WooSmart does not pretend that every Action is transactional.

Status changes may trigger external WooCommerce behavior, and email cannot be undone.

## 8. History Is a Snapshot

Execution History must preserve what was configured and what happened at the time of execution.

Later Automation edits must not rewrite historical truth.

## 9. Notification Transport Is Provider-Neutral

WooSmart uses `wp_mail()` and lets WordPress Mail Transport handle delivery.

## 10. Technical Logs and User-Facing History Are Different

Logger = technical diagnostics.

Execution History = explainable execution record.

Both are useful and should not be collapsed into one system.

---

# Development Rules

1. Review the current file before modifying it.
2. Preserve working behavior unless a change is intentional.
3. Provide complete changed files for local replacement.
4. Test major features with real WooCommerce orders.
5. Verify both UI behavior and execution behavior.
6. Check Logs and Execution History after execution tests.
7. Do not add future complexity before the current milestone is stable.
8. Do not assume old file versions are still current.
9. Keep internal identifiers in English.
10. Keep Persian Admin UI terminology consistent.
11. Do not make WooSmart dependent on a specific mail provider.
12. Do not introduce an independent currency conversion system.
13. Record meaningful architectural decisions here.
14. Keep postponed ideas documented instead of silently forgetting them.
15. Git commits should represent stable milestones.

---

# Release Strategy

The exact public version numbers may change, but a practical milestone structure is:

```text
1.0  Core MVP
1.1  Execution Control + Range Conditions
1.2  Multiple Conditions + More Order Rules
1.3  More Actions + More Triggers
1.4  Professional Builder
1.5  Templates + Import / Export
1.6  Scheduling + Retry Infrastructure
1.7  Integrations
2.0  Automation Platform
```

The commercial release for marketplaces such as **ژاکت** and **راست‌چین** should prioritize reliability, clear UX, compatibility, documentation, and supportability over feature count.

---

# Commercial Product Principles

WooSmart is intended to become a sellable WordPress / WooCommerce product.

Therefore the product should prioritize:

```text
Reliability
Predictability
Simple UX
Safe defaults
Clear error messages
Compatibility with WooCommerce
Compatibility with WordPress Mail Transport
Extensibility
Good diagnostics
Good documentation
```

A feature should not be considered valuable merely because it exists. The important question is:

> Can a normal store administrator understand it, configure it correctly, and know what happened when it runs?

---

# Current Next Step

The immediate next implementation milestone is:

```text
Execution Priority
```

Acceptance criteria:

- User can assign a Priority to an Automation.
- Lower Priority executes first.
- Equal Priority uses creation date as deterministic tie-breaker.
- `all`, `first_match`, and `first_success` all respect Priority.
- Execution History records the actual execution policy and Automation order.
- Existing Automations without explicit Priority remain compatible.
- Real WooCommerce execution confirms the ordering.

After Priority is verified:

```text
Priority
    ↓
Between / Range Condition
    ↓
Multiple Conditions
    ↓
More Conditions / Actions / Triggers
    ↓
Professional Builder
```

---

# Definition of Done for a Major Feature

A major feature is considered stable when:

```text
UI works
    AND
Data saves correctly
    AND
Validation works
    AND
Real WooCommerce execution works
    AND
Success is logged
    AND
Failure is logged
    AND
Execution History explains the result where applicable
    AND
Existing features still work
    AND
README reflects the real project state
```

---

# Final Vision

WooSmart should eventually become a visual automation platform for WooCommerce:

```text
WHEN
    something happens

IF
    conditions match

THEN
    perform actions
```

with a deterministic execution layer:

```text
Trigger
    ↓
Match
    ↓
Priority
    ↓
Policy
    ↓
Actions
    ↓
Results
    ↓
History
    ↓
Trace
```

The objective is a plugin that a non-technical store owner can use confidently while developers can extend through stable registries and APIs.

---

# README Status

This README is intentionally a **living technical document**.

It should describe:

- What is actually implemented.
- What has been actually tested.
- What is currently in development.
- What has been intentionally postponed.
- Why important architectural decisions were made.
- What the next milestone is.

Detailed day-to-day debugging notes, temporary test orders, and repetitive execution logs do not belong here unless they establish a durable architectural decision.
