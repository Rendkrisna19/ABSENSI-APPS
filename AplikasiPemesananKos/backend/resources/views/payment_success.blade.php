<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-[Poppins] h-screen flex flex-col items-center justify-center p-6 text-center">

    <div class="bg-white p-8 rounded-3xl shadow-xl max-w-xs w-full transform scale-100 hover:scale-105 transition duration-300">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-2">Pembayaran Diterima!</h2>
        <p class="text-sm text-gray-500 mb-8">
            Silakan <b>tutup halaman ini</b> dan kembali ke Aplikasi untuk konfirmasi.
        </p>
        
        <button onclick="window.close()" class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold text-sm hover:bg-gray-900 transition">
            Tutup Browser
        </button>
        <p class="text-xs text-gray-400 mt-4">*Jika tombol tidak bekerja, tekan tombol Back atau Home di HP Anda.</p>
    </div>

</body>
</html>