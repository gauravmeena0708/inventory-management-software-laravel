# Task 11 Brief: Frontend UI/UX, Navigation & Dashboard Views

## Objective
Build modern, responsive, accessible Tailwind CSS + Alpine.js frontend views, collapsible sidebar layout, dashboard KPI widgets, asset management UI with type filters, consumable stock issuance modals, agreement/payment management screens, and view rendering tests in `tests/Feature/FrontendViewRenderTest.php`.

## Specific Requirements
1. **Layout & Navigation Components**:
   - `resources/views/layouts/app.blade.php`: Modern responsive HTML shell loading Tailwind CSS, Alpine.js, flash notifications, and topbar/sidebar components.
   - `resources/views/components/sidebar.blade.php`: Collapsible grouped navigation (Dashboard, IT Assets, Consumables & Stock, Agreements & Payments, Personnel, Master Data).
   - `resources/views/components/topbar.blade.php`: User profile display with `role` badge, global asset search input, and logout form.
   - `resources/views/components/flash.blade.php` or alerts for success/error messages.
2. **Dashboard (`resources/views/dashboard.blade.php`)**:
   - 4 KPI Stat Cards:
     - Total Assets (with In-Use vs In-Stock breakdown)
     - Expiring Agreements in 30 / 180 days
     - Low Stock Items (below min_quantity)
     - Pending & Overdue Payments
   - Action Center: Table listing urgent contract renewals and low-stock items with 1-click action buttons.
   - Live Activity Feed: Stream of recent audit logs (`spatie/laravel-activitylog`).
3. **Asset Views (`resources/views/assets/`)**:
   - `index.blade.php`:
     - Filter tabs/chips: All, Desktops, Laptops, Servers, Switches, Storage.
     - Search & status filters.
     - Excel export button (`route('assets.export')`).
     - Data table displaying Asset Tag, Name, Type, Status badge, Serial Number, Assignee, Location, and Action menu.
   - `create.blade.php` & `edit.blade.php`: Form with dynamic type-specific fields (processor, RAM, storage, IP address, MAC address, contract dates, warranty expiry).
   - `show.blade.php`: Comprehensive asset overview, specifications card, assignment history timeline, and quick "Assign" / "Return" / "Decommission" action modals.
4. **Consumable & Stock Views (`resources/views/consumables/` & `resources/views/stock/`)**:
   - `consumables/index.blade.php`: Stock levels with progress bars / color indicators, Low stock warnings, and "Issue Stock" / "Restock" modal triggers.
   - `stock/index.blade.php`: Immutable ledger list showing Date, Item, Type badge (Purchase / Issue / Adjustment), Quantity, Stock After, Recipient, Recorded By, Remarks.
5. **Agreements & Payments Views (`resources/views/agreements/` & `resources/views/payments/`)**:
   - `agreements/index.blade.php`: Contracts list with annual costs, expiry warning tags, and Excel export.
   - `agreements/show.blade.php`: Contract details, attached files, and linked payment schedule milestone list.
   - `payments/index.blade.php`: Scheduled payments list with status filter (Pending, Completed, Overdue), and "Mark as Paid" action modal.
6. **Authentication Views (`resources/views/auth/`)**:
   - `auth/login.blade.php`: Clean login form with CSRF token and validation error alerts.
7. **Tests (`tests/Feature/FrontendViewRenderTest.php`)**:
   - Test dashboard renders all KPI counters and recent activity feed.
   - Test asset index renders asset items, type tabs, and status badges.
   - Test consumable index renders stock balances and entries.
   - Test agreement index and payment index render data tables.
   - Test authentication views render successfully.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-11-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
