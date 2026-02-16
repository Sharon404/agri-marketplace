import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/order_provider.dart';
import 'orders_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _paymentController = TextEditingController(text: 'mpesa');

  @override
  void dispose() {
    _paymentController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final orderProvider = context.read<OrderProvider>();
    final order = await orderProvider.checkout(_paymentController.text.trim());

    if (order != null && mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const OrdersScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final orderProvider = context.watch<OrderProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(
              controller: _paymentController,
              decoration: const InputDecoration(labelText: 'Payment method'),
            ),
            const SizedBox(height: 16),
            if (orderProvider.error != null)
              Text(orderProvider.error!, style: const TextStyle(color: Colors.red)),
            ElevatedButton(
              onPressed: orderProvider.isLoading ? null : _submit,
              child: orderProvider.isLoading
                  ? const CircularProgressIndicator()
                  : const Text('Confirm order'),
            ),
          ],
        ),
      ),
    );
  }
}
