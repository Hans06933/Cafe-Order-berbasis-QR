<?php

session_start();

require_once "../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        $stmt = $pdo->prepare("
            SELECT id, nama, username, password, role, status
            FROM users
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Username atau password salah.";
        } elseif ($user['status'] !== 'aktif') {
            $error = "Akun Anda sedang tidak aktif.";
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Username atau password salah.";
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nama']   = $user['nama'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['user_role']   = $user['role'];

            header("Location: dashboard.php");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Café Order</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fb;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .logo {
            width: 65px;
            height: 65px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: #111827;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        h1 {
            text-align: center;
            color: #111827;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #374151;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #111827;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #111827;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1f2937;
        }

        .error {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 14px;
        }

        .info {
            margin-top: 20px;
            padding: 12px;
            border-radius: 10px;
            background: #f3f4f6;
            font-size: 13px;
            color: #4b5563;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">☕</div>

            <h1>Cafe Order</h1>
            <p class="subtitle">Login Admin & Kasir</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" autocomplete="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                </div>

                <button type="submit">Login</button>
            </form>
            <a href="../index.php" class="back">← Kembali ke halaman utama</a>
        </div>
    </div>
</body>
</html>