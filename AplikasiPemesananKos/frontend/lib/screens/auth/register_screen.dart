import 'package:flutter/material.dart';
import '../../services/auth_service.dart';
import 'login_screen.dart';
import '../home/home_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  // Controller
  final _nameC = TextEditingController();
  final _emailC = TextEditingController();
  final _phoneC = TextEditingController();
  final _passC = TextEditingController();

  // State
  bool _isLoading = false;
  bool _isPasswordVisible = false;
  String? _error;

  final _authService = AuthService();

  @override
  void dispose() {
    _nameC.dispose();
    _emailC.dispose();
    _phoneC.dispose();
    _passC.dispose();
    super.dispose();
  }

  Future<void> _doRegister() async {
    // Validasi Input Kosong
    if (_nameC.text.isEmpty ||
        _emailC.text.isEmpty ||
        _phoneC.text.isEmpty ||
        _passC.text.isEmpty) {
      setState(() => _error = "Semua kolom harus diisi");
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    // Format Nomor HP (Hapus 0 di depan jika ada, lalu tambah 62)
    String rawPhone = _phoneC.text.trim();
    if (rawPhone.startsWith('0')) {
      rawPhone = rawPhone.substring(1);
    }
    String fullPhone = "62$rawPhone";

    try {
      final res = await _authService.register(
        name: _nameC.text.trim(),
        email: _emailC.text.trim(),
        password: _passC.text,
        phone: fullPhone,
      );

      if (!mounted) return;

      if (res['ok'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Registrasi berhasil')));
        Navigator.pushReplacement(
            context, MaterialPageRoute(builder: (_) => const HomeScreen()));
      } else {
        setState(() {
          _error = res['message'] ?? 'Registrasi gagal';
        });
      }
    } catch (e) {
      setState(() => _error = 'Terjadi kesalahan: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cyan = const Color(0xFF00B4D8);

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Tombol Back
              IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () => Navigator.pop(context),
              ),
              const SizedBox(height: 8),

              // 1. ASSETS GAMBAR (Style sama seperti Login)
              Center(
                child: Image.asset(
                  'assets/register.png', // Pastikan nama file sesuai assets Anda
                  height: 180,
                  errorBuilder: (context, error, stackTrace) {
                    // Placeholder jika gambar tidak ketemu
                    return Icon(Icons.app_registration,
                        size: 100, color: Colors.grey.shade300);
                  },
                ),
              ),
              const SizedBox(height: 24),

              // Judul
              const Text(
                'Daftar Akun',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 16),

              // 2. FORM INPUT
              // Input Nama
              TextField(
                controller: _nameC,
                decoration: const InputDecoration(
                  labelText: 'Nama Lengkap',
                  prefixIcon: Icon(Icons.person_outline),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(12)),
                  ),
                ),
                enabled: !_isLoading,
              ),
              const SizedBox(height: 12),

              // Input No HP (Modifikasi Prefix +62 agar rapi dalam box)
              TextField(
                controller: _phoneC,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(
                  labelText: 'Nomor WhatsApp',
                  border: const OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(12)),
                  ),
                  prefixIcon: Padding(
                    padding: const EdgeInsets.only(left: 12, right: 8),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.phone_android, size: 20, color: Colors.grey),
                        const SizedBox(width: 8),
                        Text(
                          '+62',
                          style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Colors.grey.shade700),
                        ),
                        const SizedBox(width: 4),
                        Container(
                          height: 20,
                          width: 1,
                          color: Colors.grey.shade300, // Garis pemisah kecil
                        )
                      ],
                    ),
                  ),
                ),
                enabled: !_isLoading,
              ),
              const SizedBox(height: 12),

              // Input Email
              TextField(
                controller: _emailC,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  labelText: 'Email ID',
                  prefixIcon: Icon(Icons.alternate_email),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(12)),
                  ),
                ),
                enabled: !_isLoading,
              ),
              const SizedBox(height: 12),

              // Input Password
              TextField(
                controller: _passC,
                obscureText: !_isPasswordVisible,
                decoration: InputDecoration(
                  labelText: 'Password',
                  prefixIcon: const Icon(Icons.lock_outline),
                  border: const OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(12)),
                  ),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _isPasswordVisible
                          ? Icons.visibility
                          : Icons.visibility_off,
                    ),
                    onPressed: () {
                      setState(() {
                        _isPasswordVisible = !_isPasswordVisible;
                      });
                    },
                  ),
                ),
                enabled: !_isLoading,
              ),
              const SizedBox(height: 16),

              // Error Message Display
              if (_error != null) ...[
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    _error!,
                    style: const TextStyle(color: Colors.red, fontSize: 12),
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Terms Text
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0),
                child: RichText(
                  textAlign: TextAlign.center,
                  text: TextSpan(
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
                    children: [
                      const TextSpan(text: 'Dengan mendaftar, Anda menyetujui '),
                      TextSpan(
                        text: 'Syarat & Ketentuan',
                        style: TextStyle(
                            color: cyan, fontWeight: FontWeight.bold),
                      ),
                      const TextSpan(text: ' kami.'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Tombol Daftar
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _doRegister,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: cyan,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          height: 18,
                          width: 18,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Daftar'),
                ),
              ),
              const SizedBox(height: 20),

              // Footer Login
              Center(
                child: GestureDetector(
                  onTap: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const LoginScreen()),
                    );
                  },
                  child: RichText(
                    text: TextSpan(
                      text: 'Sudah punya akun? ',
                      style: const TextStyle(color: Colors.black54),
                      children: [
                        TextSpan(
                          text: 'Masuk',
                          style: TextStyle(
                              color: cyan, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }
}