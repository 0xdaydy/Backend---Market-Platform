# Domain Context

## Glossary

### CreditAccount
A value object that encapsulates all credit-related state and behavior for a `Farmer`. It is the **seam** where credit rules live. Callers should never mutate `credit_balance_fcfa` directly — they must go through `CreditAccount::charge()` or `CreditAccount::repay()`.

Key invariants:
- `balance()` = the farmer's current credit balance (positive = debt owed)
- `surplus()` = `max(0, -balance)` — credit surplus available to offset future charges
- `availableCredit()` = `max(0, limit - balance)` — remaining credit capacity
- `canCharge(amount)` = `availableCredit >= amount`

### Debt
A record created 1:1 with a credit `Transaction`. Tracks principal, interest rate, total due, amount repaid, balance, and status (`open` → `partially_paid` → `closed`).

### Transaction
An immutable purchase record with snapshot pricing via `TransactionItem`. Can be `cash` or `credit`. Credit transactions trigger debt creation.

### Repayment
A payment made by a farmer toward their debts. Can be `cash` or `commodity`. Allocated across open debts using FIFO ordering via `RepaymentAllocator`.

### Surplus
Excess repayment stored as a negative `credit_balance_fcfa`. Auto-applied to future credit transactions before checking limits.

## Architectural Decisions

### CreditAccount as Model Wrapper (ADR-001)
`CreditAccount` wraps a `Farmer` instance rather than being a standalone service. This gives callers a natural seam: `$farmer->creditAccount()->charge($amount)`. The alternative (stateless service) would require passing the farmer to every call, increasing interface complexity.

### Controller Consolidation (ADR-002)
Pure CRUD features (Catalog, Farmers, Users, Settings) use multi-method controllers instead of single-action invokable classes. This reduces file count from 35 to ~13 action/controller files while keeping complex business logic (StoreTransaction, StoreRepayment) in dedicated action classes.

### Action Classes for Complex Operations
StoreTransactionAction and StoreRepaymentAction remain as dedicated action classes because they orchestrate multiple services, database transactions, and business rules. Consolidating them would create God controllers.

### TransactionPricing Extraction (ADR-003)
Item pricing logic (product lookup, price mismatch detection, total calculation) was extracted into `TransactionPricing` and shared between `StoreTransactionAction` (online) and `OfflinePreValidator` (offline). This eliminates duplication of the item-pricing loop.

## Module Depth Map

| Module | Depth | Notes |
|--------|-------|-------|
| CreditAccount | Deep | 7 public methods hide balance arithmetic and persistence |
| TransactionEngine | Medium | Creates debt + delegates balance update to CreditAccount |
| RepaymentAllocator | Medium | FIFO allocation logic, delegates balance update to CreditAccount |
| TransactionPricing | Medium | Shared item pricing and price-change detection |
| OfflinePreValidator | Medium | Uses TransactionPricing + CreditAccount; no longer duplicates pricing logic |
| CatalogController | Shallow | Pure CRUD passthrough; acceptable for resource controllers |
| FarmerController | Shallow | Pure CRUD passthrough; acceptable for resource controllers |
