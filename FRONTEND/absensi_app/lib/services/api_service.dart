import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';

class ApiService {
  // Pastikan URL ini benar dan tanpa garis miring di akhir
  static const String _baseUrl = 'http://127.0.0.1:8000/api';

  // --- FUNGSI HELPER (PRIBADI) ---

  /// Mengambil token autentikasi dari penyimpanan lokal.
  static Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  /// Membuat header standar untuk permintaan API yang memerlukan autentikasi.
  static Future<Map<String, String>> _getHeaders() async {
    final token = await _getToken();
    return {
      'Content-Type': 'application/json; charset=UTF-8',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  /// Menangani respons error dari API secara umum.
  static Map<String, dynamic> _handleError(dynamic e) {
    if (e is TimeoutException) {
      return {'success': false, 'message': 'Koneksi timeout. Pastikan server berjalan.'};
    }
    if (e is SocketException) {
      return {'success': false, 'message': 'Tidak dapat terhubung ke server. Periksa koneksi dan alamat IP.'};
    }
    print('Error tidak diketahui di ApiService: $e');
    return {'success': false, 'message': 'Terjadi kesalahan yang tidak diketahui: $e'};
  }

  // --- FUNGSI UTAMA (PUBLIK) ---

  /// Mengirim permintaan login ke server.
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/login'),
        headers: {
          'Content-Type': 'application/json; charset=UTF-8',
          'Accept': 'application/json',
        },
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', data['access_token']);
        return {'success': true, 'user': User.fromJson(data['user'])};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Email atau password salah.'};
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  /// Mengirim permintaan logout ke server.
  static Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$_baseUrl/logout'),
        headers: await _getHeaders(),
      );
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
    } catch (e) {
      print('Error saat logout: $e');
    }
  }

  /// Mengirim data check-in ke server.
  static Future<Map<String, dynamic>> checkIn(double lat, double long) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/check-in'),
        headers: await _getHeaders(),
        body: jsonEncode({'latitude': lat, 'longitude': long}),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(response.body);
      return {'success': response.statusCode == 200, 'message': data['message']};
    } catch (e) {
      return _handleError(e);
    }
  }
  
  /// Mengirim data check-out ke server.
  static Future<Map<String, dynamic>> checkOut(double lat, double long) async {
     try {
      final response = await http.post(
        Uri.parse('$_baseUrl/check-out'),
        headers: await _getHeaders(),
        body: jsonEncode({'latitude': lat, 'longitude': long}),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(response.body);
      return {'success': response.statusCode == 200, 'message': data['message']};
    } catch (e) {
      return _handleError(e);
    }
  }

  /// Mengirim pengajuan izin atau sakit ke server.
  static Future<Map<String, dynamic>> submitLeave(String status, String reason) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/submit-leave'),
        headers: await _getHeaders(),
        body: jsonEncode({'status': status, 'reason': reason}),
      ).timeout(const Duration(seconds: 15));

      final data = jsonDecode(response.body);
      return {'success': response.statusCode == 200, 'message': data['message']};
    } catch (e) {
      return _handleError(e);
    }
  }

  /// Mengambil data absensi hari ini dari server.
  static Future<Map<String, dynamic>?> getTodayAttendance() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/today-attendance'),
        headers: await _getHeaders(),
      ).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data']; // bisa null jika belum ada absensi
      }
      return null;
    } catch (e) {
      _handleError(e);
      return null;
    }
  }
}
