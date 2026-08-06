import 'package:flutter/material.dart';
import 'package:flutter/services.dart'; // Opsional: Untuk mengatur warna status bar
import 'services/auth_service.dart'; // Pastikan path ini benar sesuai folder Anda
import 'screens/home/home_screen.dart'; // Pastikan path ini benar
import 'screens/onboarding/onboarding_screen.dart'; // Pastikan path ini benar

void main() {
  // Pastikan binding terinisialisasi sebelum menjalankan app
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const KostApp());
}

class KostApp extends StatefulWidget {
  const KostApp({super.key});

  @override
  State<KostApp> createState() => _KostAppState();
}

class _KostAppState extends State<KostApp> {
  // Variabel state untuk menangani status login
  bool _isLoggedIn = false;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  // Fungsi untuk mengecek Token di SharedPreferences
  Future<void> _checkLoginStatus() async {
    // Kita panggil fungsi isLoggedIn() yang baru saja kita buat di AuthService
    final authService = AuthService();
    final isLoggedIn = await authService.isLoggedIn();

    if (mounted) {
      setState(() {
        _isLoggedIn = isLoggedIn;
        _isLoading = false; // Loading selesai
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    // 1. TAMPILAN SAAT LOADING (Splash Screen Sederhana)
    // Ini muncul sebentar saat aplikasi mengecek token di HP
    if (_isLoading) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(
          backgroundColor: Colors.white,
          body: Center(
            child: CircularProgressIndicator(
              color: const Color(0xFF00B4D8),
            ),
          ),
        ),
      );
    }

    // 2. TAMPILAN UTAMA APLIKASI
    return MaterialApp(
      title: 'Barokah Zuri Kost',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        fontFamily: 'Poppins',
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF00B4D8)),
        useMaterial3: true,
        // Opsional: Bikin background scaffold putih bersih default
        scaffoldBackgroundColor: Colors.white,
      ),
      
      // LOGIKA PENENTU HALAMAN:
      // Jika Login = True  -> Masuk HomeScreen
      // Jika Login = False -> Masuk OnboardingScreen
      home: _isLoggedIn ? const HomeScreen() : const OnboardingScreen(),
    );
  }
}