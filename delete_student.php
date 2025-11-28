<?php
require_once "db_connect.php";
$conn = db_connect();

$message = '';
$error = '';

// Suppression si POST avec id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $message = "Étudiant supprimé avec succès.";
        } else {
            $error = "Aucun étudiant trouvé avec cet identifiant.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . htmlspecialchars($e->getMessage());
    }
}

// Charger la liste des étudiants
$students = [];
try {
    $res = $conn->query("SELECT id, matricule, fullname, group_id FROM students ORDER BY id ASC");
    $students = $res->fetchAll();
} catch (PDOException $e) {
    $error = "Impossible de charger la liste : " . htmlspecialchars($e->getMessage());
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Suppression étudiant</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
            text-align: center;
        }
        h1 {
            font-size: 22px;
            margin-bottom: 18px;
            color: #1e293b;
        }
        .msg {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 16px;
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
        a.btn {
            display: inline-block;
            background: linear-gradient(90deg,#6366f1,#8b5cf6);
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 12px;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
            transition: background 0.2s;
        }
        a.btn:hover {
            background: linear-gradient(90deg,#4f46e5,#6366f1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Liste des étudiants</h1>
        <?php if ($message): ?>
            <div class="msg success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="padding:8px 6px;border-bottom:1px solid #e2e8f0;">ID</th>
                    <th style="padding:8px 6px;border-bottom:1px solid #e2e8f0;">Matricule</th>
                    <th style="padding:8px 6px;border-bottom:1px solid #e2e8f0;">Nom</th>
                    <th style="padding:8px 6px;border-bottom:1px solid #e2e8f0;">Groupe</th>
                    <th style="padding:8px 6px;border-bottom:1px solid #e2e8f0;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 6px;"><?= htmlspecialchars($s['id']) ?></td>
                    <td style="padding:8px 6px;"><?= htmlspecialchars($s['matricule']) ?></td>
                    <td style="padding:8px 6px;"><?= htmlspecialchars($s['fullname']) ?></td>
                    <td style="padding:8px 6px;"><?= htmlspecialchars($s['group_id']) ?></td>
                    <td style="padding:8px 6px;">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($s['id']) ?>">
                            <button type="submit" class="btn" style="background:#ef4444;color:#fff;padding:6px 12px;border-radius:6px;border:none;font-size:15px;cursor:pointer;">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="list_students.php" class="btn">Retour à la liste</a>
    </div>
</body>
</html>
