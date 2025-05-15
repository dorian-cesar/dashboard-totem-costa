<?php
header("Content-Type: application/json");

$allowedOrigins = [
    "http://localhost",
    "http://127.0.0.1",
    "http://0.0.0.0:8080",
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/db.php';

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    try {
        $params = [];
        $whereConditions = [];

        $query = "SELECT * FROM totem_logs";
        $countQuery = "SELECT COUNT(*) as total FROM totem_logs";

        // Filtro por fechas
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $whereConditions[] = "DATE(created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $_GET['start_date'];
            $params[':end_date'] = $_GET['end_date'];
        }

        // Búsqueda global
        if (!empty($_GET['search']['value'])) {
            $search = $_GET['search']['value'];
            $whereConditions[] = "(rut LIKE :search OR origen LIKE :search OR destino LIKE :search OR codigo_reserva LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
            $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }

        // Ordenamiento
        $orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
        $orderDirection = $_GET['order'][0]['dir'] ?? 'desc';
        $columns = ['id', 'rut', 'origen', 'destino', 'fecha_viaje', 'hora_viaje', 'asiento', 'codigo_reserva', 'estado_transaccion', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query .= " ORDER BY $orderColumn $orderDirection";

        // Paginación
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 200;
        $length = min($length, 200); // Nunca más de 200

        $query .= " LIMIT $length OFFSET $start";       

        // Ejecutar consulta de datos
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Conteo total (para DataTables)
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $countStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $totalRecords = $countStmt->fetchColumn();

        echo json_encode([
            'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
            'recordsTotal' => intval($totalRecords),
            'recordsFiltered' => intval($totalRecords),
            'data' => $data
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
?>
