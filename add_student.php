<?php
// add_student.php - insère un étudiant dans la table students via db_connect()

// inclure la fonction qui retourne la connexion
require_once __DIR__ . '/db_connect.php';

// appel de la fonction pour récupérer la connexion PDO
$conn = db_connect();

$message = '';
$errors = [];

if (!$conn) {
    // en cas d'erreur de connexion, on arrête proprement
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Récupère et nettoie les champs
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $fullname   = isset($_POST['fullname'])   ? trim($_POST['fullname'])   : '';
    $group_id   = isset($_POST['group_id'])   ? trim($_POST['group_id'])   : '';

    // Validation simple
    if ($student_id === '' || $fullname === '' || $group_id === '') {
        $errors[] = 'Tous les champs sont requis.';
    } else {
        try {
            // Prépare et exécute l'insertion
            $stmt = $conn->prepare("INSERT INTO students (matricule, fullname, group_id) VALUES (:matricule, :fullname, :group_id)");
            $stmt->bindParam(':matricule', $student_id);
            $stmt->bindParam(':fullname', $fullname);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->execute();
            $insertedId = $conn->lastInsertId();
            $message = 'Étudiant ajouté avec succès ! ID = ' . htmlspecialchars($insertedId, ENT_QUOTES, 'UTF-8');

            // Also append the new student to students.json (if not present) so take_attendance uses it
            $studentsFile = __DIR__ . DIRECTORY_SEPARATOR . 'students.json';
            $studentsArr = [];
            if (file_exists($studentsFile)) {
                $tmp = @file_get_contents($studentsFile);
                $decoded = json_decode($tmp, true);
                if (is_array($decoded)) $studentsArr = $decoded;
            }
            $newEntry = [
                'student_id' => (string)$student_id,
                'name' => $fullname,
                'group' => $group_id,
                'added_at' => gmdate('c')
            ];
            $found = false;
            foreach ($studentsArr as $s) {
                if (isset($s['student_id']) && (string)$s['student_id'] === (string)$student_id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $studentsArr[] = $newEntry;
                if (@file_put_contents($studentsFile, json_encode($studentsArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
                    $errors[] = "Étudiant ajouté en base, mais impossible d'écrire la liste locale (permissions).";
                } else {
                    $message .= ' La liste des étudiants a été mise à jour.';
                }
            } else {
                $message .= ' (Étudiant déjà présent dans la liste locale.)';
            }
        } catch (PDOException $e) {
            // Affiche une erreur simple (pour dev), tu peux logger au lieu d'afficher en production
            $errors[] = 'Erreur lors de l\'ajout : ' . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ajouter un étudiant</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Inter, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(120deg,#eef2ff 0%, #f6f8fb 100%);
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 420px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(60,80,180,0.08);
        }
        h1 {
            font-size: 22px;
            margin-bottom: 18px;
            color: #1e293b;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        label {
            font-size: 15px;
            color: #475569;
            margin-bottom: 6px;
        }
        input[type="text"] {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 16px;
            background: #f8fafc;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus {
            border-color: #6366f1;
            outline: none;
        }
        button {
            background: linear-gradient(90deg,#6366f1,#8b5cf6);
            color: #fff;
            padding: 12px 0;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
            transition: background 0.2s;
        }
        button:hover {
            background: linear-gradient(90deg,#4f46e5,#6366f1);
        }
        .msg {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 15px;
            text-align: center;
        }
        .msg.success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #10b98133;
        }
        .msg.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #ef444433;
        }
        @media (max-width:600px) {
            .container { padding: 18px 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajouter un étudiant</h1>
        <?php if (!empty($errors)): ?>
            <div class="msg error">
                <ul style="margin:0 0 0 18px;padding-left:6px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="msg success">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <div style="margin-top:8px;">
                    <a href="take_attendance.php" class="btn secondary" style="background:#eef2ff;color:#0f172a;padding:8px 10px;border-radius:8px;text-decoration:none;display:inline-block">Prendre la présence</a>
                </div>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <label for="student_id">Matricule :</label>
            <input type="text" name="student_id" id="student_id" value="<?= isset($student_id) ? htmlspecialchars($student_id, ENT_QUOTES, 'UTF-8') : '' ?>" required>

            <label for="fullname">Nom complet :</label>
            <input type="text" name="fullname" id="fullname" value="<?= isset($fullname) ? htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') : '' ?>" required>

            <label for="group_id">Groupe :</label>
            <input type="text" name="group_id" id="group_id" value="<?= isset($group_id) ? htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8') : '' ?>" required>

            <button type="submit">Ajouter l'étudiant</button>
        </form>
    </div>
</body>
</html>
