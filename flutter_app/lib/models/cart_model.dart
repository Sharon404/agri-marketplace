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
      id: json['id'] as int,
      userId: json['user_id'] as int,
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
      id: json['id'] as int,
      cartId: json['cart_id'] as int,
      productId: json['product_id'] as int,
      quantity: json['quantity'] as int? ?? 0,
      product: json['product'] != null
          ? ProductModel.fromJson(json['product'] as Map<String, dynamic>)
          : null,
    );
  }
}
