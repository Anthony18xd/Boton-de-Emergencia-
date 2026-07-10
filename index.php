<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Ingresa usuario y contraseña';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Credenciales incorrectas';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión a la base de datos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Panel de Emergencias</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🚨 Panel de Emergencias</h1>
                <p>Municipalidad de Huamancaca Chico</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required placeholder="admin">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="********">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Ingresar</button>
            </form>

            <p class="login-hint">Usuario por defecto: <strong>admin</strong> / <strong>password</strong></p>
        </div>
    </div>
</body>
</html>
