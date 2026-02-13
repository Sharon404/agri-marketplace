import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/deals_provider.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/home_screen.dart';
import 'screens/create_listing_screen.dart';
import 'screens/create_request_screen.dart';
import 'screens/create_supply_screen.dart';
import 'screens/activation_screen.dart';
import 'screens/deals_list_screen.dart';
import 'screens/farmer_supplies_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  final authProvider = AuthProvider();
  
  try {
    await authProvider.checkAuthStatus();
    print('✓ Auth status check completed');
  } catch (e, stackTrace) {
    print('✗ Error checking auth status: $e');
    print('Stack trace: $stackTrace');
  }
  
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => authProvider),
        ChangeNotifierProvider(create: (_) => DealsProvider()),
      ],
      child: const AgriMarketplaceApp(),
    ),
  );
}

class AgriMarketplaceApp extends StatelessWidget {
  const AgriMarketplaceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Agri Marketplace',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.green,
        visualDensity: VisualDensity.adaptivePlatformDensity,
        useMaterial3: false,
      ),
      home: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          print('Building home screen - authenticated: ${auth.isAuthenticated}');
          if (auth.isAuthenticated) {
            return const HomeScreen();
          } else {
            return const LoginScreen();
          }
        },
      ),
      routes: {
        '/login': (context) => const LoginScreen(),
        '/register': (context) => const RegisterScreen(),
        '/home': (context) => const HomeScreen(),
        '/create-listing': (context) => const CreateListingScreen(),
        '/create-request': (context) => const CreateRequestScreen(),
        '/create-supply': (context) => const CreateSupplyScreen(),
        '/activation': (context) => const ActivationScreen(),
        '/deals': (context) => const DealsListScreen(),
        '/farmer-supplies': (context) => const FarmerSuppliesScreen(),
      },
    );
  }
}