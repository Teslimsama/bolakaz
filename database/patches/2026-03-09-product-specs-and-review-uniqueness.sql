-- Add structured product specs storage.
ALTER TABLE products
  ADD COLUMN additional_info TEXT NULL AFTER description;

-- Ensure one review per user per product.
-- Remove duplicates before unique index by keeping latest row id.
DELETE r1 FROM item_rating r1
INNER JOIN item_rating r2
  ON r1.itemId = r2.itemId
 AND r1.userId = r2.userId
 AND r1.ratingId < r2.ratingId;

ALTER TABLE item_rating
  ADD UNIQUE KEY uniq_item_user (itemId, userId);
