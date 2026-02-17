import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import 'home_screen.dart';
import 'seller_verification_pending_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  // Common fields
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  String _role = 'buyer';

  // Seller-specific fields
  final _businessNameController = TextEditingController();
  final _businessAddressController = TextEditingController();
  final _taxIdController = TextEditingController();
  final _nationalIdController = TextEditingController();
  final _bankNameController = TextEditingController();
  final _bankAccountNameController = TextEditingController();
  final _bankAccountNumberController = TextEditingController();
  bool _termsAccepted = false;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _businessNameController.dispose();
    _businessAddressController.dispose();
    _taxIdController.dispose();
    _nationalIdController.dispose();
    _bankNameController.dispose();
    _bankAccountNameController.dispose();
    _bankAccountNumberController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_role == 'seller' && !_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please accept the terms and conditions')),
      );
      return;
    }

    final authProvider = context.read<AuthProvider>();
    final data = {
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'email': _emailController.text.trim(),
      'phone': _phoneController.text.trim(),
      'password': _passwordController.text.trim(),
      'role': _role,
    };

    if (_role == 'seller') {
      data.addAll({
        'business_name': _businessNameController.text.trim(),
        'business_address': _businessAddressController.text.trim(),
        'tax_id': _taxIdController.text.trim(),
        'national_id': _nationalIdController.text.trim(),
        'bank_name': _bankNameController.text.trim(),
        'bank_account_name': _bankAccountNameController.text.trim(),
        'bank_account_number': _bankAccountNumberController.text.trim(),
        'terms_accepted': _termsAccepted,
      });
      await authProvider.registerSeller(data);
    } else {
      await authProvider.register(data);
    }

    if (authProvider.isAuthenticated && mounted) {
      if (_role == 'seller') {
        // Sellers go to verification pending screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => const SellerVerificationPendingScreen(),
          ),
        );
      } else {
        // Buyers go directly to home screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const HomeScreen()),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Register')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Common fields
            TextField(
              controller: _firstNameController,
              decoration: const InputDecoration(labelText: 'First name'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _lastNameController,
              decoration: const InputDecoration(labelText: 'Last name'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _emailController,
              decoration: const InputDecoration(labelText: 'Email'),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _phoneController,
              decoration: const InputDecoration(labelText: 'Phone'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _passwordController,
              decoration: const InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _role,
              decoration: const InputDecoration(labelText: 'Role'),
              items: const [
                DropdownMenuItem(value: 'buyer', child: Text('Buyer')),
                DropdownMenuItem(value: 'seller', child: Text('Seller')),
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() {
                    _role = value;
                  });
                }
              },
            ),
            // Seller-specific fields
            if (_role == 'seller') ...[
              const SizedBox(height: 24),
              const Text(
                'Business Information',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _businessNameController,
                decoration: const InputDecoration(labelText: 'Business Name *'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _businessAddressController,
                decoration: const InputDecoration(labelText: 'Business Address *'),
                minLines: 2,
                maxLines: 4,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _taxIdController,
                decoration: const InputDecoration(labelText: 'Tax ID *'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _nationalIdController,
                decoration: const InputDecoration(labelText: 'National ID *'),
              ),
              const SizedBox(height: 24),
              const Text(
                'Bank Information',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _bankNameController,
                decoration: const InputDecoration(labelText: 'Bank Name *'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _bankAccountNameController,
                decoration: const InputDecoration(labelText: 'Account Name *'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _bankAccountNumberController,
                decoration: const InputDecoration(labelText: 'Account Number *'),
              ),
              const SizedBox(height: 24),
              Card(
                color: Colors.blue[50],
                child: Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Seller Verification Notice',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Your seller account will be verified within 24 hours. '
                        'We require government-issued ID, tax registration, and bank details '
                        'to prevent fraud and ensure marketplace safety. The information you provide '
                        'will be stored securely and used only for verification purposes.',
                        style: TextStyle(fontSize: 12),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Checkbox(
                            value: _termsAccepted,
                            onChanged: (value) {
                              setState(() {
                                _termsAccepted = value ?? false;
                              });
                            },
                          ),
                          const Expanded(
                            child: Text(
                              'I accept the verification requirements and agree that '
                              'false information may result in account termination',
                              style: TextStyle(fontSize: 12),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
            const SizedBox(height: 16),
            if (authProvider.error != null)
              Text(authProvider.error!, style: const TextStyle(color: Colors.red)),
            ElevatedButton(
              onPressed: authProvider.isLoading ? null : _submit,
              child: authProvider.isLoading
                  ? const CircularProgressIndicator()
                  : const Text('Create account'),
            ),
          ],
        ),
      ),
    );
  }
}
