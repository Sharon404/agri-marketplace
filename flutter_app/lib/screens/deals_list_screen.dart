import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/deals_provider.dart';
import '../providers/auth_provider.dart';
import 'deal_detail_screen.dart';

class DealsListScreen extends StatefulWidget {
  const DealsListScreen({super.key});

  @override
  _DealsListScreenState createState() => _DealsListScreenState();
}

class _DealsListScreenState extends State<DealsListScreen> {
  String? _selectedStatus;
  
  final List<Map<String, String>> _statusOptions = [
    {'value': '', 'label': 'All Deals'},
    {'value': 'pending_buyer_confirmation', 'label': 'Awaiting Buyer'},
    {'value': 'pending_farmer_confirmation', 'label': 'Awaiting Farmer'},
    {'value': 'both_confirmed', 'label': 'Both Confirmed'},
    {'value': 'payment_pending', 'label': 'Payment Pending'},
    {'value': 'active', 'label': 'Active'},
    {'value': 'completed', 'label': 'Completed'},
    {'value': 'cancelled', 'label': 'Cancelled'},
  ];

  @override
  void initState() {
    super.initState();
    _loadDeals();
  }

  Future<void> _loadDeals() async {
    final dealsProvider = Provider.of<DealsProvider>(context, listen: false);
    try {
      await dealsProvider.fetchDeals(
        status: _selectedStatus != null && _selectedStatus!.isNotEmpty 
          ? _selectedStatus 
          : null
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load deals: ${e.toString()}')),
        );
      }
    }
  }

  void _onStatusChanged(String? status) {
    setState(() {
      _selectedStatus = status;
    });
    _loadDeals();
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

  Widget _buildDealCard(Map<String, dynamic> deal) {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final userId = authProvider.user?['id'];
    final userRole = authProvider.user?['role'];
    
    final product = deal['product'] ?? {};
    final farmer = deal['farmer'] ?? {};
    final buyer = deal['buyer'] ?? {};
    final status = deal['status'] ?? 'unknown';
    
    // Determine if action is needed by current user
    bool needsAction = false;
    String actionText = '';
    
    if (status == 'pending_buyer_confirmation' && userRole == 'buyer') {
      needsAction = true;
      actionText = 'BUYER ACTION REQUIRED';
    } else if (status == 'pending_farmer_confirmation' && userRole == 'farmer') {
      needsAction = true;
      actionText = 'FARMER ACTION REQUIRED';
    }

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      elevation: 3,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => DealDetailScreen(dealId: deal['id']),
            ),
          ).then((_) => _loadDeals()); // Refresh after returning
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      product['name'] ?? 'Unknown Product',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _getStatusColor(status),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      _formatStatus(status),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              if (needsAction) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade100,
                    borderRadius: BorderRadius.circular(4),
                    border: Border.all(color: Colors.orange.shade700),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.notification_important, 
                        color: Colors.orange.shade700, size: 16),
                      const SizedBox(width: 8),
                      Text(
                        actionText,
                        style: TextStyle(
                          color: Colors.orange.shade900,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(Icons.shopping_basket, size: 16, color: Colors.grey),
                  const SizedBox(width: 8),
                  Text(
                    'Quantity: ${deal['quantity']} ${deal['unit'] ?? ''}',
                    style: const TextStyle(fontSize: 14),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.attach_money, size: 16, color: Colors.grey),
                  const SizedBox(width: 8),
                  Text(
                    'Total: \$${deal['total_amount'] ?? '0'}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.green,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Divider(color: Colors.grey.shade300),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Farmer:',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                        Text(
                          farmer['name'] ?? 'Unknown',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        const Text(
                          'Buyer:',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                        Text(
                          buyer['name'] ?? 'Unknown',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    'Created: ${_formatDate(deal['created_at'])}',
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(dynamic date) {
    if (date == null) return 'N/A';
    try {
      final DateTime dt = DateTime.parse(date.toString());
      return '${dt.day}/${dt.month}/${dt.year}';
    } catch (e) {
      return date.toString();
    }
  }

  @override
  Widget build(BuildContext context) {
    final dealsProvider = Provider.of<DealsProvider>(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Deals'),
        backgroundColor: Colors.green,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadDeals,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          // Filter dropdown
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.grey.shade100,
            child: DropdownButtonFormField<String>(
              value: _selectedStatus ?? '',
              decoration: const InputDecoration(
                labelText: 'Filter by Status',
                border: OutlineInputBorder(),
                filled: true,
                fillColor: Colors.white,
              ),
              items: _statusOptions.map((option) {
                return DropdownMenuItem<String>(
                  value: option['value'],
                  child: Text(option['label']!),
                );
              }).toList(),
              onChanged: _onStatusChanged,
            ),
          ),
          
          // Deals list
          Expanded(
            child: dealsProvider.isLoading
                ? const Center(child: CircularProgressIndicator())
                : dealsProvider.error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.error_outline, 
                              size: 64, color: Colors.red),
                            const SizedBox(height: 16),
                            Text('Error: ${dealsProvider.error}'),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _loadDeals,
                              child: const Text('Retry'),
                            ),
                          ],
                        ),
                      )
                    : dealsProvider.deals.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.inbox, 
                                  size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 16),
                                Text(
                                  'No deals found',
                                  style: TextStyle(
                                    fontSize: 18,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  _selectedStatus != null && _selectedStatus!.isNotEmpty
                                      ? 'Try selecting a different status filter'
                                      : 'Deals will appear here once created',
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.grey.shade500,
                                  ),
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _loadDeals,
                            child: ListView.builder(
                              itemCount: dealsProvider.deals.length,
                              itemBuilder: (context, index) {
                                return _buildDealCard(dealsProvider.deals[index]);
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}
