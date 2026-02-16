import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/order_provider.dart';

class SellerDashboardScreen extends StatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => context.read<OrderProvider>().loadSellerOrders());
  }

  @override
  Widget build(BuildContext context) {
    final orderProvider = context.watch<OrderProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Seller Dashboard')),
      body: Builder(
        builder: (context) {
          if (orderProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (orderProvider.error != null) {
            return Center(child: Text(orderProvider.error!));
          }

          if (orderProvider.orders.isEmpty) {
            return const Center(child: Text('No seller orders found'));
          }

          return ListView.builder(
            itemCount: orderProvider.orders.length,
            itemBuilder: (context, index) {
              final order = orderProvider.orders[index];
              return ListTile(
                title: Text('Order #${order.id}'),
                subtitle: Text('Status: ${order.status}'),
                trailing: Text('KES ${order.totalAmount.toStringAsFixed(2)}'),
              );
            },
          );
        },
      ),
    );
  }
}
