<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user'])) {
        header("Location: ../Auth/login.php");
        exit();
    }

    $idUser = $_SESSION['id_user'];

    // Ambil riwayat konsultasi dari database dengan JOIN ke tabel penyakit
    $sql = "SELECT r.*, p.kode_penyakit, p.nama_penyakit, p.solusi 
            FROM riwayat_konsultasi r
            LEFT JOIN penyakit p ON r.kode_penyakit = p.kode_penyakit
            WHERE r.id_user = ? 
            ORDER BY r.waktu_konsultasi DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();

    // Ambil semua data gejala
    $daftarGejala = [];
    $sqlGejala = "SELECT kode_gejala, nama_gejala FROM gejala";
    $resultGejala = $conn->query($sqlGejala);
    while ($row = $resultGejala->fetch_assoc()) {
        $daftarGejala[$row['kode_gejala']] = $row['nama_gejala'];
    }

    // Ambil informasi semua penyakit
    $penyakit = [];
    $solusiPenyakit = []; // Array untuk menyimpan solusi penyakit
    $sqlPenyakit = "SELECT kode_penyakit, nama_penyakit, solusi FROM penyakit";
    $resultPenyakit = $conn->query($sqlPenyakit);
    while ($row = $resultPenyakit->fetch_assoc()) {
        $penyakit[$row['kode_penyakit']] = $row['nama_penyakit'];
        $solusiPenyakit[$row['kode_penyakit']] = $row['solusi']; // Simpan solusi berdasarkan kode penyakit
    }

    // Ambil relasi gejala-penyakit untuk perhitungan probabilitas
    $relasiPenyakitGejala = [];
    $sqlRelasi = "SELECT kode_penyakit, kode_gejala FROM penyakit_gejala";
    $resultRelasi = $conn->query($sqlRelasi);
    while ($row = $resultRelasi->fetch_assoc()) {
        if (!isset($relasiPenyakitGejala[$row['kode_penyakit']])) {
            $relasiPenyakitGejala[$row['kode_penyakit']] = [];
        }
        $relasiPenyakitGejala[$row['kode_penyakit']][] = $row['kode_gejala'];
    }

    // Ambil total penyakit dan gejala untuk perhitungan probabilitas
    $totalPenyakit = count($penyakit);
    $totalGejala = count($daftarGejala);

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
                $likelihood = ($nc + $m * $p) / ($n + $m);
                
                // Kalikan posterior probability dengan likelihood
                $posteriorProbability *= $likelihood;
            }
            
            $hasilPerhitungan[$kodePenyakit] = $posteriorProbability;
        }
        
        return $hasilPerhitungan;
    }

    $riwayat = [];
    while ($row = $result->fetch_assoc()) {
        // Dapatkan gejala yang dipilih
        $gejalaDipilih = json_decode($row['gejala_dipilih']);
        
        // Hitung probabilitas untuk semua penyakit
        $hasilPerhitungan = hitungProbabilitasPenyakit($gejalaDipilih, $relasiPenyakitGejala, $totalPenyakit, $totalGejala, $penyakit);
        
        // Hitung total probabilitas untuk normalisasi
        $totalProb = array_sum($hasilPerhitungan);
        
        // Normalisasi probabilitas seperti di proses_konsultasi.php
        $probabilitasNormalisasi = ($totalProb > 0) ? ($row['probabilitas'] / $totalProb) * 100 : 0;
        
        // Tambahkan ke array riwayat
        $row['probabilitas_normalized'] = $probabilitasNormalisasi;
        $riwayat[] = $row;
    }

    $stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Riwayat Konsultasi</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 text-gray-800 min-h-screen">

        <div class="max-w-6xl mx-auto mt-10 bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl font-semibold text-green-700 mb-6 text-center">Riwayat Konsultasi Anda</h2>

            <?php if (count($riwayat) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left border border-gray-300">
                        <thead class="bg-green-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 border">No</th>
                                <th class="px-4 py-2 border">Kode Penyakit</th>
                                <th class="px-4 py-2 border">Hasil Diagnosa</th>
                                <th class="px-4 py-2 border">Probabilitas</th>
                                <th class="px-4 py-2 border">Solusi</th>
                                <th class="px-4 py-2 border">Gejala Dipilih</th>
                                <th class="px-4 py-2 border">Waktu Konsultasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $index => $r): ?>
                                <tr class="hover:bg-green-50">
                                    <td class="px-4 py-2 border"><?= $index + 1; ?></td>
                                    <td class="px-4 py-2 border"><?= htmlspecialchars($r['kode_penyakit'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2 border"><?= htmlspecialchars($r['nama_penyakit'] ?? $r['hasil_diagnosa']); ?></td>
                                    <td class="px-4 py-2 border"><?= round($r['probabilitas_normalized'], 2); ?>%</td>
                                    <td class="px-4 py-2 border"><?= htmlspecialchars($r['solusi'] ?? 'Solusi tidak tersedia'); ?></td>
                                    <td class="px-4 py-2 border">
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
                                    <td class="px-4 py-2 border"><?= $r['waktu_konsultasi']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-600">Belum ada riwayat konsultasi.</p>
            <?php endif; ?>

            <div class="text-center mt-6">
                <a href="../Home/dashboard.php" class="inline-block px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

    </body>
</html>

<?php $conn->close(); ?>