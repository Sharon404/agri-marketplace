import 'order_item_model.dart';

class OrderModel {
  OrderModel({
    required this.id,
    required this.buyerId,
    required this.totalAmount,
    required this.shippingAmount,
    required this.commissionAmount,
    required this.status,
    required this.paymentStatus,
    required this.items,
  });

  final int id;
  final int buyerId;
  final double totalAmount;
  final double shippingAmount;
  final double commissionAmount;
  final String status;
  final String paymentStatus;
  final List<OrderItemModel> items;

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: _toInt(json['id']) ?? 0,
      buyerId: _toInt(json['buyer_id']) ?? 0,
      totalAmount: _toDouble(json['total_amount']) ?? 0.0,
      shippingAmount: _toDouble(json['shipping_amount']) ?? 0.0,
      commissionAmount: _toDouble(json['commission_amount']) ?? 0.0,
      status: json['status'] as String? ?? 'pending',
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      items: (json['items'] as List<dynamic>? ?? [])
          .map((item) => OrderItemModel.fromJson(item as Map<String, dynamic>))
          .toList(),
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
