<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

$token = str_replace('Bearer ', '', $token);

if (!isset($_SESSION['admin']) && $token !== 'admin_token_seguro_2024') {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    $limite = min((int)($_GET['limite'] ?? 50), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);
    $solo_no_leidas = isset($_GET['no_leidas']) && $_GET['no_leidas'] === 'true';

    $where = '';
    if ($solo_no_leidas) {
        $where = 'WHERE leido = 0';
    }

    $stmt = $db->prepare("
        SELECT id, nombre, telefono, direccion, latitud, longitud, precision_m, tipo_emergencia, mensaje, leido, created_at
        FROM emergencias
        $where
        ORDER BY created_at DESC
        LIMIT :limite OFFSET :offset
    ");
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $emergencias = $stmt->fetchAll();

    $countStmt = $db->query("SELECT COUNT(*) as total FROM emergencias");
    $total = $countStmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'data' => $emergencias,
        'total' => (int)$total,
        'pendientes' => (int)$db->query("SELECT COUNT(*) FROM emergencias WHERE leido = 0")->fetchColumn()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener emergencias']);
}
