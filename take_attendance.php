<?php
// take_attendance.php
// Presentes: modernized UI with CSS/JS; store attendance in a date-specific JSON file

$studentsFile = __DIR__ . DIRECTORY_SEPARATOR . 'students.json';
if (!file_exists($studentsFile)) {
    die("Aucun étudiant trouvé. Ajoutez d'abord des étudiants.");
}

$students = json_decode(@file_get_contents($studentsFile), true);
if (!is_array($students)) $students = [];

$message = '';
$errors = [];

// Select date: either from POST or default to today
$selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
// Sanitize/validate selected date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$attendance_file = __DIR__ . DIRECTORY_SEPARATOR . "attendance_{$selectedDate}.json";
// Pre-check if a file already exists for the selected date
$downloadLink = null;
if (file_exists($attendance_file)) {
    $downloadLink = basename($attendance_file);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    // Respect the 'save_json' checkbox; default true if checkbox present
    $doSaveJson = isset($_POST['save_json']) ? boolval($_POST['save_json']) : false;
    $posted = isset($_POST['attendance']) && is_array($_POST['attendance']) ? $_POST['attendance'] : [];

    // Build attendance array first (always done)
    $attendance = [];
    foreach ($students as $s) {
        $id = (string)($s['student_id'] ?? ($s['id'] ?? ''));
        $status = isset($posted[$id]) ? 'present' : 'absent';
        $attendance[] = [
            'student_id' => $id,
            'name' => $s['name'] ?? ($s['fullname'] ?? ''),
            'status' => $status
        ];
    }

    if ($doSaveJson) {
        // Prevent overwriting if attendance already exists
        if (file_exists($attendance_file)) {
            $errors[] = "La présence pour le $selectedDate a déjà été prise.";
        } else {
            if (@file_put_contents($attendance_file, json_encode($attendance, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
                $errors[] = "Impossible d'enregistrer la présence. Vérifiez les permissions du dossier.";
            } else {
                $message = "Présence enregistrée pour le $selectedDate.";
                // Provide relative link for download
                $downloadLink = basename($attendance_file);
            }
        }
    } else {
        // Not saving JSON, but we still processed attendance in memory; give a message
        $message = "Présence traitée pour le $selectedDate. (enregistrée uniquement en mémoire)";
    }
}

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Prendre la présence</title>
    <style>
        :root{--bg:#f8fafc;--card:#fff;--muted:#6b7280;--primary:#2563eb;--success:#10b981;--danger:#ef4444}
        *{box-sizing:border-box}
        body{font-family:Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:linear-gradient(180deg,#f8fafc 0%, #ffffff 100%);margin:0;padding:32px}
        .wrap{max-width:980px;margin:0 auto}
        .card{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 10px 28px rgba(13,18,38,0.06)}
        header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
        h1{font-size:20px;margin:0;color:#0f172a}
        .muted{color:var(--muted)}
        .controls{display:flex;gap:8px;align-items:center}
        input[type=date]{padding:8px 10px;border-radius:8px;border:1px solid #e6e9ef}
        .btn{background:var(--primary);color:#fff;padding:10px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:600}
        .btn.secondary{background:#e5e7eb;color:#111}
        table{width:100%;border-collapse:collapse;margin-top:14px}
        thead th{background:#f1f5f9;padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}
        tbody td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
        tbody tr:hover{background:#fbfdff}
        .name{font-weight:600;color:#0f172a}
        .id{color:var(--muted);font-size:13px}
        .summary{display:flex;gap:12px;align-items:center;margin-top:12px}
        .badge{padding:8px 10px;border-radius:10px;background:#eef2ff;color:#1e3a8a;font-weight:600}
        .success{background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.16);color:var(--success);padding:10px;border-radius:8px}
        .error{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.12);color:var(--danger);padding:10px;border-radius:8px}
        .actions{display:flex;gap:8px}
        @media (max-width:720px){thead th:nth-child(1){display:none} tbody td:nth-child(1){display:none} .controls{flex-direction:column;align-items:stretch}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <header>
                <div>
                    <h1>Prendre la présence</h1>
                    <p class="muted">Sélectionnez la date, marquez les étudiants présents et envoyez.</p>
                </div>
                <div class="controls">
                    <form id="dateForm" method="post" style="display:flex;gap:8px;align-items:center">
                        <input type="date" name="date" value="<?= h($selectedDate) ?>">
                        <button type="button" id="reloadDate" class="btn secondary" title="Charger">Charger</button>
                    </form>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="success"><?= h($message) ?></div>
                <?php if ($downloadLink): ?>
                    <div style="margin-top:8px;">
                        <a href="<?= h($downloadLink) ?>" download class="btn secondary">Télécharger le fichier JSON</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <?php foreach($errors as $err): ?>
                    <div class="error"><?= h($err) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="date" value="<?= h($selectedDate) ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                    <div class="summary">
                        <div class="badge" id="presentCount">Présents: 0</div>
                        <div class="muted">Total étudiants: <?= count($students) ?></div>
                    </div>
                    <div class="actions">
                        <button type="button" class="btn secondary" id="markAllPresent">Tous présents</button>
                        <button type="button" class="btn secondary" id="markAllAbsent">Tous absents</button>
                        <label style="display:flex;align-items:center;gap:8px;margin-right:8px">
                            <input type="checkbox" name="save_json" value="1" checked>
                            <span class="muted">Sauvegarder au format JSON</span>
                        </label>
                        <button type="submit" class="btn" name="submit_attendance">Enregistrer</button>
                    </div>
                </div>

                <table aria-label="table des présences">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Étudiant</th>
                            <th style="text-align:center">Présent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s):
                            $id = h((string)($s['student_id'] ?? ($s['id'] ?? '')));
                            $name = h($s['name'] ?? ($s['fullname'] ?? ''));
                        ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <div class="name"><?= $name ?></div>
                                <div class="id">ID: <?= $id ?></div>
                            </td>
                            <td style="text-align:center">
                                <input type="checkbox" name="attendance[<?= $id ?>]" class="presentToggle" checked>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <script>
        // Update present count
        function updatePresentCount(){
            const checkboxes = Array.from(document.querySelectorAll('.presentToggle'));
            const present = checkboxes.filter(cb => cb.checked).length;
            document.getElementById('presentCount').textContent = 'Présents: ' + present;
        }

        document.addEventListener('DOMContentLoaded', function(){
            updatePresentCount();
            document.querySelectorAll('.presentToggle').forEach(cb => cb.addEventListener('change', updatePresentCount));
            document.getElementById('markAllPresent').addEventListener('click', function(){
                document.querySelectorAll('.presentToggle').forEach(cb => cb.checked = true);
                updatePresentCount();
            });
            document.getElementById('markAllAbsent').addEventListener('click', function(){
                document.querySelectorAll('.presentToggle').forEach(cb => cb.checked = false);
                updatePresentCount();
            });
            document.getElementById('reloadDate').addEventListener('click', function(){
                // Re-submit form to reload the selected date (no data changes) - we use POST for simplicity
                const form = document.getElementById('dateForm');
                const date = form.elements['date'].value;
                // simulate a GET with ?date= if needed or reload by posting
                // For simplicity, we perform a post by creating a temporary form
                const temp = document.createElement('form');
                temp.method = 'post';
                temp.style.display = 'none';
                const input = document.createElement('input');
                input.name = 'date';
                input.value = date;
                temp.appendChild(input);
                document.body.appendChild(temp);
                temp.submit();
            });
        });
    </script>
</body>
</html>
