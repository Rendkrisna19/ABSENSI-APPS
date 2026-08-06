<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    // 1. Tampilkan Halaman Utama Chat
    public function index()
    {
        return view('admin.chat.index');
    }

    // 2. API Internal: Ambil Daftar Kontak (User yang pernah chat)
    public function getContacts()
    {
        $adminId = Auth::id();

        // Ambil semua ID user yang berinteraksi dengan admin
        $senderIds = Chat::where('receiver_id', $adminId)->pluck('sender_id');
        $receiverIds = Chat::where('sender_id', $adminId)->pluck('receiver_id');
        
        $contactIds = $senderIds->merge($receiverIds)->unique();

        $contacts = User::whereIn('id', $contactIds)->get()->map(function($user) use ($adminId) {
            // Ambil pesan terakhir
            $lastChat = Chat::where(function($q) use ($user, $adminId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $user->id);
            })->orWhere(function($q) use ($user, $adminId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $adminId);
            })->latest()->first();

            // Hitung unread (pesan dari user ke admin yang belum dibaca)
            $unreadCount = Chat::where('sender_id', $user->id)
                ->where('receiver_id', $adminId)
                ->where('is_read', false)
                ->count();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random', // Placeholder avatar
                'last_message' => $lastChat ? Str::limit($lastChat->message, 30) : '',
                'time' => $lastChat ? $lastChat->created_at->format('H:i') : '',
                'timestamp' => $lastChat ? $lastChat->created_at->timestamp : 0, // Untuk sorting
                'unread' => $unreadCount,
            ];
        });

        // Urutkan berdasarkan pesan terbaru
        return response()->json($contacts->sortByDesc('timestamp')->values());
    }

    // 3. API Internal: Ambil Isi Chat dengan User Tertentu
    public function getConversation($userId)
    {
        $adminId = Auth::id();

        // Tandai pesan sebagai terbaca (Read)
        Chat::where('sender_id', $userId)
            ->where('receiver_id', $adminId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Ambil chat
        $chats = Chat::where(function($q) use ($adminId, $userId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $userId);
            })
            ->orWhere(function($q) use ($adminId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $adminId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($chats);
    }

    // 4. API Internal: Kirim Balasan
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required',
            'message' => 'required'
        ]);

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json($chat);
    }
}