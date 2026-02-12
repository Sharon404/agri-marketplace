# Admin Capability Approval System

## Overview

The Admin Capability Approval System provides administrators with a comprehensive interface to manage user capabilities (buy/sell permissions) on the agrarian marketplace. The system implements transaction-based approval workflows, event-driven architecture, and complete audit logging.

## Components

### 1. Controller: `Admin/CapabilityController`

**Location**: `app/Http/Controllers/Admin/CapabilityController.php`

**Methods**:

#### `index(Request $request): View|JsonResponse`
- Displays all capability requests with pagination
- Supports filtering by type (buy/sell/all) and status (pending/approved/rejected)
- Returns Blade view for web or JSON for API
- **Route**: `GET /api/admin/capabilities`

#### `approveBuy(Request $request, User $user): JsonResponse`
- Approves user's buy capability request
- Sets `can_buy = true` and `buy_approved_at = now()`
- **Route**: `POST /api/admin/capabilities/users/{user}/approve-buy`

#### `approveSell(Request $request, User $user): JsonResponse`
- Approves user's sell capability request
- Sets `can_sell = true` and `sell_approved_at = now()`
- **Route**: `POST /api/admin/capabilities/users/{user}/approve-sell`

#### `rejectBuy(Request $request, User $user): JsonResponse`
- Rejects user's buy capability request
- Clears `buy_requested_at` and sets status to 'rejected'
- Optional rejection reason stored in audit log
- **Route**: `POST /api/admin/capabilities/users/{user}/reject-buy`

#### `rejectSell(Request $request, User $user): JsonResponse`
- Rejects user's sell capability request
- Clears `sell_requested_at` and sets status to 'rejected'
- Optional rejection reason stored in audit log
- **Route**: `POST /api/admin/capabilities/users/{user}/reject-sell`

### 2. Event: `Events/CapabilityApproved`

Fired when a user's capability is approved. Useful for:
- Sending notification emails
- Logging important events
- Running background jobs
- Analytics tracking

```php
event(new CapabilityApproved(
    user: $user,
    capability: $capability,
    capabilityType: 'buy', // or 'sell'
    approvedBy: auth()->user(),
));
```

### 3. Routes

**Location**: `routes/admin.php`

```php
Route::prefix('capabilities')->group(function () {
    Route::get('/', [CapabilityController::class, 'index']);
    Route::post('/users/{user}/approve-buy', [CapabilityController::class, 'approveBuy']);
    Route::post('/users/{user}/approve-sell', [CapabilityController::class, 'approveSell']);
    Route::post('/users/{user}/reject-buy', [CapabilityController::class, 'rejectBuy']);
    Route::post('/users/{user}/reject-sell', [CapabilityController::class, 'rejectSell']);
});
```

**Middleware**: `auth:api` and `role:admin`

### 4. Blade Template

**Location**: `resources/views/admin/capabilities/index.blade.php`

#### Features
- **Filter Section**: Filter by capability type (buy/sell) and status
- **Data Table**: Displays all capability requests with user information
- **Status Badges**: Visual indicators for Approved/Pending/Rejected status
- **Action Dropdown**: Approve/Reject buttons for each request
- **Approval Modal**: Confirmation dialog for approvals
- **Rejection Modal**: Rejection dialog with optional reason field
- **Toast Notifications**: Success/error feedback messages
- **Responsive Design**: Bootstrap 5 styling with Velzon theme

#### Table Columns
| Column | Content |
|--------|---------|
| User Name | User avatar + name |
| Email | Clickable email link |
| Role | Badge showing farmer/buyer/admin |
| Buy Request | Status + timestamp if requested |
| Sell Request | Status + timestamp if requested |
| Status | Overall capability status badge |
| Actions | Dropdown menu with approve/reject options |

### 5. Database Tables

#### `user_capabilities`

**Existing Columns**:
```sql
- id: increments
- user_id: foreign key to users
- can_buy: boolean (default: false)
- can_sell: boolean (default: false)
- buy_requested_at: timestamp nullable
- sell_requested_at: timestamp nullable
- buy_approved_at: timestamp nullable
- sell_approved_at: timestamp nullable
- status: enum('active', 'suspended', 'rejected')
- created_at: timestamp
- updated_at: timestamp
```

**Optional Enhancement** (migration provided):
```sql
- buy_rejected_at: timestamp nullable
- sell_rejected_at: timestamp nullable
- rejection_reason: text nullable
```

#### `audit_logs`

Approval/rejection actions are logged with:
- `action`: 'capability_approved' or 'capability_rejected'
- `model_type`: 'UserCapability'
- `model_id`: user_id
- `changes`: JSON with context (type, approver, timestamp, etc.)

## Usage Flow

### Admin Approval Workflow

1. **Admin Views Capabilities**
   - Navigate to `/admin/capabilities`
   - System displays all pending requests

2. **Admin Reviews Request**
   - Click dropdown menu on user row
   - Select "Approve Buy" or "Approve Sell"

3. **Confirmation Dialog**
   - Modal shows capability type and impact
   - Admin clicks "Approve"

4. **System Actions**
   - Updates capability record (transaction)
   - Sets approval timestamp
   - Fires `CapabilityApproved` event
   - Logs to audit trail
   - Shows toast notification

5. **User Enabled**
   - User can now buy/sell on platform
   - Capability check passes in middleware

### Rejection Workflow

1. **Admin Views Capabilities**
   - Click dropdown menu on pending request

2. **Select Rejection**
   - Click "Reject Buy" or "Reject Sell"

3. **Optional Reason**
   - Enter rejection reason (optional)
   - Click "Reject"

4. **System Actions**
   - Clears request timestamp
   - Sets status to 'rejected'
   - Logs reason in audit trail
   - Shows notification

