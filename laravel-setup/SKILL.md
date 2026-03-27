# SKILL: Laravel Setup — Sales, Purchase & Ledger System

## Context
You are building a single-business accounting system in **Laravel + MySQL**.
This skill guides scaffolding: migrations, models, and base configuration.

---

## Stack
- Laravel 11+
- MySQL
- UUIDs on all primary keys (use `Str::uuid()` or `$table->uuid('id')->primary()`)
- Soft deletes on Sales and Purchases only
- No authentication needed unless specified

---

## UUID Rules
- Every table uses UUID as primary key
- In migrations: `$table->uuid('id')->primary();`
- In models: use `HasUuids` trait (Laravel 9+) OR boot method with `Str::uuid()`
- Foreign keys: `$table->foreignUuid('party_id')->constrained()`

---

## Tables to Generate
1. `parties` — id, name, phone (nullable), timestamps
2. `accounts` — id, name, type (enum: cash/bank), timestamps
3. `sales` — id, party_id (FK), total, timestamps, softDeletes
4. `sale_items` — id, sale_id (FK), particular (string), qty, price, total — NO item_id
5. `purchases` — id, party_id (FK), total, timestamps, softDeletes
6. `purchase_items` — id, purchase_id (FK), particular (string), qty, price, total
7. `payments` — id, party_id (FK), amount, type (enum: received/given), account_id (FK), sale_id (nullable FK), purchase_id (nullable FK), timestamps
8. `ledger` — id, party_id (nullable FK), account_id (nullable FK), dr_amount (decimal 15,2 default 0), cr_amount (decimal 15,2 default 0), type (enum: sale/purchase/payment), ref_id (uuid), ref_table (string), timestamps — NO softDeletes, NO updated_at

---

## Model Rules
- `Sale` → hasMany `SaleItem`, belongsTo `Party`
- `Purchase` → hasMany `PurchaseItem`, belongsTo `Party`
- `Payment` → belongsTo `Party`, `Account`, optionally `Sale`, `Purchase`
- `Ledger` → belongsTo `Party` (nullable), belongsTo `Account` (nullable)
- `SaleItem` & `PurchaseItem` → no `$fillable` restrictions needed, auto-calc `total = qty * price`

---

## Strict Rules
- ❌ No `balance` column anywhere
- ❌ No `updated_at` on ledger table
- ✅ Use DB transactions (`DB::transaction()`) for every write operation
- ✅ Soft delete Sales and Purchases — never hard delete
- ✅ Never delete ledger rows — reverse with counter-entries instead

---

## Sample Migration Pattern
```php
Schema::create('ledger', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('party_id')->nullable()->constrained('parties');
    $table->foreignUuid('account_id')->nullable()->constrained('accounts');
    $table->decimal('dr_amount', 15, 2)->default(0);
    $table->decimal('cr_amount', 15, 2)->default(0);
    $table->enum('type', ['sale', 'purchase', 'payment']);
    $table->uuid('ref_id');
    $table->string('ref_table');
    $table->timestamp('created_at')->useCurrent();
});
```
