import 'package:flutter/foundation.dart';

import 'category_model.dart';
import 'user_model.dart';

/// Resolves a potentially relative `/storage/...` URL to an absolute URL.
/// Images stored on the backend are returned as relative paths; we must
/// prepend the backend host so Flutter's Image.network can fetch them.
String resolveImageUrl(String url) {
  if (url.startsWith('http://') || url.startsWith('https://')) return url;
  if (url.startsWith('/')) {
    const host =
        String.fromEnvironment('STORAGE_BASE_URL', defaultValue: '');
    if (host.isNotEmpty) return '$host$url';
    final defaultHost =
        kIsWeb ? 'http://localhost:8000' : 'http://10.0.2.2:8000';
    return '$defaultHost$url';
  }
  return url;
}

class ProductModel {
  ProductModel({
    required this.id,
    required this.sellerId,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.price,
    required this.minimumOrderQuantity,
    required this.stockQuantity,
    required this.weightPerUnit,
    required this.isActive,
    this.sellerProfile,
    this.category,
    this.images = const [],
    this.shipping,
  });

  final int id;
  final int sellerId;
  final int categoryId;
  final String name;
  final String description;
  final double price;
  final int minimumOrderQuantity;
  final int stockQuantity;
  final double? weightPerUnit;
  final bool isActive;
  final SellerModel? sellerProfile;
  final CategoryModel? category;
  final List<ProductImageModel> images;
  final ProductShippingModel? shipping;

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    return ProductModel(
      id: _toInt(json['id']) ?? 0,
      sellerId: _toInt(json['seller_id']) ?? 0,
      categoryId: _toInt(json['category_id']) ?? 0,
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      price: _toDouble(json['price']) ?? 0.0,
      minimumOrderQuantity: _toInt(json['minimum_order_quantity']) ?? 1,
      stockQuantity: _toInt(json['stock_quantity']) ?? 0,
      weightPerUnit: _toDouble(json['weight_per_unit']),
      isActive: _toBool(json['is_active']) ?? true,
      sellerProfile: json['seller_profile'] != null
          ? SellerModel.fromJson(json['seller_profile'] as Map<String, dynamic>)
          : null,
      category: json['category'] != null
          ? CategoryModel.fromJson(json['category'] as Map<String, dynamic>)
          : null,
      images: (json['images'] as List<dynamic>? ?? [])
          .map((item) =>
              ProductImageModel.fromJson(item as Map<String, dynamic>))
          .toList(),
      shipping: json['shipping'] != null
          ? ProductShippingModel.fromJson(
              json['shipping'] as Map<String, dynamic>)
          : null,
    );
  }
}

double? _toDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value);
  return null;
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value);
  return null;
}

bool? _toBool(dynamic value) {
  if (value == null) return null;
  if (value is bool) return value;
  if (value is num) return value != 0;
  if (value is String) {
    final normalized = value.trim().toLowerCase();
    if (normalized == 'true' || normalized == '1') return true;
    if (normalized == 'false' || normalized == '0') return false;
  }
  return null;
}

class ProductImageModel {
  ProductImageModel({
    required this.id,
    required this.productId,
    required this.imageUrl,
    required this.isPrimary,
  });

  final int id;
  final int productId;
  final String imageUrl;
  final bool isPrimary;

  factory ProductImageModel.fromJson(Map<String, dynamic> json) {
    return ProductImageModel(
      id: _toInt(json['id']) ?? 0,
      productId: _toInt(json['product_id']) ?? 0,
      imageUrl: json['image_url'] as String? ?? '',
      isPrimary: _toBool(json['is_primary']) ?? false,
    );
  }
}

class ProductShippingModel {
  ProductShippingModel({
    required this.id,
    required this.productId,
    required this.shippingType,
    this.flatShippingFee,
    this.freeShippingMinimum,
  });

  final int id;
  final int productId;
  final String shippingType;
  final double? flatShippingFee;
  final double? freeShippingMinimum;

  factory ProductShippingModel.fromJson(Map<String, dynamic> json) {
    return ProductShippingModel(
      id: _toInt(json['id']) ?? 0,
      productId: _toInt(json['product_id']) ?? 0,
      shippingType: json['shipping_type'] as String? ?? 'free',
      flatShippingFee: _toDouble(json['flat_shipping_fee']),
      freeShippingMinimum: _toDouble(json['free_shipping_minimum']),
    );
  }
}
