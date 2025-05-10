<?php
    session_start();
    include 'connect.php';

    $success = false;
    $redirectTo = '';
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT id_user, nama, username, password, role FROM user WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['id_user'] = $user['id_user'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    $success = true;
                    // Redirect berdasarkan role
                    if ($user['role'] == 'admin') {
                        $redirectTo = "../Admin/dashboard_admin.php";
                    } else {
                        $redirectTo = "../Home/dashboard.php";
                    }
                } else {
                    $error = "Password salah!";
                }
            } else {
                $error = "Username tidak ditemukan!";
            }

            $stmt->close();
        } else {
            $error = "Error pada database: " . $conn->error;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-green-50 min-h-screen flex items-center justify-center">

        <div class="w-full max-w-md bg-white p-8 rounded shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-green-700 text-center">Login</h2>

            <?php if (!empty($error)): ?>
                <div class="mb-4 text-red-600 bg-red-100 border border-red-300 rounded px-4 py-2">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
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
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">
                    Login
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-600">
                Belum punya akun? <a href="registrasi.php" class="text-green-600 hover:underline">Daftar di sini</a>
            </p>
        </div>

        <?php if ($success && !empty($redirectTo)): ?>
        <script>
            alert("Berhasil login!");
            window.location.href = "<?= $redirectTo ?>";
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
