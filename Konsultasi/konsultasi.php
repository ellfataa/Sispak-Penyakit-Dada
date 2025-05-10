<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
        header("Location: ../Auth/login.php");
        exit();
    }

    $gejala = [];
    $sql = "SELECT * FROM gejala ORDER BY kode_gejala ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $gejala[] = $row;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Konsultasi</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 text-gray-800 min-h-screen">

        <nav class="flex justify-between items-center px-6 py-4 bg-green-600 text-white shadow">
            <h1 class="text-xl font-bold">Sistem Pakar - Konsultasi</h1>
            <div class="flex gap-4">
                <a href="../Home/dashboard.php" class="hover:underline">Dashboard</a>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto mt-10 bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl font-semibold text-green-700 mb-4 text-center">Silakan Pilih Gejala yang Anda Alami</h2>

            <form action="proses_konsultasi.php" method="post" onsubmit="return validasiForm();">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border border-gray-200 text-sm text-left">
                        <thead class="bg-green-100 text-green-700 font-semibold">
                            <tr>
                                <th class="border px-3 py-2">No</th>
                                <th class="border px-3 py-2">Kode Gejala</th>
                                <th class="border px-3 py-2">Nama Gejala</th>
                                <th class="border px-3 py-2 text-center">Pilih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gejala as $index => $g) : ?>
                            <tr class="hover:bg-green-50">
                                <td class="border px-3 py-2"><?= $index + 1; ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($g['kode_gejala']); ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($g['nama_gejala']); ?></td>
                                <td class="border px-3 py-2 text-center">
                                    <input type="checkbox" name="gejala[]" value="<?= htmlspecialchars($g['kode_gejala']); ?>" class="h-4 w-4 text-green-600">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 text-center">
                    <button type="submit"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded">
                        Proses Diagnosa
                    </button>
                </div>
            </form>
        </div>

        <script>
        function validasiForm() {
            const checkboxes = document.querySelectorAll('input[name="gejala[]"]:checked');
            if (checkboxes.length === 0) {
                alert("Silakan pilih minimal satu gejala.");
                return false;
            }
            return true;
        }
        </script>

    </body>
</html>

<?php $conn->close(); ?>
