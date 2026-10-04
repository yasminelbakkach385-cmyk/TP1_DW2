<?php
ob_start();   // met de côté tout affichage parasite, pour ne pas corrompre le PDF

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ---------- Petites fonctions utiles ----------

// Sécurise le texte avant de l'afficher dans le HTML
function h($texte) {
    return htmlspecialchars(trim($texte ?? ''), ENT_QUOTES, 'UTF-8');
}

// "2023-03-01" (format DATE de MySQL) -> "03/2023"
function moisAnnee($valeur) {
    if (!$valeur) return '';
    return date('m/Y', strtotime($valeur));
}

// Lit toutes les lignes d'une table pour un e-mail (le nom de table vient d'ici, jamais de l'utilisateur)
function lignes($pdo, $table, $email, $ordre) {
    $st = $pdo->prepare("SELECT * FROM $table WHERE email = ? ORDER BY $ordre");
    $st->execute([$email]);
    return $st->fetchAll();
}

// Découpe la photo en carré, puis la rend ronde -> renvoie une image base64
function photoRonde($fichier) {
    $type = getimagesize($fichier)[2];
    $src  = ($type == IMAGETYPE_PNG) ? imagecreatefrompng($fichier) : imagecreatefromjpeg($fichier);

    $w = imagesx($src); $h = imagesy($src); $c = min($w, $h);
    $carre = imagecrop($src, ['x' => (int)(($w - $c) / 2), 'y' => (int)(($h - $c) / 2), 'width' => $c, 'height' => $c]);

    $t = 300;
    $petit = imagecreatetruecolor($t, $t);
    imagecopyresampled($petit, $carre, 0, 0, 0, 0, $t, $t, $c, $c);

    $sortie = imagecreatetruecolor($t, $t);
    imagealphablending($sortie, false);
    imagesavealpha($sortie, true);
    imagefill($sortie, 0, 0, imagecolorallocatealpha($sortie, 255, 255, 255, 127));
    $r = $t / 2;
    for ($x = 0; $x < $t; $x++) {
        for ($y = 0; $y < $t; $y++) {
            if (($x - $r) ** 2 + ($y - $r) ** 2 <= $r ** 2) {
                imagesetpixel($sortie, $x, $y, imagecolorat($petit, $x, $y));
            }
        }
    }

    ob_start();
    imagepng($sortie);
    $png = ob_get_clean();
    return 'data:image/png;base64,' . base64_encode($png);
}

