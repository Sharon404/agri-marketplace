import '../models/product_model.dart';
import '../services/api_service.dart';

class ProductRepository {
  ProductRepository(this._apiService);

  final ApiService _apiService;

  Future<List<ProductModel>> getProducts() async {
    final response = await _apiService.getProducts();
    final data = response.data as Map<String, dynamic>;
    final items = data['data'] as List<dynamic>? ?? [];
    return items.map((item) => ProductModel.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<ProductModel> getProductById(int id) async {
    final response = await _apiService.getProductById(id);
    final data = response.data as Map<String, dynamic>;
    return ProductModel.fromJson(data['data'] as Map<String, dynamic>);
  }
}
