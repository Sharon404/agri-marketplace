import '../models/category_model.dart';
import '../models/product_model.dart';
import '../services/api_service.dart';

class ProductRepository {
  ProductRepository(this._apiService);

  final ApiService _apiService;

  Future<List<ProductModel>> getProducts({int? categoryId, String? search}) async {
    final response = await _apiService.getProducts(categoryId: categoryId, search: search);
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => ProductModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<ProductModel> getProductById(int id) async {
    final response = await _apiService.getProductById(id);
    final data = response.data as Map<String, dynamic>;
    return ProductModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<List<CategoryModel>> getCategories() async {
    final response = await _apiService.getCategories();
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => CategoryModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<List<ProductModel>> getMyProducts() async {
    final response = await _apiService.getMyProducts();
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => ProductModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<ProductModel> createProduct(Map<String, dynamic> payload) async {
    final response = await _apiService.createProduct(payload);
    final data = response.data as Map<String, dynamic>;
    return ProductModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<ProductModel> updateProduct(int id, Map<String, dynamic> payload) async {
    final response = await _apiService.updateProduct(id, payload);
    final data = response.data as Map<String, dynamic>;
    return ProductModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<void> deleteProduct(int id) async {
    await _apiService.deleteProduct(id);
  }

  Future<String> uploadImage(String filePath) async {
    return _apiService.uploadProductImage(filePath);
  }

  Future<String> uploadImageBytes(List<int> bytes, String filename) async {
    return _apiService.uploadProductImageBytes(bytes, filename);
  }
}
