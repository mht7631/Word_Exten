# WooSmart Automation

WooSmart Automation is a WordPress / WooCommerce automation plugin designed to allow store administrators to create rule-based automations without writing code.

The long-term goal is to turn WooSmart Automation into a complete, extensible, reliable, and user-friendly automation engine for WooCommerce stores.

---

# Project Vision

The main idea behind WooSmart Automation is simple:

> WHEN something happens  
> IF certain conditions are satisfied  
> THEN perform one or more actions

For example:

    WHEN
    An order is created

    IF
    Order total is greater than 1,000,000

    THEN
    Change order status to Processing

The system should eventually allow complex automation workflows such as:

    WHEN
    An order is created

    IF
    Order total > 5,000,000
    AND
    Payment method = Bank Transfer

    THEN
    Change order status to Processing
    AND
    Add an order note
    AND
    Send an email
    AND
    Send a webhook

The goal is to make these automations configurable entirely from the WordPress admin panel.

---

# Current Project Status

Version:

    1.0.0

Current stage:

    MVP / Foundation

Current development phase:

    Phase 1 - Complete the basic automation workflow

Current immediate goal:

    Build and test a complete End-to-End automation:

    Trigger
        ↓
    Conditions
        ↓
    Actions
        ↓
    Execution
        ↓
    Logging

---

# Current Architecture

The current plugin uses a modular architecture.

Main components:

    WooSmart Core
        ↓
    Admin Interface
        ↓
    Automation Manager
        ↓
    Automation / Post Type
        ↓
    Trigger System
        ↓
    Execution Engine
        ↓
    Condition Engine
        ↓
    Action Engine
        ↓
    Logger

---

# Current File Structure

    woosmart-automation/
    │
    ├── woosmart-automation.php
    │
    └── includes/
        │
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

---

# File Responsibilities

## woosmart-automation.php

Main plugin bootstrap.

Responsibilities:

- Define the plugin entry point.
- Load required classes.
- Initialize WooSmart components.
- Handle plugin activation.

---

## class-woosmart-core.php

Core plugin functionality.

Responsibilities:

- Check whether WooCommerce is active.
- Display WooCommerce dependency warnings.
- Provide WooCommerce availability status.

---

## class-woosmart-logger.php

Central logging system.

Responsibilities:

- Store automation events.
- Store execution context.
- Retrieve logs.
- Clear logs.
- Keep the latest 100 log entries in the current MVP implementation.

Current storage:

    WordPress Options

Future storage:

    Custom database table

---

## class-woosmart-admin.php

WordPress admin interface.

Current sections:

- Dashboard
- Automations
- Add Automation
- Logs

Responsibilities:

- Display automations.
- Create/edit automation forms.
- Display automation status.
- Toggle automation status.
- Duplicate automations.
- Delete automations.
- Display logs.

---

## class-woosmart-automation.php

Automation system placeholder / foundation.

Currently this class contains the basic automation initialization structure.

Future responsibilities may include:

- Automation lifecycle management.
- Automation registry.
- Shared automation services.
- Automation-level events.

---

## class-woosmart-triggers.php

WooCommerce trigger system.

Current trigger:

    order_created

Current WooCommerce hook:

    woocommerce_new_order

Future triggers will be added here.

---

## class-woosmart-post-types.php

Registers the internal Automation post type.

Post type:

    woosmart_automation

The WordPress default UI is intentionally hidden because WooSmart provides its own administration interface.

---

## class-woosmart-automation-manager.php

Handles automation CRUD operations.

Current operations:

- Create
- Update
- Enable / Disable
- Delete
- Duplicate

Automation metadata currently includes:

    _woosmart_status
    _woosmart_trigger
    _woosmart_conditions
    _woosmart_actions

---

## class-woosmart-condition-engine.php

Evaluates automation conditions.

Current field:

    order_total

Current operators:

    is_equal
    is_not_equal
    greater_than
    greater_than_or_equal
    less_than
    less_than_or_equal

