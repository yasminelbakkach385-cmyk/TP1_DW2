<?php
require __DIR__ . '/config.php';

function h($t) {
    return htmlspecialchars(trim($t ?? ''), ENT_QUOTES, 'UTF-8');
}

// Offres encore ouvertes (pas de date limite, ou date limite pas dépassée)
$offres = $pdo->query(
    "SELECT o.*,
            (SELECT GROUP_CONCAT(oc.libelle SEPARATOR ', ')
             FROM offre_competence oc WHERE oc.offre_id = o.id) AS competences
     FROM offre o
     WHERE o.date_limite IS NULL OR o.date_limite >= CURDATE()
     ORDER BY o.id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offres de stage</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

  <h1>Offres de stage</h1>
  <p class="sous">Choisissez une offre, puis postulez avec l'e-mail de votre CV.</p>

  <?php if (!$offres): ?>
    <p class="aide">Aucune offre ouverte pour le moment.</p>
  <?php endif; ?>

  <?php foreach ($offres as $o): ?>
    <div class="bloc">
      <div><b><?= h($o['intitule']) ?></b> - <?= h($o['entreprise']) ?></div>
      <?php if ($o['description']): ?>
        <p><?= nl2br(h($o['description'])) ?></p>
      <?php endif; ?>
      <p class="aide">
        Compétences demandées : <?= $o['competences'] ? h($o['competences']) : 'non précisées' ?><br>
        Date limite : <?= $o['date_limite'] ? h($o['date_limite']) : 'aucune' ?>
      </p>
      <a href="postuler.php?offre=<?= (int)$o['id'] ?>">Postuler à cette offre</a>
    </div>
  <?php endforeach; ?>

  <p><a href="form.php">Créer ou modifier mon CV</a></p>
</div>
</body>
</html>