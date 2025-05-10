<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../Auth/login.php");
        exit();
    }

    $gejala = [];
    $resultGejala = $conn->query("SELECT * FROM gejala ORDER BY kode_gejala ASC");
    while ($row = $resultGejala->fetch_assoc()) {
        $gejala[] = $row;
    }

    $penyakit = [];
    $resultPenyakit = $conn->query("SELECT * FROM penyakit ORDER BY kode_penyakit ASC");
    while ($row = $resultPenyakit->fetch_assoc()) {
        $penyakit[] = $row;
    }

    $user = [];
    $resultUser = $conn->query("SELECT * FROM user ORDER BY id_user ASC");
    while ($row = $resultUser->fetch_assoc()) {
        $user[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard Admin - Sistem Pakar</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 text-gray-800">

        <!-- Navbar -->
        <nav class="flex items-center justify-between bg-purple-300 text-purple-900 px-6 py-4 shadow-md">
            <h2 class="text-xl font-bold">Admin Panel - Sistem Pakar Diagnosa Penyakit Dada</h2>
            <div class="space-x-4">
                <a href="riwayat_konsultasi.php" class="hover:underline font-medium">Riwayat Konsultasi</a>
                <a href="../Auth/logout.php" class="hover:underline font-medium">Logout</a>
            </div>
        </nav>

        <div class="max-w-6xl mx-auto px-4 py-6">
            <h1 class="text-2xl font-semibold mb-6">Dashboard Admin</h1>

            <?php
            function render_section($title, $data, $headers, $link, $columns) {
                echo '
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-purple-800">' . $title . '</h2>
                        <a href="' . $link . '" class="bg-purple-400 hover:bg-purple-500 text-white px-4 py-1 rounded shadow text-sm">Kelola</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300 shadow-sm rounded">
                            <thead class="bg-purple-100 text-purple-800">
                                <tr>';
                foreach ($headers as $head) {
                    echo '<th class="p-2 border-b text-left text-sm">' . $head . '</th>';
                }
                echo '</tr></thead><tbody>';
                foreach ($data as $i => $row) {
                    echo '<tr class="hover:bg-purple-50">';
                    echo '<td class="p-2 border-b text-sm">' . ($i + 1) . '</td>';
                    foreach ($columns as $col) {
                        echo '<td class="p-2 border-b text-sm">' . htmlspecialchars($row[$col]) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table></div></div>';
            }

            render_section('Data Gejala', $gejala, ['No', 'Kode Gejala', 'Nama Gejala'], 'gejala.php', ['kode_gejala', 'nama_gejala']);
            render_section('Data Penyakit', $penyakit, ['No', 'Kode Penyakit', 'Nama Penyakit'], 'penyakit.php', ['kode_penyakit', 'nama_penyakit']);
            render_section('Data User', $user, ['No', 'Nama', 'Username', 'Role'], 'user.php', ['nama', 'username', 'role']);
            ?>
        </div>

    </body>
</html>

<?php $conn->close(); ?>
