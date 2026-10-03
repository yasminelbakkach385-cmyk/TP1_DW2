<?php
session_start();//Elle sert à garder les données pour que MODIFIER puisse les retrouver

$enregistre = false;

// ===== ZONE 1 : code de valider.php =====
if (isset($_POST['valider'])) {

    $d = $_SESSION['data'] ?? null;//recupere les donnees gardees en session

    // Si la session est vide, il n'y a rien a enregistrer
    if (!$d) {
        header('Location: formulaire.php');//Si $d est vide, il n'y a rien a enregistrer  On renvoie vers le formulaire.
        exit;
    }

    $txt  = "===== " . date('Y-m-d H:i:s') . " =====\n";
    $txt .= "Nom : " . ($d['nom'] ?? '') . "\n";
    $txt .= "Prénom : " . ($d['prenom'] ?? '') . "\n";
    $txt .= "Age : " . ($d['age'] ?? '') . "\n";
    $txt .= "Téléphone : " . ($d['telephone'] ?? '') . "\n";
    $txt .= "Email : " . ($d['email'] ?? '') . "\n";
    $txt .= "Filière : " . ($d['filiere'] ?? '') . "\n";
    $txt .= "Année : " . ($d['annee'] ?? '') . "\n";
    $txt .= "Modules : " . implode(', ', $d['Modules'] ?? []) . "\n";
    $txt .= "Nombre de projets : " . ($d['nombre_projets'] ?? '') . "\n";

    $types = $d['type'] ?? [];
    foreach ($types as $i => $t) {
        $txt .= "--- Projet/Stage " . ($i + 1) . " ---\n";
        $txt .= "Type : " . $t . "\n";
        $txt .= "Début : " . ($d['date_debut'][$i] ?? '') . "\n";
        $txt .= "Fin : " . ($d['date_fin'][$i] ?? '') . "\n";
        $txt .= "Lieu : " . ($d['lieu'][$i] ?? '') . "\n";
        $txt .= "Description : " . ($d['description'][$i] ?? '') . "\n";
    }

    $txt .= "Centres d'intérêt : " . ($d['interets'] ?? '') . "\n";
    $txt .= "Compétences : " . ($d['competences'] ?? '') . "\n";
    $txt .= "Langues : " . ($d['langues'] ?? '') . "\n";
    $txt .= "Remarques : " . ($d['remarques'] ?? '') . "\n";
    $txt .= "Fichier joint : " . (($d['fichier_nom'] ?? '') !== '' ? $d['fichier_nom'] : 'Aucun') . "\n\n";

    file_put_contents('informations.txt', $txt, FILE_APPEND | LOCK_EX);//FILE_APPEND : ajoute à la fin, sans effacer les anciennes fiches.
    //LOCK_EX verrouille le fichier pendant l'écriture, pour éviter que deux personnes écrivent en même temps et mélangent leurs données.
    unset($_SESSION['data']);//Supprime les donnees de la session, pour evite un double enregistrement.
    $enregistre = true;
}
// ===== FIN ZONE 1 =====

