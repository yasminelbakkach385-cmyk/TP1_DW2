<?php
require __DIR__ . '/config.php';

function h($t) {
    return htmlspecialchars(trim($t ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email'] ?? '');
    $offreId = (int)($_POST['offre_id'] ?? 0);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $offreId <= 0) {
        $message = 'E-mail invalide ou offre non choisie.';
    } else {
        // L'étudiant doit avoir créé son CV (son email doit exister dans utilisateur)
        $st = $pdo->prepare('SELECT 1 FROM utilisateur WHERE email = ?');
        $st->execute([$email]);
        if (!$st->fetch()) {
            $message = "Aucun CV pour cet e-mail. <a href='form.php'>Créez d'abord votre CV</a>.";
        } else {
            // INSERT IGNORE : si la candidature existe déjà (UNIQUE), rien n'est ajouté
            $st = $pdo->prepare(
                'INSERT IGNORE INTO candidature (offre_id, email, date_envoi) VALUES (?,?,CURDATE())'
            );
            $st->execute([$offreId, $email]);
            $message = $st->rowCount() ? 'Candidature envoyée.' : 'Vous avez déjà postulé à cette offre.';
        }
    }
}

$offres = $pdo->query('SELECT id, intitule, entreprise FROM offre ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Postuler</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

  <h1>Postuler à un stage</h1>
  <p class="sous">Utilisez l'e-mail de votre CV : il sert d'identifiant.</p>

  <?php if ($message): ?>
    <p><b><?= $message ?></b></p>
  <?php endif; ?>

  <form method="post">
    <label>Offre *</label>
    <select name="offre_id" required>
      <option value="">-- Choisir --</option>
      <?php foreach ($offres as $o): ?>
        <option value="<?= (int)$o['id'] ?>"
          <?= ((int)($_GET['offre'] ?? $_POST['offre_id'] ?? 0) === (int)$o['id']) ? 'selected' : '' ?>>
          <?= h($o['intitule']) ?> - <?= h($o['entreprise']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Votre e-mail *</label>
    <input type="email" name="email" maxlength="100" required>

    <div class="actions">
      <button type="submit" class="principal">Postuler</button>
    </div>
  </form>

  <p><a href="form.php">Créer ou modifier mon CV</a></p>
</div>
</body>
</html>