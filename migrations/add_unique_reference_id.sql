-- Migration: Add unique index on transactions.reference_id
-- Prevents duplicate payment processing at the database level
-- Date: 2026-05-18

ALTER TABLE `transactions`
    ADD UNIQUE INDEX `uk_reference_id` (`reference_id`);
