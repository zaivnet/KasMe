# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## 1. Project Overview

**Project Name:** Personal Finance Manager  
**Application Type:** Web Application  
**Primary Purpose:** Personal financial management  
**Deployment Target:** Shared Hosting / cPanel  
**Database:** MySQL / MariaDB  

The application is designed to help users manage personal finances in a simple, secure, structured, and auditable way.

The system must prioritize financial data integrity, usability, responsive design, and compatibility with conventional shared hosting.

---

## 2. Product Goals

The application must allow users to:

- Manage multiple financial accounts.
- Record income and expenses.
- Transfer funds between accounts.
- Categorize transactions.
- Monitor monthly cash flow.
- Create monthly budgets.
- Track bills and recurring expenses.
- Manage debts and receivables.
- Create saving goals.
- Review financial reports.
- Upload transaction attachments.
- Manage application preferences.
- Maintain reliable financial history.

---

## 3. Target Users

Primary users:

- Individuals managing personal finances.
- Families managing household finances.
- Freelancers managing simple personal cash flow.
- Users who need a lightweight alternative to complex accounting software.

The first release is focused on personal finance management and is not intended to replace full double-entry accounting software.

---

## 4. Core Modules

### 4.1 Authentication

Features:

- Login.
- Logout.
- Password reset.
- User profile.
- Secure session management.

All application data must be scoped to the authenticated user.

---

### 4.2 Dashboard

The dashboard should display:

- Total balance.
- Total income this month.
- Total expenses this month.
- Net cash flow.
- Budget utilization.
- Upcoming bills.
- Recent transactions.
- Income vs expense chart.
- Expense by category chart.
- Account summary.

No dummy statistics may be displayed.

---

### 4.3 Accounts

Users can create financial accounts such as:

- Cash.
- Bank account.
- E-Wallet.
- Savings.
- Credit card.
- Other account.

Features:

- Create account.
- Edit account metadata.
- Archive/deactivate account.
- View balance.
- View transaction history.
- Define opening balance when creating the account.

The balance must not be directly editable after account creation.

---

### 4.4 Categories

Two primary category types:

- Income.
- Expense.

Features:

- Create category.
- Edit category.
- Disable category.
- Optional parent/sub-category.
- Icon.
- Color.

---

### 4.5 Transactions

Transaction types:

- Income.
- Expense.
- Adjustment.

Features:

- Create transaction.
- Edit transaction.
- Soft-delete transaction.
- Date.
- Account.
- Category.
- Amount.
- Description.
- Attachment.

Rules:

- Amount is always stored as a positive value.
- Direction is determined by transaction type.
- Every monetary change must be recorded.
- Backend calculations are authoritative.

---

### 4.6 Transfers

Users can transfer funds between their own accounts.

Features:

- Source account.
- Destination account.
- Amount.
- Transfer fee.
- Date.
- Notes.

Rules:

- Source and destination accounts must be different.
- Transfer must be atomic.
- Source account decreases by amount + fee.
- Destination account increases by amount.
- Transfer must never create unbalanced money.

---

### 4.7 Budgets

Features:

- Monthly budget per expense category.
- Budget usage.
- Remaining budget.
- Percentage progress.
- Over-budget state.

Budget calculations must use actual transaction data.

---

### 4.8 Bills

Features:

- Bill name.
- Amount.
- Due date.
- Category.
- Recurrence.
- Notes.
- Payment status.

Supported recurrence:

- None.
- Weekly.
- Monthly.
- Yearly.

Statuses:

- Unpaid.
- Paid.
- Overdue.

---

### 4.9 Debts & Receivables

Features:

- Debt or receivable type.
- Person/entity name.
- Original amount.
- Remaining amount.
- Start date.
- Due date.
- Notes.
- Payment history.
- Status.

Statuses:

- Active.
- Paid.
- Overdue.

---

### 4.10 Saving Goals

Features:

- Goal name.
- Target amount.
- Current progress.
- Target date.
- Notes.
- Deposit history.
- Status.

Statuses:

- Active.
- Completed.
- Cancelled.

---

### 4.11 Reports

Reports should support:

- Daily.
- Weekly.
- Monthly.
- Yearly.
- Account filter.
- Category filter.
- Transaction type filter.
- Date range.

Possible future export:

- PDF.
- Excel/CSV.

Export is not mandatory for the initial MVP unless explicitly included in a sprint.

---

### 4.12 Settings

Settings may include:

- User profile.
- Currency.
- Timezone.
- Date format.
- Theme.
- Default dashboard preferences.

---

## 5. Functional Requirements

### FR-001
Users must only access their own financial data.

### FR-002
All monetary calculations must be performed server-side.

### FR-003
Financial records must retain an audit trail.

### FR-004
Financial transactions must not be hard-deleted by default.

### FR-005
Transfers involving multiple records must run inside database transactions.

### FR-006
The application must not depend on Node.js runtime in production.

### FR-007
The application must work on conventional shared hosting.

### FR-008
The application must be responsive on desktop, tablet, and mobile.

### FR-009
The application must not generate demo/dummy data unless explicitly requested.

### FR-010
Database changes must follow `SCHEMA.md`.

---

## 6. Non-Functional Requirements

### Performance

- Avoid unnecessary database queries.
- Use pagination for large transaction lists.
- Use eager loading where appropriate.
- Production assets must be compiled/minified.
- Avoid large unnecessary frontend libraries.

### Security

- CSRF protection.
- Server-side validation.
- Authorization checks.
- Password hashing.
- No exposed secrets.
- Safe file uploads.
- Proper session security.

### Reliability

- Use database transactions for critical financial operations.
- Roll back partial financial operations if any step fails.
- Keep schema changes reversible.

### Maintainability

- Follow Laravel conventions.
- Keep controllers thin.
- Move reusable business logic into dedicated services/actions when appropriate.
- Avoid duplicate logic.

---

## 7. Technology Constraints

Preferred stack:

- PHP.
- Laravel.
- Blade.
- Tailwind CSS.
- Alpine.js.
- Chart.js.
- MySQL / MariaDB.

Production must not require:

- Docker.
- Redis.
- MongoDB.
- Supervisor.
- PM2.
- WebSocket server.
- Node.js runtime.

---

## 8. MVP Scope

MVP includes:

1. Authentication.
2. Dashboard.
3. Accounts.
4. Categories.
5. Income & expenses.
6. Transfers.
7. Basic reports.
8. Settings.

Post-MVP:

9. Budgets.
10. Bills.
11. Debts & receivables.
12. Saving goals.
13. Export.
14. Advanced analytics.

---

## 9. Out of Scope

Unless explicitly requested:

- Cryptocurrency trading.
- Stock brokerage integration.
- Bank API aggregation.
- Tax reporting.
- Payroll.
- Full double-entry accounting.
- Multi-company accounting.
- Public payment gateway.
- ERP features.

---

## 10. Success Criteria

The application is considered successful when:

- Users can accurately record all financial activities.
- Account balances reconcile correctly with transactions.
- Transfers never create inconsistent balances.
- Reports match recorded transaction data.
- The system works reliably on shared hosting.
- UI remains usable on mobile and desktop.
- Existing features remain stable during future AI-assisted development.
