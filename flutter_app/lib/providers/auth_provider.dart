import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  bool _isAuthenticated = false;
  String? _token;
  Map<String, dynamic>? _user;

  bool get isAuthenticated => _isAuthenticated;
  String? get token => _token;
  Map<String, dynamic>? get user => _user;

  final ApiService _apiService = ApiService();

  Future<void> checkAuthStatus() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    final userData = prefs.getString('user');

    if (token != null && userData != null) {
      _token = token;
      _user = Map<String, dynamic>.from(userData as Map);
      _isAuthenticated = true;
      notifyListeners();
    }
  }

  Future<bool> login(String email, String password) async {
    try {
      final response = await _apiService.login(email, password);

      if (response.containsKey('token')) {
        _token = response['token'];
        _user = response['user'];
        _isAuthenticated = true;

        // Save to persistent storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        await prefs.setString('user', _user.toString());

        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<bool> register(String name, String email, String phone, String password, String role) async {
    try {
      final response = await _apiService.register(name, email, phone, password, role);

      if (response.containsKey('token')) {
        _token = response['token'];
        _user = response['user'];
        _isAuthenticated = true;

        // Save to persistent storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        await prefs.setString('user', _user.toString());

        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      print('Registration error: $e');
      throw Exception('Registration failed: ${e.toString()}');
    }
  }

  Future<void> logout() async {
    try {
      await _apiService.logout();
    } catch (e) {
      // Continue with local logout even if API call fails
    }

    _token = null;
    _user = null;
    _isAuthenticated = false;

    // Clear persistent storage
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    await prefs.remove('user');

    notifyListeners();
  }
}