import 'package:flutter/material.dart';
import '../services/api_service.dart';

class CreateRequestScreen extends StatefulWidget {
  const CreateRequestScreen({super.key});

  @override
  _CreateRequestScreenState createState() => _CreateRequestScreenState();
}

class _CreateRequestScreenState extends State<CreateRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _quantityController = TextEditingController();
  final _deliveryLocationController = TextEditingController();
  final ApiService _apiService = ApiService();

  List<dynamic> _products = [];
  int? _selectedProductId;
  String _urgency = 'medium';
  DateTime? _neededByDate;
  bool _isLoading = false;
  bool _isLoadingProducts = true;
  String? _errorMessage;
  int _retryCount = 0;

  @override
  void initState() {
    super.initState();
    _loadProducts();
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
        title: const Text('Create Request'),
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
                    // Error display
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
                    const SizedBox(height: 16),
                    if (_products.isNotEmpty)
                      DropdownButtonFormField<int>(
                        initialValue: _selectedProductId,
                        decoration: const InputDecoration(
                          labelText: 'Select Product Needed',
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
                        labelText: 'Quantity Needed',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter quantity needed';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _deliveryLocationController,
                      decoration: const InputDecoration(
                        labelText: 'Delivery Location',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter delivery location';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      initialValue: _urgency,
                      decoration: const InputDecoration(
                        labelText: 'Urgency',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'low', child: Text('Low')),
                        DropdownMenuItem(value: 'medium', child: Text('Medium')),
                        DropdownMenuItem(value: 'high', child: Text('High')),
                      ],
                      onChanged: (value) {
                        setState(() => _urgency = value ?? 'medium');
                      },
                    ),
                    const SizedBox(height: 16),
                    GestureDetector(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _neededByDate ?? DateTime.now().add(const Duration(days: 1)),
                          firstDate: DateTime.now(),
                          lastDate: DateTime.now().add(const Duration(days: 365)),
                        );
                        if (picked != null) {
                          setState(() => _neededByDate = picked);
                        }
                      },
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Row(
                            children: [
                              const Icon(Icons.calendar_today, color: Colors.green),
                              const SizedBox(width: 16),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Needed By Date',
                                    style: TextStyle(fontSize: 12, color: Colors.grey),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    _neededByDate != null
                                        ? '${_neededByDate!.year}-${_neededByDate!.month.toString().padLeft(2, '0')}-${_neededByDate!.day.toString().padLeft(2, '0')}'
                                        : 'Tap to select date',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: _neededByDate != null ? Colors.black : Colors.grey,
                                    ),
                                  ),
                                ],
                              ),
                              const Spacer(),
                              if (_neededByDate != null)
                                IconButton(
                                  icon: const Icon(Icons.close),
                                  onPressed: () => setState(() => _neededByDate = null),
                                ),
                            ],
                          ),
                        ),
                      ),
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
                      onPressed: _isLoading ? null : _submitRequest,
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
                          : const Text('Create Request'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  void _submitRequest() async {
    if (_formKey.currentState!.validate() && _selectedProductId != null) {
      setState(() => _isLoading = true);
      try {
        final request = await _apiService.createBuyerRequest({
          'product_id': _selectedProductId,
          'quantity': double.parse(_quantityController.text),
          'delivery_location': _deliveryLocationController.text,
          'urgency': _urgency,
          'description': _descriptionController.text,
          if (_neededByDate != null) 'needed_by': _neededByDate!.toIso8601String().split('T')[0],
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Request created successfully!')),
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
    _quantityController.dispose();
    _deliveryLocationController.dispose();
    super.dispose();
  }
}