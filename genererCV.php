<?php
// Dompdf installé avec : composer require dompdf/dompdf
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ---------- Petites fonctions utiles ----------

// Sécurise le texte avant de l'afficher dans le HTML
function h($texte) {
    return htmlspecialchars(trim($texte ?? ''), ENT_QUOTES, 'UTF-8');
}

// "2023-03" -> "03/2023"
function moisAnnee($valeur) {
    if (!$valeur) return '';
    $p = explode('-', $valeur);
    return $p[1] . '/' . $p[0];
}

// Transforme "a, b, c" en tableau ['a','b','c'] sans éléments vides
function versListe($texte) {
    return array_filter(array_map('trim', explode(',', $texte ?? '')));
}

// Découpe la photo en carré, puis la rend ronde (fond transparent) -> renvoie une image base64
function photoRonde($fichier) {
    $type = getimagesize($fichier)[2];
    $src  = ($type == IMAGETYPE_PNG) ? imagecreatefrompng($fichier) : imagecreatefromjpeg($fichier);

    // 1. carré centré
    $w = imagesx($src); $h = imagesy($src); $c = min($w, $h);
    $carre = imagecrop($src, ['x' => (int)(($w - $c) / 2), 'y' => (int)(($h - $c) / 2), 'width' => $c, 'height' => $c]);

    // 2. redimensionner à 300x300
    $t = 300;
    $petit = imagecreatetruecolor($t, $t);
    imagecopyresampled($petit, $carre, 0, 0, 0, 0, $t, $t, $c, $c);

    // 3. ne garder que les pixels à l'intérieur du cercle
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

// ---------- 1. Récupérer les données ----------
$prenom   = h($_POST['prenom']);
$nom      = h($_POST['nom']);
$poste    = h($_POST['poste']);
$email    = h($_POST['email']);
$tel      = h($_POST['telephone']);
$ville    = h($_POST['ville']);
$linkedin = h($_POST['linkedin']);
$profil   = h($_POST['profil']);

$photo = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $photo = photoRonde($_FILES['photo']['tmp_name']);
}

// ---------- 2. Construire les blocs HTML ----------

// Expériences
$htmlExp = '';
$exp_poste = $_POST['exp_poste'] ?? [];
foreach ($exp_poste as $i => $p) {
    if (trim($p) === '') continue;
    $debut = moisAnnee($_POST['exp_debut'][$i]);
    $fin   = $_POST['exp_fin'][$i] ? moisAnnee($_POST['exp_fin'][$i]) : "Aujourd'hui";
    $htmlExp .= '<div class="item">'
        . '<div class="titre">' . h($p) . '</div>'
        . '<div class="lien">' . h($_POST['exp_entreprise'][$i]) . ($_POST['exp_lieu'][$i] ? ', ' . h($_POST['exp_lieu'][$i]) : '') . '</div>'
        . '<div class="date">' . $debut . ' - ' . $fin . '</div>'
        . '<div class="texte">' . nl2br(h($_POST['exp_missions'][$i])) . '</div>'
        . '</div>';
}

// Formations
$htmlFor = '';
foreach ($_POST['for_diplome'] ?? [] as $i => $d) {
    if (trim($d) === '') continue;
    $htmlFor .= '<div class="item">'
        . '<div class="titre">' . h($d) . '</div>'
        . '<div class="lien">' . h($_POST['for_etablissement'][$i]) . '</div>'
        . '<div class="date">' . h($_POST['for_debut'][$i]) . ' - ' . h($_POST['for_fin'][$i]) . '</div>'
        . '</div>';
}

// Compétences
$htmlComp = '';
foreach ($_POST['comp_nom'] ?? [] as $i => $n) {
    if (trim($n) === '') continue;
    $htmlComp .= '<li>' . h($n) . ' <span class="niveau">(' . h($_POST['comp_niveau'][$i]) . ')</span></li>';
}

// Langues
$htmlLang = '';
foreach ($_POST['lang_nom'] ?? [] as $i => $n) {
    if (trim($n) === '') continue;
    $htmlLang .= '<li>' . h($n) . ' <span class="niveau">(' . h($_POST['lang_niveau'][$i]) . ')</span></li>';
}

// Projets
$htmlProj = '';
foreach ($_POST['proj_titre'] ?? [] as $i => $t) {
    if (trim($t) === '') continue;
    $htmlProj .= '<li><b>' . h($t) . '</b><br>' . h($_POST['proj_desc'][$i]) . '</li>';
}

// Soft skills et intérêts
$htmlSoft = '';
foreach (versListe($_POST['soft_skills']) as $s) { $htmlSoft .= '<li>' . h($s) . '</li>'; }
$htmlInt = '';
foreach (versListe($_POST['interets']) as $s) { $htmlInt .= '<li>' . h($s) . '</li>'; }

// Ligne de contact en en-tête
$contact = array_filter([$email, $tel, $ville, $linkedin]);
$htmlContact = implode(' &nbsp;|&nbsp; ', $contact);

// Petite fonction : affiche une section seulement si elle n'est pas vide
function section($titre, $contenu) {
    if (trim($contenu) === '') return '';
    return '<h2>' . $titre . '</h2>' . $contenu;
}

// ---------- 3. Le HTML du CV (mis en page en 2 colonnes avec un tableau) ----------
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
    . section('Profil', '<div class="texte">' . nl2br($profil) . '</div>')
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
$dompdf->stream('CV_' . $nom . '.pdf', ['Attachment' => true]);