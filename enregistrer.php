<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

// Lit un champ texte : valeur par défaut '' et espaces enlevés
function champ($nom) {
    return trim($_POST[$nom] ?? '');
}

// "2023-03" -> "2023-03-01" (format DATE de MySQL), vide -> NULL
function dateMois($v) {
    $v = trim($v ?? '');
    return preg_match('/^\d{4}-\d{2}$/', $v) ? $v . '-01' : null;
}

$prenom    = champ('prenom');
$nom       = champ('nom');
$poste     = champ('poste');
$email     = champ('email');
$telephone = champ('telephone');
$adresse   = champ('adresse');
$linkedin  = champ('linkedin');
$profil    = champ('profil');
$interets  = champ('interets');

// ---------- 1. Validation ----------
$erreurs = [];
if ($prenom === '' || $nom === '' || $poste === '') {
    $erreurs[] = "Le prénom, le nom et l'intitulé du poste sont obligatoires.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = "L'adresse e-mail n'est pas valide.";
}

// Photo (facultative) : on la contrôle ici, on la déplace plus bas
$photoNom = null;
$photo = $_FILES['photo'] ?? null;
if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
    $info = getimagesize($photo['tmp_name']);   // false si ce n'est pas une image
    if ($photo['size'] > 5 * 1024 * 1024) {
        $erreurs[] = "La photo dépasse 5 Mo.";
    } elseif ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
        $erreurs[] = "La photo doit être au format JPG ou PNG.";
    } else {
        $photoNom = md5($email) . ($info[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
    }
}

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p style="color:red">' . htmlspecialchars($e) . '</p>';
    }
    echo '<p><a href="form.php">← Retour au formulaire</a></p>';
    exit;
}

// ---------- 2. Enregistrement de la photo ----------
if ($photoNom !== null) {
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads');
    }
    move_uploaded_file($photo['tmp_name'], __DIR__ . '/uploads/' . $photoNom);
}

// ---------- 3. Écriture en base, en une transaction ----------
try {
    $pdo->beginTransaction();

    // La ligne principale : INSERT, ou UPDATE si l'e-mail existe déjà
    $st = $pdo->prepare(
        'INSERT INTO utilisateur (email, nom, prenom, poste, telephone, adresse, linkedin, profil, photo)
         VALUES (?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            nom=VALUES(nom), prenom=VALUES(prenom), poste=VALUES(poste),
            telephone=VALUES(telephone), adresse=VALUES(adresse),
            linkedin=VALUES(linkedin), profil=VALUES(profil),
            photo=COALESCE(VALUES(photo), photo)'
    );
    $st->execute([$email, $nom, $prenom, $poste, $telephone, $adresse, $linkedin, $profil, $photoNom]);

    // On efface les anciennes lignes liées, puis on réinsère celles du formulaire
    foreach (['formation', 'stage', 'competence', 'langue', 'projet', 'soft_skill', 'centre_interet'] as $table) {
        $pdo->prepare("DELETE FROM $table WHERE email = ?")->execute([$email]);
    }

    // Formations
    $ins = $pdo->prepare(
        'INSERT INTO formation (email, intitule, etablissement, date_debut, date_fin) VALUES (?,?,?,?,?)'
    );
    foreach ($_POST['for_intitule'] ?? [] as $i => $intitule) {
        $intitule = trim($intitule);
        if ($intitule === '') continue;
        $ins->execute([
            $email,
            $intitule,
            trim($_POST['for_etablissement'][$i] ?? ''),
            dateMois($_POST['for_debut'][$i] ?? ''),
            dateMois($_POST['for_fin'][$i] ?? ''),
        ]);
    }

    // Expériences professionnelles (stages inclus) -> table stage
    $ins = $pdo->prepare(
        'INSERT INTO stage (email, entreprise, poste, lieu, date_debut, date_fin, description)
         VALUES (?,?,?,?,?,?,?)'
    );
    foreach ($_POST['exp_poste'] ?? [] as $i => $poste_exp) {
        $poste_exp  = trim($poste_exp);
        $entreprise = trim($_POST['exp_entreprise'][$i] ?? '');
        if ($poste_exp === '' && $entreprise === '') continue;   // bloc vide : ignoré
        $ins->execute([
            $email,
            $entreprise,
            $poste_exp,
            trim($_POST['exp_lieu'][$i] ?? ''),
            dateMois($_POST['exp_debut'][$i] ?? ''),
            dateMois($_POST['exp_fin'][$i] ?? ''),
            trim($_POST['exp_missions'][$i] ?? ''),
        ]);
    }

    // Compétences
    $ins = $pdo->prepare('INSERT INTO competence (email, libelle, niveau) VALUES (?,?,?)');
    foreach ($_POST['comp_libelle'] ?? [] as $i => $libelle) {
        $libelle = trim($libelle);
        if ($libelle === '') continue;
        $ins->execute([$email, $libelle, trim($_POST['comp_niveau'][$i] ?? '')]);
    }

    // Langues
    $ins = $pdo->prepare('INSERT INTO langue (email, langue, niveau) VALUES (?,?,?)');
    foreach ($_POST['lang_nom'] ?? [] as $i => $langue) {
        $langue = trim($langue);
        if ($langue === '') continue;
        $ins->execute([$email, $langue, trim($_POST['lang_niveau'][$i] ?? '')]);
    }

    // Projets académiques
    $ins = $pdo->prepare('INSERT INTO projet (email, titre, description) VALUES (?,?,?)');
    foreach ($_POST['proj_titre'] ?? [] as $i => $titre) {
        $titre = trim($titre);
        if ($titre === '') continue;
        $ins->execute([$email, $titre, trim($_POST['proj_desc'][$i] ?? '')]);
    }

    // Soft skills : "a, b, c" -> trois lignes
    $ins = $pdo->prepare('INSERT INTO soft_skill (email, libelle) VALUES (?,?)');
    foreach (explode(',', champ('soft_skills')) as $libelle) {
        $libelle = trim($libelle);
        if ($libelle === '') continue;
        $ins->execute([$email, $libelle]);
    }

    // Centres d'intérêt : "a, b, c" -> trois lignes
    $ins = $pdo->prepare('INSERT INTO centre_interet (email, libelle) VALUES (?,?)');
    foreach (explode(',', $interets) as $libelle) {
        $libelle = trim($libelle);
        if ($libelle === '') continue;
        $ins->execute([$email, $libelle]);
    }

    $pdo->commit();   // tout s'est bien passé : on valide

} catch (PDOException $e) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();   // une erreur : on annule tout
        }
    } catch (PDOException $e2) {
        // la connexion est déjà perdue : on ignore
    }
    exit("Erreur lors de l'enregistrement : " . htmlspecialchars($e->getMessage()));
}

// ---------- 4. Génération du CV ----------
header('Location: cv.php?email=' . urlencode($email));
exit;