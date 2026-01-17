# Generate unique credentials
$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$email = "farmer.$timestamp@test.com"
$phone = "+254700" + (Get-Random -Minimum 100000 -Maximum 999999)

# Create registration payload
$body = @{
    name = "Test Farmer"
    email = $email
    phone = $phone
    password = "password123"
    role = "farmer"
} | ConvertTo-Json

Write-Host "Attempting to register:"
Write-Host "  Email: $email"
Write-Host "  Phone: $phone"
Write-Host ""

try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/register" `
        -Method Post `
        -Headers @{"Content-Type"="application/json"} `
        -Body $body `
        -UseBasicParsing
    
    Write-Host "SUCCESS - User registered" -ForegroundColor Green
    $data = $response.Content | ConvertFrom-Json
    Write-Host "  ID: $($data.user.id)"
    Write-Host "  Name: $($data.user.name)"
    Write-Host "  Email: $($data.user.email)"
    Write-Host "  Role: $($data.user.role)"
    Write-Host "  Token: $($data.token.Substring(0, 20))..."
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = [System.IO.StreamReader]::new($stream)
    $errorContent = $reader.ReadToEnd()
    
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    Write-Host $errorContent
}
