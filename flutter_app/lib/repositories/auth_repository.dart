import '../models/user_model.dart';
import '../services/api_service.dart';

class AuthRepository {
  AuthRepository(this._apiService);

  final ApiService _apiService;

  Future<UserModel> login(String email, String password) async {
    final data = await _apiService.login(email, password);
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<UserModel> register(Map<String, dynamic> payload) async {
    final data = await _apiService.register(payload);
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<UserModel> registerSeller(Map<String, dynamic> payload) async {
    final data = await _apiService.registerSeller(payload);
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    await _apiService.logout();
  }
}
