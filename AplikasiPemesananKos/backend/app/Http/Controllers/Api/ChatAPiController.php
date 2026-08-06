<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatApiController extends Controller
{
    /**
     * Kirim Pesan Baru
     * Endpoint: POST /api/chats/send
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id', // ID lawan bicara (Admin atau User lain)
            'message' => 'required|string',
        ]);

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Catatan: Untuk fitur "Realtime" yang instan di Flutter,
        // disarankan menambahkan trigger ke Firebase Cloud Messaging (FCM) atau Pusher di sini.
        
        return response()->json([
            'success' => true, 
            'message' => 'Pesan berhasil dikirim',
            'data' => $chat
        ]);
    }

    /**
     * Ambil Riwayat Chat (Detail Percakapan)
     * Endpoint: GET /api/chats/{opponentId}
     * Mengambil semua chat antara user yang login dengan user tertentu (opponent)
     */
    public function getMessages(Request $request, $opponentId)
    {
        $myId = Auth::id();

        // Logika: Ambil pesan dimana (Saya Pengirim && Dia Penerima) ATAU (Dia Pengirim && Saya Penerima)
        $chats = Chat::where(function($q) use ($myId, $opponentId) {
                $q->where('sender_id', $myId)->where('receiver_id', $opponentId);
            })
            ->orWhere(function($q) use ($myId, $opponentId) {
                $q->where('sender_id', $opponentId)->where('receiver_id', $myId);
            })
            ->orderBy('created_at', 'asc') // Urutkan dari terlama ke terbaru (bubble chat style)
            ->get();

        // Opsional: Tandai pesan dari lawan bicara sebagai sudah dibaca
        Chat::where('sender_id', $opponentId)
            ->where('receiver_id', $myId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true, 
            'data' => $chats
        ]);
    }
    
    /**
     * List Daftar Chat (Inbox)
     * Endpoint: GET /api/chat-list
     * Menampilkan daftar orang yang pernah chat dengan user login
     * beserta preview pesan terakhirnya (mirip tampilan awal WhatsApp).
     */
    public function getChatList()
    {
        $myId = Auth::id();
        
        // 1. Ambil semua ID user yang pernah berinteraksi (sebagai pengirim atau penerima)
        $senderIds = Chat::where('receiver_id', $myId)->pluck('sender_id');
        $receiverIds = Chat::where('sender_id', $myId)->pluck('receiver_id');
        
        // Gabungkan dan ambil ID unik (distinct)
        $contactIds = $senderIds->merge($receiverIds)->unique();

        // 2. Ambil detail User & Pesan Terakhir
        $contacts = User::whereIn('id', $contactIds)->get()->map(function($user) use ($myId) {
            
            // Query untuk mendapatkan pesan terakhir antara user login & kontak ini
            $lastChat = Chat::where(function($q) use ($user, $myId) {
                    $q->where('sender_id', $myId)->where('receiver_id', $user->id);
                })
                ->orWhere(function($q) use ($user, $myId) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $myId);
                })
                ->latest() // Order by created_at desc
                ->first();

            // Hitung jumlah pesan yang belum dibaca dari user ini (badge count)
            $unreadCount = Chat::where('sender_id', $user->id)
                ->where('receiver_id', $myId)
                ->where('is_read', false)
                ->count();

            // Pastikan field foto profil sesuai dengan database Anda
            // Jika tidak ada kolom photo_profile, gunakan placeholder
            $avatarUrl = $user->photo_profile 
                ? asset('storage/'.$user->photo_profile) 
                : 'https://ui-avatars.com/api/?name='.urlencode($user->name);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'avatar' => $avatarUrl,
                'last_message' => $lastChat ? $lastChat->message : '',
                'last_message_time' => $lastChat ? $lastChat->created_at : null,
                'unread_count' => $unreadCount,
            ];
        });

        // 3. Urutkan kontak berdasarkan waktu pesan terakhir (paling baru di atas)
        $sortedContacts = $contacts->sortByDesc('last_message_time')->values();

        return response()->json([
            'success' => true, 
            'data' => $sortedContacts
        ]);
    }
}