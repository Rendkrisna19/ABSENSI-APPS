import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';

import '../models/user.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  final User user;
  const HomeScreen({super.key, required this.user});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with TickerProviderStateMixin {
  String _currentTime = '';
  Timer? _timer;
  Map<String, dynamic>? _todayAttendance;
  bool _isLoading = true;

  // Status Absensi
  bool get _hasCheckedIn => _todayAttendance != null && _todayAttendance!['check_in_time'] != null;
  bool get _hasCheckedOut => _todayAttendance != null && _todayAttendance!['check_out_time'] != null;
  bool get _hasLeaveRequest => _todayAttendance != null && (_todayAttendance!['status'] == 'Izin' || _todayAttendance!['status'] == 'Sakit');

  // Animations
  late final AnimationController _pulseCtrl;
  late final Animation<double> _pulse;

  @override
  void initState() {
    super.initState();

    // Realtime clock
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _currentTime = DateFormat('HH:mm:ss').format(DateTime.now());
        });
      }
    });

    // Subtle pulse animation for header clock
    _pulseCtrl = AnimationController(vsync: this, duration: const Duration(seconds: 2))..repeat(reverse: true);
    _pulse = Tween<double>(begin: 0.0, end: 6.0).animate(CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut));

    // Fetch today attendance
    _fetchTodayAttendance();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pulseCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchTodayAttendance() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    final attendance = await ApiService.getTodayAttendance();
    if (mounted) {
      setState(() {
        _todayAttendance = attendance;
        _isLoading = false;
      });
    }
  }

  Future<Position> _getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('Layanan lokasi tidak aktif.');
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        throw Exception('Izin lokasi ditolak.');
      }
    }

    if (permission == LocationPermission.deniedForever) {
      throw Exception('Izin lokasi ditolak permanen, silakan aktifkan di pengaturan.');
    }

    return await Geolocator.getCurrentPosition();
  }

  void _handleApiResponse(Map<String, dynamic> result) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(result['message']),
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
      backgroundColor: result['success'] ? Colors.green : Colors.red,
    ));
    if (result['success']) {
      _fetchTodayAttendance(); // Refresh data
    }
  }

  void _onCheckIn() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    try {
      final position = await _getCurrentLocation();
      final result = await ApiService.checkIn(position.latitude, position.longitude);
      _handleApiResponse(result);
    } catch (e) {
      _handleApiResponse({'success': false, 'message': e.toString()});
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _onCheckOut() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    try {
      final position = await _getCurrentLocation();
      final result = await ApiService.checkOut(position.latitude, position.longitude);
      _handleApiResponse(result);
    } catch (e) {
      _handleApiResponse({'success': false, 'message': e.toString()});
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _onLeaveRequest() {
    final reasonController = TextEditingController();
    String? leaveType = 'Izin';

    showDialog(
      context: context,
      builder: (context) => Dialog(
        insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Container(
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: LinearGradient(colors: [Color(0xFF2563EB), Color(0xFF60A5FA)]),
                      ),
                      padding: const EdgeInsets.all(10),
                      child: const Icon(Icons.event_note, color: Colors.white),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text('Ajukan Izin/Sakit',
                        style: GoogleFonts.poppins(fontWeight: FontWeight.w700, fontSize: 18)),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: leaveType,
                  items: ['Izin', 'Sakit'].map((String value) {
                    return DropdownMenuItem<String>(value: value, child: Text(value));
                  }).toList(),
                  onChanged: (newValue) => leaveType = newValue,
                  decoration: InputDecoration(
                    labelText: 'Jenis Pengajuan',
                    filled: true,
                    fillColor: Colors.blue.withOpacity(0.05),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: reasonController,
                  decoration: InputDecoration(
                    labelText: 'Alasan',
                    alignLabelWithHint: true,
                    filled: true,
                    fillColor: Colors.blue.withOpacity(0.05),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(context),
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          side: BorderSide(color: Colors.blue.shade300),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: Text('Batal', style: GoogleFonts.poppins()),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () async {
                          if (reasonController.text.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Alasan tidak boleh kosong.')),
                            );
                            return;
                          }
                          Navigator.pop(context);
                          setState(() => _isLoading = true);
                          final result = await ApiService.submitLeave(leaveType!, reasonController.text);
                          _handleApiResponse(result);
                          setState(() => _isLoading = false);
                        },
                        style: ElevatedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          backgroundColor: const Color(0xFF2563EB),
                          foregroundColor: Colors.white,
                        ),
                        child: Text('Kirim', style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
                      ),
                    ),
                  ],
                )
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _logout() async {
    // Konfirmasi logout (desain baru)
    bool? confirmLogout = await showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(colors: [Color(0xFF3B82F6), Color(0xFF93C5FD)]),
                  ),
                  padding: const EdgeInsets.all(12),
                  child: const Icon(Icons.logout, color: Colors.white),
                ),
                const SizedBox(height: 12),
                Text('Konfirmasi Logout', style: GoogleFonts.poppins(fontWeight: FontWeight.w700, fontSize: 18)),
                const SizedBox(height: 8),
                Text('Anda yakin ingin keluar dari aplikasi?', style: GoogleFonts.poppins(color: Colors.black54)),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pop(false),
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          side: BorderSide(color: Colors.blue.shade300),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: Text('Batal', style: GoogleFonts.poppins()),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () => Navigator.of(context).pop(true),
                        style: ElevatedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          elevation: 0,
                          backgroundColor: const Color(0xFFEF4444),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: Text('Logout', style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
                      ),
                    ),
                  ],
                )
              ],
            ),
          ),
        );
      },
    );

    if (confirmLogout == true) {
      await ApiService.logout();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (context) => const LoginScreen()),
          (Route<dynamic> route) => false,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final scaffoldBackground = const Color(0xFFF7FAFF);

    return Scaffold(
      backgroundColor: scaffoldBackground,
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        title: Text('Absensi Karyawan', style: GoogleFonts.poppins(fontWeight: FontWeight.bold, color: const Color(0xFF0F172A))),
        actions: [
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout, color: Color(0xFF64748B)),
            tooltip: 'Logout',
          )
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchTodayAttendance,
        color: const Color(0xFF2563EB),
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          children: [
            _buildHeader(),
            const SizedBox(height: 20),
            _buildQuickStats(),
            const SizedBox(height: 16),
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 300),
              switchInCurve: Curves.easeOutBack,
              switchOutCurve: Curves.easeIn,
              child: _isLoading
                  ? const _LoadingCard(key: ValueKey('loading'))
                  : _buildActionButtons(key: const ValueKey('actions')),
            ),
            const SizedBox(height: 16),
            _buildAttendanceInfo(),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    // Blue gradient header with decorative circles & animated clock glow
    return Container(
      height: 180,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF2563EB), Color(0xFF60A5FA)],
        ),
        boxShadow: [
          BoxShadow(color: Colors.blue.shade200.withOpacity(0.5), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Stack(
        children: [
          // Decorative bubbles
          Positioned(
            right: -30,
            top: -30,
            child: _bubble(120, 0.08),
          ),
          Positioned(
            left: -20,
            bottom: -20,
            child: _bubble(180, 0.06),
          ),
          Positioned(
            right: 30,
            bottom: 20,
            child: _bubble(40, 0.10),
          ),

          // Content
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Avatar placeholder
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withOpacity(0.2),
                    border: Border.all(color: Colors.white.withOpacity(0.4)),
                  ),
                  child: const Icon(Icons.person, color: Colors.white, size: 30),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Halo, ${widget.user.name}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: GoogleFonts.poppins(fontSize: 20, fontWeight: FontWeight.w700, color: Colors.white)),
                      const SizedBox(height: 4),
                      Text(
                        DateFormat('EEEE, d MMMM yyyy', 'id_ID').format(DateTime.now()),
                        style: GoogleFonts.poppins(fontSize: 14, color: Colors.white.withOpacity(0.8)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                // Animated clock
                AnimatedBuilder(
                  animation: _pulse,
                  builder: (context, _) {
                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.15),
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.white.withOpacity(0.35),
                            blurRadius: 6 + _pulse.value,
                            spreadRadius: 0.5,
                          )
                        ],
                      ),
                      child: Text(
                        _currentTime,
                        style: GoogleFonts.spaceMono(
                          fontSize: 22,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                          letterSpacing: 1.2,
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bubble(double size, double opacity) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white.withOpacity(opacity),
      ),
    );
  }

  Widget _buildQuickStats() {
    // Small blue/white cards for quick hints
    final items = <_StatItem>[
      _StatItem(icon: Icons.place, label: 'Geo-Check', note: 'Aktif'),
      _StatItem(icon: Icons.verified, label: 'Status', note: _statusText()),
      _StatItem(icon: Icons.calendar_today, label: 'Hari Ini', note: DateFormat('dd/MM').format(DateTime.now())),
    ];

    return Row(
      children: items
          .map((e) => Expanded(
                child: _FrostCard(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(e.icon, color: const Color(0xFF2563EB)),
                      const SizedBox(height: 8),
                      Text(e.label, style: GoogleFonts.poppins(fontWeight: FontWeight.w600, fontSize: 13)),
                      const SizedBox(height: 2),
                      Text(e.note, style: GoogleFonts.poppins(color: Colors.black54, fontSize: 12)),
                    ],
                  ),
                ),
              ))
          .toList(),
    );
  }

  String _statusText() {
    if (_hasLeaveRequest) return _todayAttendance!['status'];
    if (_hasCheckedOut) return 'Selesai';
    if (_hasCheckedIn) return 'Sedang Bekerja';
    return 'Belum Check-In';
    }

  Widget _buildActionButtons({Key? key}) {
    if (_hasCheckedOut || _hasLeaveRequest) {
      return _FrostCard(
        key: key,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 8.0),
          child: Text(
            'Aktivitas absensi hari ini sudah selesai.',
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(fontSize: 15, color: Colors.grey.shade700, fontWeight: FontWeight.w500),
          ),
        ),
      );
    }

    return _FrostCard(
      key: key,
      child: Column(
        children: [
          if (!_hasCheckedIn) _primaryButton(
            label: 'CHECK IN',
            icon: Icons.login_rounded,
            onPressed: _onCheckIn,
          ),
          if (_hasCheckedIn && !_hasCheckedOut)
            _primaryButton(
              label: 'CHECK OUT',
              icon: Icons.logout_rounded,
              onPressed: _onCheckOut,
              isWarning: true,
            ),
          const SizedBox(height: 10),
          if (!_hasCheckedIn)
            _ghostButton(
              label: 'Tidak Masuk? Ajukan Izin/Sakit',
              icon: Icons.edit_document,
              onPressed: _onLeaveRequest,
            ),
        ],
      ),
    );
  }

  Widget _primaryButton({
    required String label,
    required IconData icon,
    required VoidCallback onPressed,
    bool isWarning = false,
  }) {
    final bg = isWarning ? const Color(0xFFF59E0B) : const Color(0xFF2563EB);
    return _TapScale(
      child: ElevatedButton.icon(
        icon: Icon(icon),
        label: Text(label, style: GoogleFonts.poppins(fontWeight: FontWeight.w700)),
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          elevation: 0,
          backgroundColor: bg,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(54),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
    );
  }

  Widget _ghostButton({
    required String label,
    required IconData icon,
    required VoidCallback onPressed,
  }) {
    return _TapScale(
      child: OutlinedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, color: const Color(0xFF2563EB)),
        label: Text(label, style: GoogleFonts.poppins(fontWeight: FontWeight.w600, color: const Color(0xFF2563EB))),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(50),
          side: const BorderSide(color: Color(0xFF93C5FD)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          backgroundColor: Colors.white,
        ),
      ),
    );
  }

  Widget _buildAttendanceInfo() {
    if (_todayAttendance == null) {
      return _FrostCard(
        child: ListTile(
          leading: _iconBadge(Icons.info_outline),
          title: Text('Belum ada data absensi hari ini.', style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
          subtitle: Text('Silakan lakukan Check-In atau ajukan izin/sakit.',
              style: GoogleFonts.poppins(color: Colors.black54)),
        ),
      );
    }

    return _FrostCard(
      child: Padding(
        padding: const EdgeInsets.all(2.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              leading: _iconBadge(Icons.timeline_rounded),
              title: Text('Ringkasan Hari Ini', style: GoogleFonts.poppins(fontSize: 18, fontWeight: FontWeight.w700)),
            ),
            const Divider(height: 20),
            _infoRow('Status', _todayAttendance!['status']),
            if (_hasCheckedIn)
              _infoRow('Jam Masuk', DateFormat('HH:mm:ss').format(DateTime.parse(_todayAttendance!['check_in_time']))),
            if (_hasCheckedOut)
              _infoRow('Jam Pulang', DateFormat('HH:mm:ss').format(DateTime.parse(_todayAttendance!['check_out_time']))),
            if (_hasLeaveRequest) _infoRow('Alasan', _todayAttendance!['reason'] ?? '-'),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(String title, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        children: [
          Expanded(child: Text(title, style: GoogleFonts.poppins(color: Colors.black54))),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(value, style: GoogleFonts.poppins(fontWeight: FontWeight.w600, color: const Color(0xFF1E40AF))),
          ),
        ],
      ),
    );
  }

  Widget _iconBadge(IconData icon) {
    return Container(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: const LinearGradient(colors: [Color(0xFF3B82F6), Color(0xFF93C5FD)]),
      ),
      padding: const EdgeInsets.all(10),
      child: Icon(icon, color: Colors.white),
    );
  }
}

