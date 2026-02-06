# Test Messaging API Endpoints
# Run this after logging in and getting a JWT token

$baseUrl = "http://localhost:8000/api"
$token = "YOUR_JWT_TOKEN_HERE" # Replace with actual token

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "Testing Messaging API..." -ForegroundColor Cyan

# Test 1: Send a message
Write-Host "`n1. Sending a message..." -ForegroundColor Yellow
$messageData = @{
    receiver_id = 2  # Replace with actual receiver ID
    deal_id = 1      # Optional, replace with actual deal ID
    message = "Hi! I'm interested in your tomatoes listing. Are they still available?"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/messages/send" -Method Post -Headers $headers -Body $messageData
    Write-Host "Success! Message sent" -ForegroundColor Green
    $conversationId = $response.data.conversation_id
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 2: Get all conversations
Write-Host "`n2. Getting all conversations..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/messages/conversations" -Method Get -Headers $headers
    Write-Host "Success! Retrieved conversations" -ForegroundColor Green
    if ($response.Count -gt 0) {
        $conversationId = $response[0].id
    }
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 3: Get messages in a conversation
if ($conversationId) {
    Write-Host "`n3. Getting messages in conversation..." -ForegroundColor Yellow
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/messages/conversations/$conversationId" -Method Get -Headers $headers
        Write-Host "Success! Retrieved messages" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 4: Send another message
Write-Host "`n4. Sending follow-up message..." -ForegroundColor Yellow
$followUpMessage = @{
    receiver_id = 2
    deal_id = 1
    message = "I would like to order 100 kg. Can you deliver to Nairobi?"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/messages/send" -Method Post -Headers $headers -Body $followUpMessage
    Write-Host "Success! Follow-up message sent" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 5: Get unread message count
Write-Host "`n5. Getting unread message count..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/messages/unread-count" -Method Get -Headers $headers
    Write-Host "Success! You have $($response.unread_count) unread messages" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

# Test 6: Mark messages as read
if ($conversationId) {
    Write-Host "`n6. Marking messages as read..." -ForegroundColor Yellow
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/messages/conversations/$conversationId/read" -Method Patch -Headers $headers
        Write-Host "Success! Messages marked as read" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 3
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
    }
}

# Test 7: Verify unread count decreased
Write-Host "`n7. Verifying unread count..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/messages/unread-count" -Method Get -Headers $headers
    Write-Host "Success! Unread count: $($response.unread_count)" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host "`nMessaging API testing complete!" -ForegroundColor Cyan
