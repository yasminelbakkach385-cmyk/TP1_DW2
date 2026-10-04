<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atelier CV</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <h1>Construisez votre CV</h1>
  <p class="sous">Renseignez chaque rubrique, enregistrez, puis générez le PDF.</p>

  <form action="enregistrer.php" method="post" enctype="multipart/form-data">

    <h2>Informations personnelles</h2>
    <p class="aide">Vos coordonnées et votre positionnement</p>

    <div class="perso">

      <!-- Colonne de gauche : les champs -->
      <div>
        <div class="grille">
          <div>
            <label>Prénom *</label>
            <input type="text" name="prenom" maxlength="50" required>
          </div>
          <div>
            <label>Nom *</label>
            <input type="text" name="nom" maxlength="50" required>
          </div>
        </div>

        <label>Intitulé du poste *</label>
        <input type="text" name="poste" maxlength="100" required>

        <div class="grille">
          <div>
            <label>E-mail * (identifiant)</label>
            <input type="email" name="email" maxlength="100" required>
          </div>
          <div>
            <label>Téléphone</label>
            <input type="text" name="telephone" maxlength="20">
          </div>
          <div>
            <label>Adresse</label>
            <input type="text" name="adresse" maxlength="255">
          </div>
          <div>
            <label>LinkedIn</label>
            <input type="text" name="linkedin" maxlength="255">
          </div>
        </div>
      </div>

      <!-- Colonne de droite : la photo -->
      <div>
        <label>Photo</label>
        <div class="photo">
          <img id="apercu" alt="Aperçu de la photo">
          <input type="file" name="photo" accept="image/png, image/jpeg" onchange="apercuPhoto(this)">
          JPG ou PNG · 5 Mo max
        </div>
      </div>

    </div>

    <label>Profil professionnel</label>
    <textarea name="profil"></textarea>

    <h2>Formations</h2>
    <p class="aide">Diplômes, certifications et spécialisations</p>
    <div id="formations"></div>
    <button type="button" class="ajout" onclick="ajouter('formations','tpl-form')">+ Ajouter une formation</button>

    <h2>Expériences professionnelles (stages inclus)</h2>
    <p class="aide">Présentez les missions les plus pertinentes</p>
    <div id="experiences"></div>
    <button type="button" class="ajout" onclick="ajouter('experiences','tpl-exp')">+ Ajouter une expérience</button>

    <h2>Compétences</h2>
    <p class="aide">Vos expertises techniques</p>
    <div id="competences"></div>
    <button type="button" class="ajout" onclick="ajouter('competences','tpl-comp')">+ Ajouter une compétence</button>

    <h2>Langues</h2>
    <p class="aide">Niveau de maîtrise</p>
    <div id="langues"></div>
    <button type="button" class="ajout" onclick="ajouter('langues','tpl-lang')">+ Ajouter une langue</button>

    <h2>Projets académiques</h2>
    <p class="aide">Optionnel</p>
    <div id="projets"></div>
    <button type="button" class="ajout" onclick="ajouter('projets','tpl-proj')">+ Ajouter un projet</button>

    <h2>Soft skills et centres d'intérêt</h2>
    <p class="aide">Séparez les éléments par des virgules</p>

    <label>Soft skills</label>
    <input type="text" name="soft_skills" placeholder="Travail en équipe, Esprit analytique">

    <label>Centres d'intérêt</label>
    <input type="text" name="interets" placeholder="Robotique, Course à pied, Photographie">

    <div class="actions">
      <button type="submit" name="action" value="generer" class="principal">Générer le CV PDF</button>
    </div>

  </form>
</div>

<!-- Modèle d'une formation -->
<template id="tpl-form">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <div class="grille">
      <div>
        <label>Diplôme / intitulé</label>
        <input type="text" name="for_intitule[]" maxlength="150">
      </div>
      <div>
        <label>Établissement</label>
        <input type="text" name="for_etablissement[]" maxlength="150">
      </div>
      <div>
        <label>Début</label>
        <input type="month" name="for_debut[]">
      </div>
      <div>
        <label>Fin</label>
        <input type="month" name="for_fin[]">
      </div>
    </div>
  </div>
</template>

<!-- Modèle d'une expérience professionnelle (stage inclus) -->
<template id="tpl-exp">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <div class="grille">
      <div>
        <label>Poste</label>
        <input type="text" name="exp_poste[]" maxlength="100">
      </div>
      <div>
        <label>Entreprise</label>
        <input type="text" name="exp_entreprise[]" maxlength="100">
      </div>
      <div>
        <label>Début</label>
        <input type="month" name="exp_debut[]">
      </div>
      <div>
        <label>Fin (vide = aujourd'hui)</label>
        <input type="month" name="exp_fin[]">
      </div>
    </div>
    <label>Lieu</label>
    <input type="text" name="exp_lieu[]" maxlength="100">
    <label>Missions et résultats</label>
    <textarea name="exp_missions[]"></textarea>
  </div>
</template>

<!-- Modèle d'une compétence -->
<template id="tpl-comp">
  <div class="ligne">
    <input type="text" name="comp_libelle[]" maxlength="100" placeholder="Ex : Java">
    <select name="comp_niveau[]">
      <option>Débutant</option>
      <option>Intermédiaire</option>
      <option>Avancé</option>
    </select>
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
  </div>
</template>

<!-- Modèle d'une langue -->
<template id="tpl-lang">
  <div class="ligne">
    <input type="text" name="lang_nom[]" maxlength="50" placeholder="Ex : Français">
    <select name="lang_niveau[]">
      <option>Notions</option>
      <option>A1</option>
      <option>A2</option>
      <option>B1</option>
      <option>B2</option>
      <option>C1</option>
      <option>C2</option>
      <option>Courant</option>
      <option>Langue maternelle</option>
    </select>
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
  </div>
</template>

<!-- Modèle d'un projet académique -->
<template id="tpl-proj">
  <div class="bloc">
    <button type="button" class="suppr" onclick="supprimer(this)">✕</button>
    <label>Titre du projet</label>
    <input type="text" name="proj_titre[]" maxlength="150">
    <label>Description</label>
    <textarea name="proj_desc[]"></textarea>
  </div>
</template>

<script>
// Aperçu de la photo + contrôle de la taille (5 Mo)
function apercuPhoto(input) {
  var img = document.getElementById('apercu');
  if (input.files && input.files[0]) {
    if (input.files[0].size > 5 * 1024 * 1024) {
      alert('La photo dépasse 5 Mo.');
      input.value = '';
      img.style.display = 'none';
      return;
    }
    img.src = URL.createObjectURL(input.files[0]);
    img.style.display = 'block';
  }
}

// Copie un modèle <template> dans la zone choisie
function ajouter(idZone, idModele) {
  var modele = document.getElementById(idModele);
  document.getElementById(idZone).appendChild(modele.content.cloneNode(true));
}

// Supprime le bloc ou la ligne qui contient le bouton ✕
function supprimer(bouton) {
  var element = bouton.closest('.bloc') || bouton.closest('.ligne');
  element.remove();
}

// Un élément au départ (les projets académiques sont optionnels : aucun au départ)
ajouter('formations', 'tpl-form');
ajouter('experiences', 'tpl-exp');
ajouter('competences', 'tpl-comp');
ajouter('langues', 'tpl-lang');
</script>

</body>
</html>