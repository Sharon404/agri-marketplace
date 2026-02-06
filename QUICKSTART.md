# Quick Start Guide - Agri-Marketplace Improvements

## 🚀 Getting Started

### Prerequisites
- PHP 8.1+
- MySQL/MariaDB
- Composer
- Laravel 10+

---

## 📦 Installation Steps

### 1. Navigate to Backend Directory
```powershell
cd backend
```

### 2. Install Dependencies (if not already done)
```powershell
composer install
```

### 3. Configure Environment
Make sure your `.env` file has correct database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agri_marketplace
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Backup Your Database (IMPORTANT!)
```powershell
# Create a timestamped backup
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
mysqldump -u root -p agri_marketplace > "backup_$timestamp.sql"
```

### 5. Run Migrations
```powershell
# Run all new migrations
php artisan migrate

# If you get errors, check status
php artisan migrate:status

# To rollback last batch if needed
php artisan migrate:rollback
```

---

## ✅ Verify Installation

### Check Migration Status
```powershell
php artisan migrate:status
```

You should see these new migrations as "Ran":
- `2026_02_06_000001_create_deals_table`
- `2026_02_06_000002_create_reviews_table`
- `2026_02_06_000003_create_conversations_and_messages_tables`
- `2026_02_06_000004_enhance_notifications_table`
- `2026_02_06_000005_enhance_users_table`
- `2026_02_06_000006_enhance_farmer_listings_table`
- `2026_02_06_000007_enhance_buyer_requests_table`

### Start the Server
```powershell
php artisan serve
```

Server should start at: `http://127.0.0.1:8000`

---

## 🧪 Test the APIs

### Step 1: Login to Get Token
```powershell
# Edit test-login.ps1 with your credentials
.\test-login.ps1
```

Copy the token from the response.

### Step 2: Update Test Scripts
Edit the following files and replace `YOUR_JWT_TOKEN_HERE` with your actual token:
- `test-deals.ps1`
- `test-messages.ps1`
- `test-reviews.ps1`

### Step 3: Run Tests
```powershell
# Test deals functionality
.\test-deals.ps1

# Test messaging functionality
.\test-messages.ps1

# Test reviews functionality
.\test-reviews.ps1
```

---

## 📝 Quick API Reference

### Deals API
```powershell
# List all deals
GET http://localhost:8000/api/deals

# Create deal from listing (as buyer)
POST http://localhost:8000/api/deals/from-listing
{
  "farmer_listing_id": 1,
  "quantity": 100,
  "delivery_location": "Nairobi CBD",
  "delivery_date": "2026-02-20",
  "notes": "Optional notes"
}

# Accept deal (as farmer or buyer)
PATCH http://localhost:8000/api/deals/{id}/status
{
  "status": "accepted",
  "notes": "Confirmed"
}
```

### Messages API
```powershell
# Send message
POST http://localhost:8000/api/messages/send
{
  "receiver_id": 2,
  "deal_id": 1,
  "message": "Hi, is this still available?"
}

# Get conversations
GET http://localhost:8000/api/messages/conversations

# Get messages in conversation
GET http://localhost:8000/api/messages/conversations/{id}
```

### Reviews API
```powershell
# Create review (only for completed deals)
POST http://localhost:8000/api/reviews
{
  "deal_id": 1,
  "rating": 5,
  "review_type": "overall",
  "comment": "Great quality!"
}

# Get user reviews
GET http://localhost:8000/api/reviews/user/{userId}

# Get review statistics
GET http://localhost:8000/api/reviews/user/{userId}/statistics
```

---

## 🔧 Troubleshooting

### Issue: Migration Fails with Foreign Key Error
**Solution:**
```powershell
# Check if parent tables exist
php artisan migrate:status

# If some migrations failed, rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Issue: "Column already exists" Error
**Solution:**
```powershell
# Rollback the last batch
php artisan migrate:rollback

# Or check your database manually and drop the conflicting columns
```

### Issue: API Returns 401 Unauthorized
**Solution:**
- Ensure you're logged in and have a valid JWT token
- Check if token is included in Authorization header
- Token format: `Bearer YOUR_TOKEN_HERE`

### Issue: "Deal not found" or "Unauthorized"
**Solution:**
- Ensure you have created some test data first
- Verify you're the owner of the resource (deals, listings, etc.)
- Check if deal status allows the operation

### Issue: Cannot Create Review
**Solution:**
- Deal must have status "completed"
- You must be part of the deal (farmer or buyer)
- You can only review each deal once

---

## 📊 Database Tables Overview

### New Tables
| Table | Purpose |
|-------|---------|
| `deals` | Manage transactions between buyers and farmers |
| `reviews` | Store ratings and feedback |
| `conversations` | Container for message threads |
| `messages` | Individual messages between users |

### Enhanced Tables
| Table | New Columns |
|-------|-------------|
| `users` | 13 new fields (profile, location, verification, stats) |
| `notifications` | 4 new fields (priority, action_url, metadata, expires_at) |
| `farmer_listings` | 17 new fields (pricing, product details, delivery, stats) |
| `buyer_requests` | 12 new fields (requirements, delivery, budget, tracking) |

---

## 📚 Next Steps

### For Testing
1. ✅ Create test users (farmer and buyer)
2. ✅ Create test product listings
3. ✅ Create buyer requests
4. ✅ Test deal creation flow
5. ✅ Test messaging between users
6. ✅ Complete a deal and test reviews

### For Production
1. ⚠️ Backup database before deployment
2. ⚠️ Test on staging environment first
3. ⚠️ Update API documentation
4. ⚠️ Implement frontend components
5. ⚠️ Set up monitoring and logging

---

## 📞 Support

### Documentation Files
- `MIGRATION_GUIDE.md` - Detailed migration instructions
- `IMPROVEMENTS_SUMMARY.md` - Complete feature overview
- `README.md` - Project overview

### Check Logs
```powershell
# View Laravel logs
Get-Content storage/logs/laravel.log -Tail 50

# Clear cache if needed
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Common Commands
```powershell
# List all routes
php artisan route:list

# Check for syntax errors
php artisan route:cache

# Run tests
php artisan test

# Generate API documentation
php artisan l5-swagger:generate
```

---

## ✨ Key Features Added

✅ **Complete Deal System** - Track transactions end-to-end
✅ **Reviews & Ratings** - Build trust through feedback
✅ **Direct Messaging** - Communicate securely
✅ **Enhanced Profiles** - Professional user profiles
✅ **Advanced Listings** - Detailed product information
✅ **Smart Matching** - Better buyer-farmer connections

---

**Last Updated:** February 6, 2026
**Status:** Ready for Testing
**Backend:** ✅ Complete
**Frontend:** ⏳ Pending Integration
