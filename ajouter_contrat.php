<?php
require_once 'config.php';

$errors = [];
$success = false;

// Récupérer tous les clients pour le dropdown
$clients = $pdo->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();

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
    if (empty($date_souscription)) $errors[] = "La date de souscription est requise.";
    if (empty($date_echeance)) $errors[] = "La date d'échéance est requise.";
    
    // Vérifier si le numéro de contrat existe déjà
    if (!empty($numero_contrat)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrats WHERE numero_contrat = ?");
        $stmt->execute([$numero_contrat]);
        if ($stmt->fetchColumn() > 0) $errors[] = "Ce numéro de contrat existe déjà.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO contrats (client_id, numero_contrat, type_contrat, prime_mensuelle, statut, date_souscription, date_echeance) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$client_id, $numero_contrat, $type_contrat, $prime_mensuelle, $statut, $date_souscription, $date_echeance]);
        $success = true;
        header("refresh:2;url=contrats.php");
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Contrat - CONFORT ASSURANCES SARL</title>
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
            <h1><i class="fas fa-file-signature"></i> Nouveau Contrat</h1>
            <p>Créez un nouveau contrat d'assurance pour un client</p>
        </div>
        <div class="container-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Contrat créé avec succès ! Redirection en cours...
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
                        <option value="<?= $client['id'] ?>" <?= ($_POST['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['prenom']) . ' ' . htmlspecialchars($client['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Numéro de contrat <span class="required">*</span></label>
                        <input type="text" name="numero_contrat" value="<?= htmlspecialchars($_POST['numero_contrat'] ?? '') ?>" required placeholder="CONTRAT-2024-001">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type de contrat <span class="required">*</span></label>
                        <select name="type_contrat" required>
                            <option value="auto" <?= ($_POST['type_contrat'] ?? '') == 'auto' ? 'selected' : '' ?>>Auto</option>
                            <option value="habitation" <?= ($_POST['type_contrat'] ?? '') == 'habitation' ? 'selected' : '' ?>>Habitation</option>
                            <option value="vie" <?= ($_POST['type_contrat'] ?? '') == 'vie' ? 'selected' : '' ?>>Vie</option>
                            <option value="sante" <?= ($_POST['type_contrat'] ?? '') == 'sante' ? 'selected' : '' ?>>Santé</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Prime mensuelle (MAD) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="prime_mensuelle" value="<?= htmlspecialchars($_POST['prime_mensuelle'] ?? '') ?>" required placeholder="499.99">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Statut <span class="required">*</span></label>
                        <select name="statut" required>
                            <option value="actif" <?= ($_POST['statut'] ?? '') == 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="resilie" <?= ($_POST['statut'] ?? '') == 'resilie' ? 'selected' : '' ?>>Résilié</option>
                            <option value="en_attente" <?= ($_POST['statut'] ?? '') == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-plus"></i> Date de souscription <span class="required">*</span></label>
                        <input type="date" name="date_souscription" value="<?= $_POST['date_souscription'] ?? date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> Date d'échéance <span class="required">*</span></label>
                        <input type="date" name="date_echeance" value="<?= $_POST['date_echeance'] ?? date('Y-m-d', strtotime('+1 year')) ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Créer le contrat</button>
                    <a href="contrats.php" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>