<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = '/home/pi/graber-hub/data.json';

function loadData() {
    global $DATA_FILE;
    if (!file_exists($DATA_FILE)) {
        $data = getDefaultData();
        saveData($data);
        return $data;
    }
    return json_decode(file_get_contents($DATA_FILE), true);
}

function saveData($data) {
    global $DATA_FILE;
    file_put_contents($DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function today() {
    return date('Y-m-d');
}

function nextId(&$data) {
    $data['nextId'] = ($data['nextId'] ?? 100) + 1;
    return $data['nextId'];
}

function getDefaultData() {
    return [
        'kids' => [
            ['id' => 1, 'name' => 'Aurora', 'initial' => 'A', 'color' => '#ec5281', 'sort' => 1],
            ['id' => 2, 'name' => 'Deandre', 'initial' => 'D', 'color' => '#03a9f4', 'sort' => 2],
            ['id' => 3, 'name' => 'Alexia', 'initial' => 'A', 'color' => '#a78bfa', 'sort' => 3],
            ['id' => 4, 'name' => 'Truett', 'initial' => 'T', 'color' => '#4ade80', 'sort' => 4],
        ],
        'chores' => new stdClass(),
        'schedules' => new stdClass(),
        'countdowns' => [
            ['id' => 1, 'label' => 'Bansko Trip', 'emoji' => "\xF0\x9F\x8F\x94\xEF\xB8\x8F", 'target_date' => '2026-03-16'],
            ['id' => 2, 'label' => 'Anniversary', 'emoji' => "\xF0\x9F\x92\x95", 'target_date' => '2026-06-19'],
        ],
        'verses' => [
            ['id' => 1, 'text' => 'Train up a child in the way he should go; even when he is old he will not depart from it.', 'ref' => 'Proverbs 22:6'],
            ['id' => 2, 'text' => 'Children, obey your parents in the Lord, for this is right.', 'ref' => 'Ephesians 6:1'],
            ['id' => 3, 'text' => 'I can do all things through Christ who strengthens me.', 'ref' => 'Philippians 4:13'],
            ['id' => 4, 'text' => 'Be strong and courageous. Do not be afraid; do not be discouraged, for the Lord your God will be with you wherever you go.', 'ref' => 'Joshua 1:9'],
            ['id' => 5, 'text' => 'The Lord is my shepherd; I shall not want.', 'ref' => 'Psalm 23:1'],
            ['id' => 6, 'text' => 'Trust in the Lord with all your heart and lean not on your own understanding.', 'ref' => 'Proverbs 3:5'],
            ['id' => 7, 'text' => 'For I know the plans I have for you, declares the Lord, plans to prosper you and not to harm you, plans to give you hope and a future.', 'ref' => 'Jeremiah 29:11'],
            ['id' => 8, 'text' => 'Love is patient, love is kind. It does not envy, it does not boast, it is not proud.', 'ref' => '1 Corinthians 13:4'],
            ['id' => 9, 'text' => 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.', 'ref' => 'Romans 8:28'],
            ['id' => 10, 'text' => 'The fruit of the Spirit is love, joy, peace, forbearance, kindness, goodness, faithfulness, gentleness and self-control.', 'ref' => 'Galatians 5:22-23'],
        ],
        'timer' => ['active' => false, 'end_time' => null, 'label' => ''],
        'nextId' => 100,
    ];
}

// Router
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$path = parse_url($uri, PHP_URL_PATH);
$path = preg_replace('#^/api\.php#', '', $path);
if (empty($path)) $path = '/';

// ============ DASHBOARD ============
if ($path === '/dashboard' && $method === 'GET') {
    $data = loadData();
    $d = today();
    $chores = $data['chores'][$d] ?? [];
    $schedule = $data['schedules'][$d] ?? [];

    $dayOfYear = date('z');
    $verses = $data["verses"] ?? [];
    $verse = count($verses) > 0 ? $verses[$dayOfYear % count($verses)] : null;

    // Sort schedule by start time
    usort($schedule, function($a, $b) {
        return strcmp($a['time_start'], $b['time_start']);
    });

    $kidsData = $data['kids'] ?? [];
    $kids = array_map(function($kid) use ($chores) {
        $kidChores = array_values(array_filter($chores, function($c) use ($kid) { return $c['kid_id'] === $kid['id']; }));
        $done = count(array_filter($kidChores, function($c) { return $c['done']; }));
        return array_merge($kid, ['chores' => $kidChores, 'done' => $done, 'total' => count($kidChores)]);
    }, $kidsData);

    echo json_encode(['kids' => $kids, 'schedule' => $schedule, 'countdowns' => $data['countdowns'] ?? [], 'verse' => $verse, 'date' => $d]);
    exit;
}

// ============ KIDS ============
if (preg_match('#^/kids/(\d+)$#', $path, $m) && $method === 'PUT') {
    $data = loadData();
    $id = (int)$m[1];
    foreach ($data['kids'] as &$kid) {
        if ($kid['id'] === $id) {
            if (isset($body['color'])) $kid['color'] = $body['color'];
            if (isset($body['name'])) $kid['name'] = $body['name'];
            if (isset($body['initial'])) $kid['initial'] = $body['initial'];
            saveData($data);
            echo json_encode($kid);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

// ============ CHORES ============
if ($path === '/chores' && $method === 'GET') {
    $data = loadData();
    $d = $_GET['date'] ?? today();
    echo json_encode($data['chores'][$d] ?? []);
    exit;
}

if ($path === '/chores' && $method === 'POST') {
    $data = loadData();
    $d = $body['date'] ?? today();
    if (!isset($data['chores'][$d])) $data['chores'][$d] = [];
    $id = nextId($data);
    $data['chores'][$d][] = ['id' => $id, 'kid_id' => $body['kid_id'], 'title' => $body['title'], 'emoji' => $body['emoji'] ?? '', 'done' => false];
    saveData($data);
    echo json_encode(['id' => $id]);
    exit;
}

if (preg_match('#^/chores/(\d+)$#', $path, $m) && $method === 'PUT') {
    $data = loadData();
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    if (isset($data['chores'][$d])) {
        foreach ($data['chores'][$d] as &$chore) {
            if ($chore['id'] === $id) {
                if (isset($body['title'])) $chore['title'] = $body['title'];
                if (isset($body['emoji'])) $chore['emoji'] = $body['emoji'];
                if (isset($body['kid_id'])) $chore['kid_id'] = $body['kid_id'];
                saveData($data);
                echo json_encode($chore);
                exit;
            }
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

if (preg_match('#^/chores/(\d+)/toggle$#', $path, $m) && $method === 'PUT') {
    $data = loadData();
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    if (isset($data['chores'][$d])) {
        foreach ($data['chores'][$d] as &$chore) {
            if ($chore['id'] === $id) {
                $chore['done'] = !$chore['done'];
                saveData($data);
                echo json_encode(['id' => $id, 'done' => $chore['done']]);
                exit;
            }
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

if (preg_match('#^/chores/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $data = loadData();
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    if (isset($data['chores'][$d])) {
        $data['chores'][$d] = array_values(array_filter($data['chores'][$d], function($c) use ($id) { return $c['id'] !== $id; }));
        saveData($data);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ============ SCHEDULE ============
if ($path === '/schedule' && $method === 'GET') {
    $data = loadData();
    $d = $_GET['date'] ?? today();
    $sched = $data['schedules'][$d] ?? [];
    usort($sched, function($a, $b) { return strcmp($a['time_start'], $b['time_start']); });
    echo json_encode($sched);
    exit;
}

if ($path === '/schedule' && $method === 'POST') {
    $data = loadData();
    $d = $body['date'] ?? today();
    if (!isset($data['schedules'][$d])) $data['schedules'][$d] = [];
    $id = nextId($data);
    $data['schedules'][$d][] = [
        'id' => $id,
        'time_start' => $body['time_start'],
        'time_end' => $body['time_end'] ?? '',
        'title' => $body['title'],
        'emoji' => $body['emoji'] ?? '',
        'sort' => $body['sort'] ?? count($data['schedules'][$d]),
    ];
    usort($data['schedules'][$d], function($a, $b) { return $a['sort'] - $b['sort']; });
    saveData($data);
    echo json_encode(['id' => $id]);
    exit;
}

if ($path === '/schedule/template' && $method === 'POST') {
    $data = loadData();
    $d = $body['date'] ?? today();
    $data['schedules'][$d] = [];
    foreach ($body['blocks'] as $i => $b) {
        $data['schedules'][$d][] = [
            'id' => nextId($data),
            'time_start' => $b['time_start'],
            'time_end' => $b['time_end'] ?? '',
            'title' => $b['title'],
            'emoji' => $b['emoji'] ?? '',
            'sort' => $i,
        ];
    }
    saveData($data);
    echo json_encode(['ok' => true, 'count' => count($body['blocks'])]);
    exit;
}

if (preg_match('#^/schedule/(\d+)$#', $path, $m) && $method === 'PUT') {
    $data = loadData();
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    if (isset($data['schedules'][$d])) {
        foreach ($data['schedules'][$d] as &$block) {
            if ($block['id'] === $id) {
                if (isset($body['title'])) $block['title'] = $body['title'];
                if (isset($body['emoji'])) $block['emoji'] = $body['emoji'];
                if (isset($body['time_start'])) $block['time_start'] = $body['time_start'];
                if (isset($body['time_end'])) $block['time_end'] = $body['time_end'];
                saveData($data);
                echo json_encode($block);
                exit;
            }
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

if (preg_match('#^/schedule/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $data = loadData();
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    if (isset($data['schedules'][$d])) {
        $data['schedules'][$d] = array_values(array_filter($data['schedules'][$d], function($s) use ($id) { return $s['id'] !== $id; }));
        saveData($data);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ============ COUNTDOWNS ============
if ($path === '/countdowns' && $method === 'GET') {
    $data = loadData();
    echo json_encode($data['countdowns']);
    exit;
}

if ($path === '/countdowns' && $method === 'POST') {
    $data = loadData();
    $id = nextId($data);
    $data['countdowns'][] = ['id' => $id, 'label' => $body['label'], 'emoji' => $body['emoji'] ?? '', 'target_date' => $body['target_date']];
    saveData($data);
    echo json_encode(['id' => $id]);
    exit;
}

if (preg_match('#^/countdowns/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $data = loadData();
    $id = (int)$m[1];
    $data['countdowns'] = array_values(array_filter($data['countdowns'], function($c) use ($id) { return $c['id'] !== $id; }));
    saveData($data);
    echo json_encode(['ok' => true]);
    exit;
}

// ============ TIMER ============
if ($path === '/timer' && $method === 'GET') {
    $data = loadData();
    $timer = $data['timer'] ?? ['active' => false, 'end_time' => null, 'label' => ''];
    // Auto-expire: if end_time passed more than 60s ago, clear it
    if ($timer['active'] && $timer['end_time']) {
        $end = strtotime($timer['end_time']);
        if ($end && (time() - $end) > 60) {
            $timer = ['active' => false, 'end_time' => null, 'label' => ''];
            $data['timer'] = $timer;
            saveData($data);
        }
    }
    echo json_encode($timer);
    exit;
}

if ($path === '/timer' && $method === 'POST') {
    $data = loadData();
    $minutes = isset($body['minutes']) ? (int)$body['minutes'] : 0;
    $seconds = isset($body['seconds']) ? (int)$body['seconds'] : 0;
    $totalSec = $minutes * 60 + $seconds;
    if ($totalSec > 0) {
        $endTime = date('c', time() + $totalSec);
        $data['timer'] = [
            'active' => true,
            'end_time' => $endTime,
            'label' => $body['label'] ?? '',
            'duration_sec' => $totalSec,
        ];
    } else {
        $data['timer'] = ['active' => false, 'end_time' => null, 'label' => ''];
    }
    saveData($data);
    echo json_encode($data['timer']);
    exit;
}

if ($path === '/timer' && $method === 'DELETE') {
    $data = loadData();
    $data['timer'] = ['active' => false, 'end_time' => null, 'label' => ''];
    saveData($data);
    echo json_encode(['ok' => true]);
    exit;
}

// ============ VERSES ============
if ($path === '/verses' && $method === 'GET') {
    $data = loadData();
    echo json_encode($data['verses']);
    exit;
}

if ($path === '/verses' && $method === 'POST') {
    $data = loadData();
    $id = nextId($data);
    $data['verses'][] = ['id' => $id, 'text' => $body['text'], 'ref' => $body['reference'] ?? $body['ref'] ?? ''];
    saveData($data);
    echo json_encode(['id' => $id]);
    exit;
}

// ============ DISPLAY MODE ============
if ($path === '/mode' && $method === 'GET') {
    $settingsFile = '/home/pi/graber-hub/photo-settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : ['mode' => 'dashboard'];
    echo json_encode(['mode' => $settings['mode'] ?? 'dashboard']);
    exit;
}

if ($path === '/mode' && $method === 'POST') {
    $settingsFile = '/home/pi/graber-hub/photo-settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : ['mode' => 'dashboard'];
    if (isset($body['mode'])) {
        $settings['mode'] = $body['mode'];
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }
    echo json_encode(['mode' => $settings['mode']]);
    exit;
}


if ($path === "/photo-settings" && $method === "GET") {
    $settingsFile = "/home/pi/graber-hub/photo-settings.json";
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    echo json_encode($settings);
    exit;
}

if ($path === "/photo-settings" && $method === "POST") {
    $settingsFile = "/home/pi/graber-hub/photo-settings.json";
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    if (isset($body["album_url"])) $settings["album_url"] = $body["album_url"];
    if (isset($body["mode"])) $settings["mode"] = $body["mode"];
    if (isset($body["transition_seconds"])) $settings["transition_seconds"] = $body["transition_seconds"];
    if (isset($body["show_clock"])) $settings["show_clock"] = $body["show_clock"];
    if (isset($body["show_weather"])) $settings["show_weather"] = $body["show_weather"];
    if (isset($body["ken_burns"])) $settings["ken_burns"] = $body["ken_burns"];
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    echo json_encode($settings);
    exit;
}
// ============ HEARTBEAT (combined endpoint) ============
if ($path === '/heartbeat' && $method === 'GET') {
    $data = loadData();
    $d = today();
    $chores = $data['chores'][$d] ?? [];
    $schedule = $data['schedules'][$d] ?? [];
    $verses = $data['verses'] ?? [];
    $dayOfYear = date('z');
    $verse = count($verses) > 0 ? $verses[$dayOfYear % count($verses)] : null;

    usort($schedule, function($a, $b) {
        return strcmp($a['time_start'], $b['time_start']);
    });

    $kidsData = $data['kids'] ?? [];
    $kids = array_map(function($kid) use ($chores) {
        $kidChores = array_values(array_filter($chores, function($c) use ($kid) { return $c['kid_id'] === $kid['id']; }));
        $done = count(array_filter($kidChores, function($c) { return $c['done']; }));
        return array_merge($kid, ['chores' => $kidChores, 'done' => $done, 'total' => count($kidChores)]);
    }, $kidsData);

    $timer = $data['timer'] ?? ['active' => false, 'end_time' => null, 'label' => ''];

    $settingsFile = '/home/pi/graber-hub/photo-settings.json';
    $photoSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : ['mode' => 'dashboard'];
    $mode = $photoSettings['mode'] ?? 'dashboard';
    if ($mode === 'auto') {
        $now = date('H:i');
        $start = $photoSettings['auto_schedule']['photos_start'] ?? '20:00';
        $end = $photoSettings['auto_schedule']['photos_end'] ?? '07:00';
        if ($start > $end) {
            $resolvedMode = ($now >= $start || $now < $end) ? 'photos' : 'dashboard';
        } else {
            $resolvedMode = ($now >= $start && $now < $end) ? 'photos' : 'dashboard';
        }
    } else {
        $resolvedMode = $mode;
    }

    echo json_encode([
        'dashboard' => ['kids' => $kids, 'schedule' => $schedule, 'countdowns' => $data['countdowns'] ?? [], 'verse' => $verse, 'date' => $d],
        'timer' => $timer,
        'mode' => $resolvedMode,
        'rawMode' => $mode,
        'photoSettings' => $photoSettings,
        'ts' => time()
    ]);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'not found', 'path' => $path, 'method' => $method]);
