<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Vérifier si le client a des contrats
    if (tableExists($pdo, 'contrats')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrats WHERE client_id = ?");
        $stmt->execute([$id]);
        $nbContrats = $stmt->fetchColumn();
        
        if ($nbContrats > 0) {
            header("Location: clients.php?error=impossible_supprimer_client_avec_contrats");
            exit;
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: clients.php?success=client_supprime");
} else {
    header('Location: clients.php');
}
exit;
?>