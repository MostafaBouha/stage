<?php
require_once 'config.php';

// Vérifier si les tables existent
$table_contrats_exists = tableExists($pdo, 'contrats');
$table_clients_exists = tableExists($pdo, 'clients');

// Récupérer les statistiques détaillées
$stats = getStats($pdo);

// Contrats par type
$contrats_par_type = [];
if ($table_contrats_exists) {
    $stmt = $pdo->query("
        SELECT type_contrat, COUNT(*) as total, SUM(prime_mensuelle) as ca 
        FROM contrats WHERE statut = 'actif' GROUP BY type_contrat
    ");
    $contrats_par_type = $stmt->fetchAll();
}

// Contrats arrivant à échéance
$echeances = [];
if ($table_contrats_exists) {
    $echeances = $pdo->query("
        SELECT c.*, cl.nom, cl.prenom 
        FROM contrats c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        WHERE c.date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND c.statut = 'actif'
        ORDER BY c.date_echeance
    ")->fetchAll();
}

// Derniers clients ajoutés
$derniers_clients = [];
if ($table_clients_exists) {
    $derniers_clients = $pdo->query("
        SELECT * FROM clients ORDER BY id DESC LIMIT 5
    ")->fetchAll();
}

// Derniers contrats ajoutés
$derniers_contrats = [];
if ($table_contrats_exists) {
    $derniers_contrats = $pdo->query("
        SELECT c.*, cl.nom, cl.prenom 
        FROM contrats c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        ORDER BY c.created_at DESC LIMIT 5
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - CONFORT ASSURANCES SARL</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Accueil</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
                <a href="contrats.php"><i class="fas fa-file-signature"></i> Contrats</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="container-header">
            <h1><i class="fas fa-chart-pie"></i> Tableau de Bord</h1>
            <p>Vue d'ensemble et indicateurs clés de votre activité</p>
        </div>
        <div class="container-content">
            <!-- Cartes statistiques -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x"></i>
                    <h3><?= $stats['total_clients'] ?></h3>
                    <p>Clients totaux</p>
                    <small>Portefeuille client</small>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-contract fa-2x"></i>
                    <h3><?= $stats['total_contrats_actifs'] ?></h3>
                    <p>Contrats actifs</p>
                    <small>Taux d'actifs: <?= $stats['total_clients'] > 0 ? round(($stats['total_contrats_actifs'] / $stats['total_clients']) * 100) : 0 ?>%</small>
                </div>
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                    <h3><?= number_format($stats['ca_mensuel'], 2) ?> MAD</h3>
                    <p>Chiffre d'affaires mensuel</p>
                    <small>Prime moyenne: <?= $stats['total_contrats_actifs'] > 0 ? number_format($stats['ca_mensuel'] / $stats['total_contrats_actifs'], 2) : 0 ?> MAD</small>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock fa-2x"></i>
                    <h3><?= $stats['echeances_proches'] ?></h3>
                    <p>Échéances sous 30 jours</p>
                    <small>À surveiller</small>
                </div>
            </div>

            <!-- Graphiques et sections -->
            <div class="dashboard-grid">
                <?php if (!empty($contrats_par_type)): ?>
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3><i class="fas fa-chart-bar"></i> Contrats par type</h3>
                    </div>
                    <div class="dashboard-card-body">
                        <canvas id="contratsChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3><i class="fas fa-trophy"></i> Top 5 primes mensuelles</h3>
                    </div>
                    <div class="dashboard-card-body">
                        <?php
                        $top_primes = $pdo->query("
                            SELECT c.numero_contrat, c.prime_mensuelle, c.type_contrat, cl.nom, cl.prenom
                            FROM contrats c
                            LEFT JOIN clients cl ON c.client_id = cl.id
                            WHERE c.statut = 'actif'
                            ORDER BY c.prime_mensuelle DESC
                            LIMIT 5
                        ")->fetchAll();
                        ?>
                        <?php if (empty($top_primes)): ?>
                            <p class="text-muted">Aucun contrat actif</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($top_primes as $prime): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <strong><?= number_format($prime['prime_mensuelle'], 2) ?> MAD</strong>
                                        <span><?= htmlspecialchars($prime['prenom']) ?> <?= htmlspecialchars($prime['nom']) ?></span>
                                        <small><?= ucfirst($prime['type_contrat']) ?> - <?= $prime['numero_contrat'] ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Échéances à venir -->
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h3><i class="fas fa-bell"></i> Contrats arrivant à échéance (30 jours)</h3>
                    <a href="contrats.php" class="btn btn-sm btn-primary">Voir tous</a>
                </div>
                <?php if (empty($echeances)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Aucun contrat n'arrive à échéance dans les 30 prochains jours.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>N° Contrat</th>
                                    <th>Type</th>
                                    <th>Prime</th>
                                    <th>Échéance</th>
                                    <th>Jours restants</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($echeances as $contrat):
                                    $jours_restants = floor((strtotime($contrat['date_echeance']) - time()) / (60 * 60 * 24));
                                    $classe_alerte = $jours_restants < 7 ? 'alerte-urgente' : ($jours_restants < 15 ? 'alerte-moyenne' : 'alerte-faible');
                                ?>
                                <tr class="<?= $classe_alerte ?>">
                                    <td><strong><?= htmlspecialchars($contrat['prenom'] ?? '') ?> <?= htmlspecialchars($contrat['nom'] ?? 'N/A') ?></strong></td>
                                    <td><?= htmlspecialchars($contrat['numero_contrat']) ?></td>
                                    <td><?= ucfirst($contrat['type_contrat']) ?></td>
                                    <td><?= number_format($contrat['prime_mensuelle'], 2) ?> MAD</td>
                                    <td><?= date('d/m/Y', strtotime($contrat['date_echeance'])) ?></td>
                                    <td class="font-bold"><?= $jours_restants ?> jours</td>
                                    <td><a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Renouveler</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Derniers clients et contrats -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3><i class="fas fa-user-plus"></i> Derniers clients ajoutés</h3>
                        <a href="clients.php" class="btn btn-sm btn-primary">Voir tous</a>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (empty($derniers_clients)): ?>
                            <p class="text-muted">Aucun client pour le moment.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($derniers_clients as $client): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <strong><?= htmlspecialchars($client['prenom']) ?> <?= htmlspecialchars($client['nom']) ?></strong>
                                        <small><i class="fas fa-phone"></i> <?= htmlspecialchars($client['telephone'] ?: 'Non renseigné') ?></small>
                                        <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($client['email'] ?: 'Non renseigné') ?></small>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="voir_client.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3><i class="fas fa-file-signature"></i> Derniers contrats ajoutés</h3>
                        <a href="contrats.php" class="btn btn-sm btn-primary">Voir tous</a>
                    </div>
                    <div class="dashboard-card-body">
                        <?php if (empty($derniers_contrats)): ?>
                            <p class="text-muted">Aucun contrat pour le moment.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($derniers_contrats as $contrat): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <strong><?= htmlspecialchars($contrat['numero_contrat']) ?></strong>
                                        <span><?= htmlspecialchars($contrat['prenom'] ?? '') ?> <?= htmlspecialchars($contrat['nom'] ?? 'N/A') ?></span>
                                        <small><?= ucfirst($contrat['type_contrat']) ?> - <?= number_format($contrat['prime_mensuelle'], 2) ?> MAD</small>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="voir_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-actions">
                <a href="clients.php" class="btn btn-primary"><i class="fas fa-users"></i> Gérer les clients</a>
                <a href="contrats.php" class="btn btn-primary"><i class="fas fa-file-signature"></i> Gérer les contrats</a>
                <a href="ajouter_client.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Nouveau client</a>
                <a href="ajouter_contrat.php" class="btn btn-success"><i class="fas fa-plus"></i> Nouveau contrat</a>
            </div>
        </div>
    </div>

    <script>
    <?php if (!empty($contrats_par_type)): ?>
    const ctx = document.getElementById('contratsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($item) { return ucfirst($item['type_contrat']); }, $contrats_par_type)) ?>,
            datasets: [{
                label: 'Nombre de contrats',
                data: <?= json_encode(array_column($contrats_par_type, 'total')) ?>,
                backgroundColor: ['#1e3a5f', '#28a745', '#ffc107', '#17a2b8'],
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: false }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>