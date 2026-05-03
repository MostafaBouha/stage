<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: contrats.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM contrats WHERE id = ?");
$stmt->execute([$id]);
$contrat = $stmt->fetch();

if (!$contrat) {
    header('Location: contrats.php');
    exit;
}

// Récupérer tous les clients pour le dropdown
$clients = $pdo->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $numero_contrat = trim($_POST['numero_contrat'] ?? '');
    $type_contrat = $_POST['type_contrat'] ?? '';
    $prime_mensuelle = $_POST['prime_mensuelle'] ?? '';
    $statut = $_POST['statut'] ?? 'actif';
    $date_souscription = $_POST['date_souscription'] ?? '';
    $date_echeance = $_POST['date_echeance'] ?? '';
    
    // Validation
    if (empty($client_id)) $errors[] = "Veuillez sélectionner un client.";
    if (empty($numero_contrat)) $errors[] = "Le numéro de contrat est requis.";
    if (empty($prime_mensuelle) || $prime_mensuelle <= 0) $errors[] = "La prime mensuelle doit être supérieure à 0.";
    
    // Vérifier si le numéro de contrat existe déjà (pour un autre contrat)
    if (!empty($numero_contrat) && $numero_contrat != $contrat['numero_contrat']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrats WHERE numero_contrat = ?");
        $stmt->execute([$numero_contrat]);
        if ($stmt->fetchColumn() > 0) $errors[] = "Ce numéro de contrat existe déjà.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE contrats 
            SET client_id = ?, numero_contrat = ?, type_contrat = ?, prime_mensuelle = ?, statut = ?, date_souscription = ?, date_echeance = ?
            WHERE id = ?
        ");
        $stmt->execute([$client_id, $numero_contrat, $type_contrat, $prime_mensuelle, $statut, $date_souscription, $date_echeance, $id]);
        $success = true;
        header("refresh:2;url=contrats.php");
        
        // Recharger les données
        $stmt = $pdo->prepare("SELECT * FROM contrats WHERE id = ?");
        $stmt->execute([$id]);
        $contrat = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Contrat - CONFORT ASSURANCES SARL</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="logo-area">
                <div class="logo-placeholder">
                    <img src="logo.jpeg" alt="Logo CONFORT ASSURANCES" class="logo-img">
                </div>
                <div class="agency-title">
                    <h1>CONFORT ASSURANCES SARL</h1>
                    <p>Votre partenaire de confiance pour vos assurances</p>
                </div>
            </div>
            <div class="header-nav">
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Accueil</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
                <a href="contrats.php"><i class="fas fa-file-signature"></i> Contrats</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="container-header">
            <h1><i class="fas fa-edit"></i> Modifier le Contrat</h1>
            <p>Modifiez les informations du contrat <?= htmlspecialchars($contrat['numero_contrat']) ?></p>
        </div>
        <div class="container-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Contrat modifié avec succès ! Redirection en cours...
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-modern">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Client <span class="required">*</span></label>
                    <select name="client_id" required>
                        <option value="">-- Sélectionner un client --</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $contrat['client_id'] == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['prenom']) . ' ' . htmlspecialchars($client['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Numéro de contrat <span class="required">*</span></label>
                        <input type="text" name="numero_contrat" value="<?= htmlspecialchars($contrat['numero_contrat']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type de contrat <span class="required">*</span></label>
                        <select name="type_contrat" required>
                            <option value="auto" <?= $contrat['type_contrat'] == 'auto' ? 'selected' : '' ?>>Auto</option>
                            <option value="habitation" <?= $contrat['type_contrat'] == 'habitation' ? 'selected' : '' ?>>Habitation</option>
                            <option value="vie" <?= $contrat['type_contrat'] == 'vie' ? 'selected' : '' ?>>Vie</option>
                            <option value="sante" <?= $contrat['type_contrat'] == 'sante' ? 'selected' : '' ?>>Santé</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Prime mensuelle (MAD) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="prime_mensuelle" value="<?= $contrat['prime_mensuelle'] ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Statut <span class="required">*</span></label>
                        <select name="statut" required>
                            <option value="actif" <?= $contrat['statut'] == 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="resilie" <?= $contrat['statut'] == 'resilie' ? 'selected' : '' ?>>Résilié</option>
                            <option value="en_attente" <?= $contrat['statut'] == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-plus"></i> Date de souscription</label>
                        <input type="date" name="date_souscription" value="<?= $contrat['date_souscription'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> Date d'échéance</label>
                        <input type="date" name="date_echeance" value="<?= $contrat['date_echeance'] ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                    <a href="contrats.php" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>