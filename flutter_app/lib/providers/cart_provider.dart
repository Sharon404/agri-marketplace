import 'package:flutter/foundation.dart';
import '../models/cart_model.dart';
import '../repositories/cart_repository.dart';

class CartProvider extends ChangeNotifier {
  CartProvider(this._cartRepository);

  final CartRepository _cartRepository;

  CartModel? _cart;
  bool _isLoading = false;
  String? _error;

  CartModel? get cart => _cart;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadCart() async {
    _setLoading(true);
    try {
      _cart = await _cartRepository.getCart();
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> addToCart(int productId, int quantity) async {
    _setLoading(true);
    try {
      _cart = await _cartRepository.addToCart(productId, quantity);
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> updateItem(int cartItemId, int quantity) async {
    _setLoading(true);
    try {
      _cart = await _cartRepository.updateItem(cartItemId, quantity);
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> removeItem(int cartItemId) async {
    _setLoading(true);
    try {
      _cart = await _cartRepository.removeItem(cartItemId);
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> clearCart() async {
    _setLoading(true);
    try {
      _cart = await _cartRepository.clearCart();
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }
}
