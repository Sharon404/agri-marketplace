import 'package:flutter/material.dart';
import '../services/api_service.dart';

class CreateListingScreen extends StatefulWidget {
  const CreateListingScreen({super.key});

  @override
  _CreateListingScreenState createState() => _CreateListingScreenState();
}

class _CreateListingScreenState extends State<CreateListingScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController();
  final _quantityController = TextEditingController();
  final _locationController = TextEditingController();
  final _availabilityDateController = TextEditingController();
  final ApiService _apiService = ApiService();

  List<dynamic> _products = [];
  int? _selectedProductId;
  bool _isLoading = false;
  bool _isLoadingProducts = true;
  String? _errorMessage;
  int _retryCount = 0;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  Future<void> _selectDate(BuildContext context) async {
    final now = DateTime.now();
    final tomorrow = DateTime(now.year, now.month, now.day).add(const Duration(days: 1));
    
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: tomorrow,
      firstDate: tomorrow,
      lastDate: DateTime(now.year + 1, now.month, now.day),
    );

    if (picked != null && mounted) {
      setState(() {
        _availabilityDateController.text = picked.toIso8601String().split('T')[0];
      });
    }
  }

  Future<void> _loadProducts() async {
    try {
      setState(() {
        _isLoadingProducts = true;
        _errorMessage = null;
      });

      print('Loading products... (attempt ${_retryCount + 1})');
      final products = await _apiService.getProducts();
      print('Products loaded: ${products.length} items');
      
      if (!mounted) return;
      
      setState(() {
        _products = products;
        if (products.isNotEmpty) {
          _selectedProductId = products[0]['id'];
        }
        _isLoadingProducts = false;
        _errorMessage = null;
        _retryCount = 0;
      });
    } catch (e) {
      print('Error loading products: $e');
      if (!mounted) return;
      
      setState(() {
        _isLoadingProducts = false;
        _errorMessage = e.toString();
      });

      // Auto-retry up to 2 times after 1 second delay
      if (_retryCount < 2) {
        _retryCount++;
        await Future.delayed(const Duration(seconds: 1));
        if (mounted) {
          _loadProducts();
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Failed to load products: ${e.toString()}'),
              action: SnackBarAction(
                label: 'RETRY',
                onPressed: () {
                  _retryCount = 0;
                  _loadProducts();
                },
              ),
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Create Listing'),
        backgroundColor: Colors.green,
      ),
      body: _isLoadingProducts
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const CircularProgressIndicator(),
                  const SizedBox(height: 16),
                  Text('Loading products... ${_retryCount > 0 ? '(attempt ${_retryCount + 1})' : ''}'),
                ],
              ),
            )
          : Padding(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: ListView(
                  children: [
                    if (_errorMessage != null)
                      Card(
                        color: Colors.red.shade50,
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            children: [
                              const Icon(Icons.error_outline, color: Colors.red, size: 48),
                              const SizedBox(height: 8),
                              const Text(
                                'Failed to load products',
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 8),
                              Text(_errorMessage!, textAlign: TextAlign.center),
                              const SizedBox(height: 8),
                              ElevatedButton.icon(
                                onPressed: () {
                                  _retryCount = 0;
                                  _loadProducts();
                                },
                                icon: const Icon(Icons.refresh),
                                label: const Text('Retry'),
                              ),
                            ],
                          ),
                        ),
                      ),
                    if (_products.isNotEmpty)
                      DropdownButtonFormField<int>(
                        value: _selectedProductId,
                        decoration: const InputDecoration(
                          labelText: 'Select Product',
                          border: OutlineInputBorder(),
                        ),
                        items: _products.map<DropdownMenuItem<int>>((product) {
                          return DropdownMenuItem<int>(
                            value: product['id'],
                            child: Text(product['name']),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() => _selectedProductId = value);
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Please select a product';
                          }
                          return null;
                        },
                      )
                    else if (_errorMessage == null)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            children: [
                              const Icon(Icons.inventory_2_outlined, size: 48),
                              const SizedBox(height: 8),
                              const Text('No products available'),
                              const SizedBox(height: 8),
                              ElevatedButton.icon(
                                onPressed: _loadProducts,
                                icon: const Icon(Icons.refresh),
                                label: const Text('Refresh'),
                              ),
                            ],
                          ),
                        ),
                      ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _quantityController,
                      decoration: const InputDecoration(
                        labelText: 'Quantity Available',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter quantity';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _priceController,
                      decoration: const InputDecoration(
                        labelText: 'Price per unit',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter a price';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _locationController,
                      decoration: const InputDecoration(
                        labelText: 'Farm Location',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter your farm location';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _availabilityDateController,
                      decoration: const InputDecoration(
                        labelText: 'Availability Date (YYYY-MM-DD)',
                        border: OutlineInputBorder(),
                      ),
                      readOnly: true,
                      onTap: () => _selectDate(context),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter availability date';
                        }
                        try {
                          final selected = DateTime.parse(value);
                          // Get today at midnight for fair comparison
                          final today = DateTime.now();
                          final todayMidnight = DateTime(today.year, today.month, today.day);
                          // Selected date must be at least tomorrow (today + 1 day)
                          if (selected.isBefore(todayMidnight.add(const Duration(days: 1)))) {
                            return 'Availability date must be tomorrow or later';
                          }
                        } catch (_) {
                          return 'Please enter a valid date';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _descriptionController,
                      decoration: const InputDecoration(
                        labelText: 'Description',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 3,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter a description';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: _isLoading ? null : _submitListing,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: _isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Create Listing'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  void _submitListing() async {
    if (_formKey.currentState!.validate() && _selectedProductId != null) {
      setState(() => _isLoading = true);
      try {
        final listing = await _apiService.createFarmerListing({
          'product_id': _selectedProductId,
          'quantity': double.parse(_quantityController.text),
          'unit_price': double.parse(_priceController.text),
          'location': _locationController.text,
          'availability_date': _availabilityDateController.text,
          'description': _descriptionController.text,
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Listing created successfully!')),
        );
        Navigator.pop(context);
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      } finally {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _priceController.dispose();
    _quantityController.dispose();
    _locationController.dispose();
    _availabilityDateController.dispose();
    super.dispose();
  }
}