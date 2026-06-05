**Plan: Legacy-Source-to-Haipulse System Migration**

Risk-based recommendation: move shared platform first, then Shipping, then HR, then Accounting, then remaining CRM and Misc gaps. This reduces financial-risk exposure early, preserves momentum with a shipping pilot, and keeps your requirement intact to preserve the legacy source layout, styling, flow, UI/UX, and color behavior.

**Steps**
1. Phase 0: Scope freeze and complete inventory
2. Build a module baseline from legacy-source system groupings and pages, then map overlaps vs missing in haipulse.
3. Create a module migration ledger for every missing module with dependencies: pages, listing handlers, permissions, DB tables, reports, PDFs, email/cron hooks.
4. Lock in-scope modules and defer backups/experimental files so migration execution stays clean.

5. Phase 1: UI/UX parity foundation (blocking phase)
6. Extract the legacy-source dashboard shell contract (header, sidebar, cards, forms, table spacing, icon usage, active/hover behavior).
7. Implement a compatibility styling layer in haipulse so migrated modules look and behave like the legacy source.
8. Standardize reusable wrappers for page-header, action toolbar, DataTable containers, and overview sidebars.
9. Validate desktop and mobile sidebar/nav flow before first system move.

10. Phase 2: Platform compatibility and guardrails
11. Map the legacy module permission model into haipulse module/role permissions with full CRUD parity.
12. Register all incoming module slugs in haipulse module registry.
13. Keep lift-and-shift behavior, but convert DB/table usage immediately to haipulse naming/constants.
14. Add shared adapters for DataTables JSON, PDF generation, email hooks, and logging.

15. Phase 3: Shipping system migration (pilot wave)
16. Migrate shipping core: shipping advice/invoice/stock/customer modules and required logistics masters.
17. Migrate shipping report, import, and PDF pathways.
18. Validate end-to-end shipping flow: create, update, view, list, export/PDF, reporting.

19. Phase 4: HR system migration
20. Migrate employee core: users/employees, departments, designations, documents.
21. Migrate attendance, leave requests/types, and HR reporting.
22. Migrate payroll stack: components, salary structures, employee salaries, payroll runs, payslips.
23. Validate monthly HR cycle from attendance through payslip outputs.

24. Phase 5: Accounting system migration (high-risk wave)
25. Migrate in dependency order: masters first (accounts/COA, tax/payment/currency/units), then transaction chains.
26. Sales chain: quotations, sale orders, invoices, payments received, credit notes.
27. Purchase chain: vendors, expenses/purchases, payments made, debit/vendor credits.
28. Journals and financial reports with strict balancing and reconciliation checks after each batch.

29. Phase 6: CRM and Misc gap closure
30. Move remaining missing CRM modules from the legacy source (for example leads and related notes/attachments/logs, and any missing projects/jobs if confirmed by parity matrix).
31. Move remaining setup/misc modules not yet covered but required by migrated systems.
32. Align menu placement to your five system groups: CRM, Accounting, HR, Shipping, Misc.

33. Phase 7: Data migration and staged cutover
34. Run idempotent per-module data migrations with rollback checkpoints.
35. Migrate master data first, then transactions, then files/attachments.
36. Enable systems progressively via module toggles/permissions (not big-bang), in the agreed risk order.

37. Phase 8: hardening and decommission
38. Remove temporary adapters, optimize indexes for heavy lists and reports, and finalize runbooks.
39. Decommission redundant flash-only pathways inside haipulse after parity sign-off.

**Relevant files**
- legacy-source/dashboard/admin_elements/sidebar.php
- legacy-source/dashboard/admin_elements/admin_header.php
- legacy-source/dashboard/admin_elements/admin_footer.php
- legacy-source/dashboard/modules.php
- legacy-source/dashboard/listing_modules.php
- haipulse/dashboard/admin_elements/sidebar.php
- haipulse/dashboard/admin_elements/admin_header.php
- haipulse/dashboard/modules.php
- haipulse/dashboard/setup.php
- haipulse/classes/DB.php

**Verification**
1. Module parity checklist per migrated module: listing, create, edit, view, delete, filters, reports, PDF, email hooks (if applicable).
2. UI parity checks against legacy-source reference pages for colors, spacing, typography, nav states, and responsive behavior.
3. Permission matrix checks across system admin, superadmin, and role-scoped users.
4. Data integrity checks including accounting balancing and sampled transaction tracebacks.
5. Regression checks for existing haipulse modules outside migration scope.
6. Performance checks on high-volume DataTable/report pages.

**Locked decisions applied**
1. Migration style: full lift-and-shift.
2. DB strategy: immediate conversion to haipulse naming/constants.
3. Priority: risk-based sequence selected by me.

This full plan is now saved in session memory and ready for execution handoff.