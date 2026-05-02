import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product_model.dart';
import '../providers/auth_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/product_provider.dart';
import 'cart_screen.dart';
import 'product_detail_screen.dart';
import 'seller_dashboard_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<ProductProvider>();
      provider.loadCategories();
      provider.loadProducts();
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: _buildAppBar(context),
      body: Column(
        children: [
          _buildSearchBar(context),
          _buildCategoryBar(context),
          Expanded(child: _buildProductGrid(context)),
        ],
      ),
      floatingActionButton: _buildCartFab(context),
    );
  }

  PreferredSizeWidget _buildAppBar(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return AppBar(
      backgroundColor: const Color(0xFF1A5276),
      foregroundColor: Colors.white,
      elevation: 0,
      title: const Row(
        children: [
          Icon(Icons.eco, color: Color(0xFF58D68D), size: 22),
          SizedBox(width: 6),
          Text('AgriMarket',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        ],
      ),
      actions: [
        if (auth.isAuthenticated &&
            ((auth.user?.role == 'seller') ||
                (auth.user?.sellerProfile != null)))
          TextButton.icon(
            onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(
                    builder: (_) => const SellerDashboardScreen())),
            icon: const Icon(Icons.storefront, color: Colors.white, size: 18),
            label: const Text('My Shop',
                style: TextStyle(color: Colors.white, fontSize: 12)),
          ),
        if (auth.isAuthenticated)
          IconButton(
            icon: const Icon(Icons.logout, size: 20),
            tooltip: 'Logout',
            onPressed: () async {
              await auth.logout();
              if (context.mounted) {
                Navigator.pushReplacementNamed(context, '/login');
              }
            },
          )
        else
          TextButton(
            onPressed: () => Navigator.pushNamed(context, '/login'),
            child: const Text('Sign In', style: TextStyle(color: Colors.white)),
          ),
        const SizedBox(width: 4),
      ],
    );
  }

  Widget _buildSearchBar(BuildContext context) {
    final provider = context.read<ProductProvider>();
    return Container(
      color: const Color(0xFF1A5276),
      padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _searchCtrl,
              onSubmitted: (q) => provider.search(q),
              style: const TextStyle(fontSize: 14),
              decoration: InputDecoration(
                hintText: 'Search herbs, spices, cereals, machinery...',
                hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
                filled: true,
                fillColor: Colors.white,
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(6),
                  borderSide: BorderSide.none,
                ),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.search, color: Color(0xFF1A5276)),
                  onPressed: () => provider.search(_searchCtrl.text),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryBar(BuildContext context) {
    return Consumer<ProductProvider>(
      builder: (context, provider, _) {
        final cats = provider.categories;
        if (cats.isEmpty) return const SizedBox.shrink();
        return Container(
          color: Colors.white,
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
            child: Row(
              children: [
                _catChip(context, null, 'All', provider),
                ...cats.map(
                    (cat) => _catChip(context, cat.id, cat.name, provider)),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _catChip(
      BuildContext context, int? id, String label, ProductProvider provider) {
    final selected = provider.selectedCategoryId == id;
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label,
            style: TextStyle(
                fontSize: 12, color: selected ? Colors.white : Colors.black87)),
        selected: selected,
        selectedColor: const Color(0xFF1A5276),
        backgroundColor: const Color(0xFFF0F0F0),
        onSelected: (_) => provider.selectCategory(id),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        visualDensity: VisualDensity.compact,
      ),
    );
  }

  Widget _buildProductGrid(BuildContext context) {
    return Consumer<ProductProvider>(
      builder: (context, provider, _) {
        if (provider.isLoading) {
          return const Center(child: CircularProgressIndicator());
        }
        if (provider.error != null) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(provider.error!,
                    style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 8),
                ElevatedButton(
                    onPressed: provider.loadProducts,
                    child: const Text('Retry')),
              ],
            ),
          );
        }
        final products = provider.products;
        if (products.isEmpty) {
          return const Center(child: Text('No products found'));
        }
        return LayoutBuilder(
          builder: (context, constraints) {
            return GridView.builder(
              padding: const EdgeInsets.all(8),
              gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                maxCrossAxisExtent: 95,
                childAspectRatio: 1.0,
                crossAxisSpacing: 8,
                mainAxisSpacing: 8,
              ),
              itemCount: products.length,
              itemBuilder: (context, i) => _ProductCard(product: products[i]),
            );
          },
        );
      },
    );
  }

  Widget _buildCartFab(BuildContext context) {
    return Consumer<CartProvider>(
      builder: (context, cart, _) {
        final count = cart.cart?.items.length ?? 0;
        return FloatingActionButton.extended(
          backgroundColor: const Color(0xFFE74C3C),
          onPressed: () => Navigator.push(
              context, MaterialPageRoute(builder: (_) => const CartScreen())),
          icon: Stack(
            clipBehavior: Clip.none,
            children: [
              const Icon(Icons.shopping_cart),
              if (count > 0)
                Positioned(
                  right: -6,
                  top: -6,
                  child: CircleAvatar(
                    radius: 7,
                    backgroundColor: Colors.white,
                    child: Text('$count',
                        style: const TextStyle(
                            fontSize: 9,
                            color: Color(0xFFE74C3C),
                            fontWeight: FontWeight.bold)),
                  ),
                ),
            ],
          ),
          label: const Text('Cart'),
        );
      },
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({required this.product});
  final ProductModel product;

  @override
  Widget build(BuildContext context) {
    final primaryImg = product.images.isEmpty
        ? null
        : product.images.firstWhere(
            (img) => img.isPrimary,
            orElse: () => product.images.first,
          );

    return GestureDetector(
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
            builder: (_) => ProductDetailScreen(productId: product.id)),
      ),
      child: Card(
        elevation: 1.5,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        clipBehavior: Clip.antiAlias,
        child: Stack(
          fit: StackFit.expand,
          children: [
            primaryImg != null
                ? Image.network(
                    resolveImageUrl(primaryImg.imageUrl),
                    fit: BoxFit.cover,
                    filterQuality: FilterQuality.low,
                    errorBuilder: (_, __, ___) => _placeholder(),
                  )
                : _placeholder(),
            Align(
              alignment: Alignment.bottomCenter,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 3),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Colors.transparent, Color(0xCC000000)],
                  ),
                ),
                child: Text(
                  product.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 9,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _placeholder() => Container(
        color: const Color(0xFFECF0F1),
        child: const Center(
            child: Icon(Icons.eco, size: 40, color: Color(0xFF27AE60))),
      );
}
