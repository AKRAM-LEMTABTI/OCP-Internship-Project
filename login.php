<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "TRAINEES";
$port = 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("❌ Connexion échouée : " . $conn->connect_error);
}

$message = ""; 
$type = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $niveau_etude = trim($_POST['niveau_etude'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $date_fin = trim($_POST['date_fin'] ?? '');
    $filiere = trim($_POST['filiere'] ?? '');
    $nom_etablissement = trim($_POST['nom_etablissement'] ?? '');
    $lieu_stage = trim($_POST['lieu_stage'] ?? '');

    if (
        empty($prenom) || empty($nom) || empty($telephone) || empty($date_naissance) ||
        empty($niveau_etude) || empty($date_debut) || empty($date_fin) || empty($filiere) ||
        empty($nom_etablissement) || empty($lieu_stage)
    ) {
        $message = "Tous les champs sont obligatoires.";
        $type = "error";
    } else {
       
        $date_debut_clean = str_replace('à', '', $date_debut);
        $date_fin_clean = str_replace('à', '', $date_fin);
        $periode_stage = $date_debut_clean . " AU " . $date_fin_clean;

        $stmt = $conn->prepare(
            "INSERT INTO STAGIARE 
             (prenom, nom, telephone, date_naissance, niveau_etude, periode_stage, nom_etablissement, filiere, lieu_stage) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param(
                "sssssssss",
                $prenom, $nom, $telephone, $date_naissance,
                $niveau_etude, $periode_stage, $nom_etablissement, $filiere, $lieu_stage
            );

            if ($stmt->execute()) {
                $message = "Stagiaire enregistré avec succès !";
                $type = "success";
            } else {
                $message = "Erreur lors de l'enregistrement : " . $stmt->error;
                $type = "error";
            }

            $stmt->close();
        } else {
            $message = "Erreur préparation : " . $conn->error;
            $type = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Inscription Stagiaire</title>
<link rel="stylesheet" href="css/aa.css?v=<?= time(); ?>">
</head>
<body>

<a href="presence.php" class="btn-top-left">
    Retour
</a>
 
<h1>Inscription Stagiaire</h1>

<div id="toast" class="toast <?= $type ?>"><?= $message ?></div>

<form action="" method="post">
    <section>
        <h2>Informations personnelles</h2>
        <label>Prénom</label>
        <input type="text" name="prenom" id="prenom" required>
        <label>Nom</label>
        <input type="text" name="nom" id="nom" required>
        <label>Téléphone</label>
        <input type="text" name="telephone" required>
        <label>Date de naissance</label>
        <input type="date" name="date_naissance" required>
    </section>

    <section>
        <h2>Informations académiques</h2>
        <label>Niveau d'étude</label>
        <select name="niveau_etude" required>
            <option value="">Sélectionnez</option>
            <option value="Bac+1">Bac+1</option>
            <option value="Bac+2">Bac+2</option>
            <option value="Bac+3">Bac+3</option>
            <option value="Bac+4">Bac+4</option>
            <option value="Bac+5">Bac+5</option>
        </select>
        <label>Date de début de stage</label>
        <input type="date" name="date_debut" required>
        <label>Date de fin de stage</label>
        <input type="date" name="date_fin" required>
        <label>Filière</label>
        <select name="filiere" required>
            <option value="">Sélectionnez</option>
            <option value="Informatique">Informatique</option>
            <option value="Génie Civil">Génie Civil</option>
            <option value="Électrique">Électrique</option>
            <option value="Mécanique">Mécanique</option>
            <option value="Management">Management</option>
        </select>
    </section>

    <section>
        <h2>Informations de stage</h2>
        <label>Nom de l'établissement</label>
        <select name="nom_etablissement" required>
            <option value="">Sélectionnez</option>
            <option value="ENSA">ENSA</option>
            <option value="ENSAM">ENSAM</option>
            <option value="EST">EST</option>
            <option value="BTS">BTS</option>
            <option value="UIR">UIR</option>
            <option value="ENCG">ENCG</option>
            <option value="FST">FST</option>
        </select>
        <label>Ville de stage</label>
        <select name="lieu_stage" required>
            <option value="">Sélectionnez une ville</option>
            <option value="Casablanca">Casablanca</option>
            <option value="Rabat">Rabat</option>
            <option value="Marrakech">Marrakech</option>
            <option value="Fès">Fès</option>
            <option value="Agadir">Agadir</option>
            <option value="Tangier">Tangier</option>
            <option value="Safi">Safi</option>
            <option value="Essaouira">Essaouira</option>
        </select>
    </section>

    <button type="submit">Enregistrer</button>
</form>

<script>

window.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast');
    if (toast.textContent.trim() !== "") {
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 4000);
    }
});

function capitalizeFirstLetter(inputId) {
    const input = document.getElementById(inputId);
    input.addEventListener('input', function() {
        let val = this.value;
        if(val.length > 0){
            this.value = val.charAt(0).toUpperCase() + val.slice(1);
        }
    });
}

capitalizeFirstLetter('prenom');
capitalizeFirstLetter('nom');
</script>

</body>
</html>
