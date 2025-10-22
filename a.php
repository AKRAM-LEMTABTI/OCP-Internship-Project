<?php
// Configuration de connexion
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "TRAINEES"; // ✅ Nom de ta base
$port = 3307;          // ✅ Ton port XAMPP

$conn = new mysqli($host, $user, $pass, $dbname, $port);

// Vérifie connexion
if ($conn->connect_error) {
    die("❌ Connexion échouée : " . $conn->connect_error);
}

// Vérifie si formulaire soumis en POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Récupère les champs, ou vide par défaut
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $niveau_etude = trim($_POST['niveau_etude'] ?? '');
    $periode_stage = trim($_POST['periode_stage'] ?? '');
    $nom_etablissement = trim($_POST['nom_etablissement'] ?? '');
    $filiere = trim($_POST['filiere'] ?? '');
    $lieu_stage = trim($_POST['lieu_stage'] ?? '');

    // Vérifie que rien n'est vide (sécurité côté serveur)
    if (
        empty($prenom) || empty($nom) || empty($telephone) || empty($date_naissance) ||
        empty($niveau_etude) || empty($periode_stage) || empty($nom_etablissement) ||
        empty($filiere) || empty($lieu_stage)
    ) {
        die("❌ Tous les champs sont obligatoires.");
    }

    // Prépare la requête sécurisée
    $stmt = $conn->prepare(
        "INSERT INTO STAGIARE 
         (prenom, nom, telephone, date_naissance, niveau_etude, periode_stage, nom_etablissement, filiere, lieu_stage) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        die("❌ Erreur préparation : " . $conn->error);
    }

    // Lie les paramètres
    $stmt->bind_param(
        "sssssssss",
        $prenom,
        $nom,
        $telephone,
        $date_naissance,
        $niveau_etude,
        $periode_stage,
        $nom_etablissement,
        $filiere,
        $lieu_stage
    );

    // Exécute
    if ($stmt->execute()) {
        echo "✅ Stagiaire enregistré avec succès.";
    } else {
        echo "❌ Erreur : " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription Stagiaire</title>
  <link rel="stylesheet" href="css/aa.css">
</head>
<body>
  <h1>Inscription Stagiaire</h1>
  <form action="enregistrer_stagiaire.php" method="post">

    <!-- Section Infos personnelles -->
    <section>
      <h2>Informations personnelles</h2>
      <label>Prénom *</label>
      <input type="text" name="prenom" required>
      <label>Nom *</label>
      <input type="text" name="nom" required>
      <label>Téléphone *</label>
      <input type="text" name="telephone" required>
      <label>Date de naissance *</label>
      <input type="date" name="date_naissance" required>
    </section>

    <!-- Section Infos académiques -->
    <section>
      <h2>Informations académiques</h2>
      <label>Niveau d'étude *</label>
      <input type="text" name="niveau_etude" required>
      <label>Période de stage *</label>
      <input type="text" name="periode_stage" required>
      <label>Filière *</label>
      <input type="text" name="filiere" required>
    </section>

    <!-- Section Infos stage -->
    <section>
      <h2>Informations de stage</h2>
      <label>Nom de l'établissement *</label>
      <input type="text" name="nom_etablissement" required>
      <label>Lieu de stage *</label>
      <input type="text" name="lieu_stage" required>
    </section>

    <button type="submit">Enregistrer</button>
  </form>
</body>
</html>
