# Database Migration Guide

## Overview
This guide explains how to apply the new database schema enhancements for the agri-marketplace project.

## New Features Added
1. **Deals System** - Track transactions between buyers and farmers
2. **Reviews & Ratings** - Allow users to rate each other after completed deals
3. **Messaging System** - Enable direct communication between buyers and farmers
4. **Enhanced Notifications** - Priority levels, action URLs, and metadata
5. **Enhanced User Profiles** - Business info, location, verification levels
6. **Enhanced Listings** - Pricing options, quality grades, delivery info
7. **Enhanced Requests** - Urgency levels, quality requirements, payment terms

## Running Migrations

### Step 1: Backup Your Database
```powershell
# Create a backup before running migrations
php artisan db:backup
```

### Step 2: Run the Migrations
```powershell
# Navigate to backend directory
cd backend

# Run all pending migrations
php artisan migrate

# If you encounter errors, you can rollback
php artisan migrate:rollback

# To reset and re-run all migrations (WARNING: destroys data)
php artisan migrate:fresh
```

### Step 3: Verify Migration Success
```powershell
# Check database schema
php artisan migrate:status
```

## Migration Files Created

1. **2026_02_06_000001_create_deals_table.php**
   - Creates deals table for tracking transactions
   - Links farmers, buyers, products, listings, and requests

2. **2026_02_06_000002_create_reviews_table.php**
   - Creates reviews table for ratings and feedback
   - Adds average_rating and total_reviews to users table

3. **2026_02_06_000003_create_conversations_and_messages_tables.php**
   - Creates conversations and messages tables
   - Enables in-app messaging between users

4. **2026_02_06_000004_enhance_notifications_table.php**
   - Adds priority, action_url, metadata, expires_at to notifications

5. **2026_02_06_000005_enhance_users_table.php**
   - Adds profile images, bio, business info
   - Adds detailed location (county, sub_county, coordinates)
   - Adds verification levels and activity tracking
   - Adds deal statistics

6. **2026_02_06_000006_enhance_farmer_listings_table.php**
   - Adds pricing options (bulk discounts, min order)
   - Adds product details (unit, quality grade, organic, certifications)
   - Adds availability dates and harvest status
   - Adds delivery information
   - Adds view and inquiry tracking

7. **2026_02_06_000007_enhance_buyer_requests_table.php**
   - Adds requirement details (urgency, quality, organic)
   - Adds delivery preferences
   - Adds budget range and payment terms
   - Adds offer tracking and expiration

## New API Endpoints

### Deals API
- `GET /api/deals` - List user's deals
- `GET /api/deals/{id}` - Get deal details
- `GET /api/deals/statistics` - Get deal statistics
- `POST /api/deals/from-listing` - Create deal from listing
- `POST /api/deals/from-request` - Create deal from request
- `PATCH /api/deals/{id}/status` - Update deal status
- `PATCH /api/deals/{id}/payment` - Update payment status

### Messages API
- `GET /api/messages/conversations` - List conversations
- `GET /api/messages/conversations/{id}` - Get messages
- `POST /api/messages/send` - Send message
- `PATCH /api/messages/conversations/{id}/read` - Mark as read
- `GET /api/messages/unread-count` - Get unread count

### Reviews API
- `GET /api/reviews/user/{userId}` - Get user reviews
- `GET /api/reviews/user/{userId}/statistics` - Get review stats
- `POST /api/reviews` - Create review
- `PATCH /api/reviews/{id}` - Update review
- `DELETE /api/reviews/{id}` - Delete review

## Testing the New Features

### 1. Test Deals API
```powershell
# Create deal from listing (as buyer)
curl -X POST http://localhost:8000/api/deals/from-listing `
  -H "Authorization: Bearer YOUR_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "farmer_listing_id": 1,
    "quantity": 100,
    "delivery_location": "Nairobi CBD",
    "delivery_date": "2026-02-15",
    "notes": "Please deliver in the morning"
  }'

# Accept deal (as farmer)
curl -X PATCH http://localhost:8000/api/deals/1/status `
  -H "Authorization: Bearer YOUR_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "status": "accepted",
    "notes": "Confirmed for morning delivery"
  }'
```

### 2. Test Messaging API
```powershell
# Send message
curl -X POST http://localhost:8000/api/messages/send `
  -H "Authorization: Bearer YOUR_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "receiver_id": 2,
    "deal_id": 1,
    "message": "Hi, when can you deliver?"
  }'

# Get conversations
curl -X GET http://localhost:8000/api/messages/conversations `
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Test Reviews API
```powershell
# Create review
curl -X POST http://localhost:8000/api/reviews `
  -H "Authorization: Bearer YOUR_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{
    "deal_id": 1,
    "rating": 5,
    "review_type": "overall",
    "comment": "Excellent quality products and timely delivery!"
  }'
```

## Troubleshooting

### Foreign Key Constraint Errors
If you get foreign key errors:
1. Ensure parent tables exist (users, products, etc.)
2. Check if you have data that violates constraints
3. Consider running migrations in fresh database

### Column Already Exists Errors
If columns already exist:
1. Check if migrations were partially run
2. Use `migrate:rollback` to undo last batch
3. Or manually remove columns that conflict

### Migration Order Issues
Migrations run in alphabetical order. If you need specific order:
1. Prefix with timestamps (already done)
2. Ensure dependent migrations come after parent tables

## Next Steps

1. **Seed Sample Data**
   ```powershell
   php artisan db:seed
   ```

2. **Update Frontend**
   - Implement deal creation flows
   - Add messaging UI
   - Add review submission forms

3. **Test Thoroughly**
   - Test all new endpoints
   - Verify relationships work correctly
   - Check notification creation

4. **Deploy**
   - Backup production database
   - Run migrations on staging first
   - Test on staging before production
