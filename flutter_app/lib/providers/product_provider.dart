import 'package:flutter/foundation.dart';
import '../models/category_model.dart';
import '../models/product_model.dart';
import '../repositories/product_repository.dart';

class ProductProvider extends ChangeNotifier {
  ProductProvider(this._productRepository);

  final ProductRepository _productRepository;

  List<ProductModel> _products = [];
  List<CategoryModel> _categories = [];
  int? _selectedCategoryId;
  String _searchQuery = '';
  bool _isLoading = false;
  String? _error;

  List<ProductModel> get products => _products;
  List<CategoryModel> get categories => _categories;
  int? get selectedCategoryId => _selectedCategoryId;
  String get searchQuery => _searchQuery;
  bool get isLoading => _isLoading;
  String? get error => _error;

  List<ProductModel> _myProducts = [];
  bool _myProductsLoading = false;
  String? _myProductsError;

  List<ProductModel> get myProducts => _myProducts;
  bool get myProductsLoading => _myProductsLoading;
  String? get myProductsError => _myProductsError;

  Future<void> loadProducts({int? categoryId, String? search}) async {
    _setLoading(true);
    try {
      _products = await _productRepository.getProducts(
        categoryId: categoryId ?? _selectedCategoryId,
        search: search ?? (_searchQuery.isEmpty ? null : _searchQuery),
      );
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> loadCategories() async {
    try {
      _categories = await _productRepository.getCategories();
      notifyListeners();
    } catch (_) {}
  }

  void selectCategory(int? categoryId) {
    _selectedCategoryId = categoryId;
    loadProducts(categoryId: categoryId, search: _searchQuery.isEmpty ? null : _searchQuery);
  }

  void search(String query) {
    _searchQuery = query;
    loadProducts(categoryId: _selectedCategoryId, search: query.isEmpty ? null : query);
  }

  Future<ProductModel?> getProductById(int id) async {
    try {
      return await _productRepository.getProductById(id);
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      return null;
    }
  }

  Future<void> loadMyProducts() async {
    _myProductsLoading = true;
    _myProductsError = null;
    notifyListeners();
    try {
      _myProducts = await _productRepository.getMyProducts();
    } catch (e) {
      _myProductsError = e.toString();
    } finally {
      _myProductsLoading = false;
      notifyListeners();
    }
  }

  Future<ProductModel?> createProduct(Map<String, dynamic> payload) async {
    final product = await _productRepository.createProduct(payload);
    _myProducts = [product, ..._myProducts];
    notifyListeners();
    return product;
  }

  Future<ProductModel?> updateProduct(int id, Map<String, dynamic> payload) async {
    final product = await _productRepository.updateProduct(id, payload);
    final idx = _myProducts.indexWhere((p) => p.id == id);
    if (idx >= 0) _myProducts[idx] = product;
    notifyListeners();
    return product;
  }

  Future<void> deleteProduct(int id) async {
    await _productRepository.deleteProduct(id);
    _myProducts.removeWhere((p) => p.id == id);
    notifyListeners();
  }

  Future<String> uploadImage(String filePath) async {
    return _productRepository.uploadImage(filePath);
  }

  Future<String> uploadImageBytes(List<int> bytes, String filename) async {
    return _productRepository.uploadImageBytes(bytes, filename);
  }

  void _setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }
}
