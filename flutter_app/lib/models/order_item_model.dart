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
      id: _toInt(json['id']) ?? 0,
      orderId: _toInt(json['order_id']) ?? 0,
      productId: _toInt(json['product_id']) ?? 0,
      sellerId: _toInt(json['seller_id']) ?? 0,
      quantity: _toInt(json['quantity']) ?? 0,
      pricePerUnit: _toDouble(json['price_per_unit']) ?? 0.0,
      subtotal: _toDouble(json['subtotal']) ?? 0.0,
      shippingFee: _toDouble(json['shipping_fee']) ?? 0.0,
      product: json['product'] != null
          ? ProductModel.fromJson(json['product'] as Map<String, dynamic>)
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
