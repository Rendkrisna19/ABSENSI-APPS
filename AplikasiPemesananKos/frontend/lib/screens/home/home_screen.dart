import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:intl/intl.dart'; 
import 'package:url_launcher/url_launcher.dart'; 
import '../../config/api_config.dart';
import '../auth/login_screen.dart';
import './detail_screen.dart';
import '../transaction/my_bookings_screen.dart'; 
import '../transaction/rent_detail_screen.dart';
import '../chat/chat_list_screen.dart'; 

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;

  // List Halaman
  final List<Widget> _pages = [
    const HomeContent(),        
    const MyBookingsScreen(),   
    const ChatListScreen(), // FITUR CHAT AKTIF
    const ProfileContent(),    
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: IndexedStack(
        index: _currentIndex,
        children: _pages,
      ),
      
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 20,
              offset: const Offset(0, -5),
            )
          ],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) => setState(() => _currentIndex = index),
          backgroundColor: Colors.white,
          type: BottomNavigationBarType.fixed,
          elevation: 0,
          showSelectedLabels: false,   // Modern style (No Text)
          showUnselectedLabels: false, 
          selectedItemColor: const Color(0xFF00B4D8), 
          unselectedItemColor: Colors.grey.shade400,
          iconSize: 26,
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.home_filled),
              activeIcon: Icon(Icons.home_filled),
              label: 'Home',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.assignment_outlined), 
              activeIcon: Icon(Icons.assignment),
              label: 'Riwayat',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.chat_bubble_outline_rounded),
              activeIcon: Icon(Icons.chat_bubble_rounded),
              label: 'Chat',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline_rounded),
              activeIcon: Icon(Icons.person_rounded),
              label: 'Akun',
            ),
          ],
        ),
      ),
    );
  }
}

// ==========================================
// 2. HOME CONTENT
// ==========================================
class HomeContent extends StatefulWidget {
  const HomeContent({super.key});

  @override
  State<HomeContent> createState() => _HomeContentState();
}

class _HomeContentState extends State<HomeContent> {
  List _kosts = [];
  bool _loading = true;
  String _userName = 'User';
  Map<String, dynamic>? _activeRent; 

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    
    if (mounted) setState(() => _userName = prefs.getString('name') ?? 'Kawan');

    try {
      // 1. Fetch Data Kost
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/kosts'),
        headers: {'Accept': 'application/json', if (token != null) 'Authorization': 'Bearer $token'},
      );

      // 2. Fetch Active Rent (Untuk Snippet di Home)
      final rentResponse = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/transactions/active-rent'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final list = body is Map && body['data'] != null ? body['data'] : body;
        
        // Cek Active Rent
        Map<String, dynamic>? activeData;
        if (rentResponse.statusCode == 200) {
           final rentBody = jsonDecode(rentResponse.body);
           if (rentBody['has_active_rent'] == true) {
             activeData = rentBody['data'];
           }
        }

