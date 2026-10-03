<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atelier CV</title>
<style>
  :root { --bleu:#1f3a5f; --accent:#2b6a9e; --fond:#eef1f6; --bord:#d5dbe5; --pale:#f6f8fb; }
  * { box-sizing:border-box; }
  body { font-family:"Inter",Arial,sans-serif; background:var(--fond); margin:0; padding:24px; color:var(--bleu); }
  .page { max-width:860px; margin:auto; background:#fff; border-radius:14px; padding:28px; }
  h1 { margin:0 0 4px; font-size:26px; }
  .sous { color:#6b7a90; font-size:14px; margin:0 0 24px; }
  h2 { font-size:17px; margin:30px 0 4px; }
  .aide { color:#6b7a90; font-size:12px; margin:0 0 12px; }
  label { display:block; font-size:12px; font-weight:600; margin:10px 0 4px; }
  input[type=text], input[type=email], input[type=month], select, textarea {
    width:100%; padding:10px 12px; border:1px solid var(--bord); border-radius:8px; font:inherit; font-size:14px; background:#fff;
  }
  textarea { min-height:70px; resize:vertical; }
  .grille { display:grid; grid-template-columns:1fr 1fr; gap:0 16px; }
  .perso { display:grid; grid-template-columns:1fr 170px; gap:20px; }
  .photo { border:1.5px dashed var(--bord); border-radius:10px; padding:14px; text-align:center; font-size:12px; color:#6b7a90; }
  .photo img { width:80px; height:80px; border-radius:50%; object-fit:cover; background:var(--pale); display:block; margin:0 auto 8px; }
  .bloc { background:var(--pale); border:1px solid var(--bord); border-radius:10px; padding:14px; margin-bottom:12px; position:relative; }
  .bloc .suppr { position:absolute; top:10px; right:12px; }
  button { font:inherit; cursor:pointer; }
  .suppr { background:none; border:none; color:#a33; font-size:18px; }
  .ajout { width:100%; padding:10px; border:1.5px dashed var(--bord); border-radius:8px; background:none; color:var(--accent); font-weight:600; margin-top:4px; }
  .ligne { display:grid; grid-template-columns:1fr 200px 30px; gap:10px; margin-bottom:8px; align-items:center; }
  .actions { margin-top:30px; padding-top:18px; border-top:1px solid var(--bord); text-align:right; }
  .principal { background:var(--bleu); color:#fff; border:none; padding:12px 22px; border-radius:8px; font-weight:600; }
  @media (max-width:640px) { .grille, .perso { grid-template-columns:1fr; } .ligne { grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="page">
  <h1>Construisez votre CV</h1>
  <p class="sous">Renseignez chaque rubrique, puis générez le PDF.</p>

  <!-- enctype obligatoire pour envoyer la photo -->
  <form action="genererCV.php" method="post" enctype="multipart/form-data">

    <!-- ===== INFORMATIONS PERSONNELLES ===== -->
    <h2>Informations personnelles</h2>
    <p class="aide">Vos coordonnées et votre positionnement</p>
    <div class="perso">
      <div>
        <div class="grille">
          <div><label>Prénom *</label><input type="text" name="prenom" required></div>
          <div><label>Nom *</label><input type="text" name="nom" required></div>
        </div>
        <label>Intitulé du poste *</label><input type="text" name="poste" required>
        <div class="grille">
          <div><label>E-mail * (identifiant)</label><input type="email" name="email" required></div>
          <div><label>Téléphone</label><input type="text" name="telephone"></div>
          <div><label>Ville</label><input type="text" name="ville"></div>
          <div><label>LinkedIn</label><input type="text" name="linkedin"></div>
        </div>
      </div>
      <div>
        <label>Photo</label>
        <div class="photo">
          <img id="apercu" alt="">
          <input type="file" name="photo" accept="image/png, image/jpeg" required onchange="apercuPhoto(this)">
          JPG ou PNG · 5 Mo max
        </div>
      </div>
    </div>
    <label>Profil professionnel</label>
    <textarea name="profil"></textarea>

    <!-- ===== EXPÉRIENCES ===== -->
    <h2>Expériences professionnelles (stages inclus)</h2>
    <p class="aide">Présentez les missions les plus pertinentes</p>
    <div id="experiences"></div>
    <button type="button" class="ajout" onclick="ajouter('experiences','tpl-exp')">+ Ajouter une expérience</button>

    <!-- ===== FORMATIONS ===== -->
    <h2>Formations</h2>
    <p class="aide">Diplômes, certifications et spécialisations</p>
    <div id="formations"></div>
    <button type="button" class="ajout" onclick="ajouter('formations','tpl-form')">+ Ajouter une formation</button>

    <!-- ===== COMPÉTENCES ===== -->
    <h2>Compétences</h2>
    <p class="aide">Vos expertises techniques</p>
    <div id="competences"></div>
    <button type="button" class="ajout" onclick="ajouter('competences','tpl-comp')">+ Ajouter une compétence</button>

    <!-- ===== LANGUES ===== -->
    <h2>Langues</h2>
    <p class="aide">Niveau de maîtrise</p>
    <div id="langues"></div>
    <button type="button" class="ajout" onclick="ajouter('langues','tpl-lang')">+ Ajouter une langue</button>

    <!-- ===== PROJETS ACADÉMIQUES ===== -->
    <h2>Projets académiques</h2>
    <p class="aide">Optionnel</p>
    <div id="projets"></div>
    <button type="button" class="ajout" onclick="ajouter('projets','tpl-proj')">+ Ajouter un projet</button>

    <!-- ===== SOFT SKILLS + INTÉRÊTS ===== -->
    <h2>Soft skills et centres d'intérêt</h2>
    <p class="aide">Séparez les éléments par des virgules</p>
    <label>Soft skills</label>
    <input type="text" name="soft_skills" placeholder="Travail en équipe, Esprit analytique">
    <label>Centres d'intérêt</label>
    <input type="text" name="interets" placeholder="Robotique, Course à pied, Photographie">

    <div class="actions">
      <button type="submit" class="principal">Générer le CV PDF</button>
    </div>
  </form>
</div>

<!-- ===== MODÈLES (copiés par le JavaScript à chaque clic sur "Ajouter") ===== -->
<template id="tpl-exp">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <div class="grille">
      <div><label>Poste</label><input type="text" name="exp_poste[]"></div>
      <div><label>Entreprise</label><input type="text" name="exp_entreprise[]"></div>
      <div><label>Début</label><input type="month" name="exp_debut[]"></div>
      <div><label>Fin (vide = aujourd'hui)</label><input type="month" name="exp_fin[]"></div>
    </div>
    <label>Lieu</label><input type="text" name="exp_lieu[]">
    <label>Missions et résultats</label><textarea name="exp_missions[]"></textarea>
  </div>
</template>

<template id="tpl-form">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <div class="grille">
      <div><label>Diplôme</label><input type="text" name="for_diplome[]"></div>
      <div><label>Établissement</label><input type="text" name="for_etablissement[]"></div>
      <div><label>Début (année)</label><input type="text" name="for_debut[]" placeholder="2024"></div>
      <div><label>Fin (année)</label><input type="text" name="for_fin[]" placeholder="2027"></div>
    </div>
  </div>
</template>

<template id="tpl-comp">
  <div class="ligne">
    <input type="text" name="comp_nom[]" placeholder="Ex : Java">
    <select name="comp_niveau[]">
      <option>Débutant</option><option>Intermédiaire</option><option>Avancé</option><option>Expert</option>
    </select>
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
  </div>
</template>

<template id="tpl-lang">
  <div class="ligne">
    <input type="text" name="lang_nom[]" placeholder="Ex : Français">
    <select name="lang_niveau[]">
      <option>Langue maternelle</option><option>Bilingue</option><option>C1</option><option>B2</option><option>B1</option><option>A2</option>
    </select>
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
  </div>
</template>

<template id="tpl-proj">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <label>Titre</label><input type="text" name="proj_titre[]">
    <label>Description</label><textarea name="proj_desc[]"></textarea>
  </div>
</template>

<script>
// Copie un modèle <template> dans la zone choisie
function ajouter(idZone, idModele) {
  var modele = document.getElementById(idModele);
  document.getElementById(idZone).appendChild(modele.content.cloneNode(true));
}
// Supprime le bloc qui contient le bouton ✕
function supprimer(bouton) {
  var bloc = bouton.closest('.bloc') || bouton.closest('.ligne');
  bloc.remove();
}
// Aperçu de la photo choisie
function apercuPhoto(input) {
  if (input.files && input.files[0]) {
    document.getElementById('apercu').src = URL.createObjectURL(input.files[0]);
  }
}
// Une ligne de chaque au départ
ajouter('experiences', 'tpl-exp');
ajouter('formations', 'tpl-form');
ajouter('competences', 'tpl-comp');
ajouter('langues', 'tpl-lang');
</script>
</body>
</html>