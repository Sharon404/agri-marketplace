# Quick Login Test for Agri-Marketplace
$baseUrl = "http://localhost:8000/api"

Write-Host "=== Agri-Marketplace Login Test ===" -ForegroundColor Cyan

# Register
Write-Host "`nRegistering farmer..." -ForegroundColor Yellow
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
    Write-Host "Success! Farmer registered." -ForegroundColor Green
} catch {
    Write-Host "User may already exist." -ForegroundColor Yellow
}

# Login
Write-Host "`nLogging in..." -ForegroundColor Yellow
$loginBody = @{
    email = "john@farmer.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $loginBody -ContentType "application/json"
    Write-Host "Login successful!" -ForegroundColor Green
    Write-Host ""
    Write-Host "YOUR TOKEN:" -ForegroundColor Cyan
    Write-Host $loginResponse.access_token -ForegroundColor Yellow
    Write-Host ""
    
    # Save to file
    $loginResponse.access_token | Out-File -FilePath "token.txt" -NoNewline
    Write-Host "Token saved to token.txt" -ForegroundColor Green
    
    Write-Host "`nUser: $($loginResponse.user.name) ($($loginResponse.user.role))" -ForegroundColor Cyan
    
    # Test auth
    Write-Host "`nTesting authentication..." -ForegroundColor Yellow
    $headers = @{
        "Authorization" = "Bearer $($loginResponse.access_token)"
        "Accept" = "application/json"
    }
    
    $testResponse = Invoke-RestMethod -Uri "$baseUrl/test-auth" -Method Get -Headers $headers
    Write-Host "Authentication working!" -ForegroundColor Green
    
} catch {
    Write-Host "Login failed!" -ForegroundColor Red
    Write-Host "Error: $($_.ErrorDetails.Message)" -ForegroundColor Red
}

Write-Host "`n=== Done ===" -ForegroundColor Cyan