if (!$enregistre) {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {//si quelque un ouvrire directement recap.php dupuis son navigateur on le renvoie vers formulaire car il n'y a aucune donnees
        header('Location: formulaire.php');
        exit;
    }

    $_SESSION['data'] = $_POST;//copie tout $_POST dans la session
    $_SESSION['data']['fichier_nom'] = $_FILES['fichier']['name'] ?? '';

    $nom = $_POST['nom'];//variables pour l'affichage.
    $prenom = $_POST['prenom'];
    $age = $_POST['age'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $filiere = $_POST['filiere'] ?? '';
    $annee = $_POST['annee'] ?? '';
    $modules = $_POST['Modules'] ?? [];
    $nombre_projets = $_POST['nombre_projets'];
    $type = $_POST['type'] ?? [];
    $date_debut = $_POST['date_debut'] ?? [];
    $date_fin = $_POST['date_fin'] ?? [];
    $lieu = $_POST['lieu'] ?? [];
    $description = $_POST['description'] ?? [];
    $interets = $_POST['interets'];
    $competences = $_POST['competences'];
    $remarques = $_POST['remarques'];
    $fichier = $_FILES['fichier'] ?? null;
    $langues = $_POST['langues'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif</title>
</head>
<body>

<h1 style="text-align: center;">Récapitulatif</h1>

<?php if ($enregistre): ?>
<!-- ===== ZONE 2 : message apres VALIDER ===== -->

    <p style="text-align: center; color: green;"><strong>Vos informations ont été enregistrées dans informations.txt.</strong></p>
    <p style="text-align: center;"><a href="formulaire.php">Nouvelle fiche</a></p>

<?php else: ?>

    <fieldset>
        <legend>Renseignements Personnels</legend>
        <p><strong>Nom :</strong> <?php echo htmlspecialchars($nom); ?></p>
        <p><strong>Prénom :</strong> <?php echo htmlspecialchars($prenom); ?></p>
        <p><strong>Age :</strong> <?php echo htmlspecialchars($age); ?></p>
        <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($telephone); ?></p>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($email); ?></p>
    </fieldset>

    <fieldset>
        <legend>Renseignements Academique</legend>
        <p><strong>Filiere :</strong> <?php echo htmlspecialchars($filiere); ?></p>
        <p><strong>Annee :</strong> <?php echo htmlspecialchars($annee); ?></p>

        <p><strong>Modules suivis cette annee :</strong></p>
        <ul>
            <?php foreach ($modules as $m) { echo "<li>" . htmlspecialchars($m) . "</li>"; } ?>
        </ul>

        <p><strong>Nombre de projets realises cette annee :</strong>
            <?php echo htmlspecialchars($nombre_projets); ?></p>
    </fieldset>

    <fieldset>
        <legend>Projets et stages</legend>
        <?php
        for ($i = 0; $i < count($type); $i++) {
            echo "<h3>Projet / stage " . ($i + 1) . "</h3>";
            echo "<p><strong>Type :</strong> " . htmlspecialchars($type[$i]) . "</p>";
            echo "<p><strong>Date de début :</strong> " . htmlspecialchars($date_debut[$i]) . "</p>";
            echo "<p><strong>Date de fin :</strong> " . htmlspecialchars($date_fin[$i]) . "</p>";
            echo "<p><strong>Lieu :</strong> " . htmlspecialchars($lieu[$i]) . "</p>";
            echo "<p><strong>Description :</strong> " . htmlspecialchars($description[$i]) . "</p>";
            echo "<hr>";
        }
        ?>
    </fieldset>

    <fieldset>
        <legend>Centres d'intérêt</legend>
        <p><strong>Centres d'intérêt :</strong> <?php echo htmlspecialchars($interets); ?></p>
    </fieldset>

    <fieldset>
        <legend>Compétences et Langues</legend>
        <p><strong>Compétences :</strong> <?php echo htmlspecialchars($competences); ?></p>
        <p><strong>Langues :</strong> <?php echo htmlspecialchars($langues); ?></p>
    </fieldset>

    <fieldset>
        <legend>Vos remarques</legend>
        <p><strong>Remarques :</strong> <?php echo htmlspecialchars($remarques); ?></p>
    </fieldset>

    <fieldset>
        <legend>Fichier</legend>
        <?php
        if ($fichier && $fichier['error'] == 0) {
            echo "<p><strong>Nom du fichier :</strong> " . htmlspecialchars($fichier['name']) . "</p>";
            echo "<p><strong>Taille :</strong> " . htmlspecialchars($fichier['size']) . " octets</p>";
            echo "<p><strong>Type :</strong> " . htmlspecialchars($fichier['type']) . "</p>";
        } else {
            echo "<p>Aucun fichier sélectionné.</p>";
        }
        ?>
    </fieldset>

    <br>
    <!-- ===== ZONE 3 : VALIDER envoie vers recap.php ===== -->
    <form action="recap.php" method="POST" style="display:inline">
        <button type="submit" name="valider" value="1">VALIDER</button>
    </form>

    <form action="formulaire.php" method="GET" style="display:inline">
        <button type="submit">MODIFIER</button>
    </form>

<?php endif; ?>

</body>
</html>