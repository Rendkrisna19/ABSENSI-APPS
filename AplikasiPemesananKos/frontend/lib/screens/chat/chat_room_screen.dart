import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../../config/api_config.dart';

class ChatRoomScreen extends StatefulWidget {
  final int opponentId;
  final String opponentName;
  final String? opponentAvatar;

  const ChatRoomScreen({
    super.key,
    required this.opponentId,
    required this.opponentName,
    this.opponentAvatar,
  });

  @override
  State<ChatRoomScreen> createState() => _ChatRoomScreenState();
}

class _ChatRoomScreenState extends State<ChatRoomScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  // Warna Utama (Cyan/Tosca)
  final Color _primaryColor = const Color(0xFF00B4D8);

  List _messages = [];
  bool _isLoading = true;
  int _myId = 0; // ID User yang sedang login
  Timer? _timer; // Untuk auto refresh

  @override
  void initState() {
    super.initState();
    _initChat();
  }

  // Gabungkan inisialisasi ID dan fetch pesan agar urut
  Future<void> _initChat() async {
    await _getMyId();
    await _fetchMessages();

    // Auto refresh chat setiap 3 detik
    _timer = Timer.periodic(const Duration(seconds: 3), (timer) {
      _fetchMessages(isBackground: true);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  // 1. Ambil ID sendiri dari SharedPref
  Future<void> _getMyId() async {
    final prefs = await SharedPreferences.getInstance();

    // Asumsi: Saat login Anda menyimpan seluruh object user ke 'user_data'
    // Jika Anda hanya menyimpan token, Anda harus decode JWT atau hit API profile dulu.
    // Kode di bawah ini mencoba membaca ID dari preference yang umum dipakai.

    // Cek integer langsung (kadang disimpan sbg int)
    int? idInt = prefs.getInt('user_id');
    if (idInt != null) {
      setState(() => _myId = idInt);
      return;
    }

    // Cek JSON string
    String? userStr = prefs.getString('user');
    if (userStr != null) {
      try {
        final user = jsonDecode(userStr);
        setState(() => _myId = user['id']);
      } catch (_) {}
    }
  }

  // 2. Ambil Pesan dari API
  Future<void> _fetchMessages({bool isBackground = false}) async {
    if (!isBackground) {
      // setState(() => _isLoading = true); // Jangan loading full screen biar ga kedip
    }

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/chats/${widget.opponentId}'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);

        // Cek apakah ada pesan baru dibandingkan data lokal
        // Jika beda panjang array, berarti ada pesan baru -> update & scroll
        if (mounted) {
          final newMessages = json['data'] as List;
          bool shouldScroll = newMessages.length > _messages.length;

          setState(() {
            _messages = newMessages;
            _isLoading = false;
          });

          if (shouldScroll && !isBackground) {
            _scrollToBottom();
          } else if (shouldScroll && isBackground) {
            // Opsional: scroll kalau user ada di paling bawah
            _scrollToBottom();
          }
        }
      }
    } catch (e) {
      debugPrint("Error fetching messages: $e");
    }
  }

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  // 3. Kirim Pesan
  Future<void> _sendMessage() async {
    String text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear(); // Kosongkan input biar responsif

    // OPTIMISTIC UPDATE (Tampilkan pesan sementara sebelum sukses ke server)
    // Ini bikin UX terasa sangat cepat
    final tempMsg = {
      'id': -1, // ID dummy
      'sender_id': _myId,
      'receiver_id': widget.opponentId,
      'message': text,
      'created_at': DateTime.now().toIso8601String(),
      'is_read': false,
    };

    setState(() {
      _messages.add(tempMsg);
    });
    _scrollToBottom();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      await http.post(
        Uri.parse('${ApiConfig.baseUrl}/chats/send'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'receiver_id': widget.opponentId, 'message': text}),
      );

      // Refresh data asli dari server untuk dapat ID & Timestamp yang benar
      _fetchMessages(isBackground: true);
    } catch (e) {
      // Jika gagal, bisa hapus pesan dummy atau kasih tanda silang (opsional)
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Gagal mengirim pesan, periksa koneksi.")),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F7), // Abu-abu background chat
      // HEADER
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 1,
        shadowColor: Colors.black12,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
        titleSpacing: 0,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: Colors.grey.shade200,
              backgroundImage:
                  (widget.opponentAvatar != null &&
                      widget.opponentAvatar!.startsWith('http'))
                  ? NetworkImage(widget.opponentAvatar!)
                  : null,
              child:
                  (widget.opponentAvatar == null ||
                      !widget.opponentAvatar!.startsWith('http'))
                  ? Text(
                      widget.opponentName[0].toUpperCase(),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.black54,
                      ),
                    )
                  : null,
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.opponentName,
                  style: const TextStyle(
                    color: Colors.black87,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                // Text("Online", style: TextStyle(color: Colors.green, fontSize: 11)), // Dummy status
              ],
            ),
          ],
        ),
      ),

      body: Column(
        children: [
          // LIST PESAN
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 20,
                    ),
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final msg = _messages[index];

                      // --- TAMBAHKAN PRINT INI UNTUK CEK DI CONSOLE ---
                      final senderId = msg['sender_id'];
                      print(
                        "CEK DEBUG -> Pesan: ${msg['message']} | Sender ID dari API: $senderId | ID Saya (_myId): $_myId",
                      );
                      // ------------------------------------------------

                      // Pastikan konversi ke int agar aman
                      final int senderIdInt =
                          int.tryParse(senderId.toString()) ?? 0;
                      final bool isMe = senderIdInt == _myId;

                      return _buildChatBubble(
                        msg['message'],
                        isMe,
                        msg['created_at'],
                      );
                    },
                  ),
          ),

          // INPUT AREA (STICKY BOTTOM)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  offset: const Offset(0, -2),
                  blurRadius: 10,
                ),
              ],
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0F2F5),
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: TextField(
                      controller: _messageController,
                      textCapitalization: TextCapitalization.sentences,
                      decoration: const InputDecoration(
                        hintText: "Tulis pesan...",
                        hintStyle: TextStyle(
                          color: Colors.black38,
                          fontSize: 14,
                        ),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(
                          horizontal: 20,
                          vertical: 12,
                        ),
                        isDense: true,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                InkWell(
                  onTap: _sendMessage,
                  borderRadius: BorderRadius.circular(50),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: _primaryColor,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: _primaryColor.withOpacity(0.4),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.send_rounded,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Widget Bubble Chat
  Widget _buildChatBubble(String message, bool isMe, String? timestamp) {
    // Parsing waktu sederhana (ambil Jam:Menit)
    String timeStr = "";
    if (timestamp != null) {
      try {
        DateTime dt = DateTime.parse(timestamp).toLocal();
        timeStr =
            "${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}";
      } catch (_) {}
    }

    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: isMe ? _primaryColor : Colors.white,
          // Bentuk Bubble unik (Tumpul di sisi lawan bicara)
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: isMe ? const Radius.circular(16) : Radius.zero,
            bottomRight: isMe ? Radius.zero : const Radius.circular(16),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 2,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment:
              CrossAxisAlignment.end, // Agar waktu selalu di kanan bawah bubble
          children: [
            Text(
              message,
              style: TextStyle(
                color: isMe ? Colors.white : const Color(0xFF1F2937),
                fontSize: 15,
                height: 1.3,
              ),
            ),
            const SizedBox(height: 4),
            // Waktu & Centang (Opsional)
            if (timeStr.isNotEmpty)
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    timeStr,
                    style: TextStyle(
                      color: isMe
                          ? Colors.white.withOpacity(0.7)
                          : Colors.black38,
                      fontSize: 10,
                    ),
                  ),
                  if (isMe) ...[
                    const SizedBox(width: 4),
                    Icon(
                      Icons.done_all,
                      size: 12,
                      color: Colors.white.withOpacity(0.7),
                    ),
                  ],
                ],
              ),
          ],
        ),
      ),
    );
  }
}
