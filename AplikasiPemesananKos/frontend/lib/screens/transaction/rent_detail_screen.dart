import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/api_config.dart';

class RentDetailScreen extends StatefulWidget {
  final int transactionId;

  const RentDetailScreen({super.key, required this.transactionId});

  @override
  State<RentDetailScreen> createState() => _RentDetailScreenState();
}

class _RentDetailScreenState extends State<RentDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _transaction;
  
  // Data Tambahan untuk Jatuh Tempo
  bool _isDueSoon = false;
  bool _isOverdue = false;
  int _daysRemaining = 0;

  // Controller Refund
  final TextEditingController _bankNameCtrl = TextEditingController();
  final TextEditingController _accNumberCtrl = TextEditingController();
  final TextEditingController _accNameCtrl = TextEditingController();

  final Color _primaryColor = const Color(0xFF00B4D8);
  final Color _bgGrey = const Color(0xFFF5F7FA);

  @override
  void initState() {
    super.initState();
    _fetchTransactionDetail();
  }

  // 1. Ambil Detail Transaksi
  Future<void> _fetchTransactionDetail() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/transactions/${widget.transactionId}/check'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        final data = json['data'];
        
        // HITUNG MANUAL JATUH TEMPO DI FRONTEND (BACKUP LOGIC)
        // Agar realtime tanpa bergantung 100% pada API 'active-rent'
        DateTime start = DateTime.parse(data['start_date']);
        int duration = int.tryParse(data['duration'].toString()) ?? 1;
        DateTime end = DateTime(start.year, start.month + duration, start.day);
        
        final now = DateTime.now();
        final diff = end.difference(now).inDays;

        setState(() {
          _transaction = data;
          _daysRemaining = diff;
          // Logika: H-7 muncul warning, Lewat tanggal muncul Overdue
          _isDueSoon = diff <= 7 && diff >= 0; 
          _isOverdue = diff < 0;
        });
      }
    } catch (e) {
      debugPrint("Error fetching detail: $e");
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  // 2. Logic Refund (Stop Rent)
  Future<void> _submitStopRent() async {
    if (_bankNameCtrl.text.isEmpty || _accNumberCtrl.text.isEmpty || _accNameCtrl.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Semua data rekening wajib diisi!")));
      return;
    }
    Navigator.pop(context); 
    setState(() => _isLoading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/transactions/${widget.transactionId}/stop'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'bank_name': _bankNameCtrl.text,
          'account_number': _accNumberCtrl.text,
          'account_name': _accNameCtrl.text,
        }),
      );

      if (response.statusCode == 200) {
        _fetchTransactionDetail(); 
        _showSuccessMessage("Permintaan Refund Dikirim", "Admin akan memproses pengembalian dana.");
      } else {
        final data = jsonDecode(response.body);
        throw data['message'] ?? "Gagal memproses refund";
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e"), backgroundColor: Colors.red));
      setState(() => _isLoading = false);
    }
  }

  // 3. Logic Extend (Perpanjang Sewa)
  Future<void> _submitExtendRent() async {
    // Dialog Pilih Durasi Perpanjang
    int? selectedDuration = await showDialog<int>(
      context: context,
      builder: (ctx) {
        int duration = 1;
        return StatefulBuilder(
          builder: (context, setDialogState) => AlertDialog(
            title: const Text("Perpanjang Sewa"),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text("Lanjutkan sewa berapa bulan lagi?"),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconButton(onPressed: () => duration > 1 ? setDialogState(() => duration--) : null, icon: const Icon(Icons.remove_circle)),
                    Text("$duration Bulan", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                    IconButton(onPressed: () => setDialogState(() => duration++), icon: const Icon(Icons.add_circle, color: Color(0xFF00B4D8))),
                  ],
                )
              ],
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context), child: const Text("Batal")),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, duration),
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF00B4D8)),
                child: const Text("Bayar", style: TextStyle(color: Colors.white)),
              )
            ],
          ),
        );
      }
    );

    if (selectedDuration == null) return;

    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      // Panggil API Extend
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/transactions/${widget.transactionId}/extend'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: jsonEncode({'duration': selectedDuration}),
      );

      setState(() => _isLoading = false);

      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        final url = data['payment_url'];
        
        if (await canLaunchUrl(Uri.parse(url))) {
          await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
          // Opsional: Balik ke home atau refresh
          Navigator.pop(context); 
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Gagal membuat pesanan perpanjangan")));
      }
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  // --- UI DIALOGS ---
  void _showStopRentDialog() {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: const Text("Berhenti Sewa & Refund", style: TextStyle(fontWeight: FontWeight.bold)),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: Colors.orange.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                  child: Row(
                    children: const [
                      Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 20),
                      SizedBox(width: 8),
                      Expanded(child: Text("Sisa uang sewa dikembalikan dikurangi denda 10%.", style: TextStyle(fontSize: 12, color: Colors.orange))),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const Text("Rekening Penerima Refund:", style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 10),
                TextField(controller: _bankNameCtrl, decoration: const InputDecoration(labelText: "Nama Bank", border: OutlineInputBorder(), isDense: true)),
                const SizedBox(height: 10),
                TextField(controller: _accNumberCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: "Nomor Rekening", border: OutlineInputBorder(), isDense: true)),
                const SizedBox(height: 10),
                TextField(controller: _accNameCtrl, decoration: const InputDecoration(labelText: "Atas Nama", border: OutlineInputBorder(), isDense: true)),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("Batal")),
            ElevatedButton(
              onPressed: _submitStopRent,
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              child: const Text("Ajukan Berhenti", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _showSuccessMessage(String title, String message) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => Container(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.check_circle, color: Colors.green, size: 60),
            const SizedBox(height: 15),
            Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
            const SizedBox(height: 20),
            SizedBox(width: double.infinity, child: ElevatedButton(onPressed: () => Navigator.pop(context), child: const Text("Oke")))
          ],
        ),
      ),
    );
  }

  // --- HELPERS ---
  String _formatDate(String dateStr) {
    try {
      return DateFormat('dd MMMM yyyy').format(DateTime.parse(dateStr));
    } catch (e) { return dateStr; }
  }

  String _formatCurrency(int amount) {
    return NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(amount);
  }

  Widget _buildStatusChip(String status, String rentStatus) {
    Color color = Colors.grey;
    String text = status;

    if (rentStatus == 'STOPPED') { color = Colors.red; text = "BERHENTI"; }
    else if (rentStatus == 'ACTIVE') { color = Colors.green; text = "AKTIF"; }
    else if (rentStatus == 'UPCOMING') { color = Colors.blue; text = "AKAN DATANG"; }
    else if (rentStatus == 'COMPLETED') { color = Colors.grey; text = "SELESAI"; }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(20), border: Border.all(color: color)),
      child: Text(text, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12)),
    );
  }

  // --- MAIN BUILD ---
  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    if (_transaction == null) return const Scaffold(body: Center(child: Text("Data tidak ditemukan")));

    final trx = _transaction!;
    final kost = trx['kost'] ?? {};
    final rentStatus = trx['rent_status'] ?? 'UPCOMING';
    final isPaid = trx['status'] == 'PAID';

    return Scaffold(
      backgroundColor: _bgGrey,
      appBar: AppBar(
        title: const Text("Detail Sewa", style: TextStyle(color: Colors.black, fontSize: 16)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. ALERT JIKA MAU HABIS / SUDAH HABIS
            if (isPaid && (_isDueSoon || _isOverdue) && rentStatus != 'STOPPED')
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(16),
                width: double.infinity,
                decoration: BoxDecoration(
                  color: _isOverdue ? Colors.red.shade50 : Colors.orange.shade50,
                  border: Border.all(color: _isOverdue ? Colors.red : Colors.orange),
                  borderRadius: BorderRadius.circular(12)
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.access_time_filled, color: _isOverdue ? Colors.red : Colors.orange),
                        const SizedBox(width: 10),
                        Text(
                          _isOverdue ? "Masa Sewa Berakhir!" : "Masa Sewa Segera Habis",
                          style: TextStyle(fontWeight: FontWeight.bold, color: _isOverdue ? Colors.red : Colors.orange),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _isOverdue 
                        ? "Masa sewa Anda telah habis. Silakan perpanjang untuk melanjutkan atau check-out."
                        : "Sewa Anda tersisa $_daysRemaining hari lagi. Lakukan perpanjangan agar tidak terkena denda.",
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade800),
                    ),
                  ],
                ),
              ),

            // 2. STATUS CARD
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Status Sewa", style: TextStyle(color: Colors.grey, fontSize: 12)),
                      _buildStatusChip(trx['status'], rentStatus),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(trx['external_id'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const Divider(height: 24),
                  Row(
                    children: [
                      const Icon(Icons.person, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(trx['tenant_name'] ?? "User", style: const TextStyle(fontWeight: FontWeight.bold)),
                          Text(trx['tenant_type'] == 'self' ? "Penyewa Utama" : "Penghuni (${trx['tenant_type']})", style: const TextStyle(fontSize: 10, color: Colors.grey)),
                        ],
                      )
                    ],
                  )
                ],
              ),
            ),
            
            const SizedBox(height: 16),

            // 3. INFO KOST
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(kost['name'] ?? 'Nama Kost', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text(kost['address'] ?? '-', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                  const SizedBox(height: 12),
                  _buildInfoRow("Mulai Sewa", _formatDate(trx['start_date'])),
                  const SizedBox(height: 8),
                  _buildInfoRow("Durasi", "${trx['duration']} Bulan"),
                  const SizedBox(height: 8),
                  _buildInfoRow("Total Bayar", _formatCurrency(trx['total_price']), isBold: true),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // 4. INFO REFUND (JIKA STOPPED)
            if (rentStatus == 'STOPPED')
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(color: Colors.red.withOpacity(0.05), borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.red.withOpacity(0.2))),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("Informasi Pengembalian Dana", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.red)),
                    const SizedBox(height: 10),
                    _buildInfoRow("Tanggal Berhenti", _formatDate(trx['stopped_at'] ?? DateTime.now().toString())),
                    const SizedBox(height: 5),
                    _buildInfoRow("Nominal Refund", _formatCurrency(trx['refund_amount'] ?? 0)),
                    const Divider(),
                    const Text("Ditransfer ke:", style: TextStyle(fontSize: 12, color: Colors.grey)),
                    Text("${trx['refund_bank_name']} - ${trx['refund_account_number']}", style: const TextStyle(fontWeight: FontWeight.bold)),
                    Text("a.n ${trx['refund_account_name']}"),
                  ],
                ),
              ),
          ],
        ),
      ),
      
      // 5. BOTTOM BAR (LOGIKA TOMBOL DINAMIS)
      bottomNavigationBar: (isPaid && rentStatus != 'STOPPED' && rentStatus != 'COMPLETED')
          ? Container(
              padding: const EdgeInsets.all(20),
              color: Colors.white,
              child: Row(
                children: [
                  // TOMBOL BERHENTI (Kiri) - Hanya muncul jika belum overdue
                  if (!_isOverdue) 
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _showStopRentDialog,
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          side: const BorderSide(color: Colors.red),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))
                        ),
                        child: const Text("Berhenti Sewa", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  
                  if (!_isOverdue) const SizedBox(width: 12),

                  // TOMBOL PERPANJANG (Kanan) - Muncul jika H-7 atau Overdue
                  if (_isDueSoon || _isOverdue)
                    Expanded(
                      flex: 2, // Lebih lebar agar dominan
                      child: ElevatedButton(
                        onPressed: _submitExtendRent,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _isOverdue ? Colors.red : const Color(0xFFFB8500), // Merah jika telat, Oranye jika H-7
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))
                        ),
                        child: Text(
                          _isOverdue ? "Perpanjang Sekarang" : "Perpanjang Sewa", 
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)
                        ),
                      ),
                    )
                  else
                    // Jika masih lama, tombol STOP full width atau tombol stop di kanan
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _showStopRentDialog, // Tombol Stop Utama jika masih lama
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))
                        ),
                        child: const Text("Ajukan Berhenti Sewa", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      ),
                    ),
                ],
              ),
            )
          : null,
    );
  }

  Widget _buildInfoRow(String label, String value, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.black54)),
        Text(value, style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, color: isBold ? _primaryColor : Colors.black)),
      ],
    );
  }
}