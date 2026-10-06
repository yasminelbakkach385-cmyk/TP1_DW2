<?php
require __DIR__ . '/config.php';

function h($t) {
    return htmlspecialchars(trim($t ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';

// ---------- Publication d'une offre ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entreprise  = trim($_POST['entreprise'] ?? '');
    $intitule    = trim($_POST['intitule'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dateLimite  = trim($_POST['date_limite'] ?? '');
    $dateLimite  = $dateLimite !== '' ? $dateLimite : null;

    if ($entreprise === '' || $intitule === '') {
        $message = "L'entreprise et l'intitulé sont obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                'INSERT INTO offre (entreprise, intitule, description, date_limite) VALUES (?,?,?,?)'
            );
            $st->execute([$entreprise, $intitule, $description, $dateLimite]);
            $offreId = $pdo->lastInsertId();

            // "PHP, MySQL, Java" -> une ligne par compétence
            $ins = $pdo->prepare('INSERT INTO offre_competence (offre_id, libelle) VALUES (?,?)');
            foreach (explode(',', $_POST['competences'] ?? '') as $libelle) {
                $libelle = trim($libelle);
                if ($libelle === '') continue;
                $ins->execute([$offreId, $libelle]);
            }

            $pdo->commit();
            $message = 'Offre publiée.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Erreur : " . h($e->getMessage());
        }
    }
}

// ---------- Liste des offres ----------
$offres = $pdo->query(
    'SELECT o.*, (SELECT COUNT(*) FROM candidature c WHERE c.offre_id = o.id) AS nb
     FROM offre o ORDER BY o.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Espace entreprise</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

  <h1>Espace entreprise</h1>
  <p class="sous">Publiez une offre de stage et consultez les candidats classés.</p>

  <?php if ($message): ?>
    <p><b><?= h($message) ?></b></p>
  <?php endif; ?>

  <form method="post">
    <h2>Nouvelle offre</h2>

    <label>Entreprise *</label>
    <input type="text" name="entreprise" maxlength="100" required>

    <label>Intitulé du stage *</label>
    <input type="text" name="intitule" maxlength="150" required>

    <label>Description</label>
    <textarea name="description"></textarea>

    <label>Compétences demandées</label>
    <input type="text" name="competences" placeholder="PHP, MySQL, Java">

    <label>Date limite</label>
    <input type="date" name="date_limite">

    <div class="actions">
      <button type="submit" class="principal">Publier l'offre</button>
    </div>
  </form>

  <h2>Offres publiées</h2>
  <?php if (!$offres): ?>
    <p class="aide">Aucune offre pour le moment.</p>
  <?php endif; ?>
  <?php foreach ($offres as $o): ?>
    <p>
      <b><?= h($o['intitule']) ?></b> - <?= h($o['entreprise']) ?>
      (<?= (int)$o['nb'] ?> candidature(s))
      <a href="candidats.php?id=<?= (int)$o['id'] ?>">Voir les candidats</a>
    </p>
  <?php endforeach; ?>

  <p><a href="postuler.php">Page de candidature (étudiants)</a></p>
</div>
</body>
</html>