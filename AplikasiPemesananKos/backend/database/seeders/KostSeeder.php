<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kost;
use Illuminate\Support\Str;

class KostSeeder extends Seeder
{
    public function run(): void
    {
        $kosts = [
            [
                'name' => 'The Barokah Zuri Family Kost',
                'slug' => Str::slug('The Barokah Zuri Family Kost'),
                'city' => 'Kuningan, Jawa Barat',
                'address' => 'Jl. Kuningan No. 1',
                'price_per_month' => 1500000,
                'rating' => 4.7,
                'review_count' => 100,
                'available_rooms' => 3,
                'thumbnail' => 'barokah.jpg',
                'facilities' => json_encode(['TV', 'Lemari', 'Tempat Tidur', 'AC']),
                'property_rules' => "1. Hanya untuk penyewa\n2. Tidak boleh bawa lawan jenis\n3. Tidak boleh merokok",
                'location_detail' => "Jl. Kuningan No. 1, Jawa Barat",
            ],
            [
                'name' => 'Kost Putri Antares Dekat Uniku',
                'slug' => Str::slug('Kost Putri Antares Dekat Uniku'),
                'city' => 'Kuningan',
                'address' => 'Jl. Antares No. 3',
                'price_per_month' => 1823000,
                'rating' => 4.2,
                'review_count' => 95,
                'available_rooms' => 5,
                'thumbnail' => 'antares.jpg',
                'facilities' => json_encode(['Wifi', 'Kamar Mandi Dalam', 'Meja Belajar']),
                'property_rules' => "1. Khusus Putri\n2. Tidak boleh bawa tamu menginap",
                'location_detail' => "Dekat kampus Uniku",
            ],
            [
                'name' => 'Kost Kangen Dekat Uniku',
                'slug' => Str::slug('Kost Kangen Dekat Uniku'),
                'city' => 'Kuningan',
                'address' => 'Jl. Kangen No. 5',
                'price_per_month' => 1780000,
                'rating' => 4.5,
                'review_count' => 75,
                'available_rooms' => 4,
                'thumbnail' => 'kangen.jpg',
                'facilities' => json_encode(['AC', 'Wifi', 'Tempat Jemur']),
                'property_rules' => "1. Tidak boleh bawa hewan\n2. Tidak boleh membuat keributan",
                'location_detail' => "Dekat UNIKU, lokasi strategis",
            ],
        ];

        foreach ($kosts as $kost) {
            Kost::create($kost);
        }
    }
}
