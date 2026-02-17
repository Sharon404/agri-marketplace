ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS tax_id VARCHAR(255);
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS national_id VARCHAR(255);
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS bank_name VARCHAR(255);
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS bank_account_name VARCHAR(255);
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(255);
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS business_address TEXT;
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL;
ALTER TABLE seller_profiles ADD COLUMN IF NOT EXISTS rejection_reason TEXT;
