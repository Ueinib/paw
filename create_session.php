<?php
require_once __DIR__ . '/db_connect.php';
$conn = db_connect();

$message = '';
$errors = [];
$createdSessionId = null;

// Handle creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = isset($_POST['course_id']) ? trim($_POST['course_id']) : '';
    $group = isset($_POST['group_id']) ? trim($_POST['group_id']) : '';
    $prof = isset($_POST['prof_id']) ? trim($_POST['prof_id']) : '';

    // Validate inputs (positive integers expected)
    if (!preg_match('/^\d+$/', $course)) $errors[] = 'Course id invalide.';
    if (!preg_match('/^\d+$/', $group)) $errors[] = 'Group id invalide.';
    if (!preg_match('/^\d+$/', $prof)) $errors[] = 'Professor id invalide.';

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT INTO attendance_sessions (course_id, group_id, date, opened_by, status) VALUES (?, ?, CURDATE(), ?, 'open')");
                $stmt->execute([$course, $group, $prof]);
                $createdSessionId = (int)$conn->lastInsertId();
                $message = 'Session créée avec succès. ID = ' . $createdSessionId;

                // If AJAX (or 'format=json'), return JSON response with the session id
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['format']) && $_POST['format'] === 'json');
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'ok', 'session_id' => $createdSessionId, 'message' => $message]);
                    exit;
                }
                    } catch (PDOException $e) {
                        // Log exception details for debugging but show user-friendly message
                        error_log('Create session error: ' . $e->getMessage());
                        $errors[] = 'Erreur lors de la création de la session.';
                    }
        }
}

// Load existing sessions to show in the UI
$sessions = [];
try {
    // Use date and id ordering; avoid relying on created_at column which may be missing
    $res = $conn->query("SELECT id, course_id, group_id, date, opened_by, status FROM attendance_sessions ORDER BY date DESC, id DESC");
    $sessions = $res->fetchAll();
} catch (PDOException $e) {
    error_log('Load sessions error: ' . $e->getMessage());
    $errors[] = 'Impossible de charger les sessions.';
}
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Créer une session</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,Arial,sans-serif;background:linear-gradient(140deg,#f6f8ff 0%, #fff 100%);margin:0;padding:28px}
        .wrap{max-width:1000px;margin:0 auto}
        .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 12px 30px rgba(10,20,60,0.06)}
        h1{font-size:20px;margin:0 0 12px 0;color:#0f172a}
        form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
        input[type=text]{padding:10px;border-radius:8px;border:1px solid #e6e9ef;background:#f8fafc}
        .btn{background:linear-gradient(90deg,#6366f1,#8b5cf6);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:10px;border-bottom:1px solid #e6e9ef;text-align:left}
        th{background:#f1f5f9}
        .success{background:#d1fae5;color:#059669;padding:10px;border-radius:8px;margin-bottom:12px}
        .error{background:#fee2e2;color:#b91c1c;padding:10px;border-radius:8px;margin-bottom:12px}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Créer une nouvelle session</h1>
            <?php if ($message): ?><div class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?><?php if ($createdSessionId): ?> — <strong id="createdId"><?= htmlspecialchars($createdSessionId, ENT_QUOTES, 'UTF-8') ?></strong> <button id="copyBtn" class="btn" style="background:#6b7280;">Copier</button><?php endif; ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="error"><ul style="margin:0 0 0 18px;padding-left:6px"><?php foreach($errors as $err) echo '<li>'.htmlspecialchars($err,ENT_QUOTES,'UTF-8').'</li>';?></ul></div>
            <?php endif; ?>
            <form method="post">
                <div>
                    <label for="course_id">Course ID</label><br>
                    <input type="number" pattern="\d*" name="course_id" id="course_id" placeholder="Course ID" required value="<?= isset($course) ? htmlspecialchars($course, ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div>
                    <label for="group_id">Group ID</label><br>
                    <input type="number" pattern="\d*" name="group_id" id="group_id" placeholder="Group ID" required value="<?= isset($group) ? htmlspecialchars($group, ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div>
                    <label for="prof_id">Professor ID</label><br>
                    <input type="number" pattern="\d*" name="prof_id" id="prof_id" placeholder="Professor ID" required value="<?= isset($prof) ? htmlspecialchars($prof, ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div>
                    <button class="btn" type="submit">Créer session</button>
                </div>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var copyBtn = document.getElementById('copyBtn');
                    if (copyBtn) {
                        copyBtn.addEventListener('click', function() {
                            var text = document.getElementById('createdId').textContent;
                            navigator.clipboard && navigator.clipboard.writeText(text).then(function(){
                                copyBtn.textContent = 'Copié';
                                setTimeout(function(){ copyBtn.textContent = 'Copier'; }, 1500);
                            });
                        });
                    }
                });
            </script>

            <h2 style="margin-top:18px">Sessions récentes</h2>
            <?php
                // Show a short summary and link to the full close session page where you can manage open sessions
                $openCount = 0;
                foreach ($sessions as $s) { if ($s['status'] === 'open') $openCount++; }
            ?>
            <p class="muted">Total sessions: <?= count($sessions) ?> — Sessions ouvertes: <?= $openCount ?>. <a href="close_session.php" class="btn" style="background:#6b7280;margin-left:8px;">Gérer les sessions ouvertes</a></p>
        </div>
    </div>
</body>
</html>

