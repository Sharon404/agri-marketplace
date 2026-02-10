import 'dart:convert';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Base URL can be overridden at build time with --dart-define=API_BASE_URL=...
  static const String _envBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: '');

  static String get baseUrl {
    if (_envBaseUrl.isNotEmpty) {
      return _envBaseUrl;
    }

    if (kIsWeb) {
      return 'http://localhost:8000/api';
    }

    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return 'http://10.0.2.2:8000/api';
      default:
        return 'http://localhost:8000/api';
    }
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      ).timeout(const Duration(seconds: 10));

      print('Login response status: ${response.statusCode}');
      print('Login response body: ${response.body}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Login failed: Status ${response.statusCode}');
      }
    } on TimeoutException {
      throw Exception('Login timeout: Backend server not responding');
    } catch (e) {
      throw Exception('Login error: ${e.toString()}');
    }
  }

  Future<Map<String, dynamic>> register(String name, String email, String phone, String password, String role) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'phone': phone,
          'password': password,
          'password_confirmation': password,
          'role': role,
        }),
      ).timeout(const Duration(seconds: 10));

      print('Register response status: ${response.statusCode}');
      print('Register response body: ${response.body}');

      if (response.statusCode == 201) {
        return jsonDecode(response.body);
      } else if (response.statusCode == 422) {
        // Validation error - parse and format error messages
        final errorBody = jsonDecode(response.body);
        if (errorBody is Map<String, dynamic>) {
          // Extract validation errors
          final errors = <String>[];
          errorBody.forEach((key, value) {
            if (value is List && value.isNotEmpty) {
              errors.add(value.first.toString());
            } else if (value is String) {
              errors.add(value);
            }
          });
          final errorMessage = errors.isNotEmpty ? errors.join('\n') : 'Validation failed';
          throw Exception(errorMessage);
        }
        throw Exception('Validation failed: ${response.body}');
      } else {
        // Try to parse as JSON, fallback to plain text
        try {
          final errorBody = jsonDecode(response.body);
          final errorMessage = errorBody is Map ? errorBody.toString() : response.body;
          throw Exception('Registration failed: $errorMessage (Status: ${response.statusCode})');
        } catch (e) {
          throw Exception('Registration failed: Status ${response.statusCode}');
        }
      }
    } on TimeoutException {
      throw Exception('Registration timeout: Backend server not responding');
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Registration error: ${e.toString()}');
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token != null) {
      final response = await http.post(
        Uri.parse('$baseUrl/logout'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode != 200) {
        throw Exception('Logout failed');
      }
    }
  }

  Future<List<dynamic>> getFarmerListings({Map<String, String>? filters}) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final queryParams = filters != null ? Uri(queryParameters: filters).query : '';
    final url = queryParams.isNotEmpty ? '$baseUrl/farmer-listings?$queryParams' : '$baseUrl/farmer-listings';

    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['data'] ?? [];
    } else {
      throw Exception('Failed to load listings: ${response.statusCode} - ${response.body}');
    }
  }

  Future<List<dynamic>> getBuyerRequests({Map<String, String>? filters}) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final queryParams = filters != null ? Uri(queryParameters: filters).query : '';
    final url = queryParams.isNotEmpty ? '$baseUrl/buyer-requests?$queryParams' : '$baseUrl/buyer-requests';

    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['data'];
    } else {
      throw Exception('Failed to load requests');
    }
  }

  Future<List<dynamic>> getProducts() async {
    try {
      // Products endpoint is public, but try to use token if available
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final headers = {
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

      final response = await http.get(
        Uri.parse('$baseUrl/products'),
        headers: headers,
      ).timeout(const Duration(seconds: 10));

      print('Products response status: ${response.statusCode}');
      print('Products response body: ${response.body}');

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        if (decoded is List) {
          return decoded;
        }
        if (decoded is Map<String, dynamic>) {
          if (decoded['data'] is List) {
            return decoded['data'] as List<dynamic>;
          }
          if (decoded['products'] is List) {
            return decoded['products'] as List<dynamic>;
          }
        }
        return [];
      } else {
        throw Exception('Failed to load products: ${response.statusCode}');
      }
    } on TimeoutException {
      throw Exception('Products timeout: Backend server not responding');
    } catch (e) {
      throw Exception('Products error: ${e.toString()}');
    }
  }

  Future<Map<String, dynamic>> createFarmerListing(Map<String, dynamic> listingData) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final response = await http.post(
      Uri.parse('$baseUrl/farmer-listings'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(listingData),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      try {
        final errorBody = jsonDecode(response.body);
        final errorMessage = errorBody is Map ? errorBody.toString() : response.body;
        throw Exception('Failed to create listing: $errorMessage (Status: ${response.statusCode})');
      } catch (_) {
        throw Exception('Failed to create listing: Status ${response.statusCode}');
      }
    }
  }

  Future<Map<String, dynamic>> createBuyerRequest(Map<String, dynamic> requestData) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final response = await http.post(
      Uri.parse('$baseUrl/buyer-requests'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(requestData),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to create request');
    }
  }

  // Admin API methods
  Future<Map<String, dynamic>> getAdminDashboard() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final response = await http.get(
      Uri.parse('$baseUrl/admin/dashboard'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to load admin dashboard: ${response.statusCode} - ${response.body}');
    }
  }

  Future<List<dynamic>> getAdminListings() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final response = await http.get(
      Uri.parse('$baseUrl/admin/deals'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['deals']['data'] ?? [];
    } else {
      throw Exception('Failed to load admin listings: ${response.statusCode} - ${response.body}');
    }
  }

  Future<List<dynamic>> getAdminRequests() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');

    if (token == null) throw Exception('Not authenticated');

    final response = await http.get(
      Uri.parse('$baseUrl/admin/deals'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['deals']['data'] ?? [];
    } else {
      throw Exception('Failed to load admin requests: ${response.statusCode} - ${response.body}');
    }
  }

  // Analytics methods for farmers and buyers
  Future<Map<String, dynamic>> getFarmerAnalytics() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.get(
        Uri.parse('$baseUrl/farmer/analytics'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Farmer analytics response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Farmer analytics failed: ${response.statusCode}');
      }
    } catch (e) {
      print('Farmer analytics error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> getBuyerAnalytics() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.get(
        Uri.parse('$baseUrl/buyer/analytics'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Buyer analytics response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Buyer analytics failed: ${response.statusCode}');
      }
    } catch (e) {
      print('Buyer analytics error: $e');
      rethrow;
    }
  }

  // PHASE 2: Managed Marketplace Deal Methods
  
  /// Get all deals for the authenticated user
  Future<List<dynamic>> getDeals({String? status}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      String url = '$baseUrl/deals';
      if (status != null && status.isNotEmpty) {
        url += '?status=$status';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Get deals response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // Handle both paginated and non-paginated responses
        if (data is Map && data.containsKey('data')) {
          return data['data'] ?? [];
        }
        return data is List ? data : [];
      } else {
        throw Exception('Failed to load deals: ${response.statusCode}');
      }
    } catch (e) {
      print('Get deals error: $e');
      rethrow;
    }
  }

  /// Get a specific deal by ID
  Future<Map<String, dynamic>> getDeal(int dealId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.get(
        Uri.parse('$baseUrl/deals/$dealId'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Get deal response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to load deal: ${response.statusCode}');
      }
    } catch (e) {
      print('Get deal error: $e');
      rethrow;
    }
  }

  /// Accept a deal (for buyer or farmer)
  /// Buyer accepts when status = pending_buyer_confirmation
  /// Farmer accepts when status = pending_farmer_confirmation
  Future<Map<String, dynamic>> acceptDeal(int dealId, {String? notes}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final body = {
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      };

      final response = await http.patch(
        Uri.parse('$baseUrl/deals/$dealId/accept'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 10));

      print('Accept deal response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final errorBody = jsonDecode(response.body);
        throw Exception('Failed to accept deal: ${errorBody['error'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Accept deal error: $e');
      rethrow;
    }
  }

  /// Reject a deal (for buyer or farmer)
  /// Can only reject in confirmation phases: pending_buyer_confirmation, pending_farmer_confirmation
  Future<Map<String, dynamic>> rejectDeal(int dealId, {String? reason}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final body = {
        if (reason != null && reason.isNotEmpty) 'reason': reason,
      };

      final response = await http.patch(
        Uri.parse('$baseUrl/deals/$dealId/reject'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 10));

      print('Reject deal response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        final errorBody = jsonDecode(response.body);
        throw Exception('Failed to reject deal: ${errorBody['error'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Reject deal error: $e');
      rethrow;
    }
  }

  /// Get deal statistics for the authenticated user
  Future<Map<String, dynamic>> getDealStatistics() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.get(
        Uri.parse('$baseUrl/deals/statistics'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Deal statistics response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to load deal statistics: ${response.statusCode}');
      }
    } catch (e) {
      print('Deal statistics error: $e');
      rethrow;
    }
  }

  /// Get farmer supplies (available for admin matching)
  Future<List<dynamic>> getFarmerSupplies({Map<String, String>? filters}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final queryParams = filters != null ? Uri(queryParameters: filters).query : '';
      final url = queryParams.isNotEmpty ? '$baseUrl/supplies?$queryParams' : '$baseUrl/supplies';

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 10));

      print('Get farmer supplies response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data.containsKey('data')) {
          return data['data'] ?? [];
        }
        return data is List ? data : [];
      } else {
        throw Exception('Failed to load farmer supplies: ${response.statusCode}');
      }
    } catch (e) {
      print('Get farmer supplies error: $e');
      rethrow;
    }
  }

  /// Create a farmer supply (farmers submit availability)
  Future<Map<String, dynamic>> createFarmerSupply(Map<String, dynamic> supplyData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.post(
        Uri.parse('$baseUrl/supplies'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(supplyData),
      ).timeout(const Duration(seconds: 10));

      print('Create farmer supply response status: ${response.statusCode}');

      if (response.statusCode == 201) {
        return jsonDecode(response.body);
      } else {
        final errorBody = jsonDecode(response.body);
        throw Exception('Failed to create supply: ${errorBody['message'] ?? response.statusCode}');
      }
    } catch (e) {
      print('Create farmer supply error: $e');
      rethrow;
    }
  }

  /// Get available supplies (public endpoint)
  Future<List<dynamic>> getAvailableSupplies() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/supplies/available'),
        headers: {
          'Content-Type': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      print('Get available supplies response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data.containsKey('data')) {
          return data['data'] ?? [];
        }
        return data is List ? data : [];
      } else {
        throw Exception('Failed to load available supplies: ${response.statusCode}');
      }
    } catch (e) {
      print('Get available supplies error: $e');
      rethrow;
    }
  }
}