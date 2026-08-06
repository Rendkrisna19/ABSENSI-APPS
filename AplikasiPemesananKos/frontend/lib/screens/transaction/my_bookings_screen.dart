import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/api_config.dart';
// IMPORT HALAMAN DETAIL
import 'rent_detail_screen.dart'; 

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

// Tambahkan Mixin WidgetsBindingObserver
class _MyBookingsScreenState extends State<MyBookingsScreen> with WidgetsBindingObserver {
  bool _isLoading = true;
  List _bookings = [];
  final Color _primaryColor = const Color(0xFF00B4D8);

  @override
  void initState() {
    super.initState();
    // Daftarkan observer untuk mendeteksi lifecycle aplikasi
    WidgetsBinding.instance.addObserver(this);
    _fetchMyBookings();
  }

  @override
  void dispose() {
    // Hapus observer saat widget ditutup untuk mencegah memory leak
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  // Fungsi ini dipanggil otomatis oleh Flutter saat status aplikasi berubah
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Jika aplikasi dibuka kembali (misal dari browser pembayaran), refresh data!
      _fetchMyBookings();
    }
  }

  // Agar list ter-refresh saat kembali dari halaman detail
  Future<void> _refreshData() async {
    await _fetchMyBookings();
  }

  // Ambil Data Booking dari API
  Future<void> _fetchMyBookings() async {
    // Jangan set isLoading true agar tidak kedip-kedip saat auto refresh
    // setState(() => _isLoading = true); 
    
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      // Endpoint diarahkan ke index transaksi
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/transactions'), 
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (mounted) {
          setState(() {
            _bookings = data['data']; 
            _isLoading = false;
          });
        }
      } else {
        if (mounted) setState(() => _isLoading = false);
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
      debugPrint("Error fetching bookings: $e");
    }
  }

  // Fungsi Bayar (Hanya untuk status PENDING)
  Future<void> _payBooking(String? paymentUrl) async {
    if (paymentUrl == null || paymentUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Link pembayaran tidak ditemukan")),
      );
      return;
    }

    final Uri url = Uri.parse(paymentUrl);
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Gagal membuka link pembayaran")),
      );
    }
  }

  // --- UI HELPERS ---
  String _formatCurrency(dynamic price) {
    if (price == null) return 'Rp 0';
    return NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(double.tryParse(price.toString()) ?? 0);
  }

  String _formatDate(String dateStr) {
    try {
      return DateFormat('d MMM yyyy, HH:mm').format(DateTime.parse(dateStr));
    } catch (e) {
      return dateStr;
    }
  }

  Color _getStatusColor(String status, String rentStatus) {
    if (rentStatus == 'STOPPED') return Colors.red;
    if (rentStatus == 'ACTIVE') return Colors.green;
    
    switch (status) {
      case 'PAID': return Colors.green;
      case 'PENDING': return Colors.orange;
      case 'EXPIRED': return Colors.grey;
      case 'FAILED': return Colors.red;
      default: return Colors.blue;
    }
  }

  String _getStatusText(String status, String rentStatus) {
    if (rentStatus == 'STOPPED') return 'REFUND / STOP';
    if (rentStatus == 'ACTIVE') return 'AKTIF';
    if (rentStatus == 'UPCOMING') return 'BOOKED';
    
    return status; 
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text("Pesanan Saya", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _bookings.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.receipt_long_rounded, size: 80, color: Colors.grey[300]),
                      const SizedBox(height: 16),
                      const Text("Belum ada riwayat pesanan.", style: TextStyle(color: Colors.grey)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _refreshData,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _bookings.length,
                    itemBuilder: (context, index) {
                      final booking = _bookings[index];
                      final kost = booking['kost'] ?? {};
                      final status = booking['status'] ?? 'PENDING';
                      final rentStatus = booking['rent_status'] ?? 'UPCOMING';

                      return Card(
                        margin: const EdgeInsets.only(bottom: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0, 
                        color: Colors.white,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(12),
                          onTap: () {
                            // NAVIGASI KE HALAMAN DETAIL (RentDetailScreen)
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => RentDetailScreen(transactionId: booking['id']),
                              ),
                            ).then((_) => _refreshData()); // Refresh saat kembali
                          },
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Header: Tanggal & Status
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      _formatDate(booking['created_at']),
                                      style: TextStyle(color: Colors.grey[500], fontSize: 12),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: _getStatusColor(status, rentStatus).withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        _getStatusText(status, rentStatus),
                                        style: TextStyle(
                                          color: _getStatusColor(status, rentStatus),
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                
                                // Body: Info Kost
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Icon / Thumbnail Kecil
                                    Container(
                                      width: 50,
                                      height: 50,
                                      decoration: BoxDecoration(
                                        color: Colors.grey[100],
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: const Icon(Icons.home_work_rounded, color: Colors.grey),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            kost['name'] ?? 'Nama Kost',
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            "${booking['duration']} Bulan • ${_formatCurrency(booking['total_price'])}",
                                            style: TextStyle(color: Colors.grey[600], fontSize: 13),
                                          ),
                                          if (booking['tenant_name'] != null)
                                            Padding(
                                              padding: const EdgeInsets.only(top: 4.0),
                                              child: Text(
                                                "Penghuni: ${booking['tenant_name']}",
                                                style: TextStyle(color: _primaryColor, fontSize: 11, fontWeight: FontWeight.w500),
                                              ),
                                            ),
                                        ],
                                      ),
                                    ),
                                    const Icon(Icons.chevron_right, color: Colors.grey),
                                  ],
                                ),

                                // Footer: Tombol Bayar (Hanya jika PENDING)
                                if (status == 'PENDING') ...[
                                  const SizedBox(height: 16),
                                  SizedBox(
                                    width: double.infinity,
                                    height: 40,
                                    child: ElevatedButton(
                                      onPressed: () => _payBooking(booking['payment_url']),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: _primaryColor,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                        elevation: 0,
                                      ),
                                      child: const Text("Bayar Sekarang", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                                    ),
                                  ),
                                ]
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}