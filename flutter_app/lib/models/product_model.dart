import 'category_model.dart';
import 'user_model.dart';

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
      id: json['id'] as int,
      sellerId: json['seller_id'] as int,
      categoryId: json['category_id'] as int,
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      price: _toDouble(json['price']) ?? 0.0,
      minimumOrderQuantity: _toInt(json['minimum_order_quantity']) ?? 1,
      stockQuantity: _toInt(json['stock_quantity']) ?? 0,
      weightPerUnit: _toDouble(json['weight_per_unit']),
      isActive: json['is_active'] as bool? ?? true,
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
      id: json['id'] as int,
      productId: json['product_id'] as int,
      imageUrl: json['image_url'] as String? ?? '',
      isPrimary: json['is_primary'] as bool? ?? false,
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
      id: json['id'] as int,
      productId: json['product_id'] as int,
      shippingType: json['shipping_type'] as String? ?? 'free',
      flatShippingFee: _toDouble(json['flat_shipping_fee']),
      freeShippingMinimum: _toDouble(json['free_shipping_minimum']),
    );
  }
}
