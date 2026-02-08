# Quick script to delete a specific user by email
# Usage: .\delete-user.ps1 "email@test.com"

param(
    [Parameter(Mandatory=$false)]
    [string]$Email = "farmer@test.com"
)

Write-Host "=== Delete User: $Email ===" -ForegroundColor Cyan

cd c:\Users\Admin\Desktop\agri-marketplace\backend

# Delete user via artisan tinker
docker-compose exec app php artisan tinker --execute="
    `$user = App\Models\User::where('email', '$Email')->first();
    if (`$user) {
        echo 'Found user: ' . `$user->name . '\n';
        `$user->delete();
        echo 'User deleted successfully!\n';
    } else {
        echo 'User not found with email: $Email\n';
    }
"

Write-Host ""
Write-Host "Done! You can now register with email: $Email" -ForegroundColor Green