Current logic:

    All configured conditions must pass.

Future versions will support multiple conditions and logical groups such as:

    AND
    OR
    nested condition groups

---

## class-woosmart-action-engine.php

Executes automation actions.

Current action:

    change_order_status

The action receives execution context and performs the requested WooCommerce operation.

Future versions will support multiple action types.

---

## class-woosmart-execution-engine.php

The central automation execution engine.

Responsibilities:

1. Receive a trigger.
2. Find active automations matching the trigger.
3. Load automation conditions.
4. Evaluate conditions.
5. Load actions.
6. Execute actions.
7. Log the result.

Current execution flow:

    Trigger
       ↓
    Find active automations
       ↓
    Evaluate conditions
       ↓
    Execute actions
       ↓
    Log execution

---

# Current Data Model

Each automation is currently represented by a WordPress Custom Post Type:

    woosmart_automation

Basic structure:

    Automation
    │
    ├── Title
    │
    ├── Status
    │
    ├── Trigger
    │
    ├── Conditions[]
    │
    └── Actions[]

Example:

    Automation:
        VIP Order

    Status:
        active

    Trigger:
        order_created

    Conditions:
        [
            {
                field: order_total,
                operator: greater_than,
                value: 1000000
            }
        ]

    Actions:
        [
            {
                type: change_order_status,
                status: processing
            }
        ]

---

# Current End-to-End Example

The intended first complete automation is:

    Automation Name:
        VIP Order

    Trigger:
        Order Created

    Condition:
        Order Total > 1,000,000

    Action:
        Change Order Status → Processing

Expected execution:

    Customer places order
            ↓
    WooCommerce creates order
            ↓
    woocommerce_new_order
            ↓
    WooSmart Trigger
            ↓
    Execution Engine
            ↓
    Find active automation
            ↓
    Condition Engine
            ↓
    Order Total > 1,000,000 ?
            ↓
        YES / NO
            ↓
        YES
            ↓
    Action Engine
            ↓
    Change status → Processing
            ↓
    Logger
            ↓
    Execution recorded

---

# What Has Already Been Implemented

## Foundation

- [x] Plugin bootstrap
- [x] Plugin activation hook
- [x] WooCommerce dependency detection
- [x] WooCommerce admin notice
- [x] Internal automation Custom Post Type

## Admin

- [x] WooSmart admin menu
- [x] Dashboard
- [x] Automation list
- [x] Add automation page
- [x] Edit automation page
- [x] Enable / Disable automation
- [x] Duplicate automation
- [x] Delete automation
- [x] Logs page

## Automation Management

- [x] Create automation
- [x] Update automation
- [x] Toggle automation
- [x] Duplicate automation
- [x] Delete automation
- [x] Store trigger
- [x] Store conditions
- [x] Store actions structure

## Trigger System

- [x] Trigger engine foundation
- [x] Order Created trigger

## Condition System

- [x] Condition engine
- [x] Order Total field
- [x] Equal comparison
- [x] Not Equal comparison
- [x] Greater Than
- [x] Greater Than or Equal
- [x] Less Than
- [x] Less Than or Equal

## Action System

- [x] Action engine
- [x] Change Order Status action

## Execution

- [x] Automation lookup
- [x] Active automation filtering
- [x] Trigger matching
- [x] Condition evaluation
- [x] Action execution
- [x] Execution logging

## Logging

- [x] Event logging
- [x] Context logging
- [x] Log viewer
- [x] Maximum 100 logs in MVP

---

# Current Known Limitations

The current version is intentionally an MVP.

Important limitations:

## 1. Action UI is incomplete

The backend supports:

    change_order_status

but the current admin automation builder does not yet provide a complete Action configuration interface.

This is the first major UI gap to fix.

---

## 2. Only one trigger

Current:

    order_created

More WooCommerce triggers will be added later.

---

## 3. Only one condition field

Current:

    order_total

More fields will be added.

---

## 4. Condition groups are not supported

