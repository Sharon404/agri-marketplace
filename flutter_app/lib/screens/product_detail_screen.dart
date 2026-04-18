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
                      if (p.sellerProfile != null) ...[
                        const SizedBox(height: 12),
                        const Divider(),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const CircleAvatar(
                              radius: 20,
                              backgroundColor: Color(0xFF1A5276),
                              child: Icon(Icons.storefront, color: Colors.white, size: 20),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(p.sellerProfile!.businessName,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                  if (p.sellerProfile!.verificationStatus == 'verified')
                                    const Row(
                                      children: [
                                        Icon(Icons.verified, size: 12, color: Color(0xFF27AE60)),
                                        SizedBox(width: 4),
                                        Text('Verified Seller', style: TextStyle(fontSize: 11, color: Color(0xFF27AE60))),
                                      ],
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 16),
                      const Divider(),
                      const SizedBox(height: 8),
                      const Text('Description', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      const SizedBox(height: 6),
                      Text(p.description, style: const TextStyle(fontSize: 14, height: 1.5)),
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
        aspectRatio: 1.0,
        child: Container(
          color: const Color(0xFFECF0F1),
          child: const Center(child: Icon(Icons.eco, size: 80, color: Color(0xFF27AE60))),
        ),
      );
    }
    return Column(
      children: [
        AspectRatio(
          aspectRatio: 1.0,
          child: Image.network(
            p.images[_selectedImageIndex].imageUrl,
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              color: const Color(0xFFECF0F1),
              child: const Center(child: Icon(Icons.eco, size: 80, color: Color(0xFF27AE60))),
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
                        p.images[i].imageUrl,
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
