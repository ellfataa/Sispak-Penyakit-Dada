<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user'])) {
        header("Location: ../Auth/login.php");
        exit();
    }

    $idUser = $_SESSION['id_user'];

    // Ambil riwayat konsultasi dari database
    $sql = "SELECT * FROM riwayat_konsultasi WHERE id_user = ? ORDER BY waktu_konsultasi DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();

    // Ambil informasi semua penyakit untuk perhitungan total probabilitas
    $sqlPenyakitCount = "SELECT COUNT(*) as total_penyakit FROM penyakit";
    $resultPenyakitCount = $conn->query($sqlPenyakitCount);
    $totalPenyakit = 1; // Default jika query gagal
    if ($rowCount = $resultPenyakitCount->fetch_assoc()) {
        $totalPenyakit = $rowCount['total_penyakit'];
    }

    // Ambil semua data gejala
    $daftarGejala = [];
    $sqlGejala = "SELECT kode_gejala, nama_gejala FROM gejala";
    $resultGejala = $conn->query($sqlGejala);
    while ($row = $resultGejala->fetch_assoc()) {
        $daftarGejala[$row['kode_gejala']] = $row['nama_gejala'];
    }

    // Ambil total gejala
    $totalGejala = 0;
    $sqlTotalGejala = "SELECT COUNT(*) as total_gejala FROM gejala";
    $resultTotalGejala = $conn->query($sqlTotalGejala);
    if ($row = $resultTotalGejala->fetch_assoc()) {
        $totalGejala = $row['total_gejala'];
    }

    // Ambil data relasi penyakit-gejala untuk perhitungan nc
    $relasiPenyakitGejala = [];
    $sqlRelasi = "SELECT kode_penyakit, kode_gejala FROM penyakit_gejala";
    $resultRelasi = $conn->query($sqlRelasi);
    while ($row = $resultRelasi->fetch_assoc()) {
        if (!isset($relasiPenyakitGejala[$row['kode_penyakit']])) {
            $relasiPenyakitGejala[$row['kode_penyakit']] = [];
        }
        $relasiPenyakitGejala[$row['kode_penyakit']][] = $row['kode_gejala'];
    }

    // Fungsi untuk menghitung semua probabilitas penyakit berdasarkan gejala
    function hitungSemuaProbabilitas($gejalaDipilih, $totalPenyakit, $totalGejala, $relasiPenyakitGejala) {
        // Ambil semua data penyakit
        global $conn;
        $penyakit = [];

        $sql = "SELECT kode_penyakit, nama_penyakit FROM penyakit";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $penyakit[$row['kode_penyakit']] = [
                'nama_penyakit' => $row['nama_penyakit'],
                'posterior_probability' => 0
            ];
        }

        // Hitung probabilitas untuk setiap penyakit
        foreach ($penyakit as $kodePenyakit => &$dataPenyakit) {
            // Prior probability untuk tiap penyakit (asumsi peluang yang sama)
            $priorProbability = 1 / $totalPenyakit;
            
            // Inisialisasi dengan prior probability
            $posteriorProbability = $priorProbability;
            
            // Hitung likelihood untuk setiap gejala yang dipilih
            foreach ($gejalaDipilih as $gejala) {
                // Tentukan nilai nc (1 jika gejala ada pada penyakit, 0 jika tidak)
                $nc = in_array($gejala, $relasiPenyakitGejala[$kodePenyakit] ?? []) ? 1 : 0;
                
                // Parameter laplacian smoothing
                $n = 1; // Jumlah gejala pada penyakit (selalu 1 untuk gejala biner)
                $m = $totalGejala; // Total gejala yang ada dalam database
                $p = $priorProbability; // P(vj) untuk penyakit ini
                
                // Hitung P(ai|vj) dengan laplacian smoothing
                $likelihood = ($nc + $m * $p) / ($n + $m);
                
                // Kalikan dengan posterior probability
                $posteriorProbability *= $likelihood;
            }
            
            // Simpan hasil akhir
            $dataPenyakit['posterior_probability'] = $posteriorProbability;
        }
        
        // Hitung total probabilitas
        $totalProb = array_sum(array_column($penyakit, 'posterior_probability'));
        
        return $totalProb;
    }

    $riwayat = [];
    while ($row = $result->fetch_assoc()) {
        // Dapatkan gejala yang dipilih
        $gejalaDipilih = json_decode($row['gejala_dipilih']);
        
        // Hitung total probabilitas untuk normalisasi
        $totalProb = hitungSemuaProbabilitas($gejalaDipilih, $totalPenyakit, $totalGejala, $relasiPenyakitGejala);
        
        // Normalisasi probabilitas
        $probabilitasNormalisasi = ($totalProb > 0) ? ($row['probabilitas'] / $totalProb) * 100 : 0;
        
        // Tambahkan ke array riwayat dengan probabilitas yang sudah dinormalisasi
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

        <div class="max-w-5xl mx-auto mt-10 bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl font-semibold text-green-700 mb-6 text-center">Riwayat Konsultasi Anda</h2>

            <?php if (count($riwayat) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left border border-gray-300">
                        <thead class="bg-green-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 border">No</th>
                                <th class="px-4 py-2 border">Hasil Diagnosa</th>
                                <th class="px-4 py-2 border">Probabilitas</th>
                                <th class="px-4 py-2 border">Gejala Dipilih</th>
                                <th class="px-4 py-2 border">Waktu Konsultasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $index => $r): ?>
                                <tr class="hover:bg-green-50">
                                    <td class="px-4 py-2 border"><?= $index + 1; ?></td>
                                    <td class="px-4 py-2 border"><?= htmlspecialchars($r['hasil_diagnosa']); ?></td>
                                    <td class="px-4 py-2 border"><?= round($r['probabilitas_normalized'], 2); ?>%</td>
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