        if (mounted) {
          setState(() {
            _kosts = List.from(list);
            _activeRent = activeData;
            _loading = false;
          });
        }
      }
    } catch (e) {
      debugPrint("Error: $e");
      if (mounted) setState(() => _loading = false);
    }
  }

  // --- HELPERS ---
  String _imageUrlFor(String? thumb) {
    if (thumb == null || thumb.isEmpty) return 'https://via.placeholder.com/300';
    if (thumb.startsWith('http')) return thumb;
    String base = ApiConfig.baseUrl.replaceAll('/api', '');
    if (base.endsWith('/')) base = base.substring(0, base.length - 1);
    String cleanThumb = thumb.replaceAll('kosts/', '');
    return '$base/storage/kosts/$cleanThumb';
  }

  String _formatPrice(dynamic price) {
    if (price == null) return '0';
    int val = int.tryParse(price.toString()) ?? 0;
    return NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _fetchData,
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                
                // --- HEADER ---
                Row(
                  children: [
                    Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.blue.shade50,
                        border: Border.all(color: Colors.blue.shade100, width: 2),
                      ),
                      child: Center(child: Text(_userName[0], style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.blue))),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text("Hi, $_userName", 
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            maxLines: 1, overflow: TextOverflow.ellipsis,
                          ),
                          const Text("Mau Cari Kost-kostan?", style: TextStyle(color: Colors.grey, fontSize: 12)),
                        ],
                      ),
                    ),
                    const Icon(Icons.favorite_border, size: 26),
                    const SizedBox(width: 16),
                    const Icon(Icons.notifications_outlined, size: 26),
                  ],
                ),

                const SizedBox(height: 24),

                // --- SEARCH BAR ---
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(30),
                    border: Border.all(color: Colors.grey.shade200),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 4))
                    ]
                  ),
                  child: const TextField(
                    decoration: InputDecoration(
                      hintText: "Cari kost anda",
                      hintStyle: TextStyle(color: Colors.grey, fontSize: 14),
                      prefixIcon: Icon(Icons.search, color: Colors.grey),
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    ),
                  ),
                ),

                const SizedBox(height: 24),

                // --- KATEGORI ---
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildCategoryItem(Icons.paid_outlined, "Termurah", const Color(0xFF00B4D8)),
                    _buildCategoryItem(Icons.calendar_today_rounded, "Tahunan", const Color(0xFF00B4D8)),
                    _buildCategoryItem(Icons.date_range_rounded, "Bulanan", const Color(0xFF00B4D8)),
                    _buildCategoryItem(Icons.cleaning_services_rounded, "Terbersih", const Color(0xFF00B4D8)),
                  ],
                ),

                const SizedBox(height: 24),

                // --- RIWAYAT PEMESANAN AKTIF (SNIPPET) ---
                if (_activeRent != null) ...[
                  const Text("Sewa Aktif Anda", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => RentDetailScreen(transactionId: _activeRent!['id']))),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.blue.shade100),
                        boxShadow: [BoxShadow(color: Colors.blue.withOpacity(0.05), blurRadius: 10)],
                      ),
                      child: Row(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(10),
                            child: Image.network(
                              _imageUrlFor(_activeRent!['kost']['thumbnail']),
                              width: 70, height: 70, fit: BoxFit.cover,
                              errorBuilder: (_,__,___) => Container(width: 70, height: 70, color: Colors.grey[200]),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(_activeRent!['kost']['name'] ?? 'Kost', 
                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                  maxLines: 1, overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(8)),
                                  child: Text(
                                    "Jatuh Tempo: ${_activeRent!['rent_details']['due_date_formatted'] ?? '-'}",
                                    style: TextStyle(fontSize: 10, color: Colors.blue.shade700, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey)
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],

                // --- REKOMENDASI TERBAIK ---
                const Text("Rekomendasi Terbaik", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 12),
                
                if (_loading) 
                  const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
                else if (_kosts.isEmpty)
                  const Center(child: Text("Belum ada data kost"))
                else
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _kosts.length,
                    itemBuilder: (context, index) {
                      final k = _kosts[index];
                      return Container(
                        margin: const EdgeInsets.only(bottom: 20),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
                        ),
                        child: InkWell(
                          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => DetailScreen(kost: k))),
                          borderRadius: BorderRadius.circular(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              ClipRRect(
                                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                                child: Image.network(
                                  _imageUrlFor(k['thumbnail']),
                                  height: 180, width: double.infinity, fit: BoxFit.cover,
                                  errorBuilder: (c, e, s) => Container(height: 180, color: Colors.grey[200]),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(k['name'] ?? 'Kost', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                    const SizedBox(height: 6),
                                    Row(
                                      children: [
                                        const Icon(Icons.location_on, size: 14, color: Color(0xFF00B4D8)),
                                        const SizedBox(width: 4),
                                        Text(k['city'] ?? '-', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                        const Spacer(),
                                        const Icon(Icons.star, size: 14, color: Colors.amber),
                                        const Text(" 4.8", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                      ],
                                    ),
                                    const SizedBox(height: 10),
                                    RichText(
                                      text: TextSpan(
                                        style: const TextStyle(color: Colors.black),
                                        children: [
                                          TextSpan(text: _formatPrice(k['price_per_month']), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                          const TextSpan(text: " / Bulan", style: TextStyle(color: Colors.grey, fontSize: 12)),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryItem(IconData icon, String label, Color color) {
    return Column(
      children: [
        Container(
          width: 60, height: 60,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [BoxShadow(color: color.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 4))],
          ),
          child: Icon(icon, color: Colors.white, size: 28),
        ),
        const SizedBox(height: 8),
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500)),
      ],
    );
  }
}

// ==========================================
// 3. PROFILE CONTENT (FULL FEATURE)
// ==========================================
class ProfileContent extends StatefulWidget {
  const ProfileContent({super.key});

  @override
  State<ProfileContent> createState() => _ProfileContentState();
}

class _ProfileContentState extends State<ProfileContent> {
  bool _isLoading = true;
  Map<String, dynamic>? _activeRent;
  Map<String, dynamic>? _rentDetails;
  Map<String, dynamic>? _userData;

  @override
  void initState() {
    super.initState();
    _fetchProfileData();
  }

  Future<void> _fetchProfileData() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      setState(() {
        _userData = {
          'name': prefs.getString('name') ?? 'User',
          'email': prefs.getString('email') ?? 'user@email.com',
        };
      });

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/transactions/active-rent'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['has_active_rent'] == true) {
          if (mounted) {
            setState(() {
              _activeRent = data['data'];
              _rentDetails = data['data']['rent_details'];
            });
          }
        }
      }
    } catch (e) {
      debugPrint("Error fetching profile: $e");
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _extendRent(int transactionId) async {
    int? selectedDuration = await showDialog<int>(
      context: context,
      builder: (context) {
        int duration = 1;
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text("Perpanjang Sewa"),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text("Berapa bulan ingin diperpanjang?"),
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
            );
          }
        );
      }
    );

    if (selectedDuration == null) return;

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    
    if(!mounted) return;
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/transactions/$transactionId/extend'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: jsonEncode({'duration': selectedDuration}),
      );

      if(!mounted) return;
      Navigator.pop(context); 

      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        final url = data['payment_url'];
        if (await canLaunchUrl(Uri.parse(url))) {
          await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Gagal membuat pesanan perpanjangan")));
      }
    } catch (e) {
      if(mounted) Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  Future<void> _logout() async {
    bool confirm = await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Konfirmasi Logout"),
        content: const Text("Yakin ingin keluar aplikasi?"),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Batal")),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text("Logout", style: TextStyle(color: Colors.red))),
        ],
      ),
    ) ?? false;

    if (confirm) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const LoginScreen()), (route) => false);
    }
  }

  @override
  Widget build(BuildContext context) {
    Color cardColorStart = const Color(0xFF00B4D8);
    Color cardColorEnd = const Color(0xFF0096C7);
    String statusLabel = "AKTIF";
    bool showPayButton = false;

    if (_rentDetails != null) {
      if (_rentDetails!['is_overdue'] == true) {
        cardColorStart = const Color(0xFFE63946); 
        cardColorEnd = const Color(0xFFD62828);
        statusLabel = "LEWAT JATUH TEMPO";
        showPayButton = true;
      } else if (_rentDetails!['is_due_soon'] == true) {
        cardColorStart = const Color(0xFFFB8500); 
        cardColorEnd = const Color(0xFFFFB703);
        statusLabel = "SEGERA HABIS";
        showPayButton = true;
      }
    }

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text("Profil Saya", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
      ),
      body: _isLoading 
        ? const Center(child: CircularProgressIndicator())
        : SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 40,
                  backgroundColor: const Color(0xFFE1F5FE),
                  child: Text(_userData?['name']?[0] ?? 'U', style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: Color(0xFF00B4D8))),
                ),
                const SizedBox(height: 12),
                Text(_userData?['name'] ?? 'User', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                Text(_userData?['email'] ?? '-', style: TextStyle(color: Colors.grey[600])),
                const SizedBox(height: 24),

                if (_activeRent != null)
                  InkWell(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => RentDetailScreen(transactionId: _activeRent!['id']))),
                    borderRadius: BorderRadius.circular(20),
                    child: Container(
                      width: double.infinity,
                      margin: const EdgeInsets.only(bottom: 25),
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(colors: [cardColorStart, cardColorEnd], begin: Alignment.topLeft, end: Alignment.bottomRight),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: cardColorStart.withOpacity(0.4), blurRadius: 10, offset: const Offset(0, 5))],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.home_filled, color: Colors.white, size: 16),
                              const SizedBox(width: 8),
                              const Text("TEMPAT TINGGAL SAAT INI", style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.bold)),
                              const Spacer(),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
                                child: Text(statusLabel, style: TextStyle(color: cardColorStart, fontSize: 10, fontWeight: FontWeight.bold)),
                              )
                            ],
                          ),
                          const SizedBox(height: 16),
                          Text(_activeRent!['kost']['name'] ?? 'Nama Kost', style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                          Text(_activeRent!['kost']['address'] ?? 'Alamat', style: const TextStyle(color: Colors.white70, fontSize: 13), maxLines: 1, overflow: TextOverflow.ellipsis),
                          const SizedBox(height: 20),
                          
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(color: Colors.black.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text("Jatuh Tempo", style: TextStyle(color: Colors.white70, fontSize: 10)),
                                    const SizedBox(height: 2),
                                    Text(
                                      _rentDetails?['due_date_formatted'] ?? '-',
                                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                                    ),
                                  ],
                                ),
                                if (_rentDetails != null)
                                  Text(
                                    "${_rentDetails!['days_remaining']} Hari Lagi",
                                    style: const TextStyle(color: Colors.white, fontSize: 12),
                                  ),
                              ],
                            ),
                          ),

                          if (showPayButton)
                            Padding(
                              padding: const EdgeInsets.only(top: 16.0),
                              child: SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: () => _extendRent(_activeRent!['id']),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    foregroundColor: cardColorStart,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))
                                  ),
                                  child: const Text("PERPANJANG SEKARANG", style: TextStyle(fontWeight: FontWeight.bold)),
                                ),
                              ),
                            )
                        ],
                      ),
                    ),
                  )
                else
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 25),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade200)),
                    child: Column(
                      children: [
                        Icon(Icons.house_siding_rounded, size: 40, color: Colors.grey.shade300),
                        const SizedBox(height: 10),
                        Text("Belum ada kos yang aktif", style: TextStyle(color: Colors.grey.shade500)),
                      ],
                    ),
                  ),

                _buildMenuTile(Icons.settings_outlined, "Pengaturan Akun", () {}),
                _buildMenuTile(Icons.help_outline, "Pusat Bantuan", () {}),
                _buildMenuTile(Icons.logout, "Keluar Aplikasi", () => _logout(), isRed: true),
              ],
            ),
          ),
    );
  }

  Widget _buildMenuTile(IconData icon, String title, VoidCallback onTap, {bool isRed = false}) {
    return ListTile(
      onTap: onTap,
      contentPadding: EdgeInsets.zero,
      leading: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: isRed ? Colors.red.shade50 : Colors.grey.shade100, borderRadius: BorderRadius.circular(10)),
        child: Icon(icon, color: isRed ? Colors.red : Colors.grey.shade700, size: 20),
      ),
      title: Text(title, style: TextStyle(fontWeight: FontWeight.w600, color: isRed ? Colors.red : Colors.black87)),
      trailing: const Icon(Icons.chevron_right, color: Colors.grey),
    );
  }
}