Current behavior:

    Condition 1
    AND
    Condition 2
    AND
    Condition 3

There is no advanced:

    AND / OR

grouping yet.

---

## 5. Only one action type

Current:

    change_order_status

Multiple actions are planned.

---

## 6. No advanced execution history

Current logs are general plugin logs.

Future versions will have dedicated execution records.

---

## 7. Logger uses WordPress Options

This is acceptable for the MVP.

For a production-scale automation platform, logs should eventually move to a dedicated database table.

---

## 8. Dependency architecture is still basic

Some classes currently instantiate their dependencies directly.

A future refactor may introduce a cleaner dependency/service architecture.

---

# Development Roadmap

The project will be developed in controlled phases.

---

# Phase 1 - Complete the MVP

Goal:

Create one complete automation from the WordPress admin and execute it successfully.

Tasks:

- [ ] Add Action UI
- [ ] Add Change Order Status selector
- [ ] Load existing Action during Edit
- [ ] Display Actions in Automation list
- [ ] Validate Action data
- [ ] Test Create Automation
- [ ] Test Edit Automation
- [ ] Test Enable / Disable
- [ ] Test Duplicate
- [ ] Test Delete
- [ ] Test actual WooCommerce order creation
- [ ] Verify condition execution
- [ ] Verify action execution
- [ ] Verify logs

Target result:

    Order Created
        +
    Order Total > X
        ↓
    Change Order Status
        ↓
    Log

---

# Phase 2 - Improve the Automation Builder

Goal:

Turn the basic form into a proper automation builder.

Planned UI:

    WHEN
    [ Trigger ]

    IF
    [ Field ] [ Operator ] [ Value ]

    THEN
    [ Action ] [ Configuration ]

Features:

- [ ] Better UI
- [ ] Dynamic fields
- [ ] Dynamic operators
- [ ] Dynamic action configuration
- [ ] Add/remove conditions
- [ ] Add/remove actions
- [ ] Better validation
- [ ] Human-readable summaries

---

# Phase 3 - Multiple Conditions

Goal:

Allow users to create more powerful rules.

Example:

    Order Total > 5,000,000
    AND
    Payment Method = Bank Transfer
    AND
    Customer Role = Wholesale

Features:

- [ ] Multiple conditions
- [ ] Add condition
- [ ] Remove condition
- [ ] AND groups
- [ ] OR groups
- [ ] Nested condition groups
- [ ] Condition validation

Target structure:

    Conditions
    │
    ├── Group
    │   ├── Condition
    │   ├── Condition
    │   └── Condition
    │
    └── Group
        ├── Condition
        └── Condition

---

# Phase 4 - More WooCommerce Triggers

Planned triggers include:

## Orders

- [ ] Order Created
- [ ] Order Status Changed
- [ ] Order Paid
- [ ] Order Completed
- [ ] Order Cancelled
- [ ] Order Failed
- [ ] Order Refunded
- [ ] Order On Hold

## Customers

- [ ] Customer Registered
- [ ] Customer Login
- [ ] Customer Role Changed

## Products

- [ ] Product Created
- [ ] Product Updated
- [ ] Product Stock Changed
- [ ] Product Becomes Out of Stock
- [ ] Product Becomes In Stock

## Cart / Checkout

- [ ] Cart Updated
- [ ] Checkout Started
- [ ] Checkout Completed
- [ ] Abandoned Cart

Some of these may require additional modules or scheduled processing.

---

# Phase 5 - More Conditions

Planned condition fields include:

## Order

- [ ] Order Total
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

## Customer

- [ ] Customer Role
- [ ] Customer Email
- [ ] Customer Total Spent
- [ ] Customer Order Count
- [ ] Customer Registration Date

## Product

- [ ] Product Price
- [ ] Stock Quantity
- [ ] Stock Status
- [ ] Category
- [ ] SKU
- [ ] Product Type

---

# Phase 6 - More Actions

Planned actions:

## Order Actions

