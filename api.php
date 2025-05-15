<?php
    header("Content-Type: application/json");

    // Configuración CORS (mantener tu configuración actual)
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

    // Endpoint GET
    if ($method == 'GET') {
        try {
            $query = "SELECT * FROM totem_logs";
            $countQuery = "SELECT COUNT(*) as total FROM totem_logs";
            $params = [];
            $whereConditions = [];
            
            // Solo filtra por fechas si se especifica en los parámetros
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $whereConditions[] = "DATE(created_at) BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $_GET['start_date'];
                $params[':end_date'] = $_GET['end_date'];
            }
            
            // Búsqueda global (opcional, si el DataTable la usa)
            if (!empty($_GET['search']['value'])) {
                $search = $_GET['search']['value'];
                $whereConditions[] = "(rut LIKE :search OR origen LIKE :search OR destino LIKE :search OR codigo_reserva LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
                $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            // Ordenación por defecto: id DESC (si no hay otra especificada)
            $orderColumn = $_GET['order'][0]['column'] ?? 0;
            $orderDirection = $_GET['order'][0]['dir'] ?? 'desc';
            $columns = ['id', 'rut', 'origen', 'destino', 'fecha_viaje', 'hora_viaje', 'asiento', 'codigo_reserva', 'estado_transaccion', 'created_at'];
            $orderBy = $columns[$orderColumn] ?? 'id';
            $query .= " ORDER BY $orderBy $orderDirection";
            
            
            $limit = (int)($_GET['length'] ?? 200);
            // Forzar un máximo de 200 si se recibe un valor mayor
            $limit = min($limit, 200);
            $offset = (int)($_GET['start'] ?? 0);

            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;  
    
            $stmt = $pdo->prepare($query);
            
            foreach ($params as $key => $value) {
                $paramType = $key === ':limit' || $key === ':offset' ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $paramType);
            }
            
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Conteo total
            $countStmt = $pdo->prepare($countQuery);
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $totalRecords = $countStmt->fetchColumn();

            // Si hay filtros aplicados, recordsFiltered debe reflejar solo los registros filtrados
            $filteredRecords = $totalRecords;
            if (!empty($whereConditions)) {
                // Si hay condiciones de filtro, obtener el conteo filtrado
                $filteredRecords = $totalRecords;
            } else {
                // Si no hay filtros, limitar el conteo para no sobrecargar el servidor
                $filteredRecords = min($totalRecords, 10000); // Límite máximo para conteo sin filtros
            }

            echo json_encode([
                'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
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