<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: clients.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: clients.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    
    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($prenom)) $errors[] = "Le prénom est requis.";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE clients SET nom = ?, prenom = ?, telephone = ?, email = ?, adresse = ?, date_debut = ?, date_fin = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nom, $prenom, $telephone, $email, $adresse, $date_debut, $date_fin, $id]);
        $success = true;
        header("refresh:2;url=clients.php");
        
        // Recharger les données
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Client - CONFORT ASSURANCES SARL</title>
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
            <h1><i class="fas fa-user-edit"></i> Modifier le Client</h1>
            <p>Modifiez les informations du client <?= htmlspecialchars($client['prenom']) ?> <?= htmlspecialchars($client['nom']) ?></p>
        </div>
        <div class="container-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Client modifié avec succès ! Redirection en cours...
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
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom <span class="required">*</span></label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($client['nom']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($client['prenom']) ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Téléphone</label>
                        <input type="tel" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Adresse</label>
                    <textarea name="adresse"><?= htmlspecialchars($client['adresse']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Date début assurance</label>
                        <input type="date" name="date_debut" value="<?= $client['date_debut'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> Date fin assurance</label>
                        <input type="date" name="date_fin" value="<?= $client['date_fin'] ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                    <a href="clients.php" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>