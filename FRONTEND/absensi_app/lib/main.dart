import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart'; // <-- 1. Import ini
import 'package:absensi_app/screens/login_screen.dart';

void main() async { // <-- 2. Ubah menjadi async
  // 3. Tambahkan dua baris ini sebelum runApp
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID', null); 

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Aplikasi Absensi',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
        fontFamily: 'Poppins', // Opsional: jika Anda ingin Poppins jadi default
      ),
      home: const LoginScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}
