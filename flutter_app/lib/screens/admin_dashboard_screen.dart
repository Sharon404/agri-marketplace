import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  _AdminDashboardScreenState createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> with SingleTickerProviderStateMixin {
  final ApiService _apiService = ApiService();
  Map<String, dynamic> _dashboardData = {};
  List<dynamic> _listings = [];
  List<dynamic> _requests = [];
  bool _isLoading = true;
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final dashboardData = await _apiService.getAdminDashboard();
      final listings = await _apiService.getAdminListings();
      final requests = await _apiService.getAdminRequests();

      if (mounted) {
        setState(() {
          _dashboardData = dashboardData;
          _listings = listings;
          _requests = requests;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load admin data: ${e.toString()}')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Admin Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await authProvider.logout();
              Navigator.pushReplacementNamed(context, '/login');
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Overview'),
            Tab(text: 'Listings'),
            Tab(text: 'Requests'),
            Tab(text: 'Analytics'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildOverviewTab(),
                _buildListingsTab(),
                _buildRequestsTab(),
                _buildAnalyticsTab(),
              ],
            ),
    );
  }

  Widget _buildOverviewTab() {
    final stats = _dashboardData['stats'] ?? {};

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'Platform Overview',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 20),
          _buildStatCard('Total Users', stats['total_users']?.toString() ?? '0', Icons.people),
          _buildStatCard('Farmers', stats['total_farmers']?.toString() ?? '0', Icons.agriculture),
          _buildStatCard('Buyers', stats['total_buyers']?.toString() ?? '0', Icons.shopping_cart),
          _buildStatCard('Active Listings', stats['active_listings']?.toString() ?? '0', Icons.inventory),
          _buildStatCard('Active Requests', stats['active_requests']?.toString() ?? '0', Icons.assignment),
          _buildStatCard('Pending Deals', stats['pending_deals']?.toString() ?? '0', Icons.pending),
          _buildStatCard('Active Deals', stats['active_deals']?.toString() ?? '0', Icons.business_center),
          _buildStatCard('Completed Deals', stats['completed_deals']?.toString() ?? '0', Icons.check_circle),
          _buildStatCard('Held Funds', '\$${stats['held_funds']?.toString() ?? '0'}', Icons.account_balance_wallet),
          _buildStatCard('Open Disputes', stats['total_disputes']?.toString() ?? '0', Icons.warning),
        ],
      ),
    );
  }

  Widget _buildListingsTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _listings.length,
      itemBuilder: (context, index) {
        final listing = _listings[index];
        return _buildListingCard(listing);
      },
    );
  }

  Widget _buildRequestsTab() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _requests.length,
      itemBuilder: (context, index) {
        final request = _requests[index];
        return _buildRequestCard(request);
      },
    );
  }

  Widget _buildAnalyticsTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'Market Analytics',
          style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 20),
        // Price trends
        _buildAnalyticsCard('Price Trends', 'Average prices increased by 5% this week'),
        // Volume trends
        _buildAnalyticsCard('Volume Trends', 'Trading volume up 15% from last month'),
        // Regional analysis
        _buildAnalyticsCard('Top Regions', 'Nairobi: 45%, Kiambu: 25%, Machakos: 15%'),
        // Logistics costs
        _buildAnalyticsCard('Avg Logistics Cost', '\$2.50 per km'),
        // Margins
        _buildAnalyticsCard('Platform Margins', '3-5% per transaction'),
      ],
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: Icon(icon, size: 40, color: Colors.green),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(value, style: const TextStyle(fontSize: 18)),
      ),
    );
  }

  Widget _buildListingCard(dynamic listing) {
    final farmerListing = listing['farmer_listing'] ?? {};
    final product = farmerListing['product'] ?? {};
    final farmer = farmerListing['farmer'] ?? {};

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  product['name'] ?? 'Unknown Product',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                Text(
                  '\$${farmerListing['unit_price'] ?? 0}',
                  style: const TextStyle(fontSize: 16, color: Colors.green, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Farmer: ${farmer['name'] ?? 'Unknown'}'),
            Text('Location: ${farmerListing['location'] ?? 'Unknown'}'),
            Text('Quantity: ${farmerListing['quantity'] ?? 0}'),
            Text('Status: ${listing['status'] ?? 'Unknown'}'),
            if (listing['agreed_price'] != null)
              Text('Agreed Price: \$${listing['agreed_price']}'),
            if (listing['agreed_quantity'] != null)
              Text('Agreed Quantity: ${listing['agreed_quantity']}'),
          ],
        ),
      ),
    );
  }

  Widget _buildRequestCard(dynamic request) {
    final buyerRequest = request['buyer_request'] ?? {};
    final product = buyerRequest['product'] ?? {};
    final buyer = buyerRequest['buyer'] ?? {};

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  product['name'] ?? 'Unknown Product',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                Text(
                  '\$${buyerRequest['target_price'] ?? 0}',
                  style: const TextStyle(fontSize: 16, color: Colors.blue, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Buyer: ${buyer['name'] ?? 'Unknown'}'),
            Text('Location: ${buyerRequest['delivery_location'] ?? 'Unknown'}'),
            Text('Quantity: ${buyerRequest['quantity'] ?? 0}'),
            Text('Urgency: ${buyerRequest['urgency'] ?? 'Normal'}'),
            Text('Status: ${request['status'] ?? 'Unknown'}'),
            if (request['agreed_price'] != null)
              Text('Agreed Price: \$${request['agreed_price']}'),
            if (request['agreed_quantity'] != null)
              Text('Agreed Quantity: ${request['agreed_quantity']}'),
          ],
        ),
      ),
    );
  }

  Widget _buildAnalyticsCard(String title, String value) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(value),
        leading: const Icon(Icons.analytics, color: Colors.orange),
      ),
    );
  }
}