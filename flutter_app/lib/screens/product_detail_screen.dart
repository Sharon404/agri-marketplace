import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/product_model.dart';
import '../providers/cart_provider.dart';
import '../providers/product_provider.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({super.key, required this.productId});

  final int productId;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  ProductModel? _product;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      final product = await context.read<ProductProvider>().getProductById(widget.productId);
      setState(() {
        _product = product;
        _isLoading = false;
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final cartProvider = context.watch<CartProvider>();

    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_product == null) {
      return const Scaffold(body: Center(child: Text('Product not found')));
    }

    return Scaffold(
      appBar: AppBar(title: Text(_product!.name)),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(_product!.description),
            const SizedBox(height: 12),
            Text('Price: KES ${_product!.price.toStringAsFixed(2)}'),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: cartProvider.isLoading
                  ? null
                  : () => cartProvider.addToCart(_product!.id, _product!.minimumOrderQuantity),
              child: const Text('Add to cart'),
            ),
          ],
        ),
      ),
    );
  }
}