// ---------- 1. Récupérer les données dans la base ----------
$email = trim($_GET['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('E-mail invalide.');
}

$st = $pdo->prepare('SELECT * FROM utilisateur WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch();
if (!$u) {
    exit('Aucun CV enregistré pour cet e-mail.');
}

$formations = lignes($pdo, 'formation',      $email, 'date_debut DESC');
$stages     = lignes($pdo, 'stage',          $email, 'date_debut DESC');
$comps      = lignes($pdo, 'competence',     $email, 'id');
$langues    = lignes($pdo, 'langue',         $email, 'id');
$interets   = lignes($pdo, 'centre_interet', $email, 'id');
$projets     = lignes($pdo, 'projet',         $email, 'id');
$softs       = lignes($pdo, 'soft_skill',     $email, 'id');

$prenom = h($u['prenom']);
$nom    = h($u['nom']);
$poste  = h($u['poste']);
$profil = h($u['profil']);

// Photo ronde (si elle existe sur le disque)
$photo = '';
if ($u['photo'] && is_file(__DIR__ . '/uploads/' . $u['photo'])) {
    $photo = photoRonde(__DIR__ . '/uploads/' . $u['photo']);
}

// ---------- 2. Construire les blocs HTML ----------

// Formations
$htmlFor = '';
foreach ($formations as $f) {
    $htmlFor .= '<div class="item">'
        . '<div class="titre">' . h($f['intitule']) . '</div>'
        . '<div class="lien">' . h($f['etablissement']) . '</div>'
        . '<div class="date">' . moisAnnee($f['date_debut']) . ' - ' . moisAnnee($f['date_fin']) . '</div>'
        . '</div>';
}

// Stages
$htmlExp = '';
foreach ($stages as $s) {
    $fin = $s['date_fin'] ? moisAnnee($s['date_fin']) : "Aujourd'hui";
    $htmlExp .= '<div class="item">'
        . '<div class="titre">' . h($s['poste'] ?: $s['entreprise']) . '</div>'
        . '<div class="lien">' . h($s['entreprise']) . ($s['lieu'] ? ', ' . h($s['lieu']) : '') . '</div>'
        . '<div class="date">' . moisAnnee($s['date_debut']) . ' - ' . $fin . '</div>'
        . '<div class="texte">' . nl2br(h($s['description'])) . '</div>'
        . '</div>';
}

// Compétences, langues, centres d'intérêt
$htmlComp = '';
foreach ($comps as $c) {
    $htmlComp .= '<li>' . h($c['libelle']) . ' <span class="niveau">(' . h($c['niveau']) . ')</span></li>';
}
$htmlLang = '';
foreach ($langues as $l) {
    $htmlLang .= '<li>' . h($l['langue']) . ' <span class="niveau">(' . h($l['niveau']) . ')</span></li>';
}
$htmlInt = '';
foreach ($interets as $i) {
    $htmlInt .= '<li>' . h($i['libelle']) . '</li>';
}

// Soft skills et projets académiques
$htmlSoft = '';
foreach ($softs as $s) {
    $htmlSoft .= '<li>' . h($s['libelle']) . '</li>';
}
$htmlProj = '';
foreach ($projets as $p) {
    $htmlProj .= '<li><b>' . h($p['titre']) . '</b><br>' . h($p['description']) . '</li>';
}

// Ligne de contact
$contact = array_filter([h($u['email']), h($u['telephone']), h($u['adresse']), h($u['linkedin'])]);
$htmlContact = implode(' &nbsp;|&nbsp; ', $contact);

// Affiche une section seulement si elle n'est pas vide
function section($titre, $contenu) {
    if (trim($contenu) === '') return '';
    return '<h2>' . $titre . '</h2>' . $contenu;
}

// ---------- 3. Le HTML du CV ----------
$html = '
<html><head><meta charset="UTF-8">
<style>
  @page { margin: 30px 38px; }
  body { font-family: "DejaVu Serif", serif; font-size: 10px; color: #222; }
  .entete td { vertical-align: middle; }
  .nom   { font-size: 24px; letter-spacing: 1px; text-transform: uppercase; margin: 0; }
  .poste { color: #2b2bb0; font-size: 12px; margin: 4px 0; }
  .contact { font-size: 9px; color: #2b2bb0; }
  .photo img { width: 85px; height: 85px; }
  h2 { font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 2px; margin: 16px 0 8px; }
  .item { margin-bottom: 9px; }
  .titre { font-weight: bold; font-size: 11px; }
  .lien  { color: #2b2bb0; }
  .date  { color: #555; font-size: 9px; }
  .texte { margin-top: 3px; }
  ul { margin: 0; padding-left: 14px; }
  li { margin-bottom: 4px; }
  .niveau { color: #666; }
  .gauche { width: 63%; vertical-align: top; padding-right: 18px; }
  .droite { width: 37%; vertical-align: top; }
</style></head>
<body>

<table class="entete" width="100%"><tr>
  <td>
    <p class="nom">' . $prenom . ' ' . $nom . '</p>
    <p class="poste">' . $poste . '</p>
    <p class="contact">' . $htmlContact . '</p>
  </td>
  <td class="photo" align="right" width="95">' . ($photo ? '<img src="' . $photo . '">' : '') . '</td>
</tr></table>

<table width="100%"><tr>
  <td class="gauche">'
    . section('Formations', $htmlFor)
    . section('Expériences professionnelles', $htmlExp) . '
  </td>
  <td class="droite">'
    . section('Profil', $profil !== '' ? '<div class="texte">' . nl2br($profil) . '</div>' : '')
    . section('Compétences', $htmlComp ? '<ul>' . $htmlComp . '</ul>' : '')
    . section('Langues', $htmlLang ? '<ul>' . $htmlLang . '</ul>' : '')
    . section('Soft skills', $htmlSoft ? '<ul>' . $htmlSoft . '</ul>' : '')
    . section('Projets académiques', $htmlProj ? '<ul>' . $htmlProj . '</ul>' : '')
    . section("Centres d'intérêt", $htmlInt ? '<ul>' . $htmlInt . '</ul>' : '') . '
  </td>
</tr></table>

</body></html>';

// ---------- 4. HTML -> PDF -> téléchargement ----------
$options = new Options();
$options->set('defaultFont', 'DejaVu Serif');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// On jette tout affichage parasite avant d'envoyer le PDF
ob_end_clean();

$nomFichier = preg_replace('/[^A-Za-z0-9_-]/', '_', $u['nom']);
$dompdf->stream('CV_' . $nomFichier . '.pdf', ['Attachment' => true]);