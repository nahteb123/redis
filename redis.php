<?php
$redis = new Redis();

try {
    $redis->connect('127.0.0.1', 6379);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de se connecter à Redis']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['key'])) {
        $key = $_GET['key'];
        $cached = $redis->get($key);

        if ($cached !== false) {
            $ttl = $redis->ttl($key); // récupère le TTL
            echo json_encode([
                'source' => 'cache',
                'data' => $cached,
                'expires_in' => $ttl !== -1 ? $ttl : null // null si non expirant
            ]);
        } else {
            sleep(2); // simule une latence
            $data = isset($_GET['value']) && trim($_GET['value']) !== ''
                ? $_GET['value']
                : "Donnée simulée pour la clé '$key' à " . date('H:i:s');
            
            $redis->setex($key, 60, $data); // expire après 60s

            echo json_encode([
                'source' => 'slow_db',
                'data' => $data,
                'expires_in' => 60
            ]);
        }
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'list') {
        $keys = $redis->keys('*');
        $result = [];

        foreach ($keys as $key) {
            $value = $redis->get($key);
            $ttl = $redis->ttl($key);
            $result[$key] = [
                'value' => $value,
                'expires_in' => $ttl !== -1 ? $ttl : null
            ];
        }

        echo json_encode($result);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['newKey']) && isset($_POST['newValue'])) {
        $key = $_POST['newKey'];
        $value = $_POST['newValue'];
        $redis->set($key, $value); // clé permanente
        echo json_encode([
            'success' => true,
            'message' => "Clé '$key' ajoutée avec succès."
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Requête invalide.']);
