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
      id: json['id'] as int,
      buyerId: json['buyer_id'] as int,
      totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0.0,
      shippingAmount: (json['shipping_amount'] as num?)?.toDouble() ?? 0.0,
      commissionAmount: (json['commission_amount'] as num?)?.toDouble() ?? 0.0,
      status: json['status'] as String? ?? 'pending',
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      items: (json['items'] as List<dynamic>? ?? [])
          .map((item) => OrderItemModel.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}
