<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user'])) {
        header("Location: ../Auth/login.php");
        exit();
    }

    $id_user = $_SESSION['id_user'];
    $sql = "SELECT nama, username FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $success = $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if (empty($nama) || empty($username)) {
            $error = "Nama dan Username tidak boleh kosong.";
        } else {
            // Cek jika username sudah digunakan user lain
            $sql = "SELECT id_user FROM user WHERE username = ? AND id_user != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $username, $id_user);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username sudah digunakan.";
            } else {
                if (!empty($password)) {
                    if ($password !== $confirm) {
                        $error = "Konfirmasi password tidak cocok.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $update = $conn->prepare("UPDATE user SET nama = ?, username = ?, password = ? WHERE id_user = ?");
                        $update->bind_param("sssi", $nama, $username, $hashed, $id_user);
                    }
                } else {
                    $update = $conn->prepare("UPDATE user SET nama = ?, username = ? WHERE id_user = ?");
                    $update->bind_param("ssi", $nama, $username, $id_user);
                }

                if ($update->execute()) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                    $success = "Biodata berhasil diperbarui.";
                } else {
                    $error = "Gagal memperbarui biodata.";
                }

                $update->close();
            }
            $stmt->close();
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Edit Biodata</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 text-gray-800">

        <!-- Form Edit -->
        <div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow-md">
            <h2 class="text-xl font-semibold text-green-700 mb-4">Edit Biodata</h2>

            <?php if ($success): ?>
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-2 rounded border border-green-300"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-2 rounded border border-red-300"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm();" class="space-y-4">
                <div>
                    <label class="block font-medium text-gray-700">Nama</label>
                    <input type="text" name="nama" required value="<?= htmlspecialchars($user['nama']); ?>"
                        class="w-full border border-gray-300 px-3 py-2 rounded focus:outline-none focus:ring focus:ring-green-300">
                </div>

                <div>
                    <label class="block font-medium text-gray-700">Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($user['username']); ?>"
                        class="w-full border border-gray-300 px-3 py-2 rounded focus:outline-none focus:ring focus:ring-green-300">
                </div>

                <div>
                    <label class="block font-medium text-gray-700">Password Baru (opsional)</label>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                            class="w-full border border-gray-300 px-3 py-2 rounded pr-10 focus:outline-none focus:ring focus:ring-green-300">
                        <button type="button" onclick="togglePassword('password', 'eye1')" class="absolute right-2 top-2.5 text-gray-500">
                            <svg id="eye1" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700">Konfirmasi Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password"
                            class="w-full border border-gray-300 px-3 py-2 rounded pr-10 focus:outline-none focus:ring focus:ring-green-300">
                        <button type="button" onclick="togglePassword('confirm_password', 'eye2')" class="absolute right-2 top-2.5 text-gray-500">
                            <svg id="eye2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="biodata.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Batal</a>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Simpan Perubahan</button>
                </div>
            </form>
        </div>

        <script>
            function togglePassword(inputId, eyeId) {
                const input = document.getElementById(inputId);
                const eye = document.getElementById(eyeId);
                if (input.type === "password") {
                    input.type = "text";
                    eye.classList.add("text-green-600");
                } else {
                    input.type = "password";
                    eye.classList.remove("text-green-600");
                }
            }

            function validateForm() {
                const password = document.getElementById("password").value.trim();
                const confirm = document.getElementById("confirm_password").value.trim();

                if (password && !confirm) {
                    alert("Harap isi konfirmasi password.");
                    return false;
                }

                if (password && confirm && password !== confirm) {
                    alert("Konfirmasi password tidak cocok.");
                    return false;
                }

                return true;
            }
        </script>

    </body>
</html>

<?php $conn->close(); ?>
