<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$nombre = trim($input['nombre'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$latitud = $input['latitud'] ?? null;
$longitud = $input['longitud'] ?? null;
$precision_m = $input['precision'] ?? 0;
$tipo = $input['tipo_emergencia'] ?? 'general';
$mensaje = trim($input['mensaje'] ?? '');
$direccion = trim($input['direccion'] ?? '');

$errors = [];
if ($nombre === '') $errors[] = 'El nombre es obligatorio';
if ($telefono === '') $errors[] = 'El teléfono es obligatorio';
if ($latitud === null || $longitud === null) $errors[] = 'La ubicación es obligatoria';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos', 'campos' => $errors]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO emergencias (nombre, telefono, direccion, latitud, longitud, precision_m, tipo_emergencia, mensaje, ip, user_agent)
        VALUES (:nombre, :telefono, :direccion, :latitud, :longitud, :precision_m, :tipo_emergencia, :mensaje, :ip, :user_agent)
    ");
    $stmt->execute([
        ':nombre' => $nombre,
        ':telefono' => $telefono,
        ':latitud' => $latitud,
        ':longitud' => $longitud,
        ':precision_m' => $precision_m,
        ':tipo_emergencia' => $tipo,
        ':mensaje' => $mensaje,
        ':direccion' => $direccion,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    $id = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Emergencia reportada exitosamente',
        'id' => $id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la emergencia']);
}
