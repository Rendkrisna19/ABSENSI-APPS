<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkHour;
use Illuminate\Http\Request;

class WorkHourController extends Controller
{
    /**
     * Menampilkan halaman pengaturan jam kerja.
     */
    public function index()
    {
        // Ambil pengaturan pertama, atau buat jika tidak ada.
        // Ini memastikan selalu ada satu baris data untuk di-edit.
        $workHour = WorkHour::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Jam Kerja Standar',
                'check_in_time' => '08:00',
                'check_out_time' => '17:00'
            ]
        );

        return view('admin.work-hours.index', compact('workHour'));
    }

    /**
     * Mengupdate pengaturan jam kerja.
     */
    public function update(Request $request, WorkHour $workHour)
    {
        $request->validate([
            'check_in_time' => 'required',
            'check_out_time' => 'required',
        ]);

        $workHour->update([
            'check_in_time' => $request->check_in_time,
            'check_out_time' => $request->check_out_time,
        ]);

        return redirect()->route('work-hours.index')->with('success', 'Pengaturan jam kerja berhasil diperbarui.');
    }
}
