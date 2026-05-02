import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../models/category_model.dart';
import '../models/product_model.dart';
import '../providers/product_provider.dart';

class SellerAddProductScreen extends StatefulWidget {
  const SellerAddProductScreen({super.key, this.editProduct});

  final ProductModel? editProduct;

  @override
  State<SellerAddProductScreen> createState() => _SellerAddProductScreenState();
}

class _SellerAddProductScreenState extends State<SellerAddProductScreen> {
  final _formKey = GlobalKey<FormState>();

  final _nameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _stockCtrl = TextEditingController();
  final _moqCtrl = TextEditingController();
  final _weightCtrl = TextEditingController();

  int? _selectedCategoryId;
  String _shippingType = 'free';
  final _flatFeeCtrl = TextEditingController();
  bool _isActive = true;

  List<String> _uploadedImageUrls = [];
  bool _saving = false;
  bool _uploadingImage = false;

  bool get isEdit => widget.editProduct != null;

  @override
  void initState() {
    super.initState();
    final p = widget.editProduct;
    if (p != null) {
      _nameCtrl.text = p.name;
      _descCtrl.text = p.description;
      _priceCtrl.text = p.price.toString();
      _stockCtrl.text = p.stockQuantity.toString();
      _moqCtrl.text = p.minimumOrderQuantity.toString();
      _weightCtrl.text = p.weightPerUnit?.toString() ?? '';
      _selectedCategoryId = p.categoryId;
      _isActive = p.isActive;
      _uploadedImageUrls = p.images.map((i) => i.imageUrl).toList();
      if (p.shipping != null) {
        _shippingType = p.shipping!.shippingType;
        _flatFeeCtrl.text = p.shipping!.flatShippingFee?.toString() ?? '';
      }
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<ProductProvider>();
      if (provider.categories.isEmpty) provider.loadCategories();
    });
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _priceCtrl.dispose();
    _stockCtrl.dispose();
    _moqCtrl.dispose();
    _weightCtrl.dispose();
    _flatFeeCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickAndUploadImage() async {
    final picker = ImagePicker();
    final XFile? picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
    if (picked == null) return;
    setState(() => _uploadingImage = true);
    try {
      final bytes = await picked.readAsBytes();
      final url = await context.read<ProductProvider>().uploadImageBytes(bytes, picked.name);
      setState(() => _uploadedImageUrls.add(url));
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Upload failed: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _uploadingImage = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCategoryId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a category'), backgroundColor: Colors.orange),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      final payload = <String, dynamic>{
        'name': _nameCtrl.text.trim(),
        'description': _descCtrl.text.trim(),
        'price': double.parse(_priceCtrl.text.trim()),
        'category_id': _selectedCategoryId,
        'stock_quantity': int.parse(_stockCtrl.text.trim()),
        'minimum_order_quantity': int.parse(_moqCtrl.text.trim()),
        'is_active': _isActive,
        if (_weightCtrl.text.isNotEmpty) 'weight_per_unit': double.parse(_weightCtrl.text.trim()),
        if (_uploadedImageUrls.isNotEmpty)
          'images': _uploadedImageUrls
              .asMap()
              .entries
              .map((entry) => {
                    'image_url': entry.value,
                    'is_primary': entry.key == 0,
                  })
              .toList(),
        'shipping': {
          'shipping_type': _shippingType,
          if (_shippingType == 'flat' && _flatFeeCtrl.text.isNotEmpty)
            'flat_shipping_fee': double.parse(_flatFeeCtrl.text.trim()),
        },
      };
      final provider = context.read<ProductProvider>();
      if (isEdit) {
        await provider.updateProduct(widget.editProduct!.id, payload);
      } else {
        await provider.createProduct(payload);
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isEdit ? 'Product updated!' : 'Product created!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A5276),
        foregroundColor: Colors.white,
        title: Text(isEdit ? 'Edit Product' : 'Add Product',
            style: const TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          if (_saving)
            const Padding(
              padding: EdgeInsets.all(16),
              child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
            )
          else
            TextButton(
              onPressed: _submit,
              child: const Text('SAVE', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _sectionTitle('Product Images'),
            _buildImageSection(),
            const SizedBox(height: 16),
            _sectionTitle('Basic Information'),
            _card([
              _field(_nameCtrl, 'Product Name *', validator: _required),
              const SizedBox(height: 12),
              _field(_descCtrl, 'Description *', maxLines: 4, validator: _required),
              const SizedBox(height: 12),
              _buildCategoryDropdown(),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(child: _field(_priceCtrl, 'Price (KSh) *', keyboardType: TextInputType.number, validator: _requiredNum)),
                  const SizedBox(width: 12),
                  Expanded(child: _field(_stockCtrl, 'Stock Qty *', keyboardType: TextInputType.number, validator: _requiredInt)),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(child: _field(_moqCtrl, 'Min. Order Qty *', keyboardType: TextInputType.number, validator: _requiredInt)),
                  const SizedBox(width: 12),
                  Expanded(child: _field(_weightCtrl, 'Weight/Unit (kg)', keyboardType: TextInputType.number)),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Text('Active listing:', style: TextStyle(fontSize: 14)),
                  const Spacer(),
                  Switch(
                    value: _isActive,
                    onChanged: (v) => setState(() => _isActive = v),
                    activeThumbColor: const Color(0xFF27AE60),
                  ),
                ],
              ),
            ]),
            const SizedBox(height: 16),
            _sectionTitle('Shipping'),
            _card([
              DropdownButtonFormField<String>(
                initialValue: _shippingType,
                decoration: _inputDecoration('Shipping Type'),
                items: const [
                  DropdownMenuItem(value: 'free', child: Text('Free Shipping')),
                  DropdownMenuItem(value: 'flat', child: Text('Flat Rate')),
                ],
                onChanged: (v) => setState(() => _shippingType = v ?? 'free'),
              ),
              if (_shippingType == 'flat') ...[
                const SizedBox(height: 12),
                _field(_flatFeeCtrl, 'Flat Shipping Fee (KSh)', keyboardType: TextInputType.number),
              ],
            ]),
            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) => Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1A5276))),
      );

  Widget _card(List<Widget> children) => Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
        ),
      );

  Widget _buildImageSection() {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_uploadedImageUrls.isNotEmpty)
              SizedBox(
                height: 100,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: _uploadedImageUrls.length,
                  itemBuilder: (context, i) => Stack(
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: Image.network(
                            _uploadedImageUrls[i],
                            width: 100,
                            height: 100,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              width: 100,
                              height: 100,
                              color: const Color(0xFFECF0F1),
                              child: const Icon(Icons.broken_image, color: Colors.grey),
                            ),
                          ),
                        ),
                      ),
                      Positioned(
                        top: 2,
                        right: 10,
                        child: GestureDetector(
                          onTap: () => setState(() => _uploadedImageUrls.removeAt(i)),
                          child: Container(
                            decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                            child: const Icon(Icons.close, size: 16, color: Colors.white),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _uploadingImage || _uploadedImageUrls.length >= 5 ? null : _pickAndUploadImage,
              icon: _uploadingImage
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.add_photo_alternate_outlined),
              label: Text(_uploadingImage
                  ? 'Uploading...'
                  : _uploadedImageUrls.length >= 5
                      ? 'Max 5 images'
                      : 'Add Image (${_uploadedImageUrls.length}/5)'),
              style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFF1A5276)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryDropdown() {
    return Consumer<ProductProvider>(
      builder: (context, provider, _) {
        final cats = provider.categories;
        // flatten: parents and their children
        final allCats = <CategoryModel>[];
        for (final parent in cats) {
          allCats.add(parent);
          allCats.addAll(parent.children);
        }
        return DropdownButtonFormField<int>(
          initialValue: _selectedCategoryId,
          decoration: _inputDecoration('Category *'),
          isExpanded: true,
          items: allCats
              .map((cat) => DropdownMenuItem(
                    value: cat.id,
                    child: Text(
                      cat.parentId == null ? cat.name : '  └ ${cat.name}',
                      style: TextStyle(
                        fontWeight: cat.parentId == null ? FontWeight.bold : FontWeight.normal,
                        fontSize: 13,
                      ),
                    ),
                  ))
              .toList(),
          onChanged: (v) => setState(() => _selectedCategoryId = v),
          validator: (v) => v == null ? 'Required' : null,
        );
      },
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label, {
    int maxLines = 1,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) =>
      TextFormField(
        controller: ctrl,
        maxLines: maxLines,
        keyboardType: keyboardType,
        validator: validator,
        decoration: _inputDecoration(label),
      );

  InputDecoration _inputDecoration(String label) => InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 13),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        isDense: true,
      );

  String? _required(String? v) => (v == null || v.trim().isEmpty) ? 'Required' : null;
  String? _requiredNum(String? v) {
    if (v == null || v.trim().isEmpty) return 'Required';
    if (double.tryParse(v.trim()) == null) return 'Invalid number';
    return null;
  }

  String? _requiredInt(String? v) {
    if (v == null || v.trim().isEmpty) return 'Required';
    if (int.tryParse(v.trim()) == null) return 'Must be a whole number';
    return null;
  }
}
