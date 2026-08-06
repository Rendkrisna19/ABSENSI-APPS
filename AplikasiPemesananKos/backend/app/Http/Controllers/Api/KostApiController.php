<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KostApiController extends Controller
{
    // GET /api/kosts
    public function index(Request $request): JsonResponse
    {
        $kosts = Kost::orderBy('created_at', 'desc')->get();

        $data = $kosts->map(function ($k) {
           
            $thumb = $k->thumbnail ? asset('storage/kosts/'.$k->thumbnail) : null;
            
            return [
                'id' => $k->id,
                'name' => $k->name,
                'slug' => $k->slug,
                'city' => $k->city,
                'address' => $k->address,
                'price_per_month' => $k->price_per_month,
                'rating' => $k->rating,
                'review_count' => $k->review_count,
                'available_rooms' => $k->available_rooms,
                'thumbnail' => $thumb, // URL sekarang sudah benar: .../storage/kosts/namafile.jpg
                'facilities' => $k->facilities, 
                'property_rules' => $k->property_rules,
                'location_detail' => $k->location_detail,
                'map_embed' => $k->map_embed,
                'created_at' => $k->created_at,
                'updated_at' => $k->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // GET /api/kosts/{id}
    public function show($id): JsonResponse
    {
        $k = Kost::findOrFail($id);
        
        // PERBAIKAN: Ganti 'kost' menjadi 'kosts' di sini juga
        $thumb = $k->thumbnail ? asset('storage/kosts/'.$k->thumbnail) : null;

        $kData = [
            'id' => $k->id,
            'name' => $k->name,
            'slug' => $k->slug,
            'city' => $k->city,
            'address' => $k->address,
            'price_per_month' => $k->price_per_month,
            'rating' => $k->rating,
            'review_count' => $k->review_count,
            'available_rooms' => $k->available_rooms,
            'thumbnail' => $thumb, // URL benar
            'facilities' => $k->facilities,
            'property_rules' => $k->property_rules,
            'location_detail' => $k->location_detail,
            'map_embed' => $k->map_embed,
            'created_at' => $k->created_at,
            'updated_at' => $k->updated_at,
        ];

        return response()->json(['success' => true, 'data' => $kData]);
    }

    //function chat
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id', // ID Admin atau User lain
            'message' => 'required|string',
        ]);

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // TODO: Di sini kamu bisa trigger Notifikasi FCM (Firebase) ke HP penerima
        
        return response()->json(['success' => true, 'data' => $chat]);
    }

    // Ambil Riwayat Chat dengan user tertentu
    public function getMessages(Request $request, $opponentId)
    {
        $myId = Auth::id();

        // Ambil chat dimana (sender = saya AND receiver = dia) OR (sender = dia AND receiver = saya)
        $chats = Chat::where(function($q) use ($myId, $opponentId) {
                $q->where('sender_id', $myId)->where('receiver_id', $opponentId);
            })
            ->orWhere(function($q) use ($myId, $opponentId) {
                $q->where('sender_id', $opponentId)->where('receiver_id', $myId);
            })
            ->orderBy('created_at', 'asc') // Urutkan dari yang terlama ke terbaru
            ->get();

        return response()->json(['success' => true, 'data' => $chats]);
    }
    
    // List orang yang pernah chat (Untuk halaman daftar chat)
    public function getChatList()
    {
        $myId = Auth::id();
        
        // Logic ini agak kompleks, intinya mengambil user ID lawan bicara unik
        // Bisa dioptimasi menggunakan raw query atau logic collection
        // Ini contoh sederhana mengambil pesan terakhir
        
        // ... Logic pengambilan list contact chat
        
        return response()->json(['success' => true, 'message' => 'Fitur list contact']);
    }

}