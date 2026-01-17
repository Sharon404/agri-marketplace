$email = "farmer.20260117130920@test.com"
$password = "password123"

Write-Host "Testing login:"
Write-Host "  Email: $email"
Write-Host "  Password: $password"
Write-Host ""

$body = @{
    email = $email
    password = $password
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/login" `
        -Method Post `
        -Headers @{"Content-Type"="application/json"} `
        -Body $body `
        -UseBasicParsing
    
    Write-Host "SUCCESS - Login successful" -ForegroundColor Green
    $data = $response.Content | ConvertFrom-Json
    Write-Host "  ID: $($data.user.id)"
    Write-Host "  Email: $($data.user.email)"
    Write-Host "  Role: $($data.user.role)"
    Write-Host "  Token: $($data.token.Substring(0, 20))..."
    
    # Save token for next request
    $script:authToken = $data.token
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = [System.IO.StreamReader]::new($stream)
    $errorContent = $reader.ReadToEnd()
    
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    Write-Host $errorContent
}
