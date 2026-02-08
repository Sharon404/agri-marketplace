# Managed Marketplace Implementation

## Overview

This document describes the **Managed Marketplace Model** where the platform (admin) is the sole entity that creates deals. Farmers and buyers can only accept or reject deals created by the admin.

## Business Model

### Core Principle
The platform acts as a **marketplace intermediary** that:
1. Collects buyer requests and farmer supplies
2. Matches them intelligently
3. Creates deals on their behalf
4. Manages the escrow payment system
5. Ensures secure transactions

### Key Difference from Peer-to-Peer
- **Old Model (P2P):** Farmers and buyers could directly create deals
- **New Model (Managed):** Only admin creates deals; users only accept/reject

## Deal Lifecycle

### Phase 1: Supply & Demand Collection
1. **Buyer submits request** via `POST /buyer-requests`
   - Specifies: product, quantity, delivery location, price range
   - Status: Active until fulfilled or cancelled

2. **Farmer submits supply** via `POST /supplies`
   - Specifies: product, available quantity, unit price, availability dates
   - Status: Active until depleted or supply is marked inactive

### Phase 2: Admin Matching & Deal Creation
3. **Admin reviews** via `GET /admin/buyer-requests` and `GET /admin/farmer-supplies`
   - Evaluates available requests and supplies
   - Identifies best matches

4. **Admin creates deal** via `POST /admin/deals`
   - Links buyer request + farmer supply
   - Sets quantity and agreed price
   - Deal moves to `pending_buyer_confirmation` status
   - Buyer is notified

### Phase 3: Buyer Confirmation
5. **Buyer receives notification** and views deal via `GET /deals/{id}`
   - Deal status: `pending_buyer_confirmation`
   - Buyer can review all details

6. **Buyer accepts or rejects** via `PATCH /deals/{id}/accept` or `PATCH /deals/{id}/reject`
   - Accept: Deal moves to `pending_farmer_confirmation`, farmer is notified
   - Reject: Deal moves to `rejected` status, admin is notified

### Phase 4: Farmer Confirmation
7. **Farmer receives notification** and views deal
   - Deal status: `pending_farmer_confirmation`
   - Farmer reviews deal terms

8. **Farmer accepts or rejects** via `PATCH /deals/{id}/accept` or `PATCH /deals/{id}/reject`
   - Accept: Both parties confirmed → deal moves to `both_confirmed` → **Payment record is auto-created** → deal moves to `payment_pending`
   - Reject: Deal moves to `rejected` status

### Phase 5: Payment & Fulfillment
9. **Payment record created** (auto-created when both parties confirm)
   - Status: `pending` (awaiting buyer to transfer funds)
   - Amount: Total deal amount
   - Payment method: Configurable (M-Pesa, bank transfer, etc.)

10. **Buyer initiates payment** via payment gateway
    - Funds held in escrow
    - Payment status: `escrowed`
    - Farmer and admin notified

11. **Farmer prepares and ships** product
    - Farmer marks deal as `in_transit`

12. **Buyer receives and confirms** delivery
    - Buyer marks deal as `delivered`
    - Payment status: Still `escrowed`

13. **Admin releases escrow** via `PATCH /admin/deals/{id}/release-escrow`
    - Funds transferred to farmer
    - Payment status: `released`
    - Deal status: `completed`

## Deal States & Transitions

```
pending_buyer_confirmation
    ↓ (Buyer accepts)
pending_farmer_confirmation ← (Farmer accepts from initial state)
    ↓ (Farmer accepts)
both_confirmed (auto-creates Payment)
    ↓
payment_pending
    ↓ (Buyer pays)
accepted (funds in escrow)
    ↓ (Farmer ships)
in_transit
    ↓ (Buyer receives)
delivered
    ↓ (Admin releases escrow)
completed

Alternative paths:
- Any stage → rejected (if user rejects before both confirm)
- Any stage → cancelled (if admin cancels)
```

