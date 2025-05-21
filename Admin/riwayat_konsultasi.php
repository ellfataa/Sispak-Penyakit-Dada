<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../Auth/login.php");
        exit();
    }

    // Ambil semua data penyakit
    $penyakit = [];
    $sqlPenyakit = "SELECT kode_penyakit, nama_penyakit FROM penyakit";
    $resultPenyakit = $conn->query($sqlPenyakit);
    while ($row = $resultPenyakit->fetch_assoc()) {
        $penyakit[$row['kode_penyakit']] = $row['nama_penyakit'];
    }

    // Ambil total penyakit untuk perhitungan probabilitas
    $totalPenyakit = count($penyakit);

    // Ambil semua data gejala
    $daftarGejala = [];
    $sqlGejala = "SELECT kode_gejala, nama_gejala FROM gejala";
    $resultGejala = $conn->query($sqlGejala);
    while ($row = $resultGejala->fetch_assoc()) {
        $daftarGejala[$row['kode_gejala']] = $row['nama_gejala'];
    }

    // Ambil total gejala untuk perhitungan
    $totalGejala = count($daftarGejala);

    // Ambil relasi gejala-penyakit untuk perhitungan nc
    $relasiPenyakitGejala = [];
    $sqlRelasi = "SELECT kode_penyakit, kode_gejala FROM penyakit_gejala";
    $resultRelasi = $conn->query($sqlRelasi);
    while ($row = $resultRelasi->fetch_assoc()) {
        if (!isset($relasiPenyakitGejala[$row['kode_penyakit']])) {
            $relasiPenyakitGejala[$row['kode_penyakit']] = [];
        }
        $relasiPenyakitGejala[$row['kode_penyakit']][] = $row['kode_gejala'];
    }

    // Fungsi untuk mendapatkan probabilitas masing-masing penyakit untuk perhitungan normalisasi
    function hitungProbabilitasPenyakit($gejalaDipilih, $relasiPenyakitGejala, $totalPenyakit, $totalGejala, $penyakit) {
        $hasilPerhitungan = [];
        
        // Hitung probabilitas untuk setiap penyakit
        foreach ($penyakit as $kodePenyakit => $namaPenyakit) {
            // Prior probability untuk setiap penyakit (1/jumlah_penyakit)
            $priorProbability = 1 / $totalPenyakit;
            
            // Inisialisasi dengan prior probability
            $posteriorProbability = $priorProbability;
            
            // Kalikan dengan likelihood untuk setiap gejala yang dipilih
            foreach ($gejalaDipilih as $gejala) {
                // Nilai nc (1 jika gejala ada pada penyakit, 0 jika tidak)
                $nc = in_array($gejala, $relasiPenyakitGejala[$kodePenyakit] ?? []) ? 1 : 0;
                
                // Parameter untuk perhitungan likelihood
                $n = 1; // Selalu 1 untuk gejala biner
                $m = $totalGejala;
                $p = $priorProbability;
                
                // Hitung P(ai|vj) dengan laplacian smoothing
                // Perbaikan rumus likelihood sesuai dengan proses_konsultasi.php
                $likelihood = (($nc + $m) * $p) / ($n + $m);
                
                // Kalikan posterior probability dengan likelihood
                $posteriorProbability *= $likelihood;
            }
            
            $hasilPerhitungan[$kodePenyakit] = $posteriorProbability;
        }
        
        return $hasilPerhitungan;
    }

    // Ambil riwayat konsultasi dari database beserta nama user
    $sql = "SELECT r.*, u.nama 
            FROM riwayat_konsultasi r
            JOIN user u ON r.id_user = u.id_user
            ORDER BY r.waktu_konsultasi DESC";
    $result = $conn->query($sql);

    $riwayat = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Dapatkan gejala yang dipilih
            $gejalaDipilih = json_decode($row['gejala_dipilih']);
            
            // Hitung probabilitas untuk semua penyakit
            $hasilPerhitungan = hitungProbabilitasPenyakit($gejalaDipilih, $relasiPenyakitGejala, $totalPenyakit, $totalGejala, $penyakit);
            
            // Hitung total probabilitas untuk normalisasi
            $totalProb = array_sum($hasilPerhitungan);
            
            // Normalisasi probabilitas seperti di proses_konsultasi.php
            // Pastikan kode penyakit tersedia sebelum mencoba mengaksesnya
            if (isset($row['kode_penyakit']) && isset($hasilPerhitungan[$row['kode_penyakit']])) {
                $probabilitasNormalisasi = ($totalProb > 0) ? ($hasilPerhitungan[$row['kode_penyakit']] / $totalProb) * 100 : 0;
            } else {
                // Gunakan nilai probabilitas dari database dan total yang baru dihitung sebagai fallback
                $probabilitasNormalisasi = ($totalProb > 0) ? ($row['probabilitas'] / $totalProb) * 100 : 0;
            }
            
            // Tambahkan probabilitas ternormalisasi ke data
            $row['probabilitas_normalized'] = $probabilitasNormalisasi;
            $riwayat[] = $row;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Riwayat Konsultasi Semua User - Admin</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 text-gray-800">

        <!-- Navbar -->
        <nav class="flex items-center justify-between bg-purple-300 text-purple-900 px-6 py-4 shadow-md">
            <h2 class="text-xl font-bold">Admin Panel - Sistem Pakar Diagnosa Penyakit Dada</h2>
            <div class="space-x-4">
                <a href="dashboard_admin.php" class="hover:underline font-medium">Dashboard</a>
                <a href="../Auth/logout.php" class="hover:underline font-medium">Logout</a>
            </div>
        </nav>

        <div class="max-w-6xl mx-auto px-4 py-6">
            <h1 class="text-2xl font-semibold mb-4">Riwayat Konsultasi Semua User</h1>

            <a href="export_word.php" target="_blank" class="inline-block mb-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded shadow transition">
                Export ke Word
            </a>

            <?php if (count($riwayat) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300 shadow-sm rounded">
                        <thead class="bg-purple-100 text-purple-800 text-left">
                            <tr>
                                <th class="p-3 border-b">No</th>
                                <th class="p-3 border-b">Nama User</th>
                                <th class="p-3 border-b">Hasil Diagnosa</th>
                                <th class="p-3 border-b">Probabilitas</th>
                                <th class="p-3 border-b">Gejala Dipilih</th>
                                <th class="p-3 border-b">Waktu Konsultasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $index => $r): ?>
                                <tr class="hover:bg-purple-50">
                                    <td class="p-3 border-b"><?= $index + 1; ?></td>
                                    <td class="p-3 border-b"><?= htmlspecialchars($r['nama']); ?></td>
                                    <td class="p-3 border-b"><?= htmlspecialchars($r['hasil_diagnosa']); ?></td>
                                    <td class="p-3 border-b"><?= round($r['probabilitas_normalized'], 2); ?>%</td>
                                    <td class="p-3 border-b">
                                        <?php
                                        $gejalaList = json_decode($r['gejala_dipilih']);
                                        
                                        // Tampilkan nama gejala jika tersedia, jika tidak tampilkan kode gejala
                                        $namaGejalaList = [];
                                        foreach ($gejalaList as $kodeGejala) {
                                            $namaGejalaList[] = isset($daftarGejala[$kodeGejala]) ? 
                                                $daftarGejala[$kodeGejala] : $kodeGejala;
                                        }
                                        
                                        echo htmlspecialchars(implode(', ', $namaGejalaList));
                                        ?>
                                    </td>
                                    <td class="p-3 border-b"><?= $r['waktu_konsultasi']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mt-4">Tidak ada riwayat konsultasi.</p>
            <?php endif; ?>

            <div class="mt-6">
                <a href="dashboard_admin.php" class="inline-block bg-purple-400 hover:bg-purple-500 text-white px-4 py-2 rounded shadow transition">Kembali ke Dashboard Admin</a>
            </div>
        </div>

    </body>
</html>

<?php $conn->close(); ?>