import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../../config/api_config.dart';
import 'chat_room_screen.dart'; // Kita buat file ini di langkah ke-4

class ChatListScreen extends StatefulWidget {
  const ChatListScreen({super.key});

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> {
  bool _isLoading = true;
  List _chatList = [];
  final Color _primaryColor = const Color(0xFF00B4D8);

  @override
  void initState() {
    super.initState();
    _fetchChatList();
  }

  Future<void> _fetchChatList() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/chat-list'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        setState(() {
          _chatList = json['data'];
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      debugPrint("Error fetching chats: $e");
      setState(() => _isLoading = false);
    }
  }

  // Helper formatting waktu sederhana
  String _formatTime(String? timeStr) {
    if (timeStr == null) return '';
    DateTime dt = DateTime.parse(timeStr).toLocal();
    return "${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}";
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Pesan", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      backgroundColor: Colors.white,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _chatList.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.chat_bubble_outline, size: 80, color: Colors.grey[300]),
                      const SizedBox(height: 10),
                      const Text("Belum ada percakapan", style: TextStyle(color: Colors.grey)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _fetchChatList,
                  child: ListView.builder(
                    itemCount: _chatList.length,
                    itemBuilder: (context, index) {
                      final chat = _chatList[index];
                      // Menangani potensi null pada avatar
                      String avatarUrl = chat['avatar'] ?? ''; 
                      if (!avatarUrl.startsWith('http')) avatarUrl = ''; // Validasi sederhana

                      return Column(
                        children: [
                          ListTile(
                            contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                            leading: CircleAvatar(
                              radius: 28,
                              backgroundColor: Colors.grey[200],
                              backgroundImage: avatarUrl.isNotEmpty ? NetworkImage(avatarUrl) : null,
                              child: avatarUrl.isEmpty 
                                ? Text(chat['name'][0], style: TextStyle(color: _primaryColor, fontWeight: FontWeight.bold, fontSize: 20))
                                : null,
                            ),
                            title: Text(
                              chat['name'],
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                            subtitle: Text(
                              chat['last_message'] ?? '',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: (chat['unread_count'] > 0) ? Colors.black87 : Colors.grey,
                                fontWeight: (chat['unread_count'] > 0) ? FontWeight.bold : FontWeight.normal
                              ),
                            ),
                            trailing: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  _formatTime(chat['last_message_time']),
                                  style: TextStyle(fontSize: 12, color: (chat['unread_count'] > 0) ? _primaryColor : Colors.grey),
                                ),
                                const SizedBox(height: 6),
                                if (chat['unread_count'] > 0)
                                  Container(
                                    padding: const EdgeInsets.all(6),
                                    decoration: BoxDecoration(
                                      color: _primaryColor,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Text(
                                      chat['unread_count'].toString(),
                                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                              ],
                            ),
                            onTap: () {
                              // Navigasi ke Chat Room
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => ChatRoomScreen(
                                    opponentId: chat['user_id'],
                                    opponentName: chat['name'],
                                    opponentAvatar: chat['avatar'],
                                  ),
                                ),
                              ).then((_) => _fetchChatList()); // Refresh saat kembali
                            },
                          ),
                          const Divider(height: 1, indent: 80),
                        ],
                      );
                    },
                  ),
                ),
    );
  }
}