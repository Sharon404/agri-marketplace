# Clear Test Users Script
# This script removes test users so you can re-register them

Write-Host "=== Clear Test Users ===" -ForegroundColor Cyan
Write-Host ""

# Users to clear (add more as needed)
$testEmails = @(
    'farmer@test.com',
    'buyer@test.com',
    'testfarmer@test.com',
    'testbuyer@test.com'
)

Write-Host "Connecting to Laravel backend..." -ForegroundColor Yellow

foreach ($email in $testEmails) {
    Write-Host "Deleting user: $email" -ForegroundColor Gray
    
    docker-compose exec app php artisan tinker --execute="
        \$user = App\Models\User::where('email', '$email')->first();
        if (\$user) {
            \$user->delete();
            echo 'Deleted: $email\n';
        } else {
            echo 'Not found: $email\n';
        }
    "
}

Write-Host ""
Write-Host "=== Done! ===" -ForegroundColor Green
Write-Host "You can now register new users with these emails." -ForegroundColor Green
