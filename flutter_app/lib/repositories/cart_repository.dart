import '../models/cart_model.dart';
import '../services/api_service.dart';

class CartRepository {
  CartRepository(this._apiService);

  final ApiService _apiService;

  Future<CartModel> getCart() async {
    final response = await _apiService.getCart();
    final data = response.data as Map<String, dynamic>;
    return CartModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<CartModel> addToCart(int productId, int quantity) async {
    final response = await _apiService.addToCart(productId, quantity);
    final data = response.data as Map<String, dynamic>;
    return CartModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<CartModel> updateItem(int cartItemId, int quantity) async {
    final response = await _apiService.updateCartItem(cartItemId, quantity);
    final data = response.data as Map<String, dynamic>;
    return CartModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<CartModel> removeItem(int cartItemId) async {
    final response = await _apiService.removeCartItem(cartItemId);
    final data = response.data as Map<String, dynamic>;
    return CartModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<CartModel> clearCart() async {
    final response = await _apiService.clearCart();
    final data = response.data as Map<String, dynamic>;
    return CartModel.fromJson(data['data'] as Map<String, dynamic>);
  }
}
