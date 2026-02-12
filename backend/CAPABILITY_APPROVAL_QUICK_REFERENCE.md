# Admin Capability Approval System - Quick Reference

## 🚀 Quick Start

### Access the System

**Web Interface**:
```
http://localhost:8000/admin/capabilities
```

**API Endpoint**:
```
GET http://localhost:8000/api/admin/capabilities
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 📋 File Structure

```
backend/
├── app/
│   ├── Http/Controllers/Admin/
│   │   └── CapabilityController.php          (NEW)
│   └── Events/
│       └── CapabilityApproved.php            (NEW)
├── routes/
│   └── admin.php                              (UPDATED)
├── resources/views/admin/capabilities/
│   └── index.blade.php                        (NEW)
├── database/migrations/
│   └── 2026_02_12_000003_*.php               (NEW - Optional)
├── test_capability_approval_system.php        (NEW)
├── CAPABILITY_APPROVAL_SYSTEM.md              (NEW)
└── CAPABILITY_APPROVAL_SYSTEM_IMPLEMENTATION.md (NEW)
```

---

## 🔗 Routes Overview

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/admin/capabilities` | List all capability requests |
| POST | `/api/admin/capabilities/users/{user}/approve-buy` | Approve buy capability |
| POST | `/api/admin/capabilities/users/{user}/approve-sell` | Approve sell capability |
| POST | `/api/admin/capabilities/users/{user}/reject-buy` | Reject buy capability |
| POST | `/api/admin/capabilities/users/{user}/reject-sell` | Reject sell capability |

**Authentication**: `auth:api` + `role:admin`

---

## 🎛️ Controller Methods

### `index(Request $request): View|JsonResponse`
Lists capability requests with filters.

**Query Parameters**:
- `type`: buy | sell | all (default: all)
- `status`: pending | approved | rejected | all (default: all)

**Example**:
```bash
GET /api/admin/capabilities?type=buy&status=pending
```

### `approveBuy(Request $request, User $user): JsonResponse`
Approves user's buy capability.

**Database Updates**:
- `can_buy` = true
- `buy_approved_at` = now()
- `status` = 'active'

**Events**: Fires `CapabilityApproved` event

### `approveSell(Request $request, User $user): JsonResponse`
Approves user's sell capability.

**Database Updates**:
- `can_sell` = true
- `sell_approved_at` = now()
- `status` = 'active'

**Events**: Fires `CapabilityApproved` event

### `rejectBuy(Request $request, User $user): JsonResponse`
Rejects user's buy capability request.

**Request Body** (optional):
```json
{
  "reason": "Incomplete documentation"
}
```

**Database Updates**:
- `buy_requested_at` = null (clears request)
- `status` = 'rejected'

### `rejectSell(Request $request, User $user): JsonResponse`
Rejects user's sell capability request.

**Request Body** (optional):
```json
{
  "reason": "Farm verification failed"
}
```

**Database Updates**:
- `sell_requested_at` = null (clears request)
- `status` = 'rejected'

---

## 🎨 Blade Template Features

### Filters
```blade
Type: buy | sell | all
Status: pending | approved | rejected | all
```

### Table Columns
1. User Name (with avatar)
2. Email (clickable)
3. Role (badge)
4. Buy Request Status
5. Sell Request Status
6. Overall Status
7. Actions (dropdown)

### Status Badges
- **Approved** ✅ (Green)
- **Pending** ⏱️ (Gray)
- **Rejected** ❌ (Red)
- **Requested** ⚠️ (Orange)

### Action Buttons
- Approve Buy (green checkmark)
- Reject Buy (red X)
- Approve Sell (green checkmark)
- Reject Sell (red X)

### Modals
- **Approval Modal**: Green header, confirmation
- **Rejection Modal**: Red header, optional reason field

---

## 💾 Database Schema

### user_capabilities Table

**Existing Columns**:
```sql
id (primary key)
user_id (foreign key)
can_buy (boolean)
can_sell (boolean)
buy_requested_at (timestamp nullable)
sell_requested_at (timestamp nullable)
buy_approved_at (timestamp nullable)
sell_approved_at (timestamp nullable)
status (enum: active, suspended, rejected)
created_at
updated_at
```

**Optional Enhancement Columns** (from migration):
```sql
buy_rejected_at (timestamp nullable)
sell_rejected_at (timestamp nullable)
rejection_reason (text nullable)
```

---

## 📡 API Examples

### Get Pending Buy Requests
```bash
curl -X GET "http://localhost:8000/api/admin/capabilities?type=buy&status=pending" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "user_id": 3,
        "can_buy": false,
        "buy_requested_at": "2026-02-12T10:00:00Z",
        "buy_approved_at": null,
        "user": {
          "id": 3,
          "name": "John Farmer",
          "email": "john@example.com",
          "role": "farmer"
        }
      }
    ],
    "total": 1
  },
  "message": "Capability requests retrieved successfully"
}
```

