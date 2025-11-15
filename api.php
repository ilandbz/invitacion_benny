<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Archivo donde se guardarán las respuestas
$dataFile = 'responses.json';

// Función para leer datos
function readData() {
    global $dataFile;
    
    if (!file_exists($dataFile)) {
        return ['accepted' => [], 'declined' => []];
    }
    
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    
    if ($data === null) {
        return ['accepted' => [], 'declined' => []];
    }
    
    return $data;
}

// Función para guardar datos
function saveData($data) {
    global $dataFile;
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($dataFile, $json) === false) {
        return false;
    }
    
    return true;
}

// Función para normalizar nombres (evitar duplicados)
function normalizeKey($name) {
    return strtolower(trim($name));
}

// Función para encontrar invitado
function findGuest($guests, $name) {
    $normalizedName = normalizeKey($name);
    
    foreach ($guests as $index => $guest) {
        if (normalizeKey($guest['name']) === $normalizedName) {
            return $index;
        }
    }
    
    return -1;
}

// Manejar solicitud GET (obtener todos los datos)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_all') {
        $data = readData();
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
}

// Manejar solicitud POST (guardar respuesta)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    if (!isset($request['action']) || !isset($request['name'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
        exit;
    }
    
    $action = $request['action'];
    $name = trim($request['name']);
    
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'El nombre no puede estar vacío'
        ]);
        exit;
    }
    
    // Leer datos actuales
    $data = readData();
    
    $guestData = [
        'name' => $name,
        'date' => date('d/m/Y H:i:s')
    ];
    
    if ($action === 'accept') {
        // Verificar si ya aceptó
        $acceptedIndex = findGuest($data['accepted'], $name);
        
        if ($acceptedIndex !== -1) {
            echo json_encode([
                'success' => false,
                'message' => '¡Genial, ' . $name . '! Ya confirmaste tu asistencia anteriormente 😊'
            ]);
            exit;
        }
        
        // Si había declinado, removerlo
        $declinedIndex = findGuest($data['declined'], $name);
        if ($declinedIndex !== -1) {
            array_splice($data['declined'], $declinedIndex, 1);
        }
        
        // Agregar a aceptados
        $data['accepted'][] = $guestData;
        
        if (saveData($data)) {
            echo json_encode([
                'success' => true,
                'message' => '¡Genial, ' . $name . '! Nos vemos en la fiesta de Benny 🎉🎈'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar los datos'
            ]);
        }
        
    } elseif ($action === 'decline') {
        // Verificar si ya declinó
        $declinedIndex = findGuest($data['declined'], $name);
        
        if ($declinedIndex !== -1) {
            echo json_encode([
                'success' => false,
                'message' => $name . ', ya indicaste que no podrás asistir'
            ]);
            exit;
        }
        
        // Si había aceptado, removerlo
        $acceptedIndex = findGuest($data['accepted'], $name);
        if ($acceptedIndex !== -1) {
            array_splice($data['accepted'], $acceptedIndex, 1);
        }
        
        // Agregar a declinados
        $data['declined'][] = $guestData;
        
        if (saveData($data)) {
            echo json_encode([
                'success' => true,
                'message' => 'Qué pena, ' . $name . '. ¡Te extrañaremos en la fiesta! 😢'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar los datos'
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
    }
    
    exit;
}

// Método no permitido
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
?>