## API Endpoints

### Public Endpoints (No Auth Required)
```
GET /supplies/available - List all active farmer supplies
```

### Farmer Endpoints (role:farmer, email verified)
```
POST   /supplies               - Create supply availability
GET    /supplies               - View my supplies
GET    /supplies/{id}          - View supply details
PATCH  /supplies/{id}          - Update supply
DELETE /supplies/{id}          - Delete supply (if no active deals)

GET    /deals                  - View my deals
GET    /deals/{id}             - View deal details
PATCH  /deals/{id}/accept      - Accept deal (when status = pending_farmer_confirmation)
PATCH  /deals/{id}/reject      - Reject deal (before both confirm)
GET    /deals/statistics       - View deal statistics
```

### Buyer Endpoints (role:buyer, email verified)
```
GET    /deals                  - View my deals
GET    /deals/{id}             - View deal details
PATCH  /deals/{id}/accept      - Accept deal (when status = pending_buyer_confirmation)
PATCH  /deals/{id}/reject      - Reject deal (before both confirm)
GET    /deals/statistics       - View deal statistics
```

### Admin Endpoints (role:admin)
```
GET    /admin/buyer-requests             - List all pending buyer requests
GET    /admin/farmer-supplies            - List all available farmer supplies
POST   /admin/deals                      - Create new deal
GET    /admin/deals                      - List all deals with filters
GET    /admin/deals/{id}                 - View deal details with payment info
PATCH  /admin/deals/{id}                 - Update deal (delivery details, notes)
PATCH  /admin/deals/{id}/cancel          - Cancel deal and refund if payment taken
PATCH  /admin/deals/{id}/release-escrow  - Release escrowed payment to farmer
```

### Backward Compatibility Endpoints (Deprecated but still active)
```
GET    /farmer-listings                  - List all active listings
POST   /farmer-listings                  - Create listing (old model)
GET    /buyer-requests                   - List all active buyer requests
POST   /buyer-requests                   - Create buyer request (old model)
```

**Note:** The old peer-to-peer endpoints have been **disabled**:
- ~~POST /deals/from-listing~~ (removed)
- ~~POST /deals/from-request~~ (removed)
- ~~PATCH /deals/{id}/update-status~~ (replaced with accept/reject)

## Payment System (Escrow)

### Payment Lifecycle
```
Payment States:
- pending      → Initial state (awaiting buyer to transfer)
- escrowed     → Funds received and held by admin
- released     → Funds transferred to farmer (deal completed)
- refunded     → Funds returned to buyer (deal cancelled/rejected)
- failed       → Payment transaction failed
```

### Payment Creation
- **Automatic:** Created when both buyer and farmer confirm deal
- **Status:** `pending` (buyer must complete transfer)
- **Amount:** Total deal amount
- **Escrow:** Admin controls release after delivery confirmed

### Admin Controls
- `holdInEscrow()` - Mark payment as received (status: escrowed)
- `releaseEscrow()` - Transfer to farmer (status: released)
- `refund()` - Return to buyer (status: refunded)

## Authorization & Security

### Role-Based Access Control

| Operation | Admin | Farmer | Buyer |
|-----------|-------|--------|-------|
| Create Deal | ✅ | ❌ | ❌ |
| Modify Deal | ✅ | ❌ | ❌ |
| View Own Deals | ✅ | ✅ | ✅ |
| Accept Deal | ❌ | ✅ (Farmer only) | ✅ (Buyer only) |
| Reject Deal | ❌ | ✅ | ✅ |
| Cancel Deal | ✅ | ❌ | ❌ |
| Release Escrow | ✅ | ❌ | ❌ |

### Policy Enforcement
- `DealPolicy@view()` - Users can only view deals they're part of
- `DealPolicy@accept()` - Only relevant party can accept in correct status
- `DealPolicy@reject()` - Only confirmation phase rejection allowed
- `DealPolicy@cancel()` - Only admin can cancel
- `DealPolicy@releaseEscrow()` - Only admin after delivery confirmed

