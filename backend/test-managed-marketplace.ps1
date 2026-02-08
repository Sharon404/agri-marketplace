# Test Managed Marketplace Deal Flow
# Tests the new admin-created deal workflow with buyer/farmer confirmation

$baseUrl = "http://localhost:8000/api"
$adminToken = "YOUR_ADMIN_JWT_TOKEN_HERE"
$buyerToken = "YOUR_BUYER_JWT_TOKEN_HERE"
$farmerToken = "YOUR_FARMER_JWT_TOKEN_HERE"

$adminHeaders = @{
    "Authorization" = "Bearer $adminToken"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$buyerHeaders = @{
    "Authorization" = "Bearer $buyerToken"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$farmerHeaders = @{
    "Authorization" = "Bearer $farmerToken"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "Testing MANAGED MARKETPLACE Deal Flow" -ForegroundColor Cyan
Write-Host "======================================`n" -ForegroundColor Cyan

# Step 1: Admin lists pending buyer requests
Write-Host "Step 1: Admin views pending buyer requests..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/admin/buyer-requests" -Method Get -Headers $adminHeaders
    Write-Host "Success! Found $($response.data.Count) buyer requests" -ForegroundColor Green
    if ($response.data.Count -gt 0) {
        $buyerRequestId = $response.data[0].id
        Write-Host "Using buyer request ID: $buyerRequestId" -ForegroundColor Gray
        $response.data[0] | ConvertTo-Json
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 2: Admin lists farmer supplies
Write-Host "`nStep 2: Admin views available farmer supplies..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/admin/farmer-supplies" -Method Get -Headers $adminHeaders
    Write-Host "Success! Found $($response.data.Count) farmer supplies" -ForegroundColor Green
    if ($response.data.Count -gt 0) {
        $farmerSupplyId = $response.data[0].id
        Write-Host "Using farmer supply ID: $farmerSupplyId" -ForegroundColor Gray
        $response.data[0] | ConvertTo-Json
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 3: Admin creates a deal (matching buyer request with farmer supply)
Write-Host "`nStep 3: Admin creates a deal..." -ForegroundColor Yellow
$dealData = @{
    buyer_request_id = 1
    farmer_supply_id = 1
    quantity = 50
    agreed_price = 35
    delivery_date = "2026-02-25"
    admin_notes = "Matched based on product type and quantity availability"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/admin/deals" -Method Post -Headers $adminHeaders -Body $dealData
    Write-Host "Success! Deal created with ID: $($response.deal.id)" -ForegroundColor Green
    $dealId = $response.deal.id
    Write-Host "Deal Status: $($response.deal.status)" -ForegroundColor Gray
    Write-Host "Expected Status: pending_buyer_confirmation" -ForegroundColor Gray
    $response.deal | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 4: Buyer views the deal
Write-Host "`nStep 4: Buyer views the created deal..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId" -Method Get -Headers $buyerHeaders
    Write-Host "Success! Buyer can see deal" -ForegroundColor Green
    Write-Host "Current Status: $($response.status)" -ForegroundColor Gray
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 5: Buyer accepts the deal
Write-Host "`nStep 5: Buyer accepts the deal..." -ForegroundColor Yellow
$buyerAcceptData = @{
    notes = "Sounds good, I'll confirm receipt upon delivery"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId/accept" -Method Patch -Headers $buyerHeaders -Body $buyerAcceptData
    Write-Host "Success! Buyer accepted deal" -ForegroundColor Green
    Write-Host "New Status: $($response.deal.status)" -ForegroundColor Gray
    Write-Host "Expected Status: pending_farmer_confirmation" -ForegroundColor Gray
    $response.deal | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 6: Farmer views the deal
Write-Host "`nStep 6: Farmer views the deal..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId" -Method Get -Headers $farmerHeaders
    Write-Host "Success! Farmer can see deal" -ForegroundColor Green
    Write-Host "Current Status: $($response.status)" -ForegroundColor Gray
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 7: Farmer accepts the deal
Write-Host "`nStep 7: Farmer accepts the deal..." -ForegroundColor Yellow
$farmerAcceptData = @{
    notes = "Will prepare harvest and arrange logistics"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId/accept" -Method Patch -Headers $farmerHeaders -Body $farmerAcceptData
    Write-Host "Success! Farmer accepted deal" -ForegroundColor Green
    Write-Host "New Status: $($response.deal.status)" -ForegroundColor Gray
    Write-Host "Expected Status: payment_pending (Payment should be created)" -ForegroundColor Gray
    if ($response.deal.payment) {
        Write-Host "Payment Created! Status: $($response.deal.payment.status)" -ForegroundColor Green
        Write-Host "Amount: $($response.deal.payment.amount)" -ForegroundColor Gray
    }
    $response.deal | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 8: Get deal statistics for farmer
Write-Host "`nStep 8: Farmer views deal statistics..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/statistics" -Method Get -Headers $farmerHeaders
    Write-Host "Success! Farmer statistics:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 9: Test rejection flow (create another deal and reject it)
Write-Host "`nStep 9: Testing deal rejection..." -ForegroundColor Yellow
$rejectDealData = @{
    buyer_request_id = 2
    farmer_supply_id = 2
    quantity = 30
    agreed_price = 40
    delivery_date = "2026-02-26"
    admin_notes = "Test rejection flow"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/admin/deals" -Method Post -Headers $adminHeaders -Body $rejectDealData
    $rejectDealId = $response.deal.id
    Write-Host "Created test deal ID: $rejectDealId for rejection test" -ForegroundColor Gray
    
    # Buyer rejects
    $rejectData = @{
        reason = "Price is too high for our budget"
    } | ConvertTo-Json
    
    $rejectResponse = Invoke-RestMethod -Uri "$baseUrl/deals/$rejectDealId/reject" -Method Patch -Headers $buyerHeaders -Body $rejectData
    Write-Host "Success! Deal rejected by buyer" -ForegroundColor Green
    Write-Host "Final Status: $($rejectResponse.deal.status)" -ForegroundColor Gray
    $rejectResponse.deal | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 10: Admin cancels a deal
Write-Host "`nStep 10: Testing admin deal cancellation..." -ForegroundColor Yellow
try {
    $cancelResponse = Invoke-RestMethod -Uri "$baseUrl/admin/deals/$dealId/cancel" -Method Patch -Headers $adminHeaders -Body '{}'
    Write-Host "Success! Admin cancelled deal" -ForegroundColor Green
    Write-Host "Final Status: $($cancelResponse.deal.status)" -ForegroundColor Gray
    $cancelResponse.deal | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host "`n======================================" -ForegroundColor Cyan
Write-Host "Managed Marketplace Test Complete" -ForegroundColor Cyan
