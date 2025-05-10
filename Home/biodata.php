<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user'])) {
        header("Location: ../Auth/login.php");
        exit();
    }

    $id_user = $_SESSION['id_user'];
    $sql = "SELECT nama, username, role FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Biodata Pengguna</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 min-h-screen flex flex-col">

        <!-- Navbar -->
        <nav class="flex justify-between items-center px-6 py-4 bg-green-600 text-white shadow">
            <h1 class="text-xl font-bold">Sistem Pakar Diagnosa Penyakit Dada</h1>
            <div class="flex items-center gap-4">
                <span>Halo, <?= htmlspecialchars($user['nama']); ?></span>
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <a href="../Auth/logout.php" class="hover:underline text-red-200">Logout</a>
            </div>
        </nav>

        <main class="flex-grow flex items-center justify-center py-10">
            <div class="bg-white w-full max-w-xl p-8 rounded-2xl shadow-lg">
                <h2 class="text-2xl font-bold text-green-700 mb-6 text-center">Biodata Pengguna</h2>
                <table class="w-full text-sm mb-6">
                    <tr class="border-b">
                        <th class="text-left py-2 w-1/3 text-gray-600">Nama</th>
                        <td class="py-2 font-medium text-gray-800"><?= htmlspecialchars($user['nama']); ?></td>
                    </tr>
                    <tr class="border-b">
                        <th class="text-left py-2 text-gray-600">Username</th>
                        <td class="py-2 font-medium text-gray-800"><?= htmlspecialchars($user['username']); ?></td>
                    </tr>
                    <tr>
                        <th class="text-left py-2 text-gray-600">Role</th>
                        <td class="py-2 font-medium text-gray-800"><?= htmlspecialchars($user['role']); ?></td>
                    </tr>
                </table>

                <div class="flex justify-between">
                    <a href="dashboard.php" class="px-5 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">
                        Kembali
                    </a>
                    <a href="edit_biodata.php" class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        Edit Profil
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
<?php $conn->close(); ?>