### Middleware
- `auth:api` - All protected endpoints require JWT token
- `role:admin` - Admin endpoints require admin role
- `role:farmer` - Farmer supply endpoints require farmer role
- `require.email.verified` - Deal operations require verified email

## Database Schema

### New Tables

#### farmer_supplies
```sql
- id (PK)
- farmer_id (FK → users)
- product_id (FK → products)
- quantity_available (decimal)
- unit (string)
- price_per_unit (decimal)
- available_from (date)
- available_until (date)
- is_active (boolean)
- created_at, updated_at
- Indexes: (farmer_id, is_active), (product_id, is_active)
```

#### payments
```sql
- id (PK)
- deal_id (FK → deals)
- buyer_id (FK → users)
- amount (decimal)
- status (enum: pending, escrowed, released, refunded, failed)
- payment_method (string)
- transaction_reference (string)
- escrow_released_at (timestamp)
- created_at, updated_at
- Indexes: (deal_id, status), (buyer_id, status)
```

### Updated Tables

#### deals
```sql
- New columns:
  - farmer_supply_id (FK → farmer_supplies, nullable)
  - buyer_confirmed_at (timestamp, nullable)
  - farmer_confirmed_at (timestamp, nullable)
  - admin_notes (text)
  - created_by_admin (boolean)
  
- Preserved columns (backward compatibility):
  - farmer_listing_id (FK → farmer_listings, nullable)
  - buyer_request_id (FK → buyer_requests, nullable)
```

## Migration Path (Backward Compatibility)

### Old Model (P2P - Still Supported)
- Farmers create listings
- Buyers create requests
- Farmers/buyers create deals directly
- Works with existing `farmer_listings` and `buyer_requests` tables

### New Model (Managed - Recommended)
- Farmers submit supplies via `POST /supplies`
- Buyers submit requests via `POST /buyer-requests`
- Admin matches and creates deals via `POST /admin/deals`
- Works with new `farmer_supplies` and `payments` tables

### Migration Strategy
1. Both models can coexist during transition period
2. `created_by_admin` flag distinguishes managed deals from old deals
3. New deals use `farmer_supply_id` and new deal states
4. Old deals continue with `farmer_listing_id` and legacy states
5. Gradually migrate users to supply-based model

## Example Workflows

### Workflow 1: Standard Managed Deal
```
1. Farmer creates supply: POST /supplies
   { farmer_id: 1, product_id: 5, quantity_available: 1000, price_per_unit: 35 }

2. Buyer creates request: POST /buyer-requests
   { buyer_id: 2, product_id: 5, quantity: 100, delivery_location: "Nairobi" }

3. Admin creates deal: POST /admin/deals
   { buyer_request_id: 1, farmer_supply_id: 1, quantity: 100, agreed_price: 35 }
   
4. Buyer accepts: PATCH /deals/1/accept
   { notes: "Ready to proceed" }

5. Farmer accepts: PATCH /deals/1/accept
   { notes: "Will prepare for shipment" }
   
   → Payment auto-created, deal moves to payment_pending

6. Buyer pays: POST /payments/1/pay (via payment gateway)
   
   → Payment escrowed, deal moves to accepted

7. Farmer ships: PATCH /deals/1 (update status to in_transit)

8. Buyer confirms: PATCH /deals/1 (update status to delivered)

9. Admin releases: PATCH /admin/deals/1/release-escrow
   
   → Payment released, deal completed
```

### Workflow 2: Buyer Rejects Deal
```
1. Admin creates deal (PENDING_BUYER_CONFIRMATION)
2. Buyer rejects: PATCH /deals/1/reject
   { reason: "Price too high" }
   
   → Deal moves to REJECTED
   → Farmer and admin notified
   → No payment created
```

