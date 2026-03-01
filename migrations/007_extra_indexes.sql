-- Extra indexes identified during index review (index_review.md)
-- These optimize dashboard queries that were rated "ADEQUATE" but can be improved.

-- 1. Recent Sales widget: eliminates filesort on ORDER BY created_at DESC
--    Query: SELECT ... FROM sales WHERE store_id=? ORDER BY created_at DESC LIMIT 10
CREATE INDEX idx_store_created ON sales (store_id, created_at DESC);

-- 2. Sale items by store+product: improves P&L category breakdown and slow-mover reports
--    Query: JOIN sale_items si ON ... WHERE s.store_id=? GROUP BY p.category_id
CREATE INDEX idx_store_product ON sale_items (store_id, product_id);
