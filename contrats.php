<?php
require_once 'config.php';

// Vérifier si la table contrats existe
if (!tableExists($pdo, 'contrats')) {
    die("<div class='alert alert-danger'>La table 'contrats' n'existe pas. Veuillez exécuter le script SQL de création.</div>");
}

// Filtres
$filtre_statut = $_GET['statut'] ?? '';
$filtre_type = $_GET['type'] ?? '';

// Requête avec filtres
$sql = "
    SELECT c.*, cl.nom as nom_client, cl.prenom as prenom_client 
    FROM contrats c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    WHERE 1=1
";
$params = [];

if ($filtre_statut) {
    $sql .= " AND c.statut = ?";
    $params[] = $filtre_statut;
}
if ($filtre_type) {
    $sql .= " AND c.type_contrat = ?";
    $params[] = $filtre_type;
}
$sql .= " ORDER BY c.date_echeance ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contrats = $stmt->fetchAll();

// Statistiques
$stats = getStats($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrats - CONFORT ASSURANCES SARL</title>
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
                <a href="contrats.php" class="active"><i class="fas fa-file-signature"></i> Contrats</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="container-header">
            <h1><i class="fas fa-file-contract"></i> Gestion des Contrats</h1>
            <p>Suivez et gérez tous les contrats d'assurance</p>
        </div>
        <div class="container-content">
            <!-- Statistiques -->
            <div class="quick-stats">
                <div class="quick-stat">
                    <i class="fas fa-file-contract"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= count($contrats) ?></span>
                        <span class="quick-stat-label">Total contrats</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-chart-line"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= number_format($stats['ca_mensuel'], 0) ?> MAD</span>
                        <span class="quick-stat-label">CA mensuel</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-clock"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= $stats['echeances_proches'] ?></span>
                        <span class="quick-stat-label">Échéances < 30j</span>
                    </div>
                </div>
            </div>

            <!-- Actions et filtres -->
            <div class="actions-bar">
                <div class="filters">
                    <form method="GET" class="filters-form" style="display: flex; gap: 10px;">
                        <select name="statut" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?= $filtre_statut == 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="resilie" <?= $filtre_statut == 'resilie' ? 'selected' : '' ?>>Résilié</option>
                            <option value="en_attente" <?= $filtre_statut == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                        <select name="type" onchange="this.form.submit()">
                            <option value="">Tous les types</option>
                            <option value="auto" <?= $filtre_type == 'auto' ? 'selected' : '' ?>>Auto</option>
                            <option value="habitation" <?= $filtre_type == 'habitation' ? 'selected' : '' ?>>Habitation</option>
                            <option value="vie" <?= $filtre_type == 'vie' ? 'selected' : '' ?>>Vie</option>
                            <option value="sante" <?= $filtre_type == 'sante' ? 'selected' : '' ?>>Santé</option>
                        </select>
                        <?php if ($filtre_statut || $filtre_type): ?>
                            <a href="contrats.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Réinitialiser</a>
                        <?php endif; ?>
                    </form>
                </div>
                <a href="ajouter_contrat.php" class="btn btn-success"><i class="fas fa-plus"></i> Nouveau Contrat</a>
            </div>

            <!-- Liste des contrats -->
            <div class="table-responsive">
                <?php if (empty($contrats)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Aucun contrat trouvé. <a href="ajouter_contrat.php">Cliquez ici</a> pour ajouter un contrat.
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° Contrat</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Prime mensuelle</th>
                                <th>Statut</th>
                                <th>Souscription</th>
                                <th>Échéance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contrats as $contrat): ?>
                                <?php
                                $jours_restants = (strtotime($contrat['date_echeance']) - time()) / (60 * 60 * 24);
                                $classe_echeance = '';
                                if ($contrat['statut'] == 'actif' && $jours_restants < 30) {
                                    $classe_echeance = $jours_restants < 7 ? 'alerte-urgente' : ($jours_restants < 15 ? 'alerte-moyenne' : 'alerte-faible');
                                }
                                ?>
                                <tr class="<?= $classe_echeance ?>">
                                    <td><strong><?= htmlspecialchars($contrat['numero_contrat']) ?></strong></td>
                                    <td><?= htmlspecialchars($contrat['prenom_client'] ?? '') . ' ' . htmlspecialchars($contrat['nom_client'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $contrat['type_contrat'] ?>">
                                            <?= ucfirst($contrat['type_contrat']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= number_format($contrat['prime_mensuelle'], 2) ?> MAD</strong><br><small>/mois</small></td>
                                    <td>
                                        <span class="statut statut-<?= $contrat['statut'] ?>">
                                            <?= $contrat['statut'] == 'en_attente' ? 'En attente' : ucfirst($contrat['statut']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($contrat['date_souscription'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($contrat['date_echeance'])) ?>
                                        <?php if ($contrat['statut'] == 'actif' && $jours_restants < 30): ?>
                                            <br><small class="text-warning"><?= round($jours_restants) ?> jours restants</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="voir_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                                        <a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>