5. **User Notified**
   - User sees request was rejected
   - Can request again if desired

## API Examples

### Get All Pending Requests

```bash
curl -X GET "http://localhost:8000/api/admin/capabilities?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN" \
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
        "can_sell": false,
        "buy_requested_at": "2026-02-12T10:00:00Z",
        "sell_requested_at": null,
        "buy_approved_at": null,
        "sell_approved_at": null,
        "status": "active",
        "user": {
          "id": 3,
          "name": "John Farmer",
          "email": "john@example.com",
          "role": "farmer"
        }
      }
    ],
    "per_page": 20,
    "total": 1
  },
  "message": "Capability requests retrieved successfully"
}
```

### Approve Buy Capability

```bash
curl -X POST "http://localhost:8000/api/admin/capabilities/users/3/approve-buy" \
  -H "Authorization: Bearer YOUR_TOKEN" \
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
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Incomplete farm documentation"}'
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
    "rejection_reason": "Incomplete farm documentation"
  }
}
```

## Error Handling

### Errors Handled

1. **User not found**: 404
2. **Capability record missing**: 404
3. **Already approved**: 422
4. **No pending request**: 422
5. **Database errors**: 500 with rollback
6. **Validation errors**: 422

### Example Error Response

```json
{
  "success": false,
  "message": "Buy capability already approved",
  "status": 422
}
```

## Security

- **Authentication**: All routes require `auth:api` middleware
- **Authorization**: All routes require `role:admin` middleware
- **Transaction Safety**: Approval/rejection wrapped in DB transactions
- **Audit Trail**: All actions logged to audit_logs table
- **Input Validation**: Rejection reason limited to 500 characters

## Testing

Run the comprehensive test script:

```bash
php test_capability_approval_system.php
```

**Tests Included**:
- Capability records verification
- Request capability methods
- Pending request queries
- Approval with transactions
- Event creation
- Audit logging
- Helper methods
- Routes configuration
- Template structure

## Blade Template Details

### Filters
```blade
type: buy | sell | all (default: all)
status: pending | approved | rejected | all (default: all)
```

### Status Badges
- **Approved**: Green badge with checkmark
- **Pending**: Gray badge with clock icon
- **Rejected**: Red badge with X icon

### Action Buttons
- **Approve Buy**: Only shown if buy_requested_at exists and not approved
- **Reject Buy**: Only shown if buy_requested_at exists and not approved
- **Approve Sell**: Only shown if sell_requested_at exists and not approved
- **Reject Sell**: Only shown if sell_requested_at exists and not approved

### Modals
- **Approval Modal**: Shows capability type and action impact
- **Rejection Modal**: Shows capability type and optional reason field

### Notifications
- Toast notifications for success/error
- Auto-dismiss after 5 seconds
- Dismissible with close button

## Migration Instructions

1. **Apply migration** (if using rejection fields):
   ```bash
   php artisan migrate
   ```

2. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Test the system**:
   ```bash
   php test_capability_approval_system.php
   ```

4. **Access admin panel**:
   - Navigate to `/admin/capabilities`
   - Or via API: `/api/admin/capabilities`

## Event Listeners

To respond to capability approvals, create a listener:

```php
php artisan make:listener SendCapabilityApprovedNotification
```

Then in the listener:

```php
public function handle(CapabilityApproved $event)
{
    // Send email notification
    Mail::to($event->user->email)->send(
        new CapabilityApprovedMail($event->user, $event->capabilityType)
    );
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    CapabilityApproved::class => [
        SendCapabilityApprovedNotification::class,
    ],
];
```

## Troubleshooting

### Blade Template Not Rendering
- Ensure controller returns view: `return view('admin.capabilities.index', ...)`
- Check layout extends: `@extends('admin.layout')`

### API Returning 404
- Verify controller has `use` statement
- Check routes file imported in RouteServiceProvider
- Clear routes cache: `php artisan route:clear`

### Approval Not Updating Database
- Check user has capability record
- Verify transaction not being rolled back
- Check database permissions
- Review error log in `storage/logs/`

### Events Not Firing
- Register listener in EventServiceProvider
- Verify event import in controller
- Check `should_queue` setting if using queues

## Performance Considerations

### Queries
- `User::whereHas('capability', ...)` uses EXISTS subqueries
- Pagination set to 20 per page (configurable)
- Single query per filter + pagination

### Optimization Tips
- Add index on `user_id` in user_capabilities
- Add index on `status` column
- Use eager loading: `with('user')`
- Cache filter counts if experiencing slowness

## Future Enhancements

1. **Batch Approvals**: Select multiple users and approve simultaneously
2. **Approval Workflows**: Define approval chains for higher-tier capabilities
3. **Capability Tiers**: Multiple levels of selling (wholesale, retail, export)
4. **Expiration Management**: Set capability expiration dates
5. **Performance Tiers**: Track and enforce sales/purchase limits
6. **Documentation Requirements**: Attach files to capabilities
7. **Verification Integration**: Integrate with verification system

## File Summary

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/CapabilityController.php` | Main controller logic |
| `app/Events/CapabilityApproved.php` | Event class |
| `routes/admin.php` | Route definitions |
| `resources/views/admin/capabilities/index.blade.php` | Blade template |
| `database/migrations/2026_02_12_000003_*.php` | Optional migration |
| `test_capability_approval_system.php` | Test script |

## Support & Documentation

For questions or issues:
1. Check test output: `php test_capability_approval_system.php`
2. Review error logs: `tail -f storage/logs/laravel.log`
3. Check database directly: `SELECT * FROM user_capabilities;`
4. Review audit logs: `SELECT * FROM audit_logs WHERE action LIKE 'capability_%';`
