import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ApiService _apiService = ApiService();
  Map<String, dynamic> _analytics = {};
  List<dynamic> _listings = [];
  List<dynamic> _requests = [];
  List<dynamic> _deals = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final role = authProvider.user?['role'];

      if (role == 'admin') {
        // Admins use the web-based dashboard at /admin-dashboard
        // This mobile app is for farmers and buyers only
        WidgetsBinding.instance.addPostFrameCallback((_) {
          showDialog(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('Admin Access'),
              content: const Text('Admin dashboard features are available in the web interface at:\n\nhttp://localhost:8000/admin-dashboard'),
              actions: [
                TextButton(
                  onPressed: () async {
                    await authProvider.logout();
                    Navigator.pushReplacementNamed(context, '/login');
                  },
                  child: const Text('Return to Login'),
                ),
              ],
            ),
          );
        });
        return;
      }

      // Load role-specific analytics first (required)
      if (role == 'farmer') {
        _analytics = await _apiService.getFarmerAnalytics();
      } else if (role == 'buyer') {
        _analytics = await _apiService.getBuyerAnalytics();
      }

      // Load additional data (optional - failures don't break the UI)
      if (role == 'farmer') {
        try {
          _listings = await _apiService.getFarmerListings();
        } catch (e) {
          print('Error loading farmer listings: $e');
          _listings = [];
        }
      } else if (role == 'buyer') {
        try {
          _requests = await _apiService.getBuyerRequests();
        } catch (e) {
          print('Error loading buyer requests: $e');
          _requests = [];
        }
      }

      // Load deals for both roles (optional)
      try {
        _deals = await _apiService.getDeals();
      } catch (e) {
        print('Error loading deals: $e');
        _deals = [];
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load analytics: ${e.toString()}')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final userData = authProvider.user;
    final role = userData?['role'] as String?;
    
    // Debug logging
    print('=== HOME SCREEN DEBUG ===');
    print('User data: $userData');
    print('Role: $role');
    print('Role is null: ${role == null}');

    if (role == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Error')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text('User role is missing!'),
              const SizedBox(height: 16),
              Text('User data: $userData'),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () async {
                  await authProvider.logout();
                  Navigator.pushReplacementNamed(context, '/login');
                },
                child: const Text('Go Back to Login'),
              ),
            ],
          ),
        ),
      );
    }

    if (role == 'admin') {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Agri Marketplace'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await authProvider.logout();
              Navigator.pushReplacementNamed(context, '/login');
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Text(
                    'Welcome, ${authProvider.user?['name'] ?? 'User'}!',
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 20),
                  if (role == 'farmer') ..._buildFarmerDashboard(),
                  if (role == 'buyer') ..._buildBuyerDashboard(),
                  if (role != 'farmer' && role != 'buyer' && role != 'admin') ...[
                    Center(
                      child: Column(
                        children: [
                          const Text('Invalid user role. Please contact support.'),
                          const SizedBox(height: 16),
                          Text('Role received: "$role"'),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          // Navigate to create listing/request based on user role
          final role = authProvider.user?['role'];
          if (role == 'farmer') {
            Navigator.pushNamed(context, '/create-listing');
          } else if (role == 'buyer') {
            Navigator.pushNamed(context, '/create-request');
          }
        },
        tooltip: 'Create New',
        child: const Icon(Icons.add),
      ),
    );
  }

  List<Widget> _buildFarmerDashboard() {
    final marketHighlights = _analytics['market_highlights'] as List<dynamic>? ?? [];
    final totalVerifiedBuyers = _analytics['total_verified_buyers'] ?? 0;

    // Show default content if no analytics data
    if (marketHighlights.isEmpty) {
      return [
        Container(
          padding: const EdgeInsets.all(20),
          margin: const EdgeInsets.only(bottom: 20),
          decoration: BoxDecoration(
            color: Colors.green.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.green.shade200),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                '🌾 Welcome to Agri Marketplace!',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.green,
                ),
              ),
              const SizedBox(height: 10),
              const Text(
                'Start by creating your first listing to connect with buyers in your area.',
                style: TextStyle(fontSize: 16, color: Colors.green),
              ),
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: () => Navigator.pushNamed(context, '/create-listing'),
                icon: const Icon(Icons.add),
                label: const Text('Create Your First Listing'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        _buildStatCard('Total Listings', '0', Icons.inventory, Colors.blue),
        _buildStatCard('Verified Buyers', '$totalVerifiedBuyers', Icons.verified_user, Colors.orange),
        _buildStatCard('Your Earnings', '\$0', Icons.attach_money, Colors.green),
      ];
    }

    return [
      // Hero Section for Farmers
      Container(
        padding: const EdgeInsets.all(20),
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.green.shade400, Colors.green.shade700],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.green.withOpacity(0.3),
              spreadRadius: 2,
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.agriculture, color: Colors.white, size: 32),
                SizedBox(width: 12),
                Text(
                  'Grow Your Business!',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const Text(
              'Connect with buyers across Kenya and sell your fresh produce directly',
              style: TextStyle(
                fontSize: 16,
                color: Colors.white,
                fontWeight: FontWeight.w300,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => Navigator.pushNamed(context, '/create-listing'),
              icon: const Icon(Icons.add_circle),
              label: const Text('Create Your First Listing'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: Colors.green.shade700,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(25),
                ),
              ),
            ),
          ],
        ),
      ),

      // Quick Action Buttons for Farmers
      Row(
        children: [
          Expanded(
            child: ElevatedButton.icon(
              onPressed: () {
                Navigator.pushNamed(context, '/deals');
              },
              icon: const Icon(Icons.handshake),
              label: const Text('My Deals'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blue,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: ElevatedButton.icon(
              onPressed: () {
                Navigator.pushNamed(context, '/farmer-supplies');
              },
              icon: const Icon(Icons.inventory_2),
              label: const Text('My Supplies'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.orange,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ),
        ],
      ),
      const SizedBox(height: 20),

      // Quick Stats Row
      Row(
        children: [
          Expanded(
            child: _buildStatCard(
              'Active Buyers',
              '${_analytics['total_verified_buyers'] ?? 0}',
              Icons.people,
              Colors.blue,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _buildStatCard(
              'Your Listings',
              '${_listings.length}',
              Icons.inventory,
              Colors.orange,
            ),
          ),
        ],
      ),
      const SizedBox(height: 24),

      // Market Opportunities Section
      Row(
        children: [
          const Icon(Icons.trending_up, color: Colors.green, size: 28),
          const SizedBox(width: 8),
          Text(
            'Market Opportunities',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.green.shade700,
            ),
          ),
        ],
      ),
      const SizedBox(height: 16),

      if (marketHighlights.isEmpty)
        Container(
          padding: const EdgeInsets.all(32),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Column(
            children: [
              Icon(Icons.show_chart, size: 64, color: Colors.grey.shade400),
              const SizedBox(height: 16),
              const Text(
                'Market data loading...',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF757575),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Discover what buyers are looking for',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade500,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        )
      else
        ...marketHighlights.take(6).map((highlight) => _buildMarketOpportunityCard(highlight)),

      const SizedBox(height: 24),

      // Your Listings Section
      if (_listings.isNotEmpty) ...[
        Row(
          children: [
            const Icon(Icons.inventory_2, color: Colors.green, size: 28),
            const SizedBox(width: 8),
            Text(
              'Your Listings (${_listings.length})',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.green.shade700,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        ..._listings.take(5).map((listing) => _buildListingCard(listing)),
        const SizedBox(height: 24),
      ],

      // Call to Action Section
      Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.orange.shade400, Colors.orange.shade600],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.rocket_launch, color: Colors.white, size: 28),
                SizedBox(width: 12),
                Text(
                  'Ready to sell?',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const Text(
              'List your products and reach thousands of buyers instantly',
              style: TextStyle(
                fontSize: 14,
                color: Colors.white,
                fontWeight: FontWeight.w300,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pushNamed(context, '/create-listing'),
                    icon: const Icon(Icons.add),
                    label: const Text('Add Listing'),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Colors.white),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      // Navigate to view all opportunities
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('View all opportunities coming soon!')),
                      );
                    },
                    icon: const Icon(Icons.analytics),
                    label: const Text('View Market'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.orange.shade700,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    ];
  }

  Widget _buildMarketOpportunityCard(dynamic highlight) {
    final productName = highlight['product'] ?? 'Unknown Product';
    final demandLevel = highlight['demand_level'] ?? 'Unknown';
    final buyersRequesting = highlight['buyers_requesting'] ?? 0;
    final weeklyDemand = highlight['weekly_demand'] ?? 'N/A';
    final activeSuppliers = highlight['active_suppliers'] ?? 0;
    final demandRegion = highlight['demand_region'] ?? 'N/A';

    // Get demand color
    Color getDemandColor(String demand) {
      if (demand.toLowerCase().contains('high')) return Colors.red;
      if (demand.toLowerCase().contains('medium')) return Colors.orange;
      return Colors.green;
    }

    final demandColor = getDemandColor(demandLevel);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: demandColor.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(color: demandColor.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  productName,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: demandColor,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: demandColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: demandColor.withOpacity(0.3)),
                ),
                child: Text(
                  demandLevel,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: demandColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildOpportunityMetric(
                  'Buyers',
                  '$buyersRequesting',
                  Icons.people,
                  Colors.blue,
                ),
              ),
              Expanded(
                child: _buildOpportunityMetric(
                  'Weekly Demand',
                  weeklyDemand,
                  Icons.trending_up,
                  Colors.green,
                ),
              ),
              Expanded(
                child: _buildOpportunityMetric(
                  'Suppliers',
                  '$activeSuppliers',
                  Icons.business,
                  Colors.orange,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 4),
              Text(
                'Demand in: $demandRegion',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => Navigator.pushNamed(context, '/create-listing'),
              icon: const Icon(Icons.add_circle, size: 18),
              label: const Text('List Your Produce'),
              style: ElevatedButton.styleFrom(
                backgroundColor: demandColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 10),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOpportunityMetric(String label, String value, IconData icon, Color color) {
    return Column(
      children: [
        Icon(icon, color: color, size: 20),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 10,
            color: Colors.grey.shade600,
            fontWeight: FontWeight.w500,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildListingCard(dynamic listing) {
    final productName = listing['product']?['name'] ?? 'Unknown Product';
    final quantity = listing['quantity'] ?? 0;
    final price = listing['price_per_unit'] ?? 0;
    final location = listing['location'] ?? 'N/A';
    final description = listing['description'] ?? '';
    final isActive = listing['is_active'] ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.green.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(
          color: isActive ? Colors.green.withOpacity(0.3) : Colors.grey.withOpacity(0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  productName,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: isActive ? Colors.green.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isActive ? Colors.green.withOpacity(0.3) : Colors.grey.withOpacity(0.3),
                  ),
                ),
                child: Text(
                  isActive ? 'Active' : 'Inactive',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: isActive ? Colors.green : Colors.grey,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (description.isNotEmpty)
            Text(
              description,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade700,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          if (description.isNotEmpty) const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Quantity',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '$quantity kg',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Price per Unit',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${price is int ? price : (price as double).toStringAsFixed(2)} Ksh',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 4),
              Text(
                location,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  List<Widget> _buildBuyerDashboard() {
    final supplyHighlights = _analytics['supply_highlights'] as List<dynamic>? ?? [];
    final totalVerifiedFarmers = _analytics['total_verified_farmers'] ?? 0;

    // Show default content if no analytics data
    if (supplyHighlights.isEmpty) {
      return [
        Container(
          padding: const EdgeInsets.all(20),
          margin: const EdgeInsets.only(bottom: 20),
          decoration: BoxDecoration(
            color: Colors.blue.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.blue.shade200),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                '🛒 Welcome to Agri Marketplace!',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.blue,
                ),
              ),
              const SizedBox(height: 10),
              const Text(
                'Connect directly with local farmers for fresh, quality produce.',
                style: TextStyle(fontSize: 16, color: Colors.blue),
              ),
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: () => Navigator.pushNamed(context, '/create-request'),
                icon: const Icon(Icons.add),
                label: const Text('Create Your First Request'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        _buildStatCard('Available Products', '8+', Icons.inventory, Colors.green),
        _buildStatCard('Verified Farmers', '$totalVerifiedFarmers', Icons.verified_user, Colors.blue),
        _buildStatCard('Your Requests', '0', Icons.shopping_cart, Colors.orange),
      ];
    }

    return [
      // Hero Section
      Container(
        padding: const EdgeInsets.all(20),
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.blue.shade400, Colors.blue.shade700],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.blue.withOpacity(0.3),
              spreadRadius: 2,
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.celebration, color: Colors.white, size: 32),
                SizedBox(width: 12),
                Text(
                  'Welcome to Agri Marketplace!',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const Text(
              'Discover fresh, local produce from verified farmers across Kenya',
              style: TextStyle(
                fontSize: 16,
                color: Colors.white,
                fontWeight: FontWeight.w300,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => Navigator.pushNamed(context, '/create-request'),
              icon: const Icon(Icons.add_shopping_cart),
              label: const Text('Create Your First Request'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: Colors.blue.shade700,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(25),
                ),
              ),
            ),
          ],
        ),
      ),

      // Quick Stats Row
      Row(
        children: [
          Expanded(
            child: _buildStatCard(
              'Verified Farmers',
              '${totalVerifiedFarmers}',
              Icons.verified_user,
              Colors.green,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _buildStatCard(
              'On-Time Delivery',
              '98%',
              Icons.local_shipping,
              Colors.orange,
            ),
          ),
        ],
      ),
      const SizedBox(height: 24),

      // Popular Products Section
      Row(
        children: [
          const Icon(Icons.trending_up, color: Colors.purple, size: 28),
          const SizedBox(width: 8),
          Text(
            'Popular Products Available',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.purple.shade700,
            ),
          ),
        ],
      ),
      const SizedBox(height: 16),

      if (supplyHighlights.isEmpty)
        Container(
          padding: const EdgeInsets.all(32),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Column(
            children: [
              Icon(Icons.inventory_2, size: 64, color: Colors.grey.shade400),
              const SizedBox(height: 16),
              const Text(
                'Fresh produce coming soon!',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF757575),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Farmers are preparing their best harvest for you',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade500,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        )
      else
        ...supplyHighlights.take(6).map((highlight) => _buildProductCard(highlight)),

      const SizedBox(height: 24),

      // Your Requests Section
      if (_requests.isNotEmpty) ...[
        Row(
          children: [
            const Icon(Icons.shopping_cart, color: Colors.blue, size: 28),
            const SizedBox(width: 8),
            Text(
              'Your Requests (${_requests.length})',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.blue.shade700,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        ..._requests.take(5).map((request) => _buildRequestCard(request)),
        const SizedBox(height: 24),
      ],

      // Call to Action Section
      Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.green.shade400, Colors.green.shade600],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.lightbulb, color: Colors.white, size: 28),
                SizedBox(width: 12),
                Text(
                  'Ready to get started?',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            const Text(
              'Connect with local farmers and get fresh produce delivered to your door',
              style: TextStyle(
                fontSize: 14,
                color: Colors.white,
                fontWeight: FontWeight.w300,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pushNamed(context, '/create-request'),
                    icon: const Icon(Icons.add),
                    label: const Text('Create Request'),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Colors.white),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      // Navigate to browse all products
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Browse all products coming soon!')),
                      );
                    },
                    icon: const Icon(Icons.search),
                    label: const Text('Browse All'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.green.shade700,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),

      // Quick Action Button for Buyers
      ElevatedButton.icon(
        onPressed: () {
          Navigator.pushNamed(context, '/deals');
        },
        icon: const Icon(Icons.handshake),
        label: const Text('View My Deals'),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.blue,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
          minimumSize: const Size(double.infinity, 0),
        ),
      ),
      const SizedBox(height: 20),
    ];
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              color: color.withOpacity(0.8),
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildProductCard(dynamic highlight) {
    final productName = highlight['product'] ?? 'Unknown Product';
    final availability = highlight['supply_availability'] ?? 'N/A';
    final coverage = highlight['delivery_coverage'] ?? 'N/A';

    // Get product icon based on name
    IconData getProductIcon(String product) {
      final productLower = product.toLowerCase();
      if (productLower.contains('tomato')) return Icons.restaurant;
      if (productLower.contains('potato')) return Icons.restaurant;
      if (productLower.contains('onion')) return Icons.restaurant;
      if (productLower.contains('carrot')) return Icons.restaurant;
      if (productLower.contains('banana')) return Icons.restaurant;
      if (productLower.contains('mango')) return Icons.restaurant;
      if (productLower.contains('orange')) return Icons.restaurant;
      if (productLower.contains('apple')) return Icons.restaurant;
      if (productLower.contains('rice')) return Icons.restaurant;
      if (productLower.contains('wheat')) return Icons.grass;
      if (productLower.contains('maize')) return Icons.grass;
      if (productLower.contains('coffee')) return Icons.local_cafe;
      if (productLower.contains('tea')) return Icons.emoji_food_beverage;
      return Icons.inventory;
    }

    // Get vibrant colors for different products
    Color getProductColor(String product) {
      final productLower = product.toLowerCase();
      if (productLower.contains('tomato')) return Colors.red;
      if (productLower.contains('potato')) return Colors.brown;
      if (productLower.contains('onion')) return Colors.purple;
      if (productLower.contains('carrot')) return Colors.orange;
      if (productLower.contains('banana')) return Colors.yellow;
      if (productLower.contains('mango')) return Colors.orange;
      if (productLower.contains('orange')) return Colors.orange;
      if (productLower.contains('apple')) return Colors.red;
      if (productLower.contains('rice')) return Colors.amber;
      if (productLower.contains('wheat')) return Colors.amber;
      if (productLower.contains('maize')) return Colors.yellow;
      if (productLower.contains('coffee')) return Colors.brown;
      if (productLower.contains('tea')) return Colors.green;
      return Colors.blue;
    }

    final productColor = getProductColor(productName);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: productColor.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(color: productColor.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: productColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              getProductIcon(productName),
              color: productColor,
              size: 32,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  productName,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: productColor,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Available: $availability',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Icon(Icons.location_on, size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        coverage,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => Navigator.pushNamed(context, '/create-request'),
            icon: Icon(Icons.add_shopping_cart, color: productColor),
            tooltip: 'Request this product',
          ),
        ],
      ),
    );
  }

  Widget _buildRequestCard(dynamic request) {
    final productName = request['product']?['name'] ?? 'Unknown Product';
    final quantity = request['quantity'] ?? 0;
    final maxPrice = request['max_price'] ?? 0;
    final location = request['location'] ?? 'N/A';
    final description = request['description'] ?? '';
    final isActive = request['is_active'] ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.blue.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(
          color: isActive ? Colors.blue.withOpacity(0.3) : Colors.grey.withOpacity(0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  productName,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.blue,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: isActive ? Colors.blue.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isActive ? Colors.blue.withOpacity(0.3) : Colors.grey.withOpacity(0.3),
                  ),
                ),
                child: Text(
                  isActive ? 'Active' : 'Inactive',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: isActive ? Colors.blue : Colors.grey,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (description.isNotEmpty)
            Text(
              description,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade700,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          if (description.isNotEmpty) const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Quantity Needed',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '$quantity kg',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Max Price',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${maxPrice is int ? maxPrice : (maxPrice as double).toStringAsFixed(2)} Ksh',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 4),
              Text(
                location,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildHighlightRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              '$label:',
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}