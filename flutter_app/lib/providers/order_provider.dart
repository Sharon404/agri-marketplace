import 'package:flutter/foundation.dart';
import '../models/order_model.dart';
import '../repositories/order_repository.dart';

class OrderProvider extends ChangeNotifier {
  OrderProvider(this._orderRepository);

  final OrderRepository _orderRepository;

  List<OrderModel> _orders = [];
  bool _isLoading = false;
  String? _error;

  List<OrderModel> get orders => _orders;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadOrders() async {
    _setLoading(true);
    try {
      _orders = await _orderRepository.getOrders();
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> loadSellerOrders() async {
    _setLoading(true);
    try {
      _orders = await _orderRepository.getSellerOrders();
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<OrderModel?> checkout(String paymentMethod) async {
    _setLoading(true);
    try {
      final order = await _orderRepository.checkout(paymentMethod);
      _error = null;
      return order;
    } catch (e) {
      _error = e.toString();
      return null;
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }
}
