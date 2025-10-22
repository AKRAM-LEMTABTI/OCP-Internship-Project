<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "TRAINEES";
$port = 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

$lang = $_GET['lang'] ?? 'FR';
if (!in_array($lang, ['FR', 'EN', 'AR'])) $lang = 'FR';

$text_direction = $lang === 'AR' ? 'rtl' : 'ltr';
$text_align = $lang === 'AR' ? 'right' : 'left';

$translations = [
    'FR' => [
        'TITLE' => 'Système de Présence OCP',
        'TODAY' => 'Aujourd\'hui',
        'CAREERS' => 'Carrières',
        'CONTACT' => 'Contact',
        'TRACKING' => 'Suivi quotidien des stagiaires',
        'SELECT' => 'Sélectionner',
        'PRESENT' => 'Présent',
        'LIST' => 'Liste des présences',
        'NO_ATTENDANCE' => 'Aucune présence enregistrée pour le moment.',
        'PRESENCES' => 'Présences',
        'LAST' => 'Dernière',
        'DURATION' => 'Durée stage',
        'DAYS' => 'jours',
        'ALREADY' => '⚠️ Ce stagiaire a déjà été marqué présent aujourd’hui.',
        'SUCCESS' => '✅ Présence enregistrée avec succès.',
        'FIRST' => '✅ Première présence enregistrée.',
        'LAST_DAY' => '✅ Présence enregistrée. Fin de stage aujourd\'hui !'
    ],
    'EN' => [
        'TITLE' => 'OCP Attendance System',
        'TODAY' => 'Today',
        'CAREERS' => 'Careers',
        'CONTACT' => 'Contact',
        'TRACKING' => 'Daily Trainees Tracking',
        'SELECT' => 'Select',
        'PRESENT' => 'Present',
        'LIST' => 'Attendance List',
        'NO_ATTENDANCE' => 'No attendance recorded yet.',
        'PRESENCES' => 'Attendances',
        'LAST' => 'Last',
        'DURATION' => 'Training duration',
        'DAYS' => 'days',
        'ALREADY' => '⚠️ This trainee has already been marked present today.',
        'SUCCESS' => '✅ Attendance registered successfully.',
        'FIRST' => '✅ First attendance registered.',
        'LAST_DAY' => '✅ Attendance registered. Last training day today!'
    ],
    'AR' => [
        'TITLE' => 'نظام حضور OCP',
        'TODAY' => 'اليوم',
        'CAREERS' => 'الوظائف',
        'CONTACT' => 'اتصل بنا',
        'TRACKING' => 'تتبع المتدربين اليومي',
        'SELECT' => 'اختر',
        'PRESENT' => 'حاضر',
        'LIST' => 'قائمة الحضور',
        'NO_ATTENDANCE' => 'لا توجد سجلات حضور حتى الآن.',
        'PRESENCES' => 'الحضور',
        'LAST' => 'آخر',
        'DURATION' => 'مدة التدريب',
        'DAYS' => 'أيام',
        'ALREADY' => '⚠️ تم تسجيل حضور هذا المتدرب اليوم بالفعل.',
        'SUCCESS' => '✅ تم تسجيل الحضور بنجاح.',
        'FIRST' => '✅ تم تسجيل أول حضور.',
        'LAST_DAY' => '✅ تم تسجيل الحضور. اليوم هو آخر يوم في التدريب!'
    ]
];

function trans($key, $lang, $translations) {
    return $translations[$lang][$key] ?? $key;
}

$stagiaires = [];
$sql = "SELECT identifient, prenom, nom, periode_stage FROM STAGIARE ORDER BY prenom";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dates = explode("AU", $row['periode_stage']);
        $date_debut = isset($dates[0]) ? new DateTime(trim($dates[0])) : null;
        $date_fin = isset($dates[1]) ? new DateTime(trim($dates[1])) : null;
        $date_fin_plus1 = $date_fin ? (clone $date_fin)->modify('+1 day') : null;

        if ($date_fin_plus1 && new DateTime() < $date_fin_plus1) {
            $stagiaires[] = $row;
        }
    }
}

