import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/api_config.dart';
import '../transaction/booking_screen.dart';
import '../chat/chat_room_screen.dart';

class DetailScreen extends StatelessWidget {
  final Map kost;

  const DetailScreen({super.key, required this.kost});
  String _imageUrlFor(String? thumb) {
    if (thumb == null || thumb.isEmpty) return '';
    if (thumb.toLowerCase().startsWith('http')) return thumb;

    String base = ApiConfig.baseUrl;
    if (base.endsWith('/api')) base = base.replaceAll('/api', '');
    if (base.endsWith('/')) base = base.substring(0, base.length - 1);

    String cleanThumb = thumb.replaceAll('kosts/', '').replaceAll('kost/', '');
    String url = '$base/storage/kosts/$cleanThumb';

    if (!kIsWeb) {
       // Khusus Emulator Android
       // if (url.contains('localhost')) url = url.replaceAll('localhost', '10.0.2.2');
       // if (url.contains('127.0.0.1')) url = url.replaceAll('127.0.0.1', '10.0.2.2');
    }
    try { url = Uri.parse(url).toString(); } catch (e) { url = url.replaceAll(' ', '%20'); }
    return url;
  }

  String _formatCurrency(dynamic price) {
    int val = int.tryParse(price.toString()) ?? 0;
    return NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val);
  }

  Future<void> _launchMaps() async {
    String query = Uri.encodeComponent("${kost['name']} ${kost['city']}");
    Uri googleUrl = Uri.parse("https://www.google.com/maps/search/?api=1&query=$query");
    if (kost['map_embed'] != null && kost['map_embed'].toString().isNotEmpty) {
       try { googleUrl = Uri.parse(kost['map_embed']); } catch (_) {}
    }
    if (await canLaunchUrl(googleUrl)) {
      await launchUrl(googleUrl, mode: LaunchMode.externalApplication);
    }
  }

  // --- FUNGSI NAVIGASI KE CHAT ---
  void _startChat(BuildContext context) {
    // Ambil ID pemilik kost dari data (pastikan backend kirim 'user_id')
    // Jika tidak ada, fallback ke ID 1 (Admin)
    int ownerId = int.tryParse(kost['user_id'].toString()) ?? 1;
    String ownerName = "Admin / Pemilik"; 

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatRoomScreen(
          opponentId: ownerId,
          opponentName: ownerName,
          // opponentAvatar: _imageUrlFor(kost['owner_photo']), // Jika ada foto pemilik
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final image = _imageUrlFor(kost['thumbnail']);
    final facilities = (kost['facilities'] is List) ? kost['facilities'] as List : [];
    final int rooms = int.tryParse(kost['available_rooms'].toString()) ?? 0;
    
    // Warna Utama Cyan sesuai gambar
    final Color primaryColor = const Color(0xFF00B4D8);

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          CustomScrollView(
            slivers: [
              // --- HEADER IMAGE SLIDER ---
              SliverAppBar(
                expandedHeight: 300.0,
                pinned: false, // Biarkan scroll
                backgroundColor: Colors.white,
                leading: Container(
                  margin: const EdgeInsets.all(8),
                  decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4)]),
                  child: IconButton(
                    icon: const Icon(Icons.arrow_back, color: Colors.black, size: 20),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
                actions: [
                  Container(
                    margin: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4)]),
                    child: IconButton(
                      icon: const Icon(Icons.share_outlined, color: Colors.black, size: 20),
                      onPressed: () {}, // Share logic here
                    ),
                  ),
                ],
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      image.isNotEmpty
                          ? Image.network(image, fit: BoxFit.cover)
                          : Container(color: Colors.grey.shade200, child: const Icon(Icons.image_not_supported)),
                      // Indicator Dots (Dummy Visual)
                      Positioned(
                        bottom: 16,
                        left: 0, right: 0,
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            _buildDot(true),
                            _buildDot(false),
                            _buildDot(false),
                            _buildDot(false),
                          ],
                        ),
                      )
                    ],
                  ),
                ),
              ),

              // --- CONTENT BODY ---
              SliverToBoxAdapter(
                child: Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.vertical(top: Radius.circular(24)), // Rounded Top effect
                  ),
                  transform: Matrix4.translationValues(0.0, -20.0, 0.0), // Overlap sedikit ke atas gambar
                  padding: const EdgeInsets.fromLTRB(20, 30, 20, 100), // Bottom padding untuk button
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      
                      // 1. JUDUL & HARGA
                      Text(
                        kost['name'] ?? 'Nama Kost',
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, height: 1.2),
                      ),
                      const SizedBox(height: 8),
                      
                      // Lokasi & Harga Row
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.location_on_outlined, size: 16, color: primaryColor),
                                    const SizedBox(width: 4),
                                    Expanded(child: Text("${kost['city']}, Indonesia", style: TextStyle(color: Colors.grey.shade600, fontSize: 13), maxLines: 1)),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Row(
                                  children: [
                                    const Icon(Icons.star, color: Colors.amber, size: 16),
                                    const SizedBox(width: 4),
                                    Text("${kost['rating'] ?? '4.8'}/5", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    Text(" (100 reviews)", style: TextStyle(color: Colors.grey.shade400, fontSize: 12)),
                                  ],
                                )
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                _formatCurrency(kost['price_per_month']),
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87),
                              ),
                              const Text("/Perbulan", style: TextStyle(fontSize: 12, color: Colors.grey)),
                            ],
                          )
                        ],
                      ),

                      const SizedBox(height: 16),

                      // Tersedia & Love Icon
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(4)),
                            child: Row(
                              children: [
                                const Icon(Icons.check_circle, color: Colors.green, size: 16),
                                const SizedBox(width: 6),
                                Text(rooms > 0 ? "$rooms Tersedia" : "Penuh", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.black87)),
                              ],
                            ),
                          ),
                          const Icon(Icons.favorite_border, color: Colors.grey),
                        ],
                      ),

                      const SizedBox(height: 24),

                      // 2. FASILITAS
                      _buildSectionHeader("Fasilitas", primaryColor),
                      const SizedBox(height: 16),
                      if (facilities.isEmpty)
                          const Text("- Tidak ada data fasilitas", style: TextStyle(color: Colors.grey))
                      else
                        Wrap(
                          spacing: 16,
                          runSpacing: 16,
                          children: facilities.take(4).map((f) => _buildFacilitySquare(f.toString())).toList(),
                        ),

                      const SizedBox(height: 24),

                      // 3. KEBIJAKAN PROPERTI
                      _buildSectionHeader("Kebijakan Properti", primaryColor),
                      const SizedBox(height: 12),
                      _buildPolicyItem("1", "Seluruh fasilitas kost, hanya diperuntukkan bagi Penyewa kost/penyewa kamar."),
                      _buildPolicyItem("2", "Penyewa kost dilarang menerima tamu lawan jenis di dalam kamar."),
                      _buildPolicyItem("3", "Dilarang merokok di dalam kamar maupun lingkungan kost."),

                      const SizedBox(height: 24),

                      // 4. DETAIL LOKASI (MAP PREVIEW)
                      _buildSectionHeader("Detail Lokasi", primaryColor),
                      const SizedBox(height: 8),
                      Text(kost['address'] ?? 'Alamat lengkap tidak tersedia', style: TextStyle(color: Colors.grey.shade600, fontSize: 13, height: 1.5)),
                      const SizedBox(height: 16),
                      
                      // Mockup Map Image (Clickable)
                      GestureDetector(
                        onTap: _launchMaps,
                        child: Container(
                          height: 120,
                          width: double.infinity,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            color: Colors.grey.shade200,
                            image: const DecorationImage(
                              // Placeholder Map Image mirip di gambar
                              image: NetworkImage("https://img.inews.co.id/media/822/files/inews_new/2022/03/02/Cara_Membuat_Lokasi_di_Google_Maps.jpg"), 
                              fit: BoxFit.cover,
                              opacity: 0.8
                            )
                          ),
                          child: Center(
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                              child: Icon(Icons.location_on, color: primaryColor, size: 24),
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 24),

                      // 5. INFORMASI JARAK
                      _buildSectionHeader("Informasi Jarak", primaryColor),
                      const SizedBox(height: 16),
                      _buildDistanceItem(Icons.train, "Stasiun Kereta", "Stasiun Kertapati", "2,3km"),
                      _buildDistanceItem(Icons.flight, "Bandara", "Bandara SMB II", "10,3km"),

                    ],
                  ),
                ),
              ),
            ],
          ),

          // --- BOTTOM BUTTON (Full Width) ---
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5))],
              ),
              child: Row(
                children: [
                  // --- TOMBOL CHAT (BARU) ---
                  InkWell(
                    onTap: () => _startChat(context),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        border: Border.all(color: primaryColor, width: 1.5),
                        borderRadius: BorderRadius.circular(12),
                        color: Colors.white,
                      ),
                      child: Icon(Icons.chat_bubble_outline_rounded, color: primaryColor),
                    ),
                  ),
                  
                  const SizedBox(width: 12),

                  // --- TOMBOL SEWA ---
                  Expanded(
                    child: SizedBox(
                      height: 50,
                      child: ElevatedButton(
                        onPressed: rooms > 0 ? () {
                           Navigator.push(context, MaterialPageRoute(builder: (_) => BookingScreen(kost: kost)));
                        } : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)), // Rounded pill shape
                          elevation: 0,
                        ),
                        child: const Text("Ajukan Sewa", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          )
        ],
      ),
    );
  }

  // --- WIDGET BUILDERS ---

  Widget _buildDot(bool isActive) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 4),
      width: isActive ? 10 : 8,
      height: isActive ? 10 : 8,
      decoration: BoxDecoration(
        color: isActive ? Colors.white : Colors.white54,
        shape: BoxShape.circle,
      ),
    );
  }

  Widget _buildSectionHeader(String title, Color color) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        Text("Lihat semua", style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
      ],
    );
  }

  // Kotak Fasilitas Biru Muda
  Widget _buildFacilitySquare(String name) {
    IconData icon = Icons.check_circle_outline;
    String label = name;
    
    // Mapping Icon Manual biar mirip gambar
    String lower = name.toLowerCase();
    if (lower.contains('tv')) { icon = Icons.tv; label = "TV"; }
    else if (lower.contains('lemari')) { icon = Icons.kitchen; label = "Lemari"; } // Kitchen icon mirip lemari
    else if (lower.contains('tidur') || lower.contains('bed')) { icon = Icons.bed; label = "Kasur"; }
    else if (lower.contains('ac')) { icon = Icons.ac_unit; label = "AC"; }
    else if (lower.contains('wifi')) { icon = Icons.wifi; label = "Wifi"; }

    return Column(
      children: [
        Container(
          width: 60, height: 60,
          decoration: BoxDecoration(
            color: const Color(0xFFE1F5FE), // Light Blue 50 equivalent
            borderRadius: BorderRadius.circular(16),
          ),
          child: Icon(icon, color: Colors.black87, size: 28),
        ),
        const SizedBox(height: 8),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      ],
    );
  }

  Widget _buildPolicyItem(String number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text("$number. ", style: TextStyle(color: Colors.grey.shade500, height: 1.5)),
          Expanded(
            child: Text(text, style: TextStyle(color: Colors.grey.shade500, fontSize: 13, height: 1.5)),
          ),
        ],
      ),
    );
  }

  Widget _buildDistanceItem(IconData icon, String title, String subTitle, String dist) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Row(
        children: [
          Icon(icon, size: 24, color: Colors.black87),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                Text("$subTitle : ", style: const TextStyle(color: Colors.grey, fontSize: 12)),
              ],
            ),
          ),
          Text(dist, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
        ],
      ),
    );
  }
}