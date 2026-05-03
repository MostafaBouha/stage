<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'gestion_clients';
$username = 'root';
$password = 'azerty12345';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour vérifier si une table existe
function tableExists($pdo, $tableName) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Fonction pour obtenir les statistiques rapides
function getStats($pdo) {
    $stats = [
        'total_clients' => 0,
        'total_contrats_actifs' => 0,
        'ca_mensuel' => 0,
        'echeances_proches' => 0
    ];
    
    if (tableExists($pdo, 'clients')) {
        $stats['total_clients'] = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    }
    
    if (tableExists($pdo, 'contrats')) {
        $stats['total_contrats_actifs'] = $pdo->query("SELECT COUNT(*) FROM contrats WHERE statut = 'actif'")->fetchColumn();
        $stats['ca_mensuel'] = $pdo->query("SELECT COALESCE(SUM(prime_mensuelle), 0) FROM contrats WHERE statut = 'actif'")->fetchColumn();
        $stats['echeances_proches'] = $pdo->query("
            SELECT COUNT(*) FROM contrats 
            WHERE statut = 'actif' 
            AND date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();
    }
    
    return $stats;
}
?>