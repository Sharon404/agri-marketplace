import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class DealsProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<dynamic> _deals = [];
  Map<String, dynamic>? _currentDeal;
  Map<String, dynamic>? _statistics;
  bool _isLoading = false;
  String? _error;

  List<dynamic> get deals => _deals;
  Map<String, dynamic>? get currentDeal => _currentDeal;
  Map<String, dynamic>? get statistics => _statistics;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Fetch all deals for the authenticated user
  /// Optional [status] filter: 'pending_buyer_confirmation', 'pending_farmer_confirmation', 
  /// 'both_confirmed', 'payment_pending', 'active', 'completed', 'cancelled'
  Future<void> fetchDeals({String? status}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _deals = await _apiService.getDeals(status: status);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  /// Fetch a specific deal by ID
  Future<void> fetchDeal(int dealId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _currentDeal = await _apiService.getDeal(dealId);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  /// Accept a deal (buyer or farmer depending on state)
  /// Optional [notes] can be provided with the acceptance
  Future<Map<String, dynamic>> acceptDeal(int dealId, {String? notes}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _apiService.acceptDeal(dealId, notes: notes);
      
      // Update the current deal if it's the same one
      if (_currentDeal != null && _currentDeal!['id'] == dealId) {
        _currentDeal = result['deal'];
      }
      
      // Refresh the deals list
      await fetchDeals();
      
      _isLoading = false;
      notifyListeners();
      return result;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  /// Reject a deal (buyer or farmer depending on state)
  /// Optional [reason] can be provided for the rejection
  Future<Map<String, dynamic>> rejectDeal(int dealId, {String? reason}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _apiService.rejectDeal(dealId, reason: reason);
      
      // Update the current deal if it's the same one
      if (_currentDeal != null && _currentDeal!['id'] == dealId) {
        _currentDeal = result['deal'];
      }
      
      // Refresh the deals list
      await fetchDeals();
      
      _isLoading = false;
      notifyListeners();
      return result;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  /// Fetch deal statistics for the authenticated user
  Future<void> fetchStatistics() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _statistics = await _apiService.getDealStatistics();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  /// Clear current deal
  void clearCurrentDeal() {
    _currentDeal = null;
    notifyListeners();
  }

  /// Clear all data
  void clear() {
    _deals = [];
    _currentDeal = null;
    _statistics = null;
    _error = null;
    _isLoading = false;
    notifyListeners();
  }
}
