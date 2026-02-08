import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/deals_provider.dart';
import '../providers/auth_provider.dart';

class DealDetailScreen extends StatefulWidget {
  final int dealId;

  const DealDetailScreen({super.key, required this.dealId});

  @override
  _DealDetailScreenState createState() => _DealDetailScreenState();
}

class _DealDetailScreenState extends State<DealDetailScreen> {
  final _notesController = TextEditingController();
  final _reasonController = TextEditingController();
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDeal();
  }

  @override
  void dispose() {
    _notesController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _loadDeal() async {
    final dealsProvider = Provider.of<DealsProvider>(context, listen: false);
    try {
      await dealsProvider.fetchDeal(widget.dealId);
      setState(() => _isLoading = false);
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load deal: ${e.toString()}')),
        );
      }
    }
  }

  Future<void> _acceptDeal() async {
    final notes = _notesController.text.trim();
    
    // Show confirmation dialog
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Accept Deal'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Are you sure you want to accept this deal?'),
            const SizedBox(height: 16),
            TextField(
              controller: _notesController,
              decoration: const InputDecoration(
                labelText: 'Notes (Optional)',
                hintText: 'Add any notes about this acceptance',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: const Text('Accept Deal'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final dealsProvider = Provider.of<DealsProvider>(context, listen: false);
    
    try {
      final result = await dealsProvider.acceptDeal(
        widget.dealId,
        notes: notes.isNotEmpty ? notes : null,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Deal accepted successfully'),
            backgroundColor: Colors.green,
          ),
        );
        _notesController.clear();
        await _loadDeal(); // Reload to show updated status
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to accept deal: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _rejectDeal() async {
    final reason = _reasonController.text.trim();
    
    // Show confirmation dialog
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reject Deal'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Are you sure you want to reject this deal?'),
            const SizedBox(height: 16),
            TextField(
              controller: _reasonController,
              decoration: const InputDecoration(
                labelText: 'Reason (Optional)',
                hintText: 'Explain why you are rejecting this deal',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Reject Deal'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final dealsProvider = Provider.of<DealsProvider>(context, listen: false);
    
    try {
      final result = await dealsProvider.rejectDeal(
        widget.dealId,
        reason: reason.isNotEmpty ? reason : null,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Deal rejected successfully'),
            backgroundColor: Colors.orange,
          ),
        );
        _reasonController.clear();
        await _loadDeal(); // Reload to show updated status
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to reject deal: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending_buyer_confirmation':
      case 'pending_farmer_confirmation':
        return Colors.orange;
      case 'both_confirmed':
        return Colors.blue;
      case 'payment_pending':
        return Colors.purple;
      case 'active':
        return Colors.green;
      case 'completed':
        return Colors.teal;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatStatus(String status) {
    return status.split('_').map((word) => 
      word[0].toUpperCase() + word.substring(1)
    ).join(' ');
  }

  String _formatDate(dynamic date) {
    if (date == null) return 'N/A';
    try {
      final DateTime dt = DateTime.parse(date.toString());
      return '${dt.day}/${dt.month}/${dt.year} ${dt.hour}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (e) {
      return date.toString();
    }
  }

  Widget _buildInfoRow(String label, String value, {IconData? icon}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 20, color: Colors.grey.shade600),
            const SizedBox(width: 12),
          ],
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade700,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(Map<String, dynamic> deal, String userRole) {
    final status = deal['status'];
    
    // Determine if user can accept or reject
    bool canAccept = false;
    bool canReject = false;
    
    if (status == 'pending_buyer_confirmation' && userRole == 'buyer') {
      canAccept = true;
      canReject = true;
    } else if (status == 'pending_farmer_confirmation' && userRole == 'farmer') {
      canAccept = true;
      canReject = true;
    }

    if (!canAccept && !canReject) {
      return const SizedBox.shrink();
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.orange.shade50,
        border: Border(
          top: BorderSide(color: Colors.grey.shade300),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.notification_important, color: Colors.orange.shade700),
              const SizedBox(width: 8),
              Text(
                'ACTION REQUIRED',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.orange.shade900,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              if (canReject)
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _rejectDeal,
                    icon: const Icon(Icons.close),
                    label: const Text('Reject'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              if (canAccept && canReject) const SizedBox(width: 16),
              if (canAccept)
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _acceptDeal,
                    icon: const Icon(Icons.check),
                    label: const Text('Accept'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final dealsProvider = Provider.of<DealsProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context);
    final userRole = authProvider.user?['role'];
    final deal = dealsProvider.currentDeal;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Deal Details'),
        backgroundColor: Colors.green,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadDeal,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : deal == null
              ? const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 64, color: Colors.red),
                      SizedBox(height: 16),
                      Text('Deal not found'),
                    ],
                  ),
                )
              : Column(
                  children: [
                    Expanded(
                      child: SingleChildScrollView(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            // Status header
                            Container(
                              padding: const EdgeInsets.all(16),
                              color: _getStatusColor(deal['status']),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Status:',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  Text(
                                    _formatStatus(deal['status']),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            // Deal information
                            Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Product section
                                  const Text(
                                    'Product Details',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  _buildInfoRow(
                                    'Product',
                                    deal['product']?['name'] ?? 'Unknown',
                                    icon: Icons.inventory_2,
                                  ),
                                  _buildInfoRow(
                                    'Quantity',
                                    '${deal['quantity']} ${deal['unit'] ?? ''}',
                                    icon: Icons.shopping_basket,
                                  ),
                                  _buildInfoRow(
                                    'Unit Price',
                                    '\$${deal['unit_price'] ?? '0'}',
                                    icon: Icons.attach_money,
                                  ),
                                  _buildInfoRow(
                                    'Total Amount',
                                    '\$${deal['total_amount'] ?? '0'}',
                                    icon: Icons.payment,
                                  ),

                                  const Divider(height: 32),

                                  // Parties section
                                  const Text(
                                    'Parties Involved',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  _buildInfoRow(
                                    'Farmer',
                                    deal['farmer']?['name'] ?? 'Unknown',
                                    icon: Icons.person,
                                  ),
                                  _buildInfoRow(
                                    'Buyer',
                                    deal['buyer']?['name'] ?? 'Unknown',
                                    icon: Icons.person_outline,
                                  ),

                                  const Divider(height: 32),

                                  // Timeline section
                                  const Text(
                                    'Timeline',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  _buildInfoRow(
                                    'Created',
                                    _formatDate(deal['created_at']),
                                    icon: Icons.calendar_today,
                                  ),
                                  if (deal['updated_at'] != null)
                                    _buildInfoRow(
                                      'Last Updated',
                                      _formatDate(deal['updated_at']),
                                      icon: Icons.update,
                                    ),

                                  // Payment section (if exists)
                                  if (deal['payment'] != null) ...[
                                    const Divider(height: 32),
                                    const Text(
                                      'Payment Information',
                                      style: TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    _buildInfoRow(
                                      'Payment Status',
                                      _formatStatus(deal['payment']['status'] ?? 'unknown'),
                                      icon: Icons.payment,
                                    ),
                                    _buildInfoRow(
                                      'Amount',
                                      '\$${deal['payment']['amount'] ?? '0'}',
                                      icon: Icons.attach_money,
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    // Action buttons at bottom
                    _buildActionButtons(deal, userRole ?? ''),
                  ],
                ),
    );
  }
}
