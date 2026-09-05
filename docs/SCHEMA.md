# DATABASE SCHEMA

## 1. Database Standard

Database engine:

- MySQL / MariaDB.
- InnoDB.
- UTF8MB4.

Primary key convention:

```text
BIGINT UNSIGNED AUTO_INCREMENT
```

Money convention:

```text
DECIMAL(18,2)
```

Never use FLOAT or DOUBLE for monetary values.

---

# users

Purpose:

Stores authenticated application users.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| name | VARCHAR(255) | required |
| email | VARCHAR(255) | required, unique |
| email_verified_at | TIMESTAMP | nullable |
| password | VARCHAR(255) | required |
| remember_token | VARCHAR(100) | nullable |
| created_at | TIMESTAMP | Laravel default |
| updated_at | TIMESTAMP | Laravel default |

---

# accounts

Purpose:

Stores user-owned financial accounts.

Examples:

- Cash.
- Bank.
- E-Wallet.
- Savings.
- Credit Card.
- Other.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| name | VARCHAR(100) | required |
| type | VARCHAR(30) | required |
| opening_balance | DECIMAL(18,2) | default 0 |
| currency | VARCHAR(10) | default IDR |
| icon | VARCHAR(100) | nullable |
| color | VARCHAR(20) | nullable |
| is_active | BOOLEAN | default true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Allowed `type` values:

```text
cash
bank
ewallet
savings
credit_card
other
```

Relationships:

```text
users 1 --- N accounts
```

Rules:

- Account belongs to one user.
- Opening balance is established at creation.
- Balance must not be directly edited as a normal field.
- Financial corrections should use adjustment transactions.

---

# categories

Purpose:

Stores income and expense categories.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| parent_id | BIGINT UNSIGNED | nullable self FK |
| name | VARCHAR(100) | required |
| type | VARCHAR(20) | required |
| icon | VARCHAR(100) | nullable |
| color | VARCHAR(20) | nullable |
| is_active | BOOLEAN | default true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Allowed `type` values:

```text
income
expense
```

Relationships:

```text
users 1 --- N categories
categories 1 --- N child_categories
```

---

# transactions

Purpose:

Primary ledger records for income, expense, and explicit adjustments.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| account_id | BIGINT UNSIGNED | FK accounts.id |
| category_id | BIGINT UNSIGNED | nullable FK categories.id |
| type | VARCHAR(20) | required |
| adjustment_direction | VARCHAR(10) | nullable |
| amount | DECIMAL(18,2) | required, > 0 |
| transaction_date | DATE | required |
| description | TEXT | nullable |
| attachment | VARCHAR(255) | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Allowed `type`:

```text
income
expense
adjustment
```

Allowed `adjustment_direction`:

```text
increase
decrease
```

Rules:

- `amount` is stored as positive.
- `income` adds balance.
- `expense` subtracts balance.
- `adjustment` requires `adjustment_direction`.
- `income` and `expense` should have `adjustment_direction = NULL`.
- Ownership between user, account, and category must match.
- Soft delete is preferred.

Relationships:

```text
users      1 --- N transactions
accounts   1 --- N transactions
categories 1 --- N transactions
```

Recommended indexes:

```text
(user_id, transaction_date)
(account_id, transaction_date)
(category_id, transaction_date)
(type, transaction_date)
```

---

# transfers

Purpose:

Represents movement of money between two user-owned accounts.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| from_account_id | BIGINT UNSIGNED | FK accounts.id |
| to_account_id | BIGINT UNSIGNED | FK accounts.id |
| amount | DECIMAL(18,2) | required, > 0 |
| fee | DECIMAL(18,2) | default 0 |
| transfer_date | DATE | required |
| description | TEXT | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Rules:

```text
from_account_id != to_account_id
amount > 0
fee >= 0
```

Financial effect:

```text
Source:
-(amount + fee)

Destination:
+amount
```

The transfer operation must run inside a database transaction.

Both accounts must belong to the same authenticated user.

Recommended indexes:

```text
(user_id, transfer_date)
(from_account_id, transfer_date)
(to_account_id, transfer_date)
```

---

# budgets

