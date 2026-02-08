import 'package:flutter/material.dart';
import '../services/api_service.dart';

class CreateSupplyScreen extends StatefulWidget {
  const CreateSupplyScreen({super.key});

  @override
  _CreateSupplyScreenState createState() => _CreateSupplyScreenState();
}

class _CreateSupplyScreenState extends State<CreateSupplyScreen> {
  final _formKey = GlobalKey<FormState>();
  final _quantityController = TextEditingController();
  final _priceController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _availableFromController = TextEditingController();
  final _availableUntilController = TextEditingController();
  final ApiService _apiService = ApiService();

  List<dynamic> _products = [];
  int? _selectedProductId;
  String _selectedUnit = 'kg';
  bool _isLoading = false;
  bool _isLoadingProducts = true;

  final List<String> _unitOptions = ['kg', 'ton', 'bag', 'crate', 'piece'];

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  @override
  void dispose() {
    _quantityController.dispose();
    _priceController.dispose();
    _descriptionController.dispose();
    _availableFromController.dispose();
    _availableUntilController.dispose();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    try {
      final products = await _apiService.getProducts();
      setState(() {
        _products = products;
        if (products.isNotEmpty) {
          _selectedProductId = products[0]['id'];
        }
        _isLoadingProducts = false;
      });
    } catch (e) {
      setState(() => _isLoadingProducts = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load products: ${e.toString()}')),
        );
      }
    }
  }

  Future<void> _selectDate(BuildContext context, TextEditingController controller) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null) {
      setState(() {
        controller.text = picked.toIso8601String().split('T')[0];
      });
    }
  }

  Future<void> _submitSupply() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_selectedProductId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a product')),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final supplyData = {
        'product_id': _selectedProductId,
        'quantity_available': double.parse(_quantityController.text),
        'unit': _selectedUnit,
        'price_per_unit': double.parse(_priceController.text),
        'available_from': _availableFromController.text,
        'available_until': _availableUntilController.text,
        if (_descriptionController.text.isNotEmpty)
          'description': _descriptionController.text,
      };

      await _apiService.createFarmerSupply(supplyData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Supply created successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context, true); // Return true to indicate success
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to create supply: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Create Supply'),
        backgroundColor: Colors.green,
      ),
      body: _isLoadingProducts
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: ListView(
                  children: [
                    // Product selection
                    if (_products.isNotEmpty)
                      DropdownButtonFormField<int>(
                        value: _selectedProductId,
                        decoration: const InputDecoration(
                          labelText: 'Select Product *',
                          border: OutlineInputBorder(),
                          prefixIcon: Icon(Icons.inventory_2),
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
                    else
                      const Padding(
                        padding: EdgeInsets.all(16.0),
                        child: Text(
                          'No products available. Please contact admin.',
                          style: TextStyle(color: Colors.red),
                        ),
                      ),
                    const SizedBox(height: 16),

                    // Quantity available
                    TextFormField(
                      controller: _quantityController,
                      decoration: const InputDecoration(
                        labelText: 'Quantity Available *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.production_quantity_limits),
                        hintText: 'Enter available quantity',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter quantity';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Please enter a valid number';
                        }
                        if (double.parse(value) <= 0) {
                          return 'Quantity must be greater than 0';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    // Unit selection
                    DropdownButtonFormField<String>(
                      value: _selectedUnit,
                      decoration: const InputDecoration(
                        labelText: 'Unit *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.straighten),
                      ),
                      items: _unitOptions.map((unit) {
                        return DropdownMenuItem<String>(
                          value: unit,
                          child: Text(unit.toUpperCase()),
                        );
                      }).toList(),
                      onChanged: (value) {
                        setState(() => _selectedUnit = value!);
                      },
                    ),
                    const SizedBox(height: 16),

                    // Price per unit
                    TextFormField(
                      controller: _priceController,
                      decoration: const InputDecoration(
                        labelText: 'Price per Unit *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.attach_money),
                        hintText: 'Enter price per unit',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please enter a price';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Please enter a valid number';
                        }
                        if (double.parse(value) <= 0) {
                          return 'Price must be greater than 0';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    // Available from date
                    TextFormField(
                      controller: _availableFromController,
                      decoration: InputDecoration(
                        labelText: 'Available From *',
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.calendar_today),
                        hintText: 'YYYY-MM-DD',
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.calendar_month),
                          onPressed: () => _selectDate(context, _availableFromController),
                        ),
                      ),
                      readOnly: true,
                      onTap: () => _selectDate(context, _availableFromController),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please select available from date';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    // Available until date
                    TextFormField(
                      controller: _availableUntilController,
                      decoration: InputDecoration(
                        labelText: 'Available Until *',
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.event_busy),
                        hintText: 'YYYY-MM-DD',
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.calendar_month),
                          onPressed: () => _selectDate(context, _availableUntilController),
                        ),
                      ),
                      readOnly: true,
                      onTap: () => _selectDate(context, _availableUntilController),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Please select available until date';
                        }
                        if (_availableFromController.text.isNotEmpty) {
                          final from = DateTime.parse(_availableFromController.text);
                          final until = DateTime.parse(value);
                          if (until.isBefore(from)) {
                            return 'End date must be after start date';
                          }
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    // Description (optional)
                    TextFormField(
                      controller: _descriptionController,
                      decoration: const InputDecoration(
                        labelText: 'Description (Optional)',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.description),
                        hintText: 'Add any additional details about this supply',
                      ),
                      maxLines: 4,
                    ),
                    const SizedBox(height: 24),

                    // Submit button
                    ElevatedButton.icon(
                      onPressed: _isLoading ? null : _submitSupply,
                      icon: _isLoading
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                              ),
                            )
                          : const Icon(Icons.check),
                      label: Text(_isLoading ? 'Creating...' : 'Create Supply'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        textStyle: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Info text
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.blue.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.info_outline, color: Colors.blue.shade700),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Your supply will be visible to buyers and admin can create deals from it.',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.blue.shade900,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
