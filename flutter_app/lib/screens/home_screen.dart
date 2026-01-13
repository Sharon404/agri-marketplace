import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ApiService _apiService = ApiService();
  List<dynamic> _listings = [];
  List<dynamic> _requests = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final listings = await _apiService.getFarmerListings();
      final requests = await _apiService.getBuyerRequests();

      setState(() {
        _listings = listings;
        _requests = requests;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load data: ${e.toString()}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);

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
                  _buildSection('Farmer Listings', _listings, Icons.agriculture),
                  const SizedBox(height: 20),
                  _buildSection('Buyer Requests', _requests, Icons.shopping_cart),
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

  Widget _buildSection(String title, List<dynamic> items, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 24),
            const SizedBox(width: 8),
            Text(
              title,
              style: Theme.of(context).textTheme.titleLarge,
            ),
          ],
        ),
        const SizedBox(height: 8),
        items.isEmpty
            ? Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text('No $title available'),
                ),
              )
            : Column(
                children: items.take(3).map((item) => _buildItemCard(item, title)).toList(),
              ),
        if (items.length > 3)
          TextButton(
            onPressed: () {
              // Navigate to full list
              Navigator.pushNamed(
                context,
                title == 'Farmer Listings' ? '/listings' : '/requests',
              );
            },
            child: Text('View All (${items.length})'),
          ),
      ],
    );
  }

  Widget _buildItemCard(dynamic item, String type) {
    final productName = item['product']?['name'] ?? 'Unknown Product';
    final quantity = item['quantity'] ?? 0;
    final price = item['price_per_unit'] ?? 0;
    final location = item['location'] ?? 'Unknown Location';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text(productName),
        subtitle: Text('$quantity units • \$$price each • $location'),
        trailing: type == 'Farmer Listings'
            ? const Icon(Icons.sell, color: Colors.green)
            : const Icon(Icons.shopping_bag, color: Colors.blue),
        onTap: () {
          // Navigate to detail view
          Navigator.pushNamed(
            context,
            '/item-detail',
            arguments: {'item': item, 'type': type},
          );
        },
      ),
    );
  }
}