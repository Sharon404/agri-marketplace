import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  ApiService({Dio? dio}) : _dio = dio ?? Dio() {
    _dio.options = BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
    );

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _getToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        options.headers['Accept'] = 'application/json';
        return handler.next(options);
      },
      onError: (error, handler) {
        return handler.next(error);
      },
    ));
  }

  /// Determine base URL based on platform and environment
  /// Use environment variable API_BASE_URL to override: --dart-define=API_BASE_URL=http://example.com/api
  static String get _baseUrl {
    // Check for explicit environment override
    const envBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: '');
    if (envBaseUrl.isNotEmpty) {
      return envBaseUrl;
    }

    // Web always uses localhost
    if (kIsWeb) {
      return 'http://localhost:8000/api';
    }

    // Android emulator uses 10.0.2.2 to reach host machine
    // Physical Android devices would use actual server IP
    // For dev, use 10.0.2.2:8000; for production, use actual server domain
    return 'http://10.0.2.2:8000/api';
    
    // iOS simulator: http://localhost:8000/api (mapped via XCode)
    // iOS physical: http://your-server-domain.com/api
  }

  final Dio _dio;

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _dio.post('/login', data: {
      'email': email,
      'password': password,
    });

    return _handleAuthResponse(response.data);
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> payload) async {
    final response = await _dio.post('/register', data: payload);

    return _handleAuthResponse(response.data);
  }

  Future<Map<String, dynamic>> registerSeller(Map<String, dynamic> payload) async {
    final response = await _dio.post('/register/seller', data: payload);

    return _handleAuthResponse(response.data);
  }

  Future<void> logout() async {
    await _dio.post('/logout');
    await _clearToken();
  }

  // ─── kept for repository compatibility ─────────────────────────────────
  Future<Response<dynamic>> getProductById(int id) async {
    return _dio.get('/products/$id');
  }

  Future<Response<dynamic>> addToCart(int productId, int quantity) async {
    return _dio.post('/cart/items', data: {
      'product_id': productId,
      'quantity': quantity,
    });
  }

  Future<Response<dynamic>> getCart() async {
    return _dio.get('/cart');
  }

  Future<Response<dynamic>> updateCartItem(int cartItemId, int quantity) async {
    return _dio.put('/cart/items/$cartItemId', data: {
      'quantity': quantity,
    });
  }

  Future<Response<dynamic>> removeCartItem(int cartItemId) async {
    return _dio.delete('/cart/items/$cartItemId');
  }

  Future<Response<dynamic>> clearCart() async {
    return _dio.delete('/cart');
  }

  Future<Response<dynamic>> checkout(String paymentMethod) async {
    return _dio.post('/checkout', data: {
      'payment_method': paymentMethod,
    });
  }

  Future<Response<dynamic>> getOrders() async {
    return _dio.get('/orders');
  }

  Future<Response<dynamic>> getSellerOrders() async {
    return _dio.get('/seller/orders');
  }

  Future<Response<dynamic>> getSellerProfile() async {
    return _dio.get('/seller/profile');
  }

  Future<Response<dynamic>> updateSellerProfile(Map<String, dynamic> payload) async {
    return _dio.patch('/seller/profile', data: payload);
  }

  // ─── Categories ──────────────────────────────────────────────────────────
  Future<Response<dynamic>> getCategories() async {
    return _dio.get('/categories');
  }

  // ─── Seller product management ────────────────────────────────────────────
  Future<Response<dynamic>> getMyProducts() async {
    return _dio.get('/seller/products');
  }

  Future<Response<dynamic>> createProduct(Map<String, dynamic> data) async {
    return _dio.post('/products', data: data);
  }

  Future<Response<dynamic>> updateProduct(int id, Map<String, dynamic> data) async {
    return _dio.put('/products/$id', data: data);
  }

  Future<Response<dynamic>> deleteProduct(int id) async {
    return _dio.delete('/products/$id');
  }

  /// Upload a single image; returns {'url': '...'} from the server.
  Future<String> uploadProductImage(String filePath) async {
    final formData = FormData.fromMap({
      'image': await MultipartFile.fromFile(filePath, filename: filePath.split('/').last),
    });
    final response = await _dio.post('/upload/image', data: formData);
    return (response.data as Map<String, dynamic>)['url'] as String;
  }

  Future<String> uploadProductImageBytes(List<int> bytes, String filename) async {
    final formData = FormData.fromMap({
      'image': MultipartFile.fromBytes(bytes, filename: filename),
    });
    final response = await _dio.post('/upload/image', data: formData);
    return (response.data as Map<String, dynamic>)['url'] as String;
  }

  Future<Response<dynamic>> getProducts({int? categoryId, String? search}) async {
    final params = <String, dynamic>{};
    if (categoryId != null) params['category_id'] = categoryId;
    if (search != null && search.isNotEmpty) params['search'] = search;
    return _dio.get('/products', queryParameters: params.isEmpty ? null : params);
  }

  Future<Map<String, dynamic>> _handleAuthResponse(dynamic data) async {
    if (data is! Map<String, dynamic> || data['access_token'] == null) {
      throw Exception('Invalid auth response');
    }

    final token = data['access_token'] as String;
    await _setToken(token);

    return data;
  }

  Future<void> _setToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  Future<void> _clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }
}