### Workflow 3: Admin Cancels Deal
```
1. Deal in any status
2. Admin cancels: PATCH /admin/deals/1/cancel
   { reason: "Out of stock" }
   
   → Deal moves to CANCELLED
   → If payment escrowed, automatically refunded
   → Both parties notified
```

## Testing

### Test Script
Run `test-managed-marketplace.ps1` to test the complete workflow:
```powershell
./test-managed-marketplace.ps1
```

This tests:
- Admin listing requests and supplies
- Admin creating deals
- Buyer accepting/rejecting
- Farmer accepting/rejecting
- Payment creation on both-confirm
- Deal statistics
- Deal rejection flow
- Admin cancellation

### Manual Testing

1. **Setup Test Accounts**
   ```bash
   docker-compose exec app php artisan tinker
   >>> User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'role' => 'admin', ...])
   >>> User::create(['name' => 'Farmer', 'email' => 'farmer@test.com', 'role' => 'farmer', ...])
   >>> User::create(['name' => 'Buyer', 'email' => 'buyer@test.com', 'role' => 'buyer', ...])
   ```

2. **Get JWT Tokens**
   ```bash
   POST /auth/login
   { email: "admin@test.com", password: "password" }
   ```

3. **Create Test Data**
   ```bash
   POST /supplies (as farmer)
   POST /buyer-requests (as buyer)
   ```

4. **Test Deal Creation**
   ```bash
   POST /admin/deals (as admin)
   ```

5. **Test Confirmations**
   ```bash
   PATCH /deals/{id}/accept (as buyer)
   PATCH /deals/{id}/accept (as farmer)
   ```

## Notifications

Automatic notifications are sent:
- `deal_created` → Buyer & Farmer when admin creates deal
- `deal_accepted` → Other party when user accepts
- `deal_rejected` → Both parties when user rejects
- `deal_cancelled` → Both parties when admin cancels
- `payment_required` → Buyer when both confirm (time to pay)
- `payment_escrowed` → Admin when buyer pays
- `escrow_released` → Farmer when payment released
- `deal_completed` → Both parties when deal completes

## Future Enhancements

1. **Intelligent Matching Algorithm**
   - Auto-suggest best farmer-buyer matches
   - Machine learning for price optimization
   - Location-based matching

2. **Advanced Logistics**
   - Integrated logistics partner APIs
   - Real-time shipment tracking
   - Cost optimization

3. **Payment Integrations**
   - M-Pesa integration
   - Bank transfers
   - Credit/debit cards

4. **Analytics Dashboard**
   - Transaction volume and trends
   - Commission analytics
   - User performance metrics
   - Market insights

5. **Dispute Resolution**
   - Escrow hold during disputes
   - Admin arbitration workflow
   - Rating-based dispute severity

6. **Quality Assurance**
   - Pre-delivery product verification
   - Batch sampling
   - Grade certification

## Support & Troubleshooting

### Common Issues

**Issue:** Buyer cannot accept deal
- **Cause:** Deal status is not `pending_buyer_confirmation`
- **Solution:** Check deal status via `GET /deals/{id}`. Ensure farmer hasn't already rejected.

**Issue:** Payment not auto-created
- **Cause:** Farmer didn't accept or previous operation failed
- **Solution:** Verify both `buyer_confirmed_at` and `farmer_confirmed_at` are set.

**Issue:** Admin cannot release escrow
- **Cause:** Deal not in `delivered` status or payment not `escrowed`
- **Solution:** Verify deal status and payment status before releasing.

### Debug Commands

```bash
# Check deal with all relationships
GET /admin/deals/{id}

# View payment for deal
GET /admin/deals/{id}/payment

# List deals by status
GET /admin/deals?status=payment_pending

# View farmer supplies
GET /admin/farmer-supplies?active=true
```

## References

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [JWT Authentication](https://github.com/PHP-Open-Source-Saver/jwt)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