- [ ] Change Order Status
- [ ] Add Order Note
- [ ] Remove Order Item
- [ ] Add Order Item
- [ ] Change Order Metadata
- [ ] Apply Coupon

## Customer Actions

- [ ] Change Customer Role
- [ ] Add Customer Note
- [ ] Update Customer Metadata

## Communication

- [ ] Send Email
- [ ] Send SMS
- [ ] Send WhatsApp message
- [ ] Send Admin Notification

## External Integrations

- [ ] Webhook
- [ ] HTTP Request
- [ ] REST API
- [ ] Telegram
- [ ] Slack
- [ ] Discord

---

# Phase 7 - Scheduling and Delayed Actions

The system should eventually support delayed execution.

Examples:

    WHEN
    Order Created

    THEN
    Wait 24 hours

    THEN
    Send Email

Or:

    WHEN
    Order Created

    IF
    Payment Pending

    THEN
    Wait 2 hours

    THEN
    Send reminder

Features:

- [ ] Delayed actions
- [ ] Scheduled actions
- [ ] WP-Cron integration
- [ ] Action queue
- [ ] Retry system
- [ ] Failed job handling
- [ ] Job cancellation

For larger installations, a more robust queue system may be introduced.

---

# Phase 8 - Execution History

A dedicated execution system will be added.

Each execution should have:

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

Possible statuses:

    pending
    running
    completed
    failed
    skipped
    cancelled

This will allow administrators to see exactly what happened.

---

# Phase 9 - Error Handling and Retry System

The production version should not silently fail.

Planned features:

- [ ] Structured errors
- [ ] Action-level errors
- [ ] Automation-level errors
- [ ] Retry count
- [ ] Retry delay
- [ ] Failed execution queue
- [ ] Error notifications
- [ ] Debug mode
- [ ] Detailed execution logs

Example:

    Automation:
        VIP Order

    Execution:
        #1842

    Status:
        Failed

    Action:
        Send Email

    Error:
        SMTP connection failed

    Retry:
        2 / 5

---

# Phase 10 - Performance and Scalability

The plugin should eventually support stores with large numbers of automations and orders.

Planned improvements:

- [ ] Reduce unnecessary database queries
- [ ] Cache automation configuration
- [ ] Optimize automation lookup
- [ ] Optimize condition evaluation
- [ ] Optimize action execution
- [ ] Dedicated log table
- [ ] Execution queue
- [ ] Background processing
- [ ] Avoid loading all automations when unnecessary

The architecture should remain modular and scalable.

---

# Phase 11 - Security

Security is a core requirement.

All admin actions must use:

- [x] Capability checks
- [x] Nonces
- [x] Input sanitization
- [x] Output escaping

Future security review:

- [ ] REST API authentication
- [ ] Webhook authentication
- [ ] Permission separation
- [ ] Action-level permission checks
- [ ] SSRF protection for HTTP/Webhook actions
- [ ] Secure credential storage
- [ ] Sensitive data protection

---

# Phase 12 - Professional Admin UI

The final interface should be easier to use than the current MVP.

Possible builder:

    ┌─────────────────────────────────────────┐
    │ Create Automation                       │
    ├─────────────────────────────────────────┤
    │                                         │
    │ Automation Name                         │
    │ [ VIP Customer Order                  ]  │
    │                                         │
    │ WHEN                                    │
    │ [ Order Created                      ▼]  │
    │                                         │
    │ IF                                      │
    │ [ Order Total ▼] [ Greater Than ▼]      │
    │ [ 5000000                              ] │
    │                                         │
    │ [+ Add Condition]                       │
    │                                         │
    │ THEN                                    │
    │ [ Change Order Status ▼]                │
    │ [ Processing ▼]                         │
    │                                         │
    │ [+ Add Action]                           │
    │                                         │
    │ [ Save Automation ]                     │
    │                                         │
    └─────────────────────────────────────────┘

Eventually the builder may become a visual workflow editor.

---

# Phase 13 - Automation Templates

