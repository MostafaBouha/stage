<?php
require_once 'config.php';

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Requête avec recherche
if ($search) {
    $stmt = $pdo->prepare("
        SELECT * FROM clients 
        WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ?
        ORDER BY nom, prenom
    ");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY nom, prenom");
}
$clients = $stmt->fetchAll();

// Statistiques
$stats = getStats($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Clients - CONFORT ASSURANCES SARL</title>
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
                <a href="clients.php" class="active"><i class="fas fa-users"></i> Clients</a>
                <a href="contrats.php"><i class="fas fa-file-signature"></i> Contrats</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="container-header">
            <h1><i class="fas fa-user-friends"></i> Gestion des Clients</h1>
            <p>Consultez, ajoutez et gérez l'ensemble de vos clients</p>
        </div>
        <div class="container-content">
            <!-- Statistiques rapides -->
            <div class="quick-stats">
                <div class="quick-stat">
                    <i class="fas fa-users"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= $stats['total_clients'] ?></span>
                        <span class="quick-stat-label">Clients</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-file-contract"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= $stats['total_contrats_actifs'] ?></span>
                        <span class="quick-stat-label">Contrats actifs</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="quick-stat-info">
                        <span class="quick-stat-value"><?= number_format($stats['ca_mensuel'], 0) ?> MAD</span>
                        <span class="quick-stat-label">CA mensuel</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <form method="GET" style="flex: 1; display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Rechercher un client (nom, prénom, email, téléphone)" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Rechercher</button>
                        <?php if ($search): ?>
                            <a href="clients.php" class="btn btn-secondary"><i class="fas fa-times"></i> Réinitialiser</a>
                        <?php endif; ?>
                    </form>
                </div>
                <a href="ajouter_client.php" class="btn btn-success"><i class="fas fa-plus"></i> Nouveau Client</a>
            </div>

            <!-- Liste des clients -->
            <div class="table-responsive">
                <?php if (empty($clients)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Aucun client trouvé. <a href="ajouter_client.php">Cliquez ici</a> pour ajouter votre premier client.
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Client</th>
                                <th><i class="fas fa-phone"></i> Téléphone</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-map-marker-alt"></i> Adresse</th>
                                <th><i class="fas fa-calendar-alt"></i> Période assurance</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="client-name">
                                    <strong><?= htmlspecialchars($client['prenom']) ?> <?= htmlspecialchars($client['nom']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($client['telephone'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($client['email'] ?: '-') ?></td>
                                <td class="client-address"><?= htmlspecialchars(substr($client['adresse'] ?? '', 0, 50)) ?><?= strlen($client['adresse'] ?? '') > 50 ? '...' : '' ?></td>
                                <td>
                                    <?php if ($client['date_debut']): ?>
                                        <?= date('d/m/Y', strtotime($client['date_debut'])) ?> → <?= $client['date_fin'] ? date('d/m/Y', strtotime($client['date_fin'])) : 'En cours' ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non renseignée</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="voir_client.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="modifier_client.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="supprimer_client.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')" title="Supprimer"><i class="fas fa-trash"></i></a>
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