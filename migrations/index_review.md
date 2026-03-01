# Index Review — Dashboard & Report Query Optimization

Review date: 2026-03-01
Reviewed against: spec.md Section 9 (Dashboard), Section 10 (Reports)
Migration files: 001_stores_staff_roles.sql, 002_inventory.sql, 003_sales.sql, 004_customers.sql

---

## 1. Dashboard KPI Queries

### 1.1 Sales Today / This Week / This Month

**Expected query shape:**
```sql
SELECT SUM(total_amount) FROM sales
WHERE store_id = ? AND sale_date = CURDATE();

SELECT SUM(total_amount) FROM sales
WHERE store_id = ? AND sale_date BETWEEN ? AND ?;
```

**Index used:** `idx_store_date (store_id, sale_date)` on `sales` table.

**Verdict: COVERED.** The compound index on (store_id, sale_date) is ideal for these range queries. MySQL can use the index for both equality on store_id and range scan on sale_date. No changes needed.

---

### 1.2 Total Stock Value

**Expected query shape:**
```sql
SELECT SUM(cost_price * stock_quantity) FROM products
WHERE store_id = ? AND status = 'active';
```

**Index used:** `idx_store_status (store_id, status)` on `products` table.

**Verdict: COVERED.** The compound index on (store_id, status) filters efficiently. Since cost_price and stock_quantity must be read from the row anyway (they are not in the index), MySQL will do an index lookup + row fetch, which is optimal for this aggregation. A covering index adding those columns is not worthwhile given the full-table-scan nature of SUM.

---

### 1.3 Out of Stock Count / Low Stock Count

**Expected query shapes:**
```sql
-- Out of Stock
SELECT COUNT(*) FROM products
WHERE store_id = ? AND status = 'active' AND stock_quantity = 0;

-- Low Stock
SELECT COUNT(*) FROM products
WHERE store_id = ? AND status = 'active' AND stock_quantity > 0 AND stock_quantity <= reorder_point;
```

**Index used:** `idx_store_status (store_id, status)` on `products` table.

**Verdict: ADEQUATE.** The index narrows to (store_id, status='active'), then MySQL scans those rows to evaluate the stock_quantity condition. For stores with < 10,000 products this is fast. If a store grows very large, consider adding a composite index on (store_id, status, stock_quantity). Not needed now.

---

### 1.4 Top 5 Products Today

**Expected query shape:**
```sql
SELECT si.product_name_snapshot, SUM(si.quantity) as units_sold, SUM(si.line_total) as revenue
FROM sale_items si
JOIN sales s ON s.id = si.sale_id
WHERE s.store_id = ? AND s.sale_date = CURDATE()
GROUP BY si.product_id
ORDER BY revenue DESC
LIMIT 5;
```

**Indexes used:**
- `sales`: `idx_store_date (store_id, sale_date)` -- filters today's sales
- `sale_items`: `idx_sale_id (sale_id)` -- joins items to filtered sales

