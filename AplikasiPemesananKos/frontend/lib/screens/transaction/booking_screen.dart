import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/api_config.dart';

class BookingScreen extends StatefulWidget {
  final Map kost;

  const BookingScreen({super.key, required this.kost});

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  // --- STATE DURASI & TANGGAL ---
  int _duration = 1;
  DateTime _startDate = DateTime.now().add(const Duration(days: 1));
  
  // --- STATE BOOKING ORANG LAIN ---
  bool _isBookingForSelf = true; // Default: Diri Sendiri
  final TextEditingController _tenantNameController = TextEditingController();
  final TextEditingController _tenantPhoneController = TextEditingController();
  String _selectedRelation = 'family'; // Default relation jika orang lain

  // --- STATE TRANSAKSI ---
  bool _isLoading = false;
  int? _currentTransactionId;

  // --- WARNA ---
  final Color _primaryColor = const Color(0xFF00B4D8);
  final Color _bgGrey = const Color(0xFFF5F7FA);

  @override
  void dispose() {
    _tenantNameController.dispose();
    _tenantPhoneController.dispose();
    super.dispose();
  }

  // --- HELPERS ---

  String _formatPrice(int price) {
    return NumberFormat.currency(
      locale: 'id_ID',
      symbol: 'Rp ',
      decimalDigits: 0,
    ).format(price);
  }

  int get _totalPrice {
    int pricePerMonth = int.tryParse(widget.kost['price_per_month'].toString()) ?? 0;
    return pricePerMonth * _duration;
  }

