# Quick Login Test Script for Docker Environment
$baseUrl = "http://localhost:8000/api"

Write-Host "=== Agri-Marketplace Quick Login Test ===" -ForegroundColor Cyan
Write-Host ""

# Register a new farmer
Write-Host "1. Registering a new farmer..." -ForegroundColor Yellow
$registerBody = @{
    name = "John Farmer"
    email = "john@farmer.com"
    password = "password123"
    password_confirmation = "password123"
    phone = "0712345678"
    role = "farmer"
    location = "Nairobi"
} | ConvertTo-Json

try {
    $registerResponse = Invoke-RestMethod -Uri "$baseUrl/register" -Method Post -Body $registerBody -ContentType "application/json"
    Write-Host "✓ Farmer registered successfully!" -ForegroundColor Green
    Write-Host "User ID: $($registerResponse.user.id)" -ForegroundColor Cyan
} catch {
    $errorDetails = $_.ErrorDetails.Message | ConvertFrom-Json
    if ($errorDetails.email -contains "The email has already been taken.") {
        Write-Host "User already exists, will try to login..." -ForegroundColor Yellow
    } else {
        Write-Host "Error: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

# Login
Write-Host "`n2. Logging in..." -ForegroundColor Yellow
$loginBody = @{
    email = "john@farmer.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $loginBody -ContentType "application/json"
    Write-Host "✓ Login successful!" -ForegroundColor Green
    Write-Host ""
    Write-Host "=== SAVE THIS TOKEN ===" -ForegroundColor Cyan
    Write-Host $loginResponse.access_token -ForegroundColor Yellow
    Write-Host "=====================" -ForegroundColor Cyan
    Write-Host ""
    
    # Save token to file
    $loginResponse.access_token | Out-File -FilePath "token.txt" -NoNewline
    Write-Host "Token saved to token.txt" -ForegroundColor Green
    
    Write-Host "`nUser Info:" -ForegroundColor Cyan
    Write-Host "Name: $($loginResponse.user.name)"
    Write-Host "Email: $($loginResponse.user.email)"
    Write-Host "Role: $($loginResponse.user.role)"
    Write-Host "Location: $($loginResponse.user.location)"
    
    # Test authenticated endpoint
    Write-Host "`n3. Testing authenticated endpoint..." -ForegroundColor Yellow
    $headers = @{
        "Authorization" = "Bearer $($loginResponse.access_token)"
        "Accept" = "application/json"
    }
    
    $testResponse = Invoke-RestMethod -Uri "$baseUrl/test-auth" -Method Get -Headers $headers
    Write-Host "✓ Authentication working!" -ForegroundColor Green
    Write-Host "Authenticated as: $($testResponse.user.name)" -ForegroundColor Cyan
    
} catch {
    Write-Host "✗ Login failed!" -ForegroundColor Red
    Write-Host "Error: $($_.ErrorDetails.Message)" -ForegroundColor Red
}

Write-Host "`n=== Test Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Copy the token above"
Write-Host "2. Edit test-deals.ps1 and replace token"
Write-Host "3. Run the test scripts"
Write-Host ""
