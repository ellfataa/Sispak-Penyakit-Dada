<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../Auth/login.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $kode = trim($_POST['kode_gejala']);
        $nama = trim($_POST['nama_gejala']);

        $sql = "INSERT INTO gejala (kode_gejala, nama_gejala) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $kode, $nama);

        if ($stmt->execute()) {
            header("Location: tambah_gejala.php?success=1");
            exit();
        } else {
            echo "Gagal tambah gejala: " . $conn->error;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Tambah Gejala</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            window.onload = () => {
                const params = new URLSearchParams(window.location.search);
                if (params.get('success') === '1') {
                    alert('Gejala berhasil ditambahkan!');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            };
        </script>
    </head>
    <body class="bg-purple-50 min-h-screen">

        <!-- Form Tambah Gejala -->
        <div class="max-w-xl mx-auto mt-8 bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-bold mb-4 text-purple-800">Tambah Gejala</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Gejala:</label>
                    <input type="text" name="kode_gejala" required class="w-full border border-gray-300 px-3 py-2 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Gejala:</label>
                    <input type="text" name="nama_gejala" required class="w-full border border-gray-300 px-3 py-2 rounded">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
                    <a href="gejala.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Kembali</a>
                </div>
            </form>
        </div>

    </body>
</html>
