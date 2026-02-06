# Test Deals API Endpoints
# Run this after logging in and getting a JWT token

$baseUrl = "http://localhost:8000/api"
$token = "YOUR_JWT_TOKEN_HERE" # Replace with actual token

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "Testing Deals API..." -ForegroundColor Cyan

# Test 1: Get all deals
Write-Host "`n1. Getting all deals..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals" -Method Get -Headers $headers
    Write-Host "Success! Found $($response.total) deals" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 2: Create deal from listing (Buyer)
Write-Host "`n2. Creating deal from listing..." -ForegroundColor Yellow
$dealFromListing = @{
    farmer_listing_id = 1
    quantity = 50
    delivery_location = "Nairobi CBD"
    delivery_date = "2026-02-20"
    notes = "Please deliver between 8 AM - 12 PM"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/from-listing" -Method Post -Headers $headers -Body $dealFromListing
    Write-Host "Success! Deal created with ID: $($response.deal.id)" -ForegroundColor Green
    $dealId = $response.deal.id
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 3: Create deal from request (Farmer)
Write-Host "`n3. Creating deal from buyer request..." -ForegroundColor Yellow
$dealFromRequest = @{
    buyer_request_id = 1
    quantity = 75
    offered_price = 45
    delivery_date = "2026-02-18"
    notes = "Fresh harvest, Grade A quality"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/from-request" -Method Post -Headers $headers -Body $dealFromRequest
    Write-Host "Success! Deal offer created with ID: $($response.deal.id)" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 4: Get deal by ID
if ($dealId) {
    Write-Host "`n4. Getting deal details..." -ForegroundColor Yellow
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId" -Method Get -Headers $headers
        Write-Host "Success! Retrieved deal #$dealId" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 5: Accept deal
if ($dealId) {
    Write-Host "`n5. Accepting deal..." -ForegroundColor Yellow
    $statusUpdate = @{
        status = "accepted"
        notes = "Confirmed! Will deliver on time."
    } | ConvertTo-Json

    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId/status" -Method Patch -Headers $headers -Body $statusUpdate
        Write-Host "Success! Deal accepted" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 6: Update payment status
if ($dealId) {
    Write-Host "`n6. Updating payment status..." -ForegroundColor Yellow
    $paymentUpdate = @{
        payment_status = "paid"
        notes = "Payment received via M-Pesa"
    } | ConvertTo-Json

    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/deals/$dealId/payment" -Method Patch -Headers $headers -Body $paymentUpdate
        Write-Host "Success! Payment status updated" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 7: Get deal statistics
Write-Host "`n7. Getting deal statistics..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals/statistics" -Method Get -Headers $headers
    Write-Host "Success! Retrieved statistics" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 8: Get pending deals
Write-Host "`n8. Getting pending deals..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/deals?status=pending" -Method Get -Headers $headers
    Write-Host "Success! Found $($response.total) pending deals" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host "`nDeals API testing complete!" -ForegroundColor Cyan
