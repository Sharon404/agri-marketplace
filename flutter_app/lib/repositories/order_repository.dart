import '../models/order_model.dart';
import '../services/api_service.dart';

class OrderRepository {
  OrderRepository(this._apiService);

  final ApiService _apiService;

  Future<List<OrderModel>> getOrders() async {
    final response = await _apiService.getOrders();
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => OrderModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<List<OrderModel>> getSellerOrders() async {
    final response = await _apiService.getSellerOrders();
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => OrderModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<OrderModel> checkout(String paymentMethod) async {
    final response = await _apiService.checkout(paymentMethod);
    final data = response.data as Map<String, dynamic>;
    return OrderModel.fromJson(data['data'] as Map<String, dynamic>);
  }
}