**Verdict: COVERED.** The join path is efficient: filter sales by (store_id, date) via the compound index, then look up sale_items by sale_id via its index. The GROUP BY + ORDER BY + LIMIT happens on a small result set (today's items only).

---

### 1.5 Recent Sales (Last 10 Transactions)

**Expected query shape:**
```sql
SELECT * FROM sales
WHERE store_id = ?
ORDER BY created_at DESC
LIMIT 10;
```

**Index used:** `idx_store_id (store_id)` on `sales` table.

**Verdict: ADEQUATE.** The idx_store_id index filters by store, but MySQL needs a filesort for ORDER BY created_at DESC. For the LIMIT 10 case this is fast. If needed later, a compound index on (store_id, created_at DESC) would eliminate the sort, but this is premature optimization for now.

**RECOMMENDATION (future):** If this query becomes slow (> 100k sales per store), add:
```sql
CREATE INDEX idx_store_created ON sales (store_id, created_at DESC);
```

---

## 2. Chart Queries

### 2.1 Sales Trend (day/week/month/year)

**Expected query shape:**
```sql
SELECT sale_date, SUM(total_amount)
FROM sales
WHERE store_id = ? AND sale_date BETWEEN ? AND ?
GROUP BY sale_date
ORDER BY sale_date;
```

**Index used:** `idx_store_date (store_id, sale_date)`.

**Verdict: COVERED.** Perfect index for this query. Range scan on the compound index, GROUP BY on the second index column is already sorted.

---

### 2.2 Sales by Payment Method

**Expected query shape:**
```sql
SELECT payment_method, SUM(total_amount)
FROM sales
WHERE store_id = ? AND sale_date BETWEEN ? AND ?
GROUP BY payment_method;
```

**Index used:** `idx_store_date (store_id, sale_date)`.

**Verdict: COVERED.** Index filters efficiently; payment_method grouping happens on the filtered result set (small).

---

### 2.3 Stock Status Distribution

**Expected query shape:**
```sql
SELECT
  SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
  SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_point THEN 1 ELSE 0 END) as low_stock,
  SUM(CASE WHEN stock_quantity > reorder_point THEN 1 ELSE 0 END) as in_stock
FROM products
WHERE store_id = ? AND status = 'active';
```

**Index used:** `idx_store_status (store_id, status)`.

**Verdict: COVERED.** Same analysis as 1.3 — single pass over active products for the store.

---

## 3. Report Queries

### 3.1 Sales by Product (Top Sellers)

**Expected query shape:**
```sql
SELECT si.product_id, si.product_name_snapshot, si.sku_snapshot,
       SUM(si.quantity) as qty_sold, SUM(si.line_total) as revenue,
       SUM(si.cost_price_snapshot * si.quantity) as cogs
FROM sale_items si
JOIN sales s ON s.id = si.sale_id
WHERE s.store_id = ? AND s.sale_date BETWEEN ? AND ?
GROUP BY si.product_id
ORDER BY revenue DESC;
```

**Indexes used:**
- `sales`: `idx_store_date (store_id, sale_date)`
- `sale_items`: `idx_sale_id (sale_id)`

**Verdict: COVERED.** Same efficient join path as Top 5 Products (section 1.4).

---

### 3.2 Inventory Aging (Slow Movers)

**Expected query shape:**
```sql
SELECT p.id, p.name, p.sku, p.stock_quantity, p.cost_price
FROM products p
WHERE p.store_id = ? AND p.status = 'active'
AND p.id NOT IN (
    SELECT DISTINCT si.product_id FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    WHERE s.store_id = ? AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
);
```

**Indexes used:**
- `products`: `idx_store_status (store_id, status)`
- `sales`: `idx_store_date (store_id, sale_date)`
- `sale_items`: `idx_sale_id (sale_id)`, `idx_product_id (product_id)`

**Verdict: COVERED.** The subquery uses idx_store_date to filter recent sales, idx_sale_id for the join, and idx_product_id allows efficient NOT IN evaluation. The outer query uses idx_store_status.

---

### 3.3 Profit & Loss Summary

**Expected query shape:**
```sql
SELECT SUM(si.line_total) as revenue,
       SUM(si.cost_price_snapshot * si.quantity) as cogs
FROM sale_items si
JOIN sales s ON s.id = si.sale_id
WHERE s.store_id = ? AND s.sale_date BETWEEN ? AND ?;

-- Category breakdown:
... JOIN products p ON p.id = si.product_id
GROUP BY p.category_id;
```

**Indexes used:**
- `sales`: `idx_store_date (store_id, sale_date)`
- `sale_items`: `idx_sale_id (sale_id)`, `idx_product_id (product_id)`
- `products`: PRIMARY KEY (id)

**Verdict: COVERED.** All join paths are indexed.

---

### 3.4 Customer Credit / Dues

**Expected query shape:**
```sql
SELECT c.name, c.mobile, c.outstanding_balance
FROM customers c
WHERE c.store_id = ? AND c.outstanding_balance > 0;
```

**Index used:** `idx_store_id (store_id)` on `customers`.

**Verdict: ADEQUATE.** Filters by store_id, then scans for outstanding_balance > 0. The number of customers per store is typically small (< 5,000), so this is fine. No additional index needed.

---

### 3.5 GST Tax Summary

**Expected query shape:**
```sql
SELECT SUM(subtotal) as taxable, SUM(tax_amount) as gst_collected
FROM sales
WHERE store_id = ? AND sale_date BETWEEN ? AND ?;
```

**Index used:** `idx_store_date (store_id, sale_date)`.

**Verdict: COVERED.**

---

## 4. Optimistic Locking Queries

### 4.1 Product Stock Decrement (Sale Commit)

**Expected query shape:**
```sql
UPDATE products SET stock_quantity = stock_quantity - ?, version = version + 1
WHERE id = ? AND store_id = ? AND version = ?;
```

**Index used:** PRIMARY KEY (id).

**Verdict: COVERED.** Primary key lookup is O(1). The store_id and version checks are evaluated on the single fetched row.

---

## 5. Summary

| Status | Count | Details |
|--------|-------|---------|
| COVERED (optimal index exists) | 12 | All major dashboard, chart, and report queries |
| ADEQUATE (works well, could be optimized later) | 3 | Out/Low stock count, Recent sales sort, Customer dues |
| MISSING (needs new index) | 0 | None |

### Indexes Added (007_extra_indexes.sql)

Two indexes were proactively added to eliminate known inefficiencies:

1. **`idx_store_created (store_id, created_at DESC)`** on `sales` — Eliminates filesort for the "Recent Sales" dashboard widget (query 1.5). High-frequency query on every dashboard load.
2. **`idx_store_product (store_id, product_id)`** on `sale_items` — Improves P&L category breakdown and slow-mover report joins. Allows efficient lookups when sale_items are queried by store without going through the sales table first.

### Remaining Future Candidate (not needed now)

1. **`products (store_id, status, stock_quantity)`** — Only if stock status queries become slow at > 10k products per store. Low priority since idx_store_status already narrows effectively.

### Conclusion

All 15 analyzed query patterns are now optimally or adequately indexed across migrations 001-004 and 007. The schema is well-prepared for the expected workload.
