import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product_model.dart';
import '../providers/cart_provider.dart';
import '../providers/product_provider.dart';
import 'cart_screen.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({super.key, required this.productId});
  final int productId;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  ProductModel? _product;
  bool _loading = true;
  String? _error;
  int _quantity = 1;
  int _selectedImageIndex = 0;
  bool _addingToCart = false;

  @override
  void initState() {
    super.initState();
    _loadProduct();
  }

  Future<void> _loadProduct() async {
    try {
      final p = await context.read<ProductProvider>().getProductById(widget.productId);
      if (mounted) setState(() { _product = p; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A5276),
        foregroundColor: Colors.white,
        title: Text(_product?.name ?? 'Product', maxLines: 1, overflow: TextOverflow.ellipsis),
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_cart),
            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen())),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
              : _product == null
                  ? const Center(child: Text('Product not found'))
                  : _buildBody(_product!),
    );
  }

  Widget _buildBody(ProductModel p) {
    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildImageGallery(p),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Text(
                        'KSh ${p.price.toStringAsFixed(0)}',
                        style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Color(0xFFE74C3C)),
                      ),
                      if (p.category != null) ...[
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFFD5F5E3),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(p.category!.name,
                              style: const TextStyle(color: Color(0xFF1E8449), fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ],
                      const SizedBox(height: 12),
                      _infoRow(Icons.inventory_2_outlined, 'Stock available', '${p.stockQuantity} units'),
                      _infoRow(Icons.shopping_basket_outlined, 'Min. order', '${p.minimumOrderQuantity} units'),
                      if (p.weightPerUnit != null)
                        _infoRow(Icons.scale_outlined, 'Weight per unit', '${p.weightPerUnit} kg'),
                      if (p.shipping != null)
                        _infoRow(
                          Icons.local_shipping_outlined,
                          'Shipping',
                          p.shipping!.shippingType == 'free'
                              ? 'Free shipping'
                              : p.shipping!.shippingType == 'flat'
                                  ? 'KSh ${p.shipping!.flatShippingFee?.toStringAsFixed(0) ?? "-"} flat'
                                  : 'Calculated',
                        ),
                      const SizedBox(height: 16),
                      const Divider(),
                      const SizedBox(height: 8),
                      const Text('Description', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      const SizedBox(height: 6),
                      Text(p.description, style: const TextStyle(fontSize: 14, height: 1.5)),
                      const SizedBox(height: 20),
                      // Seller / Shop section
                      if (p.sellerProfile != null) _buildShopSection(p),
                      const SizedBox(height: 20),
                      // Reviews section
                      _buildReviewsSection(),
                      const SizedBox(height: 80),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        _buildAddToCartBar(p),
      ],
    );
  }

  Widget _buildImageGallery(ProductModel p) {
    if (p.images.isEmpty) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: Container(
          color: const Color(0xFFECF0F1),
          child: const Center(child: Icon(Icons.eco, size: 80, color: Color(0xFF27AE60))),
        ),
      );
    }
    return Column(
      children: [
        GestureDetector(
          onTap: () => _showFullImage(p.images[_selectedImageIndex].imageUrl),
          child: AspectRatio(
            aspectRatio: 16 / 9,
            child: Image.network(
              resolveImageUrl(p.images[_selectedImageIndex].imageUrl),
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                color: const Color(0xFFECF0F1),
                child: const Center(child: Icon(Icons.eco, size: 80, color: Color(0xFF27AE60))),
              ),
            ),
          ),
        ),
        if (p.images.length > 1)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: List.generate(
                p.images.length,
                (i) => GestureDetector(
                  onTap: () => setState(() => _selectedImageIndex = i),
                  child: Container(
                    margin: const EdgeInsets.only(right: 8),
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: i == _selectedImageIndex ? const Color(0xFF1A5276) : Colors.transparent,
                        width: 2,
                      ),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(2),
                      child: Image.network(
                        resolveImageUrl(p.images[i].imageUrl),
                        width: 56,
                        height: 56,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          width: 56,
                          height: 56,
                          color: const Color(0xFFECF0F1),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  void _showFullImage(String imageUrl) {
    showDialog(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: EdgeInsets.zero,
        child: Stack(
          children: [
            Center(
              child: InteractiveViewer(
                child: Image.network(
                  resolveImageUrl(imageUrl),
                  fit: BoxFit.contain,
                  errorBuilder: (_, __, ___) => const Icon(Icons.broken_image, color: Colors.white, size: 80),
                ),
              ),
            ),
            Positioned(
              top: 12,
              right: 12,
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white, size: 28),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildShopSection(ProductModel p) {
    final seller = p.sellerProfile!;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('About the Shop', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: const Color(0xFF1A5276),
                backgroundImage: seller.logoUrl != null && seller.logoUrl!.isNotEmpty
                    ? NetworkImage(resolveImageUrl(seller.logoUrl!))
                    : null,
                child: seller.logoUrl == null || seller.logoUrl!.isEmpty
                    ? const Icon(Icons.storefront, color: Colors.white, size: 24)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(seller.businessName,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                    const SizedBox(height: 2),
                    if (seller.verificationStatus == 'verified')
                      const Row(
                        children: [
                          Icon(Icons.verified, size: 13, color: Color(0xFF27AE60)),
                          SizedBox(width: 4),
                          Text('Verified Seller',
                              style: TextStyle(fontSize: 12, color: Color(0xFF27AE60), fontWeight: FontWeight.w500)),
                        ],
                      )
                    else
                      const Text('Unverified',
                          style: TextStyle(fontSize: 12, color: Colors.orange)),
                  ],
                ),
              ),
            ],
          ),
          if (seller.description != null && seller.description!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(seller.description!,
                style: const TextStyle(fontSize: 13, color: Colors.black87, height: 1.5)),
          ],
          const SizedBox(height: 10),
          const Row(
            children: [
              Icon(Icons.location_on_outlined, size: 15, color: Color(0xFF1A5276)),
              SizedBox(width: 4),
              Text('Kenya', style: TextStyle(fontSize: 13, color: Colors.grey)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildReviewsSection() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Customer Reviews', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              Row(
                children: List.generate(
                  5,
                  (i) => const Icon(Icons.star_border, size: 16, color: Colors.amber),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Center(
            child: Column(
              children: [
                Icon(Icons.rate_review_outlined, size: 40, color: Colors.grey),
                SizedBox(height: 8),
                Text('No reviews yet', style: TextStyle(color: Colors.grey, fontSize: 13)),
                SizedBox(height: 4),
                Text('Be the first to review this product!',
                    style: TextStyle(color: Colors.grey, fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAddToCartBar(ProductModel p) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
      decoration: const BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8, offset: Offset(0, -2))],
      ),
      child: Row(
        children: [
          // Quantity selector
          Container(
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xFFBDC3C7)),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.remove, size: 16),
                  onPressed: _quantity > p.minimumOrderQuantity ? () => setState(() => _quantity--) : null,
                  constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: Text('$_quantity', style: const TextStyle(fontWeight: FontWeight.bold)),
                ),
                IconButton(
                  icon: const Icon(Icons.add, size: 16),
                  onPressed: _quantity < p.stockQuantity ? () => setState(() => _quantity++) : null,
                  constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFE74C3C),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
              ),
              onPressed: p.stockQuantity > 0 && !_addingToCart ? _addToCart : null,
              icon: _addingToCart
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Icon(Icons.shopping_cart_outlined),
              label: Text(p.stockQuantity > 0 ? 'Add to Cart' : 'Out of Stock',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _addToCart() async {
    setState(() => _addingToCart = true);
    try {
      await context.read<CartProvider>().addToCart(widget.productId, _quantity);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Added to cart!'),
            backgroundColor: const Color(0xFF27AE60),
            action: SnackBarAction(
              label: 'VIEW CART',
              textColor: Colors.white,
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen())),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _addingToCart = false);
    }
  }

  Widget _infoRow(IconData icon, String label, String value) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Row(
          children: [
            Icon(icon, size: 15, color: Colors.grey),
            const SizedBox(width: 6),
            Text('$label: ', style: const TextStyle(fontSize: 13, color: Colors.grey)),
            Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          ],
        ),
      );
}
