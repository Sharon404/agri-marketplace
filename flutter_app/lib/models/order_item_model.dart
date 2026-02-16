import 'product_model.dart';

class OrderItemModel {
  OrderItemModel({
    required this.id,
    required this.orderId,
    required this.productId,
    required this.sellerId,
    required this.quantity,
    required this.pricePerUnit,
    required this.subtotal,
    required this.shippingFee,
    this.product,
  });

  final int id;
  final int orderId;
  final int productId;
  final int sellerId;
  final int quantity;
  final double pricePerUnit;
  final double subtotal;
  final double shippingFee;
  final ProductModel? product;

  factory OrderItemModel.fromJson(Map<String, dynamic> json) {
    return OrderItemModel(
      id: json['id'] as int,
      orderId: json['order_id'] as int,
      productId: json['product_id'] as int,
      sellerId: json['seller_id'] as int,
      quantity: json['quantity'] as int? ?? 0,
      pricePerUnit: (json['price_per_unit'] as num?)?.toDouble() ?? 0.0,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      shippingFee: (json['shipping_fee'] as num?)?.toDouble() ?? 0.0,
      product: json['product'] != null
          ? ProductModel.fromJson(json['product'] as Map<String, dynamic>)
          : null,
    );
  }
}
