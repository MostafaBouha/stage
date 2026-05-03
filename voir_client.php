<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: contrats.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, cl.nom, cl.prenom, cl.telephone, cl.email 
    FROM contrats c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$contrat = $stmt->fetch();

if (!$contrat) {
    header('Location: contrats.php');
    exit;
}

$jours_restants = floor((strtotime($contrat['date_echeance']) - time()) / (60 * 60 * 24));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Contrat - CONFORT ASSURANCES SARL</title>
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
            <h1><i class="fas fa-file-alt"></i> Détails du Contrat</h1>
            <p>Contrat n° <?= htmlspecialchars($contrat['numero_contrat']) ?></p>
        </div>
        <div class="container-content">
            <div class="contrat-info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <label><i class="fas fa-hashtag"></i> Numéro de contrat</label>
                        <p><strong><?= htmlspecialchars($contrat['numero_contrat']) ?></strong></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-tag"></i> Type de contrat</label>
                        <p><span class="badge badge-<?= $contrat['type_contrat'] ?>"><?= ucfirst($contrat['type_contrat']) ?></span></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-money-bill-wave"></i> Prime mensuelle</label>
                        <p><strong><?= number_format($contrat['prime_mensuelle'], 2) ?> MAD</strong></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-toggle-on"></i> Statut</label>
                        <p><span class="statut statut-<?= $contrat['statut'] ?>"><?= $contrat['statut'] == 'en_attente' ? 'En attente' : ucfirst($contrat['statut']) ?></span></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-calendar-plus"></i> Date de souscription</label>
                        <p><?= date('d/m/Y', strtotime($contrat['date_souscription'])) ?></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-calendar-times"></i> Date d'échéance</label>
                        <p><?= date('d/m/Y', strtotime($contrat['date_echeance'])) ?>
                        <?php if ($contrat['statut'] == 'actif'): ?>
                            <br><small class="<?= $jours_restants < 30 ? 'text-warning' : 'text-muted' ?>"><?= $jours_restants ?> jours restants</small>
                        <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="client-info-card">
                <h3><i class="fas fa-user"></i> Informations du client</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label><i class="fas fa-user"></i> Nom complet</label>
                        <p><strong><?= htmlspecialchars($contrat['prenom']) ?> <?= htmlspecialchars($contrat['nom']) ?></strong></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-phone"></i> Téléphone</label>
                        <p><?= htmlspecialchars($contrat['telephone'] ?: 'Non renseigné') ?></p>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <p><?= htmlspecialchars($contrat['email'] ?: 'Non renseigné') ?></p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Modifier</a>
                <a href="contrats.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>
    </div>

    <style>
    .contrat-info-card, .client-info-card {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid #e0e6ed;
    }
    .client-info-card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #1a3a5f;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .info-item label {
        font-weight: 600;
        color: #1a3a5f;
        margin-bottom: 5px;
        display: block;
        font-size: 0.85rem;
    }
    .info-item p {
        margin: 0;
        font-size: 1rem;
        color: #2c3e50;
    }
    </style>
</body>
</html>