### Approve Buy Capability
```bash
curl -X POST "http://localhost:8000/api/admin/capabilities/users/3/approve-buy" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

**Response**:
```json
{
  "success": true,
  "message": "Buy capability approved successfully",
  "data": {
    "user_id": 3,
    "user_name": "John Farmer",
    "capability_type": "buy",
    "approved_at": "2026-02-12T10:15:00Z"
  }
}
```

### Reject Sell Capability
```bash
curl -X POST "http://localhost:8000/api/admin/capabilities/users/3/reject-sell" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Documentation incomplete"}'
```

**Response**:
```json
{
  "success": true,
  "message": "Sell capability request rejected",
  "data": {
    "user_id": 3,
    "user_name": "John Farmer",
    "capability_type": "sell",
    "rejection_reason": "Documentation incomplete"
  }
}
```

---

## 🧪 Testing

### Run Tests
```bash
docker exec agri-backend-app php test_capability_approval_system.php
```

### Test Coverage
- ✅ Capability record verification
- ✅ Request capability methods
- ✅ Pending request queries
- ✅ Database transactions
- ✅ Event creation
- ✅ Audit logging
- ✅ Helper methods
- ✅ Route configuration
- ✅ Blade template structure

---

## 🔒 Security

### Authentication
All routes require JWT token in `Authorization` header.

### Authorization
All routes require `role:admin` middleware.

### Transaction Safety
All database modifications wrapped in transactions with automatic rollback on error.

### Audit Trail
All approvals and rejections logged to audit_logs table.

### Input Validation
- Rejection reason: max 500 characters
- User validation
- Capability record validation

---

## ⚙️ Configuration

### Environment Setup
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart backend
docker restart agri-backend-app
```

### Migration (Optional)
```bash
# Apply optional rejection tracking fields
php artisan migrate

# Or for single migration
php artisan migrate --path=database/migrations/2026_02_12_000003_add_rejection_fields_to_user_capabilities.php
```

---

## 📊 Typical Workflow

### Step 1: Admin Views Dashboard
```
Navigate to /admin/capabilities
```

### Step 2: See Pending Requests
```
Table shows users with buy_requested_at or sell_requested_at
Status shows "Pending"
```

### Step 3: Review User Details
```
See user name, email, role
Check what capability is requested
```

### Step 4: Take Action
```
Click dropdown menu
Select "Approve Buy" or "Reject Buy" (or Sell)
```

### Step 5: Confirm Action
```
Modal appears with confirmation
Admin confirms
```

### Step 6: Update Processed
```
Database updated in transaction
Event fired
Audit logged
Notification shown
Page reloads
```

### Step 7: Status Updated
```
User now shows capability as approved
Or shows pending with rejection
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Routes not found | `php artisan route:clear` |
| Template not rendering | Check layout extends `admin.layout` |
| Approval failing | Verify user has capability record |
| Events not firing | Register listener in EventServiceProvider |
| API 401 error | Check JWT token validity |
| API 403 error | Verify user is admin |
| Database errors | Check PostgreSQL container running |

---

## 📖 Documentation Files

| File | Content |
|------|---------|
| `CAPABILITY_APPROVAL_SYSTEM.md` | Comprehensive documentation |
| `CAPABILITY_APPROVAL_SYSTEM_IMPLEMENTATION.md` | Implementation details |
| This file | Quick reference guide |

---

## 🎯 Key Points

✅ **All components created and tested**

✅ **Full CRUD operations for capability approval**

✅ **Transaction-safe database operations**

✅ **Event-driven architecture**

✅ **Comprehensive audit trail**

✅ **User-friendly Blade template**

✅ **REST API with JSON responses**

✅ **Production-ready code**

---

## 📞 Direct References

### Controller Location
```
backend/app/Http/Controllers/Admin/CapabilityController.php
```

### Event Location
```
backend/app/Events/CapabilityApproved.php
```

### Routes Location
```
backend/routes/admin.php (lines with 'capabilities')
```

### Template Location
```
backend/resources/views/admin/capabilities/index.blade.php
```

### Test Script
```
backend/test_capability_approval_system.php
```

---

## ✅ Deployment Checklist

- [ ] Controller created (`CapabilityController.php`)
- [ ] Event created (`CapabilityApproved.php`)
- [ ] Routes added to `routes/admin.php`
- [ ] Blade template created (`index.blade.php`)
- [ ] Test executed successfully
- [ ] Caches cleared
- [ ] Backend restarted
- [ ] Routes cached (production)

---

**Status**: ✅ READY FOR PRODUCTION

All components are implemented, tested, and documented.
