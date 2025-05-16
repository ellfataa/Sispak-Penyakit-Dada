<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
        header("Location: ../Auth/login.php");
        exit();
    }

    // Validasi input gejala
    if (!isset($_POST['gejala']) || empty($_POST['gejala'])) {
        echo "Silakan pilih minimal satu gejala.";
        exit();
    }

    $gejalaDipilih = $_POST['gejala'];
    $idUser = $_SESSION['id_user'];

    // Ambil semua data penyakit
    $penyakit = [];
    $totalPenyakit = 0;
    $sql = "SELECT kode_penyakit, nama_penyakit FROM penyakit";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $penyakit[$row['kode_penyakit']] = [
            'nama_penyakit' => $row['nama_penyakit'],
            'gejala' => [],
            'jumlah_gejala' => 0,
            'nc_values' => [],
            'detail_perhitungan' => []
        ];
        $totalPenyakit++;
    }

    // Ambil semua gejala yang ada dalam database
    $totalGejala = 0;
    $sql = "SELECT COUNT(*) as total_gejala FROM gejala";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $totalGejala = $row['total_gejala'];
    }

    // Ambil semua gejala untuk keperluan detail output
    $daftarGejala = [];
    $sql = "SELECT kode_gejala, nama_gejala FROM gejala";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $daftarGejala[$row['kode_gejala']] = $row['nama_gejala'];
    }

    // Ambil relasi gejala-penyakit
    $sqlRelasi = "SELECT kode_penyakit, kode_gejala FROM penyakit_gejala";
    $resultRelasi = $conn->query($sqlRelasi);
    while ($row = $resultRelasi->fetch_assoc()) {
        $kodePenyakit = $row['kode_penyakit'];
        $kodeGejala = $row['kode_gejala'];

        if (isset($penyakit[$kodePenyakit])) {
            $penyakit[$kodePenyakit]['gejala'][] = $kodeGejala;
            $penyakit[$kodePenyakit]['jumlah_gejala']++;
        }
    }

    // Implementasi Naive Bayes
    $hasil = [];
    $logHasil = []; // Untuk dokumentasi langkah-langkah

    // TAHAP 1: Menentukan nilai nc untuk setiap class penyakit
    foreach ($penyakit as $kodePenyakit => &$dataPenyakit) {
        $dataPenyakit['detail_perhitungan'][] = "<strong>Tahap 1: Menentukan nilai nc untuk penyakit $kodePenyakit</strong>";
        
        // Untuk setiap gejala yang dipilih oleh pengguna, periksa apakah ada dalam penyakit ini
        foreach ($gejalaDipilih as $gejala) {
            // Jika gejala ada pada penyakit, berikan nilai nc = 1, jika tidak nc = 0
            $nc = in_array($gejala, $dataPenyakit['gejala']) ? 1 : 0;
            $dataPenyakit['nc_values'][$gejala] = $nc;
            
            // Tambahkan log untuk detail perhitungan
            $namaGejala = isset($daftarGejala[$gejala]) ? $daftarGejala[$gejala] : $gejala;
            $dataPenyakit['detail_perhitungan'][] = "nc gejala $gejala ($namaGejala) untuk $kodePenyakit = $nc";
        }
    }

    // TAHAP 2: Menghitung nilai P(ai|vj) dan P(vj) untuk setiap class
    foreach ($penyakit as $kodePenyakit => &$dataPenyakit) {
        $dataPenyakit['detail_perhitungan'][] = "<strong>Tahap 2: Menghitung nilai P(ai|vj) dan P(vj) untuk penyakit $kodePenyakit</strong>";
        
        // P(vj) - prior probability untuk tiap penyakit (asumsi peluang yang sama)
        $priorProbability = 1 / $totalPenyakit;
        $dataPenyakit['prior_probability'] = $priorProbability;
        
        // Log untuk prior probability
        $dataPenyakit['detail_perhitungan'][] = "P($kodePenyakit) = 1/$totalPenyakit = " . number_format($priorProbability, 4);
        
        // Hitung likelihood P(ai|vj) untuk setiap gejala yang dipilih
        $dataPenyakit['likelihood'] = [];
        foreach ($gejalaDipilih as $gejala) {
            // Rumus: P(gejala|penyakit) = (nc + m*p) / (n + m)
            // nc = jumlah record pada data learning yang v = vj dan a = ai
            // n = selalu bernilai 1 (karena gejala bersifat biner)
            // m = jumlah seluruh gejala
            // p = P(vj)
            
            $nc = $dataPenyakit['nc_values'][$gejala];
            $n = 1; // Jumlah gejala pada penyakit (selalu 1 untuk gejala biner)
            $m = $totalGejala; // Total gejala yang ada dalam database
            $p = $priorProbability; // P(vj) untuk penyakit ini
            
            // Hitung P(ai|vj)
            $likelihood = ($nc + $m * $p) / ($n + $m);
            $dataPenyakit['likelihood'][$gejala] = $likelihood;
            
            // Log detail perhitungan
            $dataPenyakit['detail_perhitungan'][] = "P($gejala|$kodePenyakit) = ($nc + $m * $p) / ($n + $m) = " . number_format($likelihood, 4);
        }
    }

    // TAHAP 3: Menghitung P(ai|vj) x P(vj) untuk setiap penyakit
    foreach ($penyakit as $kodePenyakit => &$dataPenyakit) {
        $dataPenyakit['detail_perhitungan'][] = "<strong>Tahap 3: Menghitung P(ai|vj) x P(vj) untuk penyakit $kodePenyakit</strong>";
        
        // Inisialisasi dengan prior probability
        $posteriorProbability = $dataPenyakit['prior_probability'];
        
        // Kalikan dengan semua likelihood untuk mendapatkan posterior probability
        $calcDetail = "Probabilitas $kodePenyakit = P($kodePenyakit) = " . number_format($posteriorProbability, 6);
        
        foreach ($gejalaDipilih as $gejala) {
            $posteriorProbability *= $dataPenyakit['likelihood'][$gejala];
            $namaGejala = isset($daftarGejala[$gejala]) ? $daftarGejala[$gejala] : $gejala;
            $calcDetail .= " × P($gejala|$kodePenyakit)[" . number_format($dataPenyakit['likelihood'][$gejala], 4) . "]";
        }
        
        $calcDetail .= " = " . number_format($posteriorProbability, 8);
        $dataPenyakit['detail_perhitungan'][] = $calcDetail;
        
        // Simpan hasil akhir
        $dataPenyakit['posterior_probability'] = $posteriorProbability;
        
        // Format untuk output
        $hasil[$kodePenyakit] = [
            'kode_penyakit' => $kodePenyakit,
            'nama_penyakit' => $dataPenyakit['nama_penyakit'],
            'probabilitas' => $posteriorProbability,
            'detail' => $dataPenyakit['detail_perhitungan']
        ];
    }

    // TAHAP 4: Menentukan hasil klasifikasi - penyakit dengan probabilitas tertinggi
    $logHasil[] = "<strong>Tahap 4: Menentukan hasil klasifikasi (penyakit dengan probabilitas tertinggi)</strong>";
    
    // Urutkan berdasarkan probabilitas tertinggi
    uasort($hasil, function ($a, $b) {
        return $b['probabilitas'] <=> $a['probabilitas'];
    });

    // Ambil hasil diagnosa (penyakit dengan probabilitas tertinggi)
    $diagnosa = null;
    $probabilitasFix = 0;
    $kodePenyakitTertinggi = null; // Tambahkan variabel untuk menyimpan kode penyakit
    $totalProb = 0;
    
    // Pastikan ada hasil yang ditemukan
    if (!empty($hasil)) {
        $kodeTertinggi = array_key_first($hasil);
        $diagnosa = $hasil[$kodeTertinggi]['nama_penyakit'];
        $probabilitasFix = $hasil[$kodeTertinggi]['probabilitas'];
        $kodePenyakitTertinggi = $kodeTertinggi; // Simpan kode penyakit dengan probabilitas tertinggi
        
        // Hitung total probabilitas untuk normalisasi
        $totalProb = array_sum(array_column($hasil, 'probabilitas'));
    }

    // Simpan ke riwayat_konsultasi dengan kode penyakit
    $gejalaJson = json_encode($gejalaDipilih);

    if ($diagnosa) {
        $stmt = $conn->prepare("INSERT INTO riwayat_konsultasi (id_user, gejala_dipilih, hasil_diagnosa, probabilitas, kode_penyakit) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $idUser, $gejalaJson, $diagnosa, $probabilitasFix, $kodePenyakitTertinggi);
        $stmt->execute();
        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hasil Diagnosa</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 text-gray-800 min-h-screen">

        <div class="max-w-4xl mx-auto my-10 bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl font-semibold text-green-700 mb-4 text-center">Hasil Diagnosa</h2>

            <?php if ($diagnosa) : ?>
                <div class="text-center mb-6">
                    <p class="text-lg mb-2">Berdasarkan gejala yang Anda pilih, kemungkinan Anda menderita:</p>
                    <p class="text-2xl font-bold text-red-600 mb-2"><?= htmlspecialchars($diagnosa); ?> (<?= htmlspecialchars($kodePenyakitTertinggi); ?>)</p>
                    <?php 
                        $probabilitasNormalisasi = ($totalProb > 0) ? ($probabilitasFix / $totalProb) * 100 : 0;
                    ?>
                    <p class="text-green-700 mb-4">Probabilitas: <strong><?= round($probabilitasNormalisasi, 2); ?>%</strong></p>
                    
                    <div class="mt-4 bg-yellow-50 p-4 rounded border border-yellow-200 text-left">
                        <h4 class="font-semibold text-yellow-700 mb-2">Gejala yang Anda Pilih:</h4>
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($gejalaDipilih as $gejala): ?>
                                <li><?= htmlspecialchars(isset($daftarGejala[$gejala]) ? $daftarGejala[$gejala] : $gejala); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-xl font-semibold text-green-700 mb-4">Detail Perhitungan Naive Bayes:</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-green-100">
                                <tr>
                                    <th class="py-2 px-4 border-b">Penyakit</th>
                                    <th class="py-2 px-4 border-b">Probabilitas</th>
                                    <th class="py-2 px-4 border-b">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hasil as $kodePenyakit => $data): ?>
                                <tr class="<?= ($data['nama_penyakit'] === $diagnosa) ? 'bg-green-100' : '' ?>">
                                    <td class="py-2 px-4 border-b font-medium"><?= htmlspecialchars($data['nama_penyakit']); ?> (<?= htmlspecialchars($kodePenyakit); ?>)</td>
                                    <td class="py-2 px-4 border-b text-center">
                                        <?php 
                                            $normProb = ($totalProb > 0) ? ($data['probabilitas'] / $totalProb) * 100 : 0;
                                            echo round($normProb, 2) . '%'; 
                                        ?>
                                    </td>
                                    <td class="py-2 px-4 border-b text-sm">
                                        <div class="space-y-1">
                                            <?php foreach ($data['detail'] as $detailItem): ?>
                                                <div><?= $detailItem; ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-8 p-4 bg-blue-50 rounded border border-blue-200">
                    <h4 class="font-semibold text-blue-700 mb-2">Keterangan Metode Naive Bayes:</h4>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Tahap 1:</strong> Menentukan nilai nc untuk setiap class penyakit</li>
                        <li><strong>Tahap 2:</strong> Menghitung nilai P(ai|vj) dan P(vj)</li>  
                        <li><strong>Tahap 3:</strong> Menghitung nilai P(ai|vj) × P(vj) untuk setiap penyakit</li>
                        <li><strong>Tahap 4:</strong> Menentukan hasil klasifikasi yaitu penyakit dengan probabilitas tertinggi</li>
                    </ul>
                    
                    <h4 class="font-semibold text-blue-700 mt-4 mb-2">Keterangan Rumus:</h4>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>nc</strong> = Jumlah record pada data learning yang penyakit = vj dan gejala = ai (bernilai 1 jika gejala ada pada penyakit, 0 jika tidak)</li>
                        <li><strong>n</strong> = Selalu bernilai 1 (karena gejala bersifat biner)</li>
                        <li><strong>m</strong> = Jumlah seluruh gejala</li>
                        <li><strong>p</strong> = Prior probability sama dengan nilai P(vj)</li>
                        <li><strong>P(vj)</strong> = Prior probability untuk tiap penyakit (1/jumlah penyakit)</li>
                        <li><strong>P(ai|vj)</strong> = Likelihood gejala terhadap penyakit = (nc + m*p) / (n + m)</li>
                        <li><strong>Hasil akhir</strong> = P(vj) × P(a1|vj) × P(a2|vj) × ... × P(an|vj)</li>
                    </ul>
                </div>
            <?php else : ?>
                <div class="text-center p-6 bg-red-50 rounded border border-red-200">
                    <p class="text-red-600 text-lg">Gejala yang dipilih tidak cukup untuk menentukan penyakit.</p>
                    <p class="mt-2">Silakan pilih lebih banyak gejala untuk hasil yang lebih akurat.</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-8">
                <a href="konsultasi.php" class="inline-block px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition duration-200">
                    Konsultasi Lagi
                </a>
                <a href="../Home/dashboard.php" class="inline-block px-5 py-2 ml-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition duration-200">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </body>
</html>

<?php $conn->close(); ?>