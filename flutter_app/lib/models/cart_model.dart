import 'product_model.dart';

class CartModel {
  CartModel({
    required this.id,
    required this.userId,
    required this.items,
  });

  final int id;
  final int userId;
  final List<CartItemModel> items;

  factory CartModel.fromJson(Map<String, dynamic> json) {
    return CartModel(
      id: _toInt(json['id']) ?? 0,
      userId: _toInt(json['user_id']) ?? 0,
      items: (json['items'] as List<dynamic>? ?? [])
          .map((item) => CartItemModel.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

class CartItemModel {
  CartItemModel({
    required this.id,
    required this.cartId,
    required this.productId,
    required this.quantity,
    this.product,
  });

  final int id;
  final int cartId;
  final int productId;
  final int quantity;
  final ProductModel? product;

  factory CartItemModel.fromJson(Map<String, dynamic> json) {
    return CartItemModel(
      id: _toInt(json['id']) ?? 0,
      cartId: _toInt(json['cart_id']) ?? 0,
      productId: _toInt(json['product_id']) ?? 0,
      quantity: _toInt(json['quantity']) ?? 0,
      product: json['product'] != null
          ? ProductModel.fromJson(json['product'] as Map<String, dynamic>)
          : null,
    );
  }
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value);
  return null;
}