$message = $_SESSION['message'] ?? "";
$type = $_SESSION['type'] ?? "success";
unset($_SESSION['message'], $_SESSION['type']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['stagiaire_id'])) {
    $id = $_POST['stagiaire_id'];
    $todayStr = $_POST['date'] ?? date("Y-m-d");

    $check = $conn->prepare("SELECT prenom, nom, periode_stage FROM STAGIARE WHERE identifient = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $resCheck = $check->get_result();

    if ($resCheck->num_rows > 0) {
        $data = $resCheck->fetch_assoc();
        $prenom = $data['prenom'];
        $nom = $data['nom'];

        $dates = explode("AU", $data['periode_stage']);
        $date_debut = isset($dates[0]) ? new DateTime(trim($dates[0])) : null;
        $date_fin = isset($dates[1]) ? new DateTime(trim($dates[1])) : null;
        $duree_stage = ($date_debut && $date_fin) ? $date_fin->diff($date_debut)->days + 1 : null;

        $todayDate = new DateTime($todayStr);
        $lastDay = $date_fin && $todayDate->format("Y-m-d") === $date_fin->format("Y-m-d");

        $stmt = $conn->prepare("SELECT date_derniere_presence FROM PRESENCE WHERE stagiaire_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if ($row['date_derniere_presence'] === $todayStr) {
                $_SESSION['message'] = trans('ALREADY', $lang, $translations);
                $_SESSION['type'] = "error";
            } else {
                $update = $conn->prepare("UPDATE PRESENCE SET total_presences = total_presences + 1, date_derniere_presence = ?, duree_stage = ? WHERE stagiaire_id = ?");
                $update->bind_param("sii", $todayStr, $duree_stage, $id);
                $update->execute();
                $_SESSION['message'] = $lastDay ? trans('LAST_DAY', $lang, $translations) : trans('SUCCESS', $lang, $translations);
                $_SESSION['type'] = "success";
            }
        } else {
            $insert = $conn->prepare("INSERT INTO PRESENCE (stagiaire_id, nom, prenom, date_derniere_presence, total_presences, duree_stage) VALUES (?, ?, ?, ?, 1, ?)");
            $insert->bind_param("isssi", $id, $nom, $prenom, $todayStr, $duree_stage);
            $insert->execute();
            $_SESSION['message'] = $lastDay ? trans('LAST_DAY', $lang, $translations) : trans('FIRST', $lang, $translations);
            $_SESSION['type'] = "success";
        }
        header("Location: presence.php?lang=$lang");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= strtolower($lang) ?>" dir="<?= $text_direction ?>">
<head>
<meta charset="UTF-8" />
<title><?= trans('TITLE', $lang, $translations) ?></title>
<link rel="stylesheet" href="css/presence.css?v=<?= time(); ?>" />
</head>
<body style="text-align: <?= $text_align ?>;">

<div class="topbar">
    <div class="register-btn">
        <a href="/presence/login.php" target="_blank">
            <img src="photos/psps.svg" alt="icon" class="icon"> S'inscrire
        </a>
    </div>
    <div class="left"></div>
    <div class="right">
        <a href="https://careers.ocpgroup.ma/fr" target="_blank"><?= trans('CAREERS', $lang, $translations) ?></a>
        <a href="https://www.ocpgroup.ma/fr/Contact-us" target="_blank"><?= trans('CONTACT', $lang, $translations) ?></a>
        <a href="?lang=EN">EN</a>
        <a href="?lang=FR">FR</a>
        <a href="?lang=AR">AR</a>
    </div>
</div>

<div class="topbar1">
    <p class="date"><?= trans('TODAY', $lang, $translations) ?> : <?= date("d/m/Y"); ?></p>
    <h1 class="sys"><?= trans('TITLE', $lang, $translations) ?></h1>
    <a href="https://www.ocpgroup.ma/fr#section2" target="_blank"><img src="photos/OCPFR.webp" alt="Logo OCP" class="logo-ocp" /></a>
</div>

<header>
    <div class="titre">
        <h1 class="cadre-transparent"><?= trans('TRACKING', $lang, $translations) ?></h1>
    </div>
</header>

<section class="main-content">
<form method="POST">
<div class="form-container">
    <select name="stagiaire_id" id="stagiaire_id" required>
        <option value=""><?= trans('SELECT', $lang, $translations) ?></option>
        <?php foreach ($stagiaires as $stagiaire): ?>
            <option value="<?= htmlspecialchars($stagiaire['identifient']) ?>">
                <?= htmlspecialchars($stagiaire['prenom'] . ' ' . $stagiaire['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" id="date" name="date" required readonly />
    <button type="submit"><?= trans('PRESENT', $lang, $translations) ?></button>
</div>
</form>

<section class="presence-list">
<h2 class="cadre-list"><?= trans('LIST', $lang, $translations) ?></h2>
<ul>
<?php
$liste = $conn->query("
    SELECT p.*, s.periode_stage 
    FROM PRESENCE p
    JOIN STAGIARE s ON p.stagiaire_id = s.identifient
    ORDER BY p.nom, p.prenom
");
if ($liste && $liste->num_rows > 0):
    while ($row = $liste->fetch_assoc()):
        $periode = $row['periode_stage'];
        $finStr = explode("AU", $periode)[1] ?? null;
        $date_fin = $finStr ? new DateTime(trim($finStr)) : null;
        $date_fin_plus1 = $date_fin ? (clone $date_fin)->modify('+1 day') : null;
        if ($date_fin_plus1 && new DateTime() < $date_fin_plus1):
?>
    <li>
      <?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?>
      | <?= trans('PRESENCES', $lang, $translations) ?>: <?= $row['total_presences'] ?>
      | <?= trans('LAST', $lang, $translations) ?>: <?= $row['date_derniere_presence'] ?>
      | <?= trans('DURATION', $lang, $translations) ?>: <?= htmlspecialchars($row['duree_stage'] ?? 'N/A') ?> <?= trans('DAYS', $lang, $translations) ?>
    </li>
<?php
        endif;
    endwhile;
else:
?>
    <li><?= trans('NO_ATTENDANCE', $lang, $translations) ?></li>
<?php endif; ?>
</ul>
</section>
</section>

<?php if (!empty($message)): ?>
<div id="toast" class="toast <?= $type === 'error' ? 'error' : '' ?>">
  <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("date").value = today;

    const toast = document.getElementById("toast");
    if (toast) {
        toast.classList.add("show");

        const isError = toast.classList.contains("error");
        const successSound = document.getElementById("success-sound");
        const errorSound = document.getElementById("error-sound");

        if (isError) {
            errorSound.play();
        } else {
            successSound.play();
        }

        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.style.display = "none", 500);
        }, 4000);
    }
});
</script>

<audio id="success-sound" src="sounds/success.mp3" preload="auto"></audio>
<audio id="error-sound" src="sounds/error.mp3" preload="auto"></audio>

</body>
</html>