The plugin may provide predefined automation templates.

Examples:

## High Value Order

    WHEN Order Created
    IF Order Total > 5,000,000
    THEN Change Status → Processing

## Failed Payment Notification

    WHEN Payment Failed
    THEN Send Email

## Low Stock Alert

    WHEN Product Stock Changes
    IF Stock < 5
    THEN Send Admin Notification

Templates should be installable with one click.

---

# Phase 14 - Integrations

Long-term integrations may include:

- [ ] WooCommerce
- [ ] WordPress
- [ ] Email providers
- [ ] SMS providers
- [ ] WhatsApp
- [ ] Telegram
- [ ] Slack
- [ ] Discord
- [ ] Google Sheets
- [ ] CRM systems
- [ ] External REST APIs
- [ ] Webhooks

Integrations should be modular so that the core plugin does not become unnecessarily dependent on external services.

---

# Phase 15 - Developer API

A professional automation plugin should eventually provide extension points for developers.

Potential APIs:

    Register Trigger
    Register Condition
    Register Operator
    Register Action
    Register Integration

Example concept:

    woosmart_register_trigger()

    woosmart_register_condition()

    woosmart_register_action()

This will allow third-party extensions to add functionality without modifying the core plugin.

---

# Phase 16 - Testing

Testing will be introduced progressively.

## Unit Tests

Test:

- [ ] Condition engine
- [ ] Operator comparison
- [ ] Action engine
- [ ] Trigger handling
- [ ] Automation manager

## Integration Tests

Test:

- [ ] WooCommerce order creation
- [ ] Trigger execution
- [ ] Condition evaluation
- [ ] Action execution
- [ ] Logging

## Security Tests

Test:

- [ ] Unauthorized admin requests
- [ ] Invalid nonce
- [ ] Invalid automation ID
- [ ] Invalid action
- [ ] Invalid condition
- [ ] Malicious input

## Regression Tests

Every major feature should be tested against existing functionality.

---

# Phase 17 - Database Architecture

Current:

    WordPress Custom Post Type
    +
    Post Meta
    +
    WordPress Options

Future:

    Automations
    Executions
    Logs
    Scheduled Jobs

Potential tables:

    wp_woosmart_automations
    wp_woosmart_executions
    wp_woosmart_logs
    wp_woosmart_jobs

The exact database architecture will be decided after the MVP and performance requirements become clearer.

---

# Phase 18 - Licensing and Monetization

Once the core product becomes stable, possible editions may be introduced.

## Free

Basic automation functionality.

## Pro

Advanced:

- Multiple conditions
- Multiple actions
- Scheduling
- Webhooks
- Advanced triggers
- Advanced actions
- Execution history

## Enterprise

Potential features:

- Advanced integrations
- High-volume execution
- Priority support
- Multi-site features
- Advanced monitoring

This phase is intentionally postponed until the core product is stable.

---

# Development Principles

The project should follow these principles.

## 1. Modular Architecture

Each major responsibility should have its own class/module.

---

## 2. Do Not Break Existing Features

Every new feature should preserve existing functionality.

---

## 3. Incremental Development

Do not implement many unrelated features simultaneously.

Each phase should be completed and tested before moving to the next.

---

## 4. End-to-End Validation

Whenever a core feature is implemented, it should be tested through the actual WooCommerce workflow.

Example:

    Real WooCommerce Order
        ↓
    Real Trigger
        ↓
    Real Automation
        ↓
    Real Condition
        ↓
    Real Action
        ↓
    Real Log

---

## 5. Full File Replacement During Development

During collaborative development, when a file needs modification, the complete file should be provided instead of isolated code fragments.

This reduces accidental merge errors and makes file replacement safer.

---

## 6. Git as Source of Truth

The GitHub repository is the primary project source.

Repository:

    https://github.com/mht7631/Word_Exten/tree/main/woosmart-automation

All major milestones should be committed.