  String _imageUrlFor(String? thumb) {
    if (thumb == null || thumb.isEmpty) return '';
    if (thumb.startsWith('http')) return thumb;
    String base = ApiConfig.baseUrl;
    if (base.endsWith('/')) base = base.substring(0, base.length - 1);
    if (base.endsWith('/api')) base = base.replaceAll('/api', '');
    return '$base/storage/kosts/${thumb.replaceAll('kosts/', '')}';
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: _primaryColor,
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _startDate) {
      setState(() => _startDate = picked);
    }
  }

  // --- LOGIKA TRANSAKSI ---
  Future<void> _createBooking() async {
    // 1. Validasi Input Penghuni
    if (!_isBookingForSelf) {
      if (_tenantNameController.text.isEmpty || _tenantPhoneController.text.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Nama dan Nomor HP penghuni wajib diisi!")),
        );
        return;
      }
    }

    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      // 2. Siapkan Body Request Sesuai Backend Laravel
      Map<String, dynamic> requestBody = {
        'kost_id': widget.kost['id'],
        'duration': _duration,
        'start_date': DateFormat('yyyy-MM-dd').format(_startDate),
        // Logika Tenant
        'tenant_type': _isBookingForSelf ? 'self' : _selectedRelation,
      };

      // Jika untuk orang lain, kirim detailnya
      if (!_isBookingForSelf) {
        requestBody['tenant_name'] = _tenantNameController.text;
        requestBody['tenant_phone'] = _tenantPhoneController.text;
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/transactions'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(requestBody),
      );

      final data = jsonDecode(response.body);
      
      if (response.statusCode == 201) {
        String paymentUrl = data['payment_url'];
        _currentTransactionId = data['data']['id'];

        if (await canLaunchUrl(Uri.parse(paymentUrl))) {
          await launchUrl(Uri.parse(paymentUrl), mode: LaunchMode.externalApplication);
          if (mounted) _showPaymentConfirmationDialog();
        } else {
          throw 'Gagal membuka link pembayaran';
        }
      } else {
        throw data['message'] ?? 'Gagal membuat pesanan';
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Error: $e"), backgroundColor: Colors.red)
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _checkPaymentStatus() async {
    if (_currentTransactionId == null) return;
    showDialog(context: context, barrierDismissible: false, builder: (ctx) => const Center(child: CircularProgressIndicator()));

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/transactions/$_currentTransactionId/check'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );
      
      if (mounted) Navigator.pop(context); // Tutup loading

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'PAID') {
           if (mounted) {
             Navigator.pop(context); // Tutup dialog konfirmasi
             _showSuccessDialog();
           }
        } else {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Pembayaran belum selesai. Coba lagi nanti.")));
          }
        }
      }
    } catch (e) {
      if (mounted) Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  // --- DIALOGS ---
  void _showPaymentConfirmationDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: Column(
            children: [
              const Icon(Icons.hourglass_top_rounded, size: 50, color: Colors.orange),
              const SizedBox(height: 10),
              const Text("Menunggu Pembayaran", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            ],
          ),
          content: const Text("Selesaikan pembayaran di browser Anda, lalu tekan tombol di bawah.", textAlign: TextAlign.center),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Nanti Saja", style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              onPressed: _checkPaymentStatus,
              style: ElevatedButton.styleFrom(backgroundColor: _primaryColor),
              child: const Text("Saya Sudah Bayar", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: const [
            Icon(Icons.check_circle_rounded, size: 80, color: Colors.green),
            SizedBox(height: 16),
            Text("Berhasil!", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
            SizedBox(height: 8),
            Text("Booking Anda telah dikonfirmasi.", textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
          ],
        ),
        actions: [
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context);
              },
              style: ElevatedButton.styleFrom(backgroundColor: _primaryColor, shape: const StadiumBorder()),
              child: const Text("Selesai", style: TextStyle(color: Colors.white)),
            ),
          )
        ],
      ),
    );
  }

  // --- WIDGET BUILDERS ---

  Widget _buildTenantForm() {
    return AnimatedCrossFade(
      firstChild: Container(), // Kalau Self, kosong
      secondChild: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 16),
          const Divider(),
          const SizedBox(height: 8),
          const Text("Informasi Penghuni", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          const SizedBox(height: 12),
          
          // Input Nama
          TextFormField(
            controller: _tenantNameController,
            decoration: InputDecoration(
              labelText: "Nama Lengkap Penghuni",
              hintText: "Contoh: Budi Santoso",
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            ),
          ),
          const SizedBox(height: 12),
          
          // Input HP
          TextFormField(
            controller: _tenantPhoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: "Nomor WhatsApp / HP",
              hintText: "Contoh: 08123456789",
              prefixIcon: const Icon(Icons.phone_android),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            ),
          ),
          const SizedBox(height: 16),

          // Pilihan Hubungan
          const Text("Hubungan dengan Anda:", style: TextStyle(fontSize: 12, color: Colors.grey)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _buildChoiceChip('family', 'Keluarga'),
              _buildChoiceChip('friend', 'Teman'),
              _buildChoiceChip('partner', 'Pasangan'),
            ],
          ),
        ],
      ),
      crossFadeState: _isBookingForSelf ? CrossFadeState.showFirst : CrossFadeState.showSecond,
      duration: const Duration(milliseconds: 300),
    );
  }

  Widget _buildChoiceChip(String value, String label) {
    bool isSelected = _selectedRelation == value;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        if (selected) setState(() => _selectedRelation = value);
      },
      selectedColor: _primaryColor.withOpacity(0.2),
      labelStyle: TextStyle(
        color: isSelected ? _primaryColor : Colors.black,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
      backgroundColor: Colors.white,
      side: BorderSide(color: isSelected ? _primaryColor : Colors.grey.shade300),
    );
  }

  // --- MAIN UI ---
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgGrey,
      appBar: AppBar(
        title: const Text("Konfirmasi Pesanan", style: TextStyle(color: Colors.black, fontWeight: FontWeight.w700)),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // --- SECTION 1: INFO KOST ---
            Container(
              color: Colors.white,
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  // Gambar
                  Container(
                    height: 180,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 5))
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: Image.network(
                        _imageUrlFor(widget.kost['thumbnail']),
                        fit: BoxFit.cover,
                        errorBuilder: (ctx, err, stack) => Container(
                          color: Colors.grey[200],
                          child: const Icon(Icons.image_not_supported, color: Colors.grey),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Nama & Harga
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(widget.kost['name'] ?? 'Nama Kost',
                                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 6),
                            Text(widget.kost['city'] ?? 'Lokasi',
                                style: const TextStyle(color: Colors.grey, fontSize: 14)),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: _primaryColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text("/bln", style: TextStyle(color: _primaryColor, fontWeight: FontWeight.bold, fontSize: 12)),
                      )
                    ],
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 12),

            // --- SECTION 2: FORM DETAIL SEWA ---
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              color: Colors.white,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Detail Booking", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 20),
                  
                  // PILIH TANGGAL
                  const Text("Mulai Kos", style: TextStyle(color: Colors.grey, fontSize: 13)),
                  const SizedBox(height: 8),
                  InkWell(
                    onTap: _selectDate,
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: _bgGrey,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.calendar_today_rounded, color: _primaryColor, size: 20),
                          const SizedBox(width: 12),
                          Text(DateFormat('dd MMMM yyyy').format(_startDate),
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                          const Spacer(),
                          const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Colors.grey),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // PILIH DURASI
                  const Text("Durasi Sewa", style: TextStyle(color: Colors.grey, fontSize: 13)),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
                    decoration: BoxDecoration(
                      color: _bgGrey,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        IconButton(
                          onPressed: () { if (_duration > 1) setState(() => _duration--); },
                          icon: const Icon(Icons.remove_circle_outline), color: Colors.grey,
                        ),
                        Text("$_duration Bulan", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                        IconButton(
                          onPressed: () => setState(() => _duration++),
                          icon: const Icon(Icons.add_circle), color: _primaryColor, iconSize: 32,
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // --- FITUR BARU: PILIH PENGHUNI ---
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      border: Border.all(color: _primaryColor.withOpacity(0.3)),
                      borderRadius: BorderRadius.circular(12),
                      color: _primaryColor.withOpacity(0.03),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text("Penghuni Kost", style: TextStyle(fontWeight: FontWeight.bold)),
                            Switch(
                              value: !_isBookingForSelf, // True jika untuk orang lain
                              activeColor: _primaryColor,
                              onChanged: (val) {
                                setState(() {
                                  _isBookingForSelf = !val; 
                                });
                              },
                            ),
                          ],
                        ),
                        Row(
                          children: [
                            Icon(_isBookingForSelf ? Icons.person : Icons.people, size: 16, color: Colors.grey),
                            const SizedBox(width: 8),
                            Text(
                              _isBookingForSelf ? "Untuk Saya Sendiri" : "Untuk Orang Lain",
                              style: TextStyle(color: _isBookingForSelf ? Colors.black : _primaryColor, fontWeight: FontWeight.w500),
                            ),
                          ],
                        ),
                        
                        // FORM TAMBAHAN (MUNCUL JIKA UNTUK ORANG LAIN)
                        _buildTenantForm(),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 100),
          ],
        ),
      ),
      
      // --- BOTTOM BAR ---
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -4))
          ],
        ),
        child: Row(
          children: [
            Expanded(
              flex: 4,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Total Pembayaran", style: TextStyle(color: Colors.grey, fontSize: 12)),
                  Text(
                    _formatPrice(_totalPrice),
                    style: TextStyle(color: _primaryColor, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
            Expanded(
              flex: 5,
              child: SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _createBooking,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _primaryColor,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isLoading 
                    ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) 
                    : const Text("Bayar Sekarang", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}