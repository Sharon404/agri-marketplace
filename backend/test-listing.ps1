# Test creating a farmer listing
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
    $farmerId = $loginData.user.id
    Write-Host "  Logged in as user ID: $farmerId"
    Write-Host "  Token: $($authToken.Substring(0, 20))..."
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = [System.IO.StreamReader]::new($stream)
    Write-Host "Login failed: $($_.Exception.Response.StatusCode)"
    Write-Host $reader.ReadToEnd()
    exit
}

# Step 2: Create a farmer listing
Write-Host ""
Write-Host "Step 2: Creating farmer listing..."
$listingBody = @{
    farmer_id = $farmerId
    product_id = 1  # Rice
    quantity = 100
    unit_price = 50
    location = "Nairobi, Kenya"
    availability_date = (Get-Date).AddDays(7).ToString("yyyy-MM-dd")
    description = "Fresh white rice from Western Kenya"
    is_active = $true
} | ConvertTo-Json

try {
    $listingResponse = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/farmer-listings" `
        -Method Post `
        -Headers @{
            "Content-Type" = "application/json"
            "Authorization" = "Bearer $authToken"
        } `
        -Body $listingBody `
        -UseBasicParsing
    
    $listingData = $listingResponse.Content | ConvertFrom-Json
    Write-Host "SUCCESS - Listing created" -ForegroundColor Green
    Write-Host "  ID: $($listingData.id)"
    Write-Host "  Product: $($listingData.product.name)"
    Write-Host "  Quantity: $($listingData.quantity)"
    Write-Host "  Unit Price: $($listingData.unit_price)"
    Write-Host "  Location: $($listingData.location)"
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = [System.IO.StreamReader]::new($stream)
    $errorContent = $reader.ReadToEnd()
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    Write-Host $errorContent
}
