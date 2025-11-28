<?php
require_once __DIR__ . '/db_connect.php';
$conn = db_connect();

$message = '';
$errors = [];

// Accept POST (form) or GET param id (quick link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $sessionId = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $sessionId = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    if (isset($_GET['id']) && intval($_GET['id']) > 0) $sessionId = intval($_GET['id']);
    if ($sessionId <= 0) {
        $errors[] = 'Identifiant de session invalide.';
    } else {
            try {
                $stmt = $conn->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ? AND status = 'open'");
                $stmt->execute([$sessionId]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Session #' . $sessionId . ' fermée avec succès.';
                } else {
                    $errors[] = 'Aucune session ouverte trouvée avec cet identifiant.';
                }
            } catch (PDOException $e) {
                error_log('Close session error: ' . $e->getMessage());
                $errors[] = 'Erreur lors de la fermeture de la session.';
            }
    }
}
// Load open sessions for listing
$openSessions = [];
try {
    $stmt = $conn->query("SELECT id, course_id, group_id, date, opened_by FROM attendance_sessions WHERE status = 'open' ORDER BY date DESC, id DESC");
    $openSessions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Load open sessions error: ' . $e->getMessage());
    $errors[] = 'Impossible de charger les sessions ouvertes.';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fermer une session</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,Arial,sans-serif;background:linear-gradient(140deg,#fff 0%, #f6f8ff 100%);margin:0;padding:28px}
        .wrap{max-width:720px;margin:0 auto}
        .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 12px 30px rgba(10,20,60,0.06)}
        h1{font-size:20px;margin-bottom:12px;color:#0f172a}
        form{display:flex;gap:8px;align-items:center}
        input[type=number]{padding:10px;border-radius:8px;border:1px solid #e6e9ef;background:#f8fafc}
        .btn{background:linear-gradient(90deg,#6366f1,#8b5cf6);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
        .success{background:#d1fae5;color:#059669;padding:10px;border-radius:8px;margin-bottom:12px}
        .error{background:#fee2e2;color:#b91c1c;padding:10px;border-radius:8px;margin-bottom:12px}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Fermer une session</h1>
            <?php if ($message): ?><div class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="error"><ul style="margin:0 0 0 18px;padding-left:6px"><?php foreach($errors as $err) echo '<li>'.htmlspecialchars($err,ENT_QUOTES,'UTF-8').'</li>';?></ul></div>
            <?php endif; ?>
            <form method="post">
                <label for="session_id">Session ID à fermer</label>
                <input type="number" name="session_id" id="session_id" min="1" required>
                <button type="submit" class="btn">Fermer</button>
            </form>
            <h2 style="margin-top:14px">Sessions ouvertes</h2>
            <?php if (empty($openSessions)): ?>
                <p class="muted">Aucune session ouverte actuellement.</p>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th>ID</th><th>Course</th><th>Group</th><th>Date</th><th>Opened by</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($openSessions as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['id']) ?></td>
                            <td><?= htmlspecialchars($s['course_id']) ?></td>
                            <td><?= htmlspecialchars($s['group_id']) ?></td>
                            <td><?= htmlspecialchars($s['date']) ?></td>
                            <td><?= htmlspecialchars($s['opened_by']) ?></td>
                            <td>
                                <form method="post" style="display:inline" onsubmit="return confirm('Fermer la session <?= htmlspecialchars($s['id']) ?> ?')">
                                    <input type="hidden" name="session_id" value="<?= htmlspecialchars($s['id']) ?>">
                                    <button class="btn" type="submit" style="background:#ef4444">Fermer</button>
                                </form>
                                <a href="?id=<?= htmlspecialchars($s['id']) ?>" class="btn" style="background:#6366f1;margin-left:6px;">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2 style="margin-top:20px">Sessions fermées récemment</h2>
            <?php
                // load last closed sessions for display
                $closedSessions = [];
                try {
                    $stmt = $conn->query("SELECT id, course_id, group_id, date, opened_by FROM attendance_sessions WHERE status = 'closed' ORDER BY date DESC, id DESC LIMIT 20");
                    $closedSessions = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log('Load closed sessions error: ' . $e->getMessage());
                }
            ?>
            <?php if (empty($closedSessions)): ?>
                <p class="muted">Aucune session fermée récemment.</p>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th>ID</th><th>Course</th><th>Group</th><th>Date</th><th>Opened by</th></tr></thead>
                    <tbody>
                        <?php foreach($closedSessions as $cs): ?>
                        <tr>
                            <td><?= htmlspecialchars($cs['id']) ?></td>
                            <td><?= htmlspecialchars($cs['course_id']) ?></td>
                            <td><?= htmlspecialchars($cs['group_id']) ?></td>
                            <td><?= htmlspecialchars($cs['date']) ?></td>
                            <td><?= htmlspecialchars($cs['opened_by']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php if ($message): ?>
                <script>setTimeout(function(){ window.location.href='create_session.php'; }, 2000);</script>
            <?php endif; ?>
            <div style="margin-top:12px"><a href="create_session.php" class="btn" style="background:#6b7280;text-decoration:none;">Créer / Voir sessions</a></div>
        </div>
    </div>
</body>
</html>
<!-- legacy ended -->