Purpose:

Monthly budget targets per expense category.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| category_id | BIGINT UNSIGNED | FK categories.id |
| amount | DECIMAL(18,2) | required |
| month | TINYINT UNSIGNED | 1-12 |
| year | SMALLINT UNSIGNED | required |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Unique constraint:

```text
(user_id, category_id, month, year)
```

Rules:

- Category must belong to user.
- Category should be expense type.
- Budget usage is calculated from actual expense transactions.

---

# bills

Purpose:

Tracks upcoming and recurring bills.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| category_id | BIGINT UNSIGNED | nullable FK categories.id |
| name | VARCHAR(150) | required |
| amount | DECIMAL(18,2) | required |
| due_date | DATE | required |
| recurrence | VARCHAR(20) | required |
| status | VARCHAR(20) | required |
| notes | TEXT | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Allowed recurrence:

```text
none
weekly
monthly
yearly
```

Allowed status:

```text
unpaid
paid
overdue
```

---

# debts

Purpose:

Tracks money the user owes or money owed to the user.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| type | VARCHAR(20) | required |
| person_name | VARCHAR(150) | required |
| original_amount | DECIMAL(18,2) | required |
| remaining_amount | DECIMAL(18,2) | required |
| start_date | DATE | required |
| due_date | DATE | nullable |
| status | VARCHAR(20) | required |
| notes | TEXT | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Allowed type:

```text
debt
receivable
```

Allowed status:

```text
active
paid
overdue
```

Rules:

```text
original_amount > 0
remaining_amount >= 0
remaining_amount <= original_amount
```

---

# debt_payments

Purpose:

Tracks installment/payment history against a debt or receivable.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| debt_id | BIGINT UNSIGNED | FK debts.id |
| account_id | BIGINT UNSIGNED | FK accounts.id |
| amount | DECIMAL(18,2) | required |
| payment_date | DATE | required |
| notes | TEXT | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Rules:

- Payment must belong to the same user as the debt/account.
- Payment amount must be positive.
- Payment must not exceed remaining amount unless explicitly designed later.
- Payment operation should update debt state atomically.

---

# saving_goals

Purpose:

Stores personal saving targets.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id |
| name | VARCHAR(150) | required |
| target_amount | DECIMAL(18,2) | required |
| target_date | DATE | nullable |
| description | TEXT | nullable |
| status | VARCHAR(20) | required |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP | nullable |

Allowed status:

```text
active
completed
cancelled
```

---

# saving_goal_transactions

Purpose:

Tracks contributions and withdrawals against a saving goal.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| saving_goal_id | BIGINT UNSIGNED | FK saving_goals.id |
| account_id | BIGINT UNSIGNED | FK accounts.id |
| type | VARCHAR(20) | required |
| amount | DECIMAL(18,2) | required |
| transaction_date | DATE | required |
| notes | TEXT | nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Allowed type:

```text
contribution
withdrawal
```

---

# settings

Purpose:

Stores user-specific application preferences.

Columns:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK users.id, unique |
| currency | VARCHAR(10) | default IDR |
| date_format | VARCHAR(30) | default d M Y |
| timezone | VARCHAR(100) | default Asia/Jakarta |
| theme | VARCHAR(20) | default system |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Allowed theme:

```text
light
dark
system
```

---

# ACCOUNT BALANCE FORMULA

Conceptual balance:

```text
opening_balance
+ income
- expense
+ positive_adjustments
- negative_adjustments
+ incoming_transfers
- outgoing_transfers
- transfer_fees
- debt payments
+ receivable payments
- saving goal contributions
+ saving goal withdrawals
```

The database schema must support reconciliation from source records.

---

# FINANCIAL DATA PRINCIPLES

1. Transactions are authoritative financial records.
2. Transfers must be atomic.
3. Monetary values use DECIMAL.
4. Frontend is not authoritative for balances.
5. Financial history must not disappear silently.
6. Ownership must be enforced on every user-owned record.
7. Structural changes require `SCHEMA.md` updates.
8. Database migrations must remain reversible.
9. Reports must derive from real persisted financial data.
10. Dummy financial data must not be inserted into production.
