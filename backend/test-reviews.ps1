# Test Reviews API Endpoints
# Run this after completing a deal

$baseUrl = "http://localhost:8000/api"
$token = "YOUR_JWT_TOKEN_HERE" # Replace with actual token

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "Testing Reviews API..." -ForegroundColor Cyan

# Test 1: Create a review
Write-Host "`n1. Creating a review..." -ForegroundColor Yellow
$reviewData = @{
    deal_id = 1  # Must be a completed deal
    rating = 5
    review_type = "overall"
    comment = "Excellent quality products! The tomatoes were fresh and the delivery was on time. Highly recommend this farmer."
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews" -Method Post -Headers $headers -Body $reviewData
    Write-Host "Success! Review created with ID: $($response.review.id)" -ForegroundColor Green
    $reviewId = $response.review.id
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 2: Create multiple reviews for different aspects
Write-Host "`n2. Creating quality review..." -ForegroundColor Yellow
$qualityReview = @{
    deal_id = 2
    rating = 5
    review_type = "quality"
    comment = "Grade A quality vegetables"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews" -Method Post -Headers $headers -Body $qualityReview
    Write-Host "Success! Quality review created" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 3: Create delivery review
Write-Host "`n3. Creating delivery review..." -ForegroundColor Yellow
$deliveryReview = @{
    deal_id = 2
    rating = 4
    review_type = "delivery"
    comment = "Good delivery service, arrived within promised timeframe"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews" -Method Post -Headers $headers -Body $deliveryReview
    Write-Host "Success! Delivery review created" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 4: Get reviews for a user
Write-Host "`n4. Getting reviews for user..." -ForegroundColor Yellow
$userId = 1  # Replace with actual user ID
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews/user/$userId" -Method Get -Headers $headers
    Write-Host "Success! Retrieved $($response.total) reviews" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 5: Get review statistics for a user
Write-Host "`n5. Getting review statistics..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews/user/$userId/statistics" -Method Get -Headers $headers
    Write-Host "Success! Retrieved statistics" -ForegroundColor Green
    Write-Host "Total Reviews: $($response.total_reviews)" -ForegroundColor Cyan
    Write-Host "Average Rating: $([math]::Round($response.average_rating, 2))" -ForegroundColor Cyan
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 6: Update a review
if ($reviewId) {
    Write-Host "`n6. Updating review..." -ForegroundColor Yellow
    $updateData = @{
        rating = 5
        comment = "Updated: Absolutely fantastic service! Best farmer I've dealt with."
    } | ConvertTo-Json

    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/reviews/$reviewId" -Method Patch -Headers $headers -Body $updateData
        Write-Host "Success! Review updated" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 7: Try to create duplicate review (should fail)
Write-Host "`n7. Testing duplicate review prevention..." -ForegroundColor Yellow
$duplicateReview = @{
    deal_id = 1
    rating = 4
    review_type = "overall"
    comment = "Another review"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews" -Method Post -Headers $headers -Body $duplicateReview
    Write-Host "Warning! Duplicate review was created (should have been prevented)" -ForegroundColor Red
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Good! Duplicate review was prevented as expected" -ForegroundColor Green
    Write-Host "Error message: $_" -ForegroundColor Yellow
}

# Test 8: Get paginated reviews
Write-Host "`n8. Getting paginated reviews..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/reviews/user/$userId`?page=1" -Method Get -Headers $headers
    Write-Host "Success! Page 1 of reviews retrieved" -ForegroundColor Green
    Write-Host "Current Page: $($response.current_page) of $($response.last_page)" -ForegroundColor Cyan
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host "`nReviews API testing complete!" -ForegroundColor Cyan
Write-Host "`nNote: Some tests may fail if:" -ForegroundColor Yellow
Write-Host "- The deal is not completed yet" -ForegroundColor Yellow
Write-Host "- You've already reviewed the deal" -ForegroundColor Yellow
Write-Host "- The deal doesn't belong to you" -ForegroundColor Yellow
