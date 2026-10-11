<?php
date_default_timezone_set('America/New_York');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Data-Version');
header('Access-Control-Expose-Headers: X-Data-Version');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = getenv('GRABER_HUB_DATA_FILE') ?: '/home/pi/graber-hub/data.json';

function loadData() {
    global $DATA_FILE;
    if (!file_exists($DATA_FILE)) {
        $data = getDefaultData();
        saveData($data);
        return loadData();
    }
    $handle = fopen($DATA_FILE, 'r');
    if (!$handle || !flock($handle, LOCK_SH)) {
        throw new RuntimeException('Unable to read dashboard data');
    }
    $json = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException('Dashboard data is invalid JSON');
    }
    return $data;
}

function saveData($data) {
    global $DATA_FILE;
    $handle = fopen($DATA_FILE, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock dashboard data');
    }

    rewind($handle);
    $existingJson = stream_get_contents($handle);
    $existing = $existingJson === '' ? null : json_decode($existingJson, true);
    $incomingVersion = currentDataVersion($data);
    if (is_array($existing)) {
        $serverVersion = currentDataVersion($existing);
        if ($incomingVersion !== $serverVersion) {
            sendDataVersion($serverVersion);
            flock($handle, LOCK_UN);
            fclose($handle);
            http_response_code(409);
            echo json_encode(['error' => 'stale data', 'data_version' => $serverVersion]);
            exit;
        }
    }

    $data['data_version'] = $incomingVersion + 1;
    writeDataToHandle($handle, $data);
    sendDataVersion($data['data_version']);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function writeDataToHandle($handle, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Unable to encode dashboard data');
    }
    rewind($handle);
    if (!ftruncate($handle, 0) || fwrite($handle, $json) === false) {
        throw new RuntimeException('Unable to save dashboard data');
    }
    fflush($handle);
}

function currentDataVersion($data) {
    return max(1, (int)($data['data_version'] ?? 1));
}

function sendDataVersion($version) {
    header('X-Data-Version: ' . $version);
}

/**
 * Serialize a read-modify-write operation behind one file lock. This prevents
 * two phones (or rapid taps) from overwriting each other's chore changes.
 */
function mutateData($mutator) {
    global $DATA_FILE;
    $handle = fopen($DATA_FILE, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        http_response_code(503);
        echo json_encode(['error' => 'dashboard data is busy']);
        exit;
    }

    rewind($handle);
    $json = stream_get_contents($handle);
    $data = $json === '' ? getDefaultData() : json_decode($json, true);
    if (!is_array($data)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        http_response_code(500);
        echo json_encode(['error' => 'dashboard data is invalid']);
        exit;
    }

    $version = currentDataVersion($data);
    $clientVersion = isset($_SERVER['HTTP_X_DATA_VERSION'])
        ? (int)$_SERVER['HTTP_X_DATA_VERSION']
        : null;
    if ($clientVersion !== null && $clientVersion !== $version) {
        sendDataVersion($version);
        flock($handle, LOCK_UN);
        fclose($handle);
        http_response_code(409);
        echo json_encode(['error' => 'stale data', 'data_version' => $version]);
        exit;
    }

    $result = $mutator($data);
    $status = (int)($result['__status'] ?? 200);
    unset($result['__status']);
    if ($status >= 400) {
        sendDataVersion($version);
        flock($handle, LOCK_UN);
        fclose($handle);
        http_response_code($status);
        echo json_encode($result);
        exit;
    }

    $data['data_version'] = $version + 1;
    writeDataToHandle($handle, $data);
    sendDataVersion($data['data_version']);
    flock($handle, LOCK_UN);
    fclose($handle);
    $result['data_version'] = $data['data_version'];
    echo json_encode($result);
    exit;
}

function today() {
    return date('Y-m-d');
}

function nextId(&$data) {
    $data['nextId'] = ($data['nextId'] ?? 100) + 1;
    return $data['nextId'];
}

