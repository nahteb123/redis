<?php
$redis = new Redis();

try {
    // Remplace par l’IP de ta machine Ubuntu ici
    $redis->connect('127.0.0.1', 6379);

    // Si Redis a un mot de passe, décommente la ligne ci-dessous :
    // $redis->auth('ton_mot_de_passe');

    $redis->set("test_key", "Hello depuis WAMP !");
    $value = $redis->get("test_key");

    echo "Connexion réussie !<br>";
    echo "Valeur lue depuis Redis : <strong>$value</strong>";
} catch (Exception $e) {
    echo "Erreur de connexion à Redis : " . $e->getMessage();
}
$redis->connect('127.0.0.1', 6379);
?>
