<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\User;
use App\Models\Transaction; // Pastikan Model Transaction diimport
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // Import DB untuk query grafik
use Carbon\Carbon; // Import Carbon untuk tanggal

class KostController extends Controller
{
    public function dashboard(Request $request) {
        // 1. Statistik Dasar
        $totalKost = Kost::count();
        $totalUser = User::where('role', 'user')->count();
        $totalAdmin = User::where('role', 'admin')->count();

        // 2. Total Pendapatan (Semua waktu, status PAID)
        $totalRevenue = Transaction::where('status', 'PAID')->sum('total_price');

        // 3. Logika Filter Grafik
        $filter = $request->input('filter', 'month'); // Default tampilkan per Bulan
        $chartLabels = [];
        $chartData = [];

        $query = Transaction::where('status', 'PAID');

        if ($filter == 'year') {
            // Tampilkan data 5 tahun terakhir
            $data = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('SUM(total_price) as total')
                    )
                    ->groupBy('year')
                    ->orderBy('year', 'ASC')
                    ->get();
            
            foreach ($data as $row) {
                $chartLabels[] = $row->year;
                $chartData[] = $row->total;
            }

        } elseif ($filter == 'day') {
            // Tampilkan data harian di bulan ini
            $data = $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->select(
                        DB::raw('DAY(created_at) as day'),
                        DB::raw('SUM(total_price) as total')
                    )
                    ->groupBy('day')
                    ->orderBy('day', 'ASC')
                    ->get();

            // Mapping agar tanggal yang kosong tetap muncul sebagai 0
            $daysInMonth = Carbon::now()->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $chartLabels[] = $i; // Tanggal 1, 2, 3...
                $found = $data->firstWhere('day', $i);
                $chartData[] = $found ? $found->total : 0;
            }

        } else { 
            // DEFAULT: 'month' (Tampilkan Jan-Des tahun ini)
            $data = $query->whereYear('created_at', Carbon::now()->year)
                    ->select(
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('SUM(total_price) as total')
                    )
                    ->groupBy('month')
                    ->orderBy('month', 'ASC')
                    ->get();

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            foreach ($months as $index => $monthName) {
                $chartLabels[] = $monthName;
                $found = $data->firstWhere('month', $index + 1);
                $chartData[] = $found ? $found->total : 0;
            }
        }

        return view('admin.dashboard', compact(
            'totalKost', 
            'totalUser', 
            'totalAdmin', 
            'totalRevenue',
            'chartLabels',
            'chartData',
            'filter'
        ));
    }

    public function index()
    {
        $kosts = Kost::latest()->get();
        return view('admin.kost.index', compact('kosts'));
    }

    public function create()
    {
        return view('admin.kost.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'price_per_month' => 'required|numeric',
            'address' => 'required',
            'available_rooms' => 'required|numeric',
            'thumbnail' => 'image|mimes:jpeg,png,jpg|max:2048',
            'facilities' => 'array'
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);
        
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('kosts', $filename, 'public'); 
            $data['thumbnail'] = $filename;
        }

        Kost::create($data);

        return redirect()->route('admin.kost.index')->with('success', 'Kost berhasil ditambahkan');
    }

    public function edit($id)
    {
        $kost = Kost::findOrFail($id);
        return view('admin.kost.edit', compact('kost'));
    }

    public function update(Request $request, $id)
    {
        $kost = Kost::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'price_per_month' => 'required|numeric',
            'address' => 'required',
            'available_rooms' => 'required|numeric',
            'thumbnail' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('thumbnail')) {
            if ($kost->thumbnail && Storage::disk('public')->exists('kosts/' . $kost->thumbnail)) {
                Storage::disk('public')->delete('kosts/' . $kost->thumbnail);
            }
            
            $file = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('kosts', $filename, 'public');
            $data['thumbnail'] = $filename;
        }

        $kost->update($data);

        return redirect()->route('admin.kost.index')->with('success', 'Kost berhasil diupdate');
    }   

    public function destroy($id)
    {
        $kost = Kost::findOrFail($id);
        if ($kost->thumbnail) {
             Storage::delete('public/kosts/' . $kost->thumbnail);
        }
        $kost->delete();
        return redirect()->route('admin.kost.index')->with('success', 'Kost dihapus');
    }
}