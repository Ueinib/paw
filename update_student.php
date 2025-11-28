<?php
require_once __DIR__ . '/db_connect.php';
$conn = db_connect();

$message = '';
$errors = [];

// Si pas d'id GET, afficher la liste des étudiants à choisir
if (!isset($_GET['id'])) {
    // Charger la liste
    $students = [];
    try {
        $res = $conn->query('SELECT id, matricule, fullname, group_id FROM students ORDER BY id ASC');
        $students = $res->fetchAll();
    } catch (PDOException $e) {
        $errors[] = 'Impossible de charger la liste : ' . htmlspecialchars($e->getMessage());
    }
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Choisir un étudiant à modifier</title>
        <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
        <style>
            body{font-family:Inter,Arial,Helvetica,sans-serif;background:linear-gradient(140deg,#f6f8ff 0%, #ffffff 100%);margin:0;padding:40px}
            .wrap{max-width:800px;margin:0 auto}
            .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 12px 30px rgba(10,20,60,0.06)}
            h1{margin:0 0 18px 0;color:#0f172a;text-align:center}
            table{width:100%;border-collapse:collapse;margin-bottom:18px}
            th,td{padding:10px 8px;border-bottom:1px solid #e2e8f0;text-align:left}
            th{background:#f1f5f9;color:#374151}
            tr:hover{background:#f8fafc}
            .btn{background:linear-gradient(90deg,#6366f1,#8b5cf6);color:#fff;padding:7px 14px;border-radius:8px;border:0;cursor:pointer;font-weight:600;text-decoration:none;display:inline-block}
            .msg{padding:10px;border-radius:8px;margin-bottom:12px}
            .error{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.12);color:#ef4444}
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="card">
                <h1>Choisir un étudiant à modifier</h1>
                <?php if (!empty($errors)): ?>
                    <div class="msg error">
                        <ul style="margin:0 0 0 18px;padding-left:6px">
                            <?php foreach ($errors as $err) : ?>
                                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Groupe</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['id']) ?></td>
                            <td><?= htmlspecialchars($s['matricule']) ?></td>
                            <td><?= htmlspecialchars($s['fullname']) ?></td>
                            <td><?= htmlspecialchars($s['group_id']) ?></td>
                            <td><a href="update_student.php?id=<?= htmlspecialchars($s['id']) ?>" class="btn">Modifier</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="list_students.php" class="btn" style="background:#6b7280;">Retour à la liste</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Partie modification d'un étudiant
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Identifiant étudiant invalide.');
}

$message = '';
$errors = [];

// Charger l'étudiant
try {
    $stmt = $conn->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur BD: ' . htmlspecialchars($e->getMessage()));
}
if (!$student) {
    die('Étudiant introuvable. <a href="update_student.php">Retour à la liste</a>');
}

$origMatricule = (string)$student['matricule'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $matricule = isset($_POST['matricule']) ? trim($_POST['matricule']) : '';
    $group_id = isset($_POST['group_id']) ? trim($_POST['group_id']) : '';

    if ($fullname === '') $errors[] = 'Le nom est requis.';
    if ($matricule === '') $errors[] = 'Le matricule est requis.';
    if ($group_id === '') $errors[] = 'Le groupe est requis.';
    if (!preg_match('/^\d+$/', $matricule)) $errors[] = 'Le matricule doit être uniquement des chiffres.';

    if (empty($errors)) {
        try {
            $update = $conn->prepare('UPDATE students SET fullname = ?, matricule = ?, group_id = ? WHERE id = ?');
            $update->execute([$fullname, $matricule, $group_id, $id]);
            $message = 'Étudiant mis à jour avec succès.';

            // Met à jour students.json si présent
            $studentsFile = __DIR__ . DIRECTORY_SEPARATOR . 'students.json';
            if (file_exists($studentsFile)) {
                $txt = @file_get_contents($studentsFile);
                $arr = json_decode($txt, true);
                if (is_array($arr)) {
                    $updated = false;
                    foreach ($arr as &$entry) {
                        if (isset($entry['student_id']) && (string)$entry['student_id'] === $origMatricule) {
                            $entry['student_id'] = (string)$matricule;
                            $entry['name'] = $fullname;
                            $entry['group'] = $group_id;
                            $updated = true;
                            break;
                        }
                    }
                    unset($entry);
                    if ($updated) {
                        @file_put_contents($studentsFile, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                    }
                }
            }

            // Actualise les données pour l'affichage
            $student['fullname'] = $fullname;
            $student['matricule'] = $matricule;
            $student['group_id'] = $group_id;
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la mise à jour: ' . htmlspecialchars($e->getMessage());
        }
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Modifier l'étudiant</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,Arial,Helvetica,sans-serif;background:linear-gradient(140deg,#f6f8ff 0%, #ffffff 100%);margin:0;padding:40px}
        .wrap{max-width:720px;margin:0 auto}
        .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 12px 30px rgba(10,20,60,0.06)}
        h1{margin:0 0 8px 0;color:#0f172a}
        .muted{color:#6b7280}
        form{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
        label{display:block;color:#475569;margin-bottom:6px;font-size:14px}
        input[type=text]{padding:10px;border-radius:8px;border:1px solid #e6e9ef;background:#f8fafc}
        .full{grid-column:1 / -1}
        .actions{grid-column:1 / -1;display:flex;gap:8px;justify-content:flex-end}
        .btn{background:linear-gradient(90deg,#6366f1,#8b5cf6);color:#fff;padding:10px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:600}
        .btn.secondary{background:#6b7280;color:#fff}
        .msg{padding:10px;border-radius:8px;margin-bottom:12px}
        .success{background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.16);color:#059669}
        .error{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.12);color:#ef4444}
        @media (max-width:640px){form{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Modifier l'étudiant</h1>
            <p class="muted">ID: <?= htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($errors)): ?>
                <div class="msg error">
                    <ul style="margin:0 0 0 18px;padding-left:6px">
                        <?php foreach ($errors as $err) : ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="msg success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post">
                <div>
                    <label for="matricule">Matricule</label>
                    <input type="text" id="matricule" name="matricule" value="<?= htmlspecialchars($student['matricule'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label for="group_id">Groupe</label>
                    <input type="text" id="group_id" name="group_id" value="<?= htmlspecialchars($student['group_id'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="full">
                    <label for="fullname">Nom complet</label>
                    <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($student['fullname'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="actions">
                    <a href="update_student.php" class="btn secondary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;padding:9px 12px">Annuler</a>
                    <button type="submit" class="btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
