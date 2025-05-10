<?php
    include 'connect.php';

    $success = false;
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = 'user'; // Tetap diset sebagai user meskipun ditampilkan

        // Cek apakah username sudah digunakan
        $cek = $conn->prepare("SELECT id_user FROM user WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO user (nama, username, password, role) VALUES (?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssss", $nama, $username, $passwordHash, $role);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $error = "Error saat registrasi: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Error pada database: " . $conn->error;
            }
        }
        $cek->close();
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Form Registrasi</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 min-h-screen flex items-center justify-center">

        <div class="w-full max-w-md bg-white p-8 rounded shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-green-700 text-center">Form Registrasi</h2>

            <?php if (!empty($error)): ?>
                <div class="mb-4 text-red-600 bg-red-100 border border-red-300 rounded px-4 py-2">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="registrasi.php" method="POST" class="space-y-4" id="registerForm">
                <div>
                    <label for="nama" class="block text-gray-700 font-medium">Nama</label>
                    <input type="text" id="nama" name="nama" required
                        class="w-full border border-gray-300 px-3 py-2 rounded focus:outline-none focus:ring focus:ring-green-300">
                </div>
                <div>
                    <label for="username" class="block text-gray-700 font-medium">Username</label>
                    <input type="text" id="username" name="username" required
                        class="w-full border border-gray-300 px-3 py-2 rounded focus:outline-none focus:ring focus:ring-green-300">
                </div>
                <div>
                    <label for="password" class="block text-gray-700 font-medium">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full border border-gray-300 px-3 py-2 rounded pr-10 focus:outline-none focus:ring focus:ring-green-300">
                        <button type="button" onclick="togglePassword()" class="absolute right-2 top-2.5 text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
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
                    <label for="role" class="block text-gray-700 font-medium">Role</label>
                    <select id="role_display" disabled
                            class="w-full bg-gray-100 text-gray-500 border border-gray-300 px-3 py-2 rounded">
                        <option selected>User</option>
                    </select>
                    <input type="hidden" name="role" value="user">
                </div>
                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">
                    Daftar
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-600">
                Sudah punya akun? <a href="login.php" class="text-green-600 hover:underline">Login di sini</a>
            </p>
        </div>

        <?php if ($success): ?>
        <script>
            alert("Registrasi berhasil! Silakan login.");
            window.location.href = "login.php";
        </script>
        <?php endif; ?>

        <script>
            function togglePassword() {
                const input = document.getElementById("password");
                input.type = input.type === "password" ? "text" : "password";
            }
        </script>

    </body>
</html>

<?php $conn->close(); ?>
