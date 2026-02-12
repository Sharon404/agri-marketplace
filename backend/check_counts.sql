-- Check data counts
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as approved_farmers FROM users WHERE role = 'farmer' AND approval_status = 'approved';
SELECT COUNT(*) as approved_buyers FROM users WHERE role = 'buyer' AND approval_status = 'approved';
SELECT COUNT(*) as farmer_listings FROM farmer_listings WHERE is_active = true;
SELECT COUNT(*) as buyer_requests FROM buyer_requests WHERE is_active = true;
SELECT COUNT(*) as deals FROM deals;
