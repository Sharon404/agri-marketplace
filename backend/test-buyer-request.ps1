# Test creating a buyer request with authenticated user ID
$email = "farmer.20260117130920@test.com"
$password = "password123"

# Step 1: Login to get token
Write-Host "Step 1: Getting authentication token..."
$loginBody = @{
    email = $email
    password = $password
} | ConvertTo-Json

try {
    $loginResponse = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/login" `
        -Method Post `
        -Headers @{"Content-Type"="application/json"} `
        -Body $loginBody `
        -UseBasicParsing
    
    $loginData = $loginResponse.Content | ConvertFrom-Json
    $authToken = $loginData.token
    $userId = $loginData.user.id
    Write-Host "  Logged in as user ID: $userId"
} catch {
    Write-Host "Login failed"
    exit
}

# Step 2: Create a buyer request with correct buyer_id from token
Write-Host ""
Write-Host "Step 2: Creating buyer request with authenticated user..."
$buyerBody = @{
    product_id = 3  # Maize
    quantity = 200
    target_price = 40
    delivery_location = "Mombasa Port"
    urgency = "high"
    description = "Need quality maize for export"
    is_active = $true
} | ConvertTo-Json

try {
    $buyerResponse = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/buyer-requests" `
        -Method Post `
        -Headers @{
            "Content-Type" = "application/json"
            "Authorization" = "Bearer $authToken"
        } `
        -Body $buyerBody `
        -UseBasicParsing
    
    $buyerData = $buyerResponse.Content | ConvertFrom-Json
    Write-Host "SUCCESS - Buyer request created" -ForegroundColor Green
    Write-Host "  ID: $($buyerData.id)"
    Write-Host "  Buyer ID: $($buyerData.buyer_id)"
    Write-Host "  Product: $($buyerData.product.name)"
    Write-Host "  Quantity: $($buyerData.quantity)"
    Write-Host "  Target Price: $($buyerData.target_price)"
    Write-Host "  Urgency: $($buyerData.urgency)"
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = [System.IO.StreamReader]::new($stream)
    $errorContent = $reader.ReadToEnd()
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    Write-Host $errorContent
}
