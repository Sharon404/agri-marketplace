# Simple Login Test
# Tests login with existing test users

$baseUrl = "http://localhost:8000/api"

Write-Host "`n=== Testing Farmer Login ===" -ForegroundColor Cyan

$farmerBody = @{
    email = "farmer@test.com"
    password = "password123"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $farmerBody -ContentType "application/json"
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "User: $($response.user.name) ($($response.user.role))"
    Write-Host "`nYour JWT Token:" -ForegroundColor Yellow
    Write-Host $response.access_token -ForegroundColor Gray
    
    # Save to environment variable
    $env:AUTH_TOKEN = $response.access_token
    Write-Host "`nToken saved to `$env:AUTH_TOKEN" -ForegroundColor Cyan
    
} catch {
    Write-Host "FAILED!" -ForegroundColor Red
    Write-Host $_.Exception.Message
}

Write-Host "`n=== Testing Buyer Login ===" -ForegroundColor Cyan

$buyerBody = @{
    email = "buyer@test.com"
    password = "password123"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $buyerBody -ContentType "application/json"
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "User: $($response.user.name) ($($response.user.role))"
    Write-Host "`nYour JWT Token:" -ForegroundColor Yellow
    Write-Host $response.access_token -ForegroundColor Gray
    
} catch {
    Write-Host "FAILED!" -ForegroundColor Red
    Write-Host $_.Exception.Message
}
