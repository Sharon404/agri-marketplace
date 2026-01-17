# Comprehensive database verification
Write-Host "=== AGRI-MARKETPLACE DATABASE VERIFICATION ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Users
Write-Host "1. USERS TABLE" -ForegroundColor Yellow
Write-Host "   Checking user ID 8 (Test Farmer)..."
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT id, name, email, phone, role, created_at FROM users WHERE id = 8;" | ForEach-Object { if ($_ -ne "") { Write-Host "   $_" } }

# Test 2: Farmer Listings
Write-Host ""
Write-Host "2. FARMER LISTINGS TABLE" -ForegroundColor Yellow
Write-Host "   Listings for user ID 8..."
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT fl.id, fl.farmer_id, p.name, fl.quantity, fl.unit_price, fl.location FROM farmer_listings fl LEFT JOIN products p ON fl.product_id = p.id WHERE fl.farmer_id = 8;" | ForEach-Object { if ($_ -ne "") { Write-Host "   $_" } }

# Test 3: Buyer Requests
Write-Host ""
Write-Host "3. BUYER REQUESTS TABLE" -ForegroundColor Yellow
Write-Host "   Requests for user ID 8..."
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT br.id, br.buyer_id, p.name, br.quantity, br.target_price, br.urgency FROM buyer_requests br LEFT JOIN products p ON br.product_id = p.id WHERE br.buyer_id = 8;" | ForEach-Object { if ($_ -ne "") { Write-Host "   $_" } }

# Test 4: Total counts
Write-Host ""
Write-Host "4. DATABASE COUNTS" -ForegroundColor Yellow
Write-Host "   Total users:"
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT COUNT(*) FROM users;" | ForEach-Object { if ($_ -match '^\s*\d+') { Write-Host "   $_" } }

Write-Host "   Total products:"
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT COUNT(*) FROM products;" | ForEach-Object { if ($_ -match '^\s*\d+') { Write-Host "   $_" } }

Write-Host "   Total farmer listings:"
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT COUNT(*) FROM farmer_listings;" | ForEach-Object { if ($_ -match '^\s*\d+') { Write-Host "   $_" } }

Write-Host "   Total buyer requests:"
docker exec agri-marketplace-db-1 psql -U agri -d agri -c "SELECT COUNT(*) FROM buyer_requests;" | ForEach-Object { if ($_ -match '^\s*\d+') { Write-Host "   $_" } }

Write-Host ""
Write-Host "=== VERIFICATION COMPLETE ===" -ForegroundColor Green