/// Small frosted/white card with soft shadow & rounded corners
class _FrostCard extends StatelessWidget {
  final Widget child;
  const _FrostCard({Key? key, required this.child}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOutCubic,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: const [
          BoxShadow(color: Color(0x1A000000), blurRadius: 12, offset: Offset(0, 6)),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: child,
    );
  }
}


/// Tap scale interaction for buttons/cards (subtle animation)
class _TapScale extends StatefulWidget {
  final Widget child;
  const _TapScale({required this.child});

  @override
  State<_TapScale> createState() => _TapScaleState();
}

class _TapScaleState extends State<_TapScale> with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _anim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 120), lowerBound: 0.0, upperBound: 0.05);
    _anim = Tween<double>(begin: 1.0, end: 0.95).animate(CurvedAnimation(parent: _ctrl, curve: Curves.easeOut));
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => _ctrl.forward(),
      onTapCancel: () => _ctrl.reverse(),
      onTapUp: (_) => _ctrl.reverse(),
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => Transform.scale(scale: _anim.value, child: child),
        child: widget.child,
      ),
    );
  }
}

class _LoadingCard extends StatelessWidget {
  const _LoadingCard({super.key});

  @override
  Widget build(BuildContext context) {
    return const _FrostCard(
      child: Padding(
        padding: EdgeInsets.symmetric(vertical: 14.0),
        child: Center(child: CircularProgressIndicator(color: Color(0xFF2563EB))),
      ),
    );
  }
}

class _StatItem {
  final IconData icon;
  final String label;
  final String note;
  _StatItem({required this.icon, required this.label, required this.note});
}
