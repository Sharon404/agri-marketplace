import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product_model.dart';
import '../providers/auth_provider.dart';
import '../providers/product_provider.dart';
import 'seller_add_product_screen.dart';

class SellerDashboardScreen extends StatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ProductProvider>().loadMyProducts();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final seller = auth.user?.sellerProfile;
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A5276),
        foregroundColor: Colors.white,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Seller Dashboard', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            if (seller != null)
              Text(seller.businessName, style: const TextStyle(fontSize: 11, color: Color(0xFF85C1E9))),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: const Color(0xFF58D68D),
          labelColor: Colors.white,
          unselectedLabelColor: const Color(0xFF85C1E9),
          tabs: const [
            Tab(icon: Icon(Icons.inventory_2, size: 18), text: 'My Products'),
            Tab(icon: Icon(Icons.receipt_long, size: 18), text: 'Orders'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: const Color(0xFF27AE60),
        onPressed: () => Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const SellerAddProductScreen()),
        ).then((_) => context.read<ProductProvider>().loadMyProducts()),
        icon: const Icon(Icons.add),
        label: const Text('Add Product'),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _MyProductsTab(),
          _OrdersTab(),
        ],
      ),
    );
  }
}

class _MyProductsTab extends StatelessWidget {
  const _MyProductsTab();

  @override
  Widget build(BuildContext context) {
    return Consumer<ProductProvider>(
      builder: (context, provider, _) {
        if (provider.myProductsLoading) {
          return const Center(child: CircularProgressIndicator());
        }
        if (provider.myProductsError != null) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(provider.myProductsError!, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 8),
                ElevatedButton(onPressed: provider.loadMyProducts, child: const Text('Retry')),
              ],
            ),
          );
        }
        final products = provider.myProducts;
        if (products.isEmpty) {
          return const Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey),
                SizedBox(height: 12),
                Text('No products yet', style: TextStyle(color: Colors.grey, fontSize: 16)),
                SizedBox(height: 4),
                Text('Tap + Add Product to get started', style: TextStyle(color: Colors.grey, fontSize: 12)),
              ],
            ),
          );
        }
        return ListView.builder(
          padding: const EdgeInsets.all(12),
          itemCount: products.length,
          itemBuilder: (context, i) => _SellerProductTile(product: products[i]),
        );
      },
    );
  }
}

class _SellerProductTile extends StatelessWidget {
  const _SellerProductTile({required this.product});
  final ProductModel product;

  @override
  Widget build(BuildContext context) {
    final primaryImg = product.images.isEmpty
        ? null
        : product.images.firstWhere((i) => i.isPrimary, orElse: () => product.images.first);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      child: ListTile(
        contentPadding: const EdgeInsets.all(10),
        leading: ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: SizedBox(
            width: 64,
            height: 64,
            child: primaryImg != null
                ? Image.network(primaryImg.imageUrl, fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _imgPlaceholder())
                : _imgPlaceholder(),
          ),
        ),
        title: Text(product.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('KSh ${product.price.toStringAsFixed(0)}',
                style: const TextStyle(color: Color(0xFFE74C3C), fontWeight: FontWeight.bold)),
            const SizedBox(height: 2),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: product.isActive ? const Color(0xFFD5F5E3) : const Color(0xFFFDEBD0),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    product.isActive ? 'Active' : 'Inactive',
                    style: TextStyle(
                      fontSize: 10,
                      color: product.isActive ? const Color(0xFF1E8449) : const Color(0xFFE59866),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Text('Stock: ${product.stockQuantity}',
                    style: const TextStyle(fontSize: 11, color: Colors.grey)),
              ],
            ),
          ],
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (action) => _handleAction(context, action),
          itemBuilder: (_) => [
            const PopupMenuItem(value: 'edit', child: Row(children: [Icon(Icons.edit, size: 16), SizedBox(width: 8), Text('Edit')])),
            const PopupMenuItem(value: 'delete', child: Row(children: [Icon(Icons.delete, size: 16, color: Colors.red), SizedBox(width: 8), Text('Delete', style: TextStyle(color: Colors.red))])),
          ],
        ),
      ),
    );
  }

  void _handleAction(BuildContext context, String action) async {
    if (action == 'edit') {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => SellerAddProductScreen(editProduct: product)),
      ).then((_) => context.read<ProductProvider>().loadMyProducts());
    } else if (action == 'delete') {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Delete Product'),
          content: Text('Delete "${product.name}"? This cannot be undone.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete'),
            ),
          ],
        ),
      );
      if (confirmed == true && context.mounted) {
        try {
          await context.read<ProductProvider>().deleteProduct(product.id);
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Product deleted'), backgroundColor: Colors.green),
            );
          }
        } catch (e) {
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
            );
          }
        }
      }
    }
  }

  Widget _imgPlaceholder() => Container(
        color: const Color(0xFFECF0F1),
        child: const Icon(Icons.eco, color: Color(0xFF27AE60), size: 28),
      );
}

class _OrdersTab extends StatelessWidget {
  const _OrdersTab();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.receipt_long, size: 64, color: Colors.grey),
          SizedBox(height: 12),
          Text('Orders will appear here', style: TextStyle(color: Colors.grey, fontSize: 16)),
        ],
      ),
    );
  }
}
