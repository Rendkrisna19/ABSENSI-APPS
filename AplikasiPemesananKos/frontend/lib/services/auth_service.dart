import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class AuthService {
  // --- HELPER: Simpan Sesi (Login & Register pakai ini biar seragam) ---
  Future<void> _saveSession(Map<String, dynamic> body) async {
    final prefs = await SharedPreferences.getInstance();
    
    // 1. Simpan Token
    final token = body['data']?['token'];
    if (token != null) await prefs.setString('token', token);

    // 2. Simpan Data User Lengkap (PENTING untuk Chat & Profile)
    final user = body['data']?['user'];
    if (user != null) {
      // Simpan field individual (Opsional, untuk akses cepat)
      await prefs.setString('name', user['name'] ?? '');
      await prefs.setString('email', user['email'] ?? '');
      await prefs.setString('role', user['role'] ?? 'user');
      
      // PENTING: Simpan ID secara spesifik (untuk Chat)
      if (user['id'] != null) {
        await prefs.setInt('user_id', user['id']); 
      }

      // PENTING: Simpan seluruh object user sebagai JSON String
      // Ini solusi terbaik agar _getMyId() di ChatScreen bisa bekerja
      await prefs.setString('user', jsonEncode(user));
    }
  }

  // REGISTER
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/auth/register');
    try {
      final res = await http.post(
        uri,
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone ?? '',
        }),
      );

      final body = jsonDecode(res.body);
      
      if (res.statusCode == 201 || res.statusCode == 200) {
        await _saveSession(body); // <--- Pakai helper simpan
        return {'ok': true, 'data': body};
      } else {
        String message = 'Registrasi gagal';
        if (body['message'] != null) message = body['message'];
        if (body['errors'] != null) {
          final errors = body['errors'] as Map;
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) message = first.first;
        }
        return {'ok': false, 'message': message};
      }
    } catch (e) {
      return {'ok': false, 'message': 'Terjadi kesalahan koneksi'};
    }
  }

  // LOGIN
  Future<Map<String, dynamic>> login(String email, String password) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/auth/login');
    try {
      final res = await http.post(
        uri,
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        await _saveSession(body); // <--- Pakai helper simpan
        return {'ok': true, 'data': body};
      } else {
        String message = 'Login gagal';
        if (body['message'] != null) message = body['message'];
        return {'ok': false, 'message': message};
      }
    } catch (e) {
      return {'ok': false, 'message': 'Terjadi kesalahan koneksi'};
    }
  }

  // LOGOUT
  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    
    // Hapus data lokal DULUAN agar user merasa logout instan
    await prefs.clear(); 

    // Baru request ke server (opsional, fire and forget)
    if (token != null) {
      final uri = Uri.parse('${ApiConfig.baseUrl}/auth/logout');
      try {
        await http.post(uri, headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        });
      } catch (_) {}
    }
  }

  // --- CEK APAKAH SUDAH LOGIN? (Untuk Main.dart) ---
  Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.containsKey('token');
  }
}