Suggested commit naming:

    v1.0.0 - Initial MVP
    v1.1.0 - Complete Action UI
    v1.2.0 - Multiple Conditions
    v1.3.0 - Multiple Actions
    v1.4.0 - Additional Triggers
    v1.5.0 - Execution History

---

# Versioning Strategy

Semantic versioning will be used:

    MAJOR.MINOR.PATCH

Example:

    1.0.0

Major:

    Breaking architecture or API changes.

Minor:

    New functionality without breaking existing functionality.

Patch:

    Bug fixes and small improvements.

---

# Current Development Plan

The immediate development sequence is:

    STEP 1
    Complete Action UI
        ↓
    STEP 2
    Save Action configuration
        ↓
    STEP 3
    Load Action configuration during Edit
        ↓
    STEP 4
    Display Actions in Automation list
        ↓
    STEP 5
    Create a real automation
        ↓
    STEP 6
    Create a real WooCommerce order
        ↓
    STEP 7
    Verify Trigger
        ↓
    STEP 8
    Verify Condition
        ↓
    STEP 9
    Verify Action
        ↓
    STEP 10
    Verify Logs
        ↓
    STEP 11
    Commit stable version
        ↓
    STEP 12
    Start Phase 2

---

# First Target Milestone

The first important milestone is:

## WooSmart Automation v1.1.0

A complete working automation should be possible:

    WHEN
    Order Created

    IF
    Order Total > X

    THEN
    Change Order Status → Processing

The automation must be configurable from the WordPress admin and must work against a real WooCommerce order.

Success criteria:

- [ ] Automation can be created
- [ ] Trigger is saved correctly
- [ ] Condition is saved correctly
- [ ] Action is saved correctly
- [ ] Automation can be edited
- [ ] Automation can be enabled/disabled
- [ ] Automation executes
- [ ] Order status changes
- [ ] Execution is logged
- [ ] Failed executions are logged
- [ ] No PHP fatal errors
- [ ] Existing admin functionality continues to work

---

# Long-Term Goal

The final goal is to transform WooSmart Automation from a basic automation plugin into a complete WooCommerce automation platform.

The final concept:

    ┌─────────────────────────────────────────────┐
    │             WooSmart Automation             │
    ├─────────────────────────────────────────────┤
    │                                             │
    │  TRIGGERS                                   │
    │      ↓                                      │
    │  CONDITIONS                                 │
    │      ↓                                      │
    │  LOGICAL GROUPS                             │
    │      ↓                                      │
    │  ACTIONS                                    │
    │      ↓                                      │
    │  DELAYS / SCHEDULES                         │
    │      ↓                                      │
    │  EXECUTION QUEUE                            │
    │      ↓                                      │
    │  RETRIES                                    │
    │      ↓                                      │
    │  EXECUTION HISTORY                          │
    │      ↓                                      │
    │  LOGGING / MONITORING                       │
    │                                             │
    └─────────────────────────────────────────────┘

The system should eventually allow store owners to automate complex WooCommerce workflows without programming.

---

# Project Status Legend

    [x] Completed
    [ ] Planned
    🟢 Stable
    🟡 MVP / Needs Improvement
    🔴 Not Implemented

---

# Current Status Summary

    Core:
        🟢 Implemented

    Admin:
        🟡 MVP

    Trigger Engine:
        🟡 One trigger

    Condition Engine:
        🟡 Basic

    Action Engine:
        🟡 One action

    Execution Engine:
        🟢 Functional foundation

    Logger:
        🟡 MVP

    Automation Builder:
        🟡 Incomplete

    Scheduling:
        🔴 Not implemented

    Execution History:
        🔴 Not implemented

    Integrations:
        🔴 Not implemented

    Developer API:
        🔴 Not implemented

    Automated Tests:
        🔴 Not implemented

---

# Development Rule

Do not rush to implement all planned features at once.

The project should evolve in controlled stages:

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
    Next Feature

The priority is not simply adding features.

The priority is building a stable automation engine that can grow into a professional WooCommerce automation platform.

---

# End
