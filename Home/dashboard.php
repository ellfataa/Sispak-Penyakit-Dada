<?php
    session_start();

    if (!isset($_SESSION['id_user'])) {
        header("Location: ../login.php");
        exit();
    }

    $nama = $_SESSION['nama'];
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard Sistem Pakar</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 min-h-screen flex flex-col">

        <!-- Navbar -->
        <nav class="flex justify-between items-center px-6 py-4 bg-green-600 text-white shadow">
            <h1 class="text-xl font-bold">Sistem Pakar Diagnosa Penyakit Dada</h1>
            <div class="flex items-center gap-4">
                <span>Halo, <?= htmlspecialchars($nama); ?></span>
                <a href="biodata.php" class="hover:underline">Biodata</a>
                <a href="../Auth/logout.php" class="hover:underline text-red-200">Logout</a>
            </div>
        </nav>

        <main class="flex-grow flex items-center justify-center py-10">
            <div class="bg-white w-full max-w-2xl p-8 rounded-2xl shadow-lg text-center">
                <h2 class="text-3xl font-bold text-green-700 mb-4">Dashboard</h2>
                <p class="text-gray-600 mb-8">Silakan mulai diagnosa penyakit atau lihat riwayat konsultasi Anda.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="../konsultasi/konsultasi.php"
                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow transition">
                    Mulai Diagnosa
                    </a>
                    <a href="../konsultasi/riwayat.php"
                    class="px-6 py-3 bg-green-400 hover:bg-green-500 text-white rounded-lg shadow transition">
                    Riwayat Konsultasi
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center text-sm text-gray-500 py-4">
            &copy; <?= date("Y"); ?> Sistem Pakar Diagnosa Penyakit Dada. All rights reserved.
        </footer>

    </body>
</html>
