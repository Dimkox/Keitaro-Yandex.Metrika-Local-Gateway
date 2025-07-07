<?php
// Стабильные: Путь к лог-файлу.
define('LOG_FILE', __DIR__ . '/yandex_log.txt');

function render_log_data(array $data) {
    echo '<ul>';
    foreach ($data as $key => $value) {
        echo '<li>';
        echo "<strong>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . ":</strong> ";
        if (is_array($value)) {
            echo '<div class="nested">';
            render_log_data($value);
            echo '</div>';
        } else {
            echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лог отправки в Яндекс.Метрику</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #1e1e1e; color: #d4d4d4; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { border-bottom: 2px solid #444; padding-bottom: 10px; }
        .log-entry { background-color: #252526; border: 1px solid #333; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .log-header { padding: 10px 15px; background-color: #333; font-weight: bold; }
        .log-header .status-success { color: #4CAF50; }
        .log-header .status-error { color: #F44336; }
        .log-body { padding: 15px; }
        h3 { color: #569cd6; margin-top: 0; }
        ul { list-style: none; padding-left: 0; }
        li { word-wrap: break-word; }
        .nested { border-left: 2px solid #444; margin-left: 10px; padding-left: 15px; }
        pre { background-color: #1a1a1a; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; color: #ce9178; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Лог отправки в Яндекс.Метрику</h1>
        <?php
        if (!file_exists(LOG_FILE)) {
            echo '<p>Лог-файл пока пуст. Ожидание первого постбэка...</p>';
        } else {
            $log_lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_lines = array_reverse($log_lines);

            foreach ($log_lines as $line) {
                $log = json_decode($line, true);
                if (!$log) continue;
                $status_class = $log['status'] === 'SUCCESS' ? 'status-success' : 'status-error';
        ?>
        <div class="log-entry">
            <div class="log-header">
                <span class="timestamp"><?= htmlspecialchars($log['timestamp'], ENT_QUOTES, 'UTF-8') ?></span> |
                Статус: <span class="<?= $status_class ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="log-body">
                <h3><span style="color: #9cdcfe;">1.</span> Входящие данные от Keitaro:</h3>
                <?php render_log_data($log['incoming_request']); ?>

                <h3><span style="color: #9cdcfe;">2.</span> Что было отправлено в Яндекс:</h3>
                <?php render_log_data($log['sent_to_yandex']); ?>

                <h3><span style="color: #9cdcfe;">3.</span> Ответ от Яндекса:</h3>
                <?php if (isset($log['yandex_response']) && !empty($log['yandex_response'])): ?>
                    <pre><code><?= htmlspecialchars(json_encode($log['yandex_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></code></pre>
                <?php else: ?>
                    <p>Ответ отсутствует или пуст.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
            }
        }
        ?>
    </div>
</body>
</html>