function normalizeDate($value, $fallback = null) {
    $candidate = is_string($value) ? trim($value) : '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $candidate);
    return $date && $date->format('Y-m-d') === $candidate
        ? $candidate
        : ($fallback ?? today());
}

function normalizeIntList($values, $min, $max) {
    if (!is_array($values)) return [];
    $normalized = [];
    foreach ($values as $value) {
        $number = (int)$value;
        if ($number >= $min && $number <= $max) $normalized[] = $number;
    }
    $normalized = array_values(array_unique($normalized));
    sort($normalized);
    return $normalized;
}

function normalizeChoreTemplate($input, $existing = []) {
    $merged = array_merge($existing, is_array($input) ? $input : []);
    $repeat = in_array(($merged['repeat'] ?? 'none'), ['daily', 'weekly', 'monthly', 'none'], true)
        ? $merged['repeat']
        : 'none';
    $legacyEveryOtherWeek = filter_var($merged['every_other_week'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $weekInterval = (int)($merged['week_interval'] ?? ($legacyEveryOtherWeek ? 2 : 1));
    if (!in_array($weekInterval, [1, 2, 4], true)) $weekInterval = 1;

    return [
        'id' => (int)($merged['id'] ?? 0),
        'kid_id' => (int)($merged['kid_id'] ?? 0),
        'title' => trim((string)($merged['title'] ?? '')),
        'emoji' => (string)($merged['emoji'] ?? ''),
        'repeat' => $repeat,
        'days_of_week' => normalizeIntList($merged['days_of_week'] ?? [], 0, 6),
        'week_interval' => $weekInterval,
        'every_other_week' => $weekInterval === 2,
        'days_of_month' => normalizeIntList($merged['days_of_month'] ?? [], 1, 31),
        'start_date' => normalizeDate($merged['start_date'] ?? null),
        'active' => filter_var($merged['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ];
}

function choreTemplateDueOnDate($template, $date) {
    if (!($template['active'] ?? true)) return false;
    $target = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $start = DateTimeImmutable::createFromFormat('!Y-m-d', normalizeDate($template['start_date'] ?? null));
    if (!$target || !$start || $target < $start) return false;

    $repeat = $template['repeat'] ?? 'none';
    if ($repeat === 'daily') return true;
    if ($repeat === 'none') return $target == $start;
    if ($repeat === 'monthly') {
        $days = normalizeIntList($template['days_of_month'] ?? [], 1, 31);
        if (!$days) $days = [(int)$start->format('j')];
        return in_array((int)$target->format('j'), $days, true);
    }
    if ($repeat === 'weekly') {
        $days = normalizeIntList($template['days_of_week'] ?? [], 0, 6);
        if (!$days) $days = [(int)$start->format('w')];
        if (!in_array((int)$target->format('w'), $days, true)) return false;
        $interval = (int)($template['week_interval'] ?? (($template['every_other_week'] ?? false) ? 2 : 1));
        if (!in_array($interval, [1, 2, 4], true)) $interval = 1;
        $daysSinceStart = (int)$start->diff($target)->format('%a');
        return intdiv($daysSinceStart, 7) % $interval === 0;
    }
    return false;
}

function normalizeChoreTitle($title) {
    $title = trim((string)$title);
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title);
    if ($normalized === null) {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', ' ', $title);
    }
    $normalized = trim(preg_replace('/\s+/', ' ', (string)$normalized));
    return function_exists('mb_strtolower')
        ? mb_strtolower($normalized, 'UTF-8')
        : strtolower($normalized);
}

function sameChoreIdentity($left, $right) {
    if ((int)($left['kid_id'] ?? 0) !== (int)($right['kid_id'] ?? 0)) return false;
    $leftTitle = normalizeChoreTitle($left['title'] ?? '');
    $rightTitle = normalizeChoreTitle($right['title'] ?? '');
    return $leftTitle !== '' && $leftTitle === $rightTitle;
}

function selectDashboardVerse($data) {
    $verses = $data['verses'] ?? [];
    if (!$verses) return null;

    $activeVerseId = $data['active_verse_id'] ?? null;
    if ($activeVerseId !== null) {
        foreach ($verses as $verse) {
            if ((int)($verse['id'] ?? 0) === (int)$activeVerseId) return $verse;
        }
    }

    return $verses[(int)date('z') % count($verses)];
}

/** Move yesterday's unfinished chores into today, merging same-kid/same-title matches. */
function rollOverUnfinishedChores(&$data, $date) {
    if ($date !== today()) return false;

    $target = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$target) return false;
    $previousDate = $target->modify('-1 day')->format('Y-m-d');
    $previousChores = $data['chores'][$previousDate] ?? [];
    if (!is_array($previousChores) || !$previousChores) return false;

    $remaining = [];
    $changed = false;
    foreach ($previousChores as $chore) {
        if (!empty($chore['done'])) {
            $remaining[] = $chore;
            continue;
        }

        $alreadyExists = false;
        foreach ($data['chores'][$date] as $targetChore) {
            if (sameChoreIdentity($targetChore, $chore)) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $data['chores'][$date][] = $chore;
        }
        $changed = true;
    }

    if ($changed) {
        $data['chores'][$previousDate] = $remaining;
    }
    return $changed;
}

/** Materialize recurring chores and roll unfinished chores forward once, under one lock. */
function loadDataWithRecurringChores($date) {
    global $DATA_FILE;
    $date = normalizeDate($date);
    $handle = fopen($DATA_FILE, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock dashboard data');
    }

    rewind($handle);
    $json = stream_get_contents($handle);
    $isNewDataFile = $json === '';
    $data = $isNewDataFile ? getDefaultData() : json_decode($json, true);
    if (!is_array($data)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw new RuntimeException('Dashboard data is invalid JSON');
    }

    if (!isset($data['chores']) || !is_array($data['chores'])) {
        $data['chores'] = [];
    }
    if (!isset($data['schedules']) || !is_array($data['schedules'])) {
        $data['schedules'] = [];
    }
    if (!isset($data['chores'][$date]) || !is_array($data['chores'][$date])) {
        $data['chores'][$date] = [];
    }
    $changed = $isNewDataFile;
    foreach (($data['chore_templates'] ?? []) as $rawTemplate) {
        $template = normalizeChoreTemplate($rawTemplate, $rawTemplate);
        if (!choreTemplateDueOnDate($template, $date)) continue;
        $alreadyExists = false;
        foreach ($data['chores'][$date] as $chore) {
            if (
                (int)($chore['template_id'] ?? 0) === $template['id'] ||
                sameChoreIdentity($chore, $template)
            ) {
                $alreadyExists = true;
                break;
            }
        }
        if ($alreadyExists) continue;
        $data['chores'][$date][] = [
            'id' => nextId($data),
            'kid_id' => $template['kid_id'],
            'title' => $template['title'],
            'emoji' => $template['emoji'],
            'done' => false,
            'template_id' => $template['id'],
        ];
        $changed = true;
    }

    if (rollOverUnfinishedChores($data, $date)) {
        $changed = true;
    }

    if ($changed) {
        $data['data_version'] = currentDataVersion($data) + 1;
        writeDataToHandle($handle, $data);
    }
    sendDataVersion(currentDataVersion($data));
    flock($handle, LOCK_UN);
    fclose($handle);
    return $data;
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
        'chore_templates' => [],
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
        'data_version' => 1,
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
    $d = today();
    $data = loadDataWithRecurringChores($d);
    $dataVersion = currentDataVersion($data);
    sendDataVersion($dataVersion);
    $chores = $data['chores'][$d] ?? [];
    $schedule = $data['schedules'][$d] ?? [];

    $verse = selectDashboardVerse($data);

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

    echo json_encode(['kids' => $kids, 'schedule' => $schedule, 'countdowns' => $data['countdowns'] ?? [], 'verse' => $verse, 'date' => $d, 'data_version' => $dataVersion]);
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
    $d = $_GET['date'] ?? today();
    $data = loadDataWithRecurringChores($d);
    echo json_encode($data['chores'][$d] ?? []);
    exit;
}

if ($path === '/chores' && $method === 'POST') {
    mutateData(function (&$data) use ($body) {
        $d = $body['date'] ?? today();
        if (!isset($data['chores'][$d])) $data['chores'][$d] = [];
        $id = nextId($data);
        $data['chores'][$d][] = ['id' => $id, 'kid_id' => $body['kid_id'], 'title' => trim($body['title']), 'emoji' => $body['emoji'] ?? '', 'done' => false];
        return ['id' => $id];
    });
}

if (preg_match('#^/chores/(\d+)$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    mutateData(function (&$data) use ($id, $d, $body) {
        if (isset($data['chores'][$d])) {
            foreach ($data['chores'][$d] as &$chore) {
                if ($chore['id'] === $id) {
                    if (isset($body['title'])) $chore['title'] = trim($body['title']);
                    if (isset($body['emoji'])) $chore['emoji'] = $body['emoji'];
                    if (isset($body['kid_id'])) $chore['kid_id'] = $body['kid_id'];
                    return $chore;
                }
            }
        }
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if (preg_match('#^/chores/(\d+)/toggle$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    mutateData(function (&$data) use ($id, $d) {
        if (isset($data['chores'][$d])) {
            foreach ($data['chores'][$d] as &$chore) {
                if ($chore['id'] === $id) {
                    $chore['done'] = !$chore['done'];
                    return ['id' => $id, 'done' => $chore['done']];
                }
            }
        }
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if (preg_match('#^/chores/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $id = (int)$m[1];
    $d = $_GET['date'] ?? today();
    mutateData(function (&$data) use ($id, $d) {
        $chores = $data['chores'][$d] ?? [];
        $remaining = array_values(array_filter($chores, function($c) use ($id) { return $c['id'] !== $id; }));
        if (count($remaining) === count($chores)) {
            return ['__status' => 404, 'error' => 'not found'];
        }
        $data['chores'][$d] = $remaining;
        return ['ok' => true];
    });
}

// ============ CHORE TEMPLATES ============
if ($path === '/chore-templates' && $method === 'GET') {
    $data = loadData();
    sendDataVersion(currentDataVersion($data));
    $templates = array_map(function ($template) {
        return normalizeChoreTemplate($template, $template);
    }, $data['chore_templates'] ?? []);
    echo json_encode($templates);
    exit;
}

if ($path === '/chore-templates' && $method === 'POST') {
    mutateData(function (&$data) use ($body) {
        $template = normalizeChoreTemplate($body);
        if ($template['kid_id'] <= 0 || $template['title'] === '') {
            return ['__status' => 422, 'error' => 'kid_id and title are required'];
        }
        $template['id'] = nextId($data);
        if (!isset($data['chore_templates']) || !is_array($data['chore_templates'])) {
            $data['chore_templates'] = [];
        }
        $data['chore_templates'][] = $template;
        return ['id' => $template['id']];
    });
}

if (preg_match('#^/chore-templates/(\d+)$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    mutateData(function (&$data) use ($id, $body) {
        foreach (($data['chore_templates'] ?? []) as $index => $existing) {
            if ((int)($existing['id'] ?? 0) !== $id) continue;
            $template = normalizeChoreTemplate($body, $existing);
            $template['id'] = $id;
            if ($template['kid_id'] <= 0 || $template['title'] === '') {
                return ['__status' => 422, 'error' => 'kid_id and title are required'];
            }
            $data['chore_templates'][$index] = $template;
            return ['id' => $id, 'ok' => true];
        }
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if (preg_match('#^/chore-templates/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $id = (int)$m[1];
    mutateData(function (&$data) use ($id) {
        $templates = $data['chore_templates'] ?? [];
        $remaining = array_values(array_filter($templates, function ($template) use ($id) {
            return (int)($template['id'] ?? 0) !== $id;
        }));
        if (count($remaining) === count($templates)) {
            return ['__status' => 404, 'error' => 'not found'];
        }
        $data['chore_templates'] = $remaining;
        return ['ok' => true];
    });
}

// ============ SCHEDULE ============
if ($path === '/schedule' && $method === 'GET') {
    $data = loadData();
    sendDataVersion(currentDataVersion($data));
    $d = normalizeDate($_GET['date'] ?? '', today());
    $sched = $data['schedules'][$d] ?? [];
    usort($sched, function($a, $b) { return strcmp($a['time_start'], $b['time_start']); });
    echo json_encode($sched);
    exit;
}

if ($path === '/schedule' && $method === 'POST') {
    mutateData(function (&$data) use ($body) {
        $d = normalizeDate($body['date'] ?? '', today());
        $title = trim((string)($body['title'] ?? ''));
        $timeStart = trim((string)($body['time_start'] ?? ''));
        if ($title === '' || $timeStart === '') {
            return ['__status' => 422, 'error' => 'title and time_start are required'];
        }
        if (!isset($data['schedules'][$d])) $data['schedules'][$d] = [];
        $id = nextId($data);
        $data['schedules'][$d][] = [
            'id' => $id,
            'time_start' => $timeStart,
            'time_end' => trim((string)($body['time_end'] ?? '')),
            'title' => $title,
            'emoji' => $body['emoji'] ?? '',
            'sort' => $body['sort'] ?? count($data['schedules'][$d]),
        ];
        usort($data['schedules'][$d], function($a, $b) { return $a['sort'] - $b['sort']; });
        return ['id' => $id];
    });
}

if ($path === '/schedule/template' && $method === 'POST') {
    mutateData(function (&$data) use ($body) {
        $d = normalizeDate($body['date'] ?? '', today());
        $blocks = $body['blocks'] ?? null;
        if (!is_array($blocks)) {
            return ['__status' => 422, 'error' => 'blocks must be an array'];
        }
        $replacement = [];
        foreach ($blocks as $i => $b) {
            $title = trim((string)($b['title'] ?? ''));
            $timeStart = trim((string)($b['time_start'] ?? ''));
            if ($title === '' || $timeStart === '') {
                return ['__status' => 422, 'error' => 'every block requires title and time_start'];
            }
            $replacement[] = [
                'id' => nextId($data),
                'time_start' => $timeStart,
                'time_end' => trim((string)($b['time_end'] ?? '')),
                'title' => $title,
                'emoji' => $b['emoji'] ?? '',
                'sort' => $i,
            ];
        }
        $data['schedules'][$d] = $replacement;
        return ['ok' => true, 'count' => count($replacement)];
    });
}

if (preg_match('#^/schedule/(\d+)$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    $d = normalizeDate($_GET['date'] ?? '', today());
    mutateData(function (&$data) use ($id, $d, $body) {
        if (isset($data['schedules'][$d])) {
            foreach ($data['schedules'][$d] as &$block) {
                if ($block['id'] === $id) {
                    if (isset($body['title'])) $block['title'] = trim((string)$body['title']);
                    if (isset($body['emoji'])) $block['emoji'] = $body['emoji'];
                    if (isset($body['time_start'])) $block['time_start'] = trim((string)$body['time_start']);
                    if (isset($body['time_end'])) $block['time_end'] = trim((string)$body['time_end']);
                    return $block;
                }
            }
        }
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if (preg_match('#^/schedule/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $id = (int)$m[1];
    $d = normalizeDate($_GET['date'] ?? '', today());
    mutateData(function (&$data) use ($id, $d) {
        $schedule = $data['schedules'][$d] ?? [];
        $remaining = array_values(array_filter($schedule, function($s) use ($id) { return $s['id'] !== $id; }));
        if (count($remaining) === count($schedule)) {
            return ['__status' => 404, 'error' => 'not found'];
        }
        $data['schedules'][$d] = $remaining;
        return ['ok' => true];
    });
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
    sendDataVersion(currentDataVersion($data));
    echo json_encode($data['verses'] ?? []);
    exit;
}

if ($path === '/verses' && $method === 'POST') {
    mutateData(function (&$data) use ($body) {
        $text = trim((string)($body['text'] ?? ''));
        $reference = trim((string)($body['reference'] ?? $body['ref'] ?? ''));
        if ($text === '' || $reference === '') {
            return ['__status' => 422, 'error' => 'text and reference are required'];
        }
        $id = nextId($data);
        if (!isset($data['verses']) || !is_array($data['verses'])) $data['verses'] = [];
        $data['verses'][] = ['id' => $id, 'text' => $text, 'ref' => $reference];
        return ['success' => true, 'id' => $id];
    });
}

if (preg_match('#^/verses/(\d+)$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    mutateData(function (&$data) use ($id, $body) {
        if (!isset($data['verses']) || !is_array($data['verses'])) {
            return ['__status' => 404, 'error' => 'not found'];
        }
        foreach ($data['verses'] as &$verse) {
            if ((int)($verse['id'] ?? 0) !== $id) continue;
            $text = trim((string)($body['text'] ?? $verse['text'] ?? ''));
            $reference = trim((string)($body['reference'] ?? $body['ref'] ?? $verse['ref'] ?? ''));
            if ($text === '' || $reference === '') {
                return ['__status' => 422, 'error' => 'text and reference are required'];
            }
            $verse['text'] = $text;
            $verse['ref'] = $reference;
            return ['success' => true, 'id' => $id];
        }
        unset($verse);
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if (preg_match('#^/verses/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $id = (int)$m[1];
    mutateData(function (&$data) use ($id) {
        $verses = $data['verses'] ?? [];
        $remaining = array_values(array_filter($verses, function ($verse) use ($id) {
            return (int)($verse['id'] ?? 0) !== $id;
        }));
        if (count($remaining) === count($verses)) {
            return ['__status' => 404, 'error' => 'not found'];
        }
        $data['verses'] = $remaining;
        if ((int)($data['active_verse_id'] ?? 0) === $id) {
            $data['active_verse_id'] = null;
        }
        return ['success' => true];
    });
}

if ($path === '/verses/active' && $method === 'GET') {
    $data = loadData();
    sendDataVersion(currentDataVersion($data));
    echo json_encode(['active_verse_id' => $data['active_verse_id'] ?? null]);
    exit;
}

if (preg_match('#^/verses/active/(\d+)$#', $path, $m) && $method === 'PUT') {
    $id = (int)$m[1];
    mutateData(function (&$data) use ($id) {
        foreach (($data['verses'] ?? []) as $verse) {
            if ((int)($verse['id'] ?? 0) === $id) {
                $data['active_verse_id'] = $id;
                return ['success' => true, 'id' => $id];
            }
        }
        return ['__status' => 404, 'error' => 'not found'];
    });
}

if ($path === '/verses/active' && $method === 'DELETE') {
    mutateData(function (&$data) {
        $data['active_verse_id'] = null;
        return ['success' => true];
    });
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
    $d = today();
    $data = loadDataWithRecurringChores($d);
    $dataVersion = currentDataVersion($data);
    sendDataVersion($dataVersion);
    $chores = $data['chores'][$d] ?? [];
    $schedule = $data['schedules'][$d] ?? [];
    $verse = selectDashboardVerse($data);

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
        'dashboard' => ['kids' => $kids, 'schedule' => $schedule, 'countdowns' => $data['countdowns'] ?? [], 'verse' => $verse, 'date' => $d, 'data_version' => $dataVersion],
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
