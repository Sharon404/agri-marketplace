import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
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
      try {
        _user = json.decode(userData) as Map<String, dynamic>;
        _isAuthenticated = true;
        print('Restored user from storage: $_user');
        print('Restored user role: ${_user?['role']}');
      } catch (e) {
        print('Error decoding user data: $e');
        _isAuthenticated = false;
      }
      notifyListeners();
    }
  }

  Future<bool> login(String email, String password) async {
    try {
      final response = await _apiService.login(email, password);

      print('=== LOGIN RESPONSE ===');
      print('Full response: $response');
      print('User key exists: ${response.containsKey('user')}');
      print('Access token key exists: ${response.containsKey('access_token')}');
      
      // Backend returns 'access_token' from JWT auth
      if (response.containsKey('access_token') || response.containsKey('token')) {
        _token = response['access_token'] ?? response['token'];
        _user = response['user'];
        _isAuthenticated = true;
        
        print('User object: $_user');
        print('User role: ${_user?['role']}');

        // Save to persistent storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        await prefs.setString('user', json.encode(_user));

        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      print('Login error: $e');
      return false;
    }
  }

  Future<bool> register(String name, String email, String phone, String password, String role) async {
    try {
      final response = await _apiService.register(name, email, phone, password, role);

      print('=== REGISTER RESPONSE ===');
      print('Full response: $response');
      print('User key exists: ${response.containsKey('user')}');

      // Backend returns 'access_token' from JWT auth
      if (response.containsKey('access_token') || response.containsKey('token')) {
        _token = response['access_token'] ?? response['token'];
        _user = response['user'] as Map<String, dynamic>? ?? {};
        _isAuthenticated = true;
        
        print('User object: $_user');
        print('User role: ${_user?['role']}');

        // Save to persistent storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        await prefs.setString('user', json.encode(_user));

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