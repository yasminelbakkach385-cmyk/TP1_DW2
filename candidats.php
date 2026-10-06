<?php
require __DIR__ . '/config.php';

function h($t) {
    return htmlspecialchars(trim($t ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare('SELECT * FROM offre WHERE id = ?');
$st->execute([$id]);
$offre = $st->fetch();
if (!$offre) {
    exit('Offre introuvable. <a href="offre.php">Retour</a>');
}

// Compétences demandées par l'offre
$st = $pdo->prepare('SELECT libelle FROM offre_competence WHERE offre_id = ?');
$st->execute([$id]);
$demandees = $st->fetchAll(PDO::FETCH_COLUMN);
$total = count($demandees);

// Candidats classés : score = nombre de compétences demandées que le candidat possède
$st = $pdo->prepare(
    'SELECT u.nom, u.prenom, u.email, ca.date_envoi, ca.statut,
            COUNT(DISTINCT oc.id) AS score
     FROM candidature ca
     JOIN utilisateur u ON u.email = ca.email
     LEFT JOIN competence c ON c.email = u.email
     LEFT JOIN offre_competence oc
            ON oc.offre_id = ca.offre_id AND LOWER(oc.libelle) = LOWER(c.libelle)
     WHERE ca.offre_id = ?
     GROUP BY u.email, u.nom, u.prenom, ca.date_envoi, ca.statut
     ORDER BY score DESC, ca.date_envoi ASC'
);
$st->execute([$id]);
$candidats = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Candidats</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

  <h1><?= h($offre['intitule']) ?></h1>
  <p class="sous"><?= h($offre['entreprise']) ?></p>
  <p class="aide">Compétences demandées : <?= $demandees ? h(implode(', ', $demandees)) : 'aucune' ?></p>

  <h2>Candidats classés</h2>
  <?php if (!$candidats): ?>
    <p class="aide">Aucune candidature pour le moment.</p>
  <?php else: ?>
    <table width="100%" border="1" cellpadding="6" style="border-collapse:collapse">
      <tr>
        <th>#</th><th>Candidat</th><th>E-mail</th><th>Score</th><th>Date</th><th>Statut</th><th>CV</th>
      </tr>
      <?php foreach ($candidats as $rang => $c): ?>
      <tr>
        <td><?= $rang + 1 ?></td>
        <td><?= h($c['prenom']) ?> <?= h($c['nom']) ?></td>
        <td><?= h($c['email']) ?></td>
        <td><?= (int)$c['score'] ?> / <?= $total ?></td>
        <td><?= h($c['date_envoi']) ?></td>
        <td><?= h($c['statut']) ?></td>
        <td><a href="cv.php?email=<?= urlencode($c['email']) ?>">PDF</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <p><a href="offre.php">← Retour aux offres</a></p>
</div>
</body>
</html>