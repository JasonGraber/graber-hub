<?php
/**
 * Graber Hub Voice Webhook
 * Handles voice commands from Google Assistant (via IFTTT or direct webhook)
 * 
 * Endpoints:
 *   POST /voice.php/chore-done    — Mark a chore as done
 *   POST /voice.php/chores-left   — How many chores left for a kid
 *   POST /voice.php/whats-next    — What's the current schedule block
 *   GET  /voice.php/status        — Health check
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = '/home/pi/graber-hub/data.json';

function loadData() {
    global $DATA_FILE;
    return json_decode(file_get_contents($DATA_FILE), true);
}

function saveData($data) {
    global $DATA_FILE;
    file_put_contents($DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function today() { return date('Y-m-d'); }

function fuzzyMatch($needle, $haystack) {
    $needle = strtolower(trim($needle));
    $haystack = strtolower(trim($haystack));
    
    // Exact match
    if ($needle === $haystack) return 100;
    
    // Contains match
    if (strpos($haystack, $needle) !== false) return 90;
    if (strpos($needle, $haystack) !== false) return 85;
    
    // Word match — check if key words overlap
    $needleWords = explode(' ', $needle);
    $haystackWords = explode(' ', $haystack);
    $matches = 0;
    foreach ($needleWords as $nw) {
        foreach ($haystackWords as $hw) {
            if ($nw === $hw || (strlen($nw) > 3 && strpos($hw, $nw) !== false)) {
                $matches++;
                break;
            }
        }
    }
    if ($matches > 0) return 50 + ($matches / max(count($needleWords), count($haystackWords))) * 40;
    
    // Levenshtein distance
    $lev = levenshtein($needle, $haystack);
    $maxLen = max(strlen($needle), strlen($haystack));
    if ($maxLen === 0) return 0;
    $score = (1 - $lev / $maxLen) * 100;
    return max(0, $score);
}

function findKid($name, $kids) {
    $name = strtolower(trim($name));
    
    // Common nicknames/mishearings
    $aliases = [
        'aurora' => ['aurora', 'rora', 'aura', 'arora'],
        'deandre' => ['deandre', 'dre', 'andre', 'deondre', 'dee', 'dee andre'],
        'alexia' => ['alexia', 'lexi', 'lexia', 'alex', 'alexis'],
        'truett' => ['truett', 'tru', 'true', 'truet', 'truitt', 'truet'],
    ];
    
    foreach ($kids as $kid) {
        $kidName = strtolower($kid['name']);
        // Direct match
        if ($name === $kidName) return $kid;
        // Alias match
        if (isset($aliases[$kidName])) {
            foreach ($aliases[$kidName] as $alias) {
                if ($name === $alias || strpos($name, $alias) !== false) return $kid;
            }
        }
    }
    
    // Fuzzy fallback
    $bestScore = 0;
    $bestKid = null;
    foreach ($kids as $kid) {
        $score = fuzzyMatch($name, $kid['name']);
        if ($score > $bestScore && $score > 50) {
            $bestScore = $score;
            $bestKid = $kid;
        }
    }
    return $bestKid;
}

function findChore($choreName, $chores, $kidId) {
    $kidChores = array_filter($chores, function($c) use ($kidId) {
        return $c['kid_id'] === $kidId && !$c['done'];
    });
    
    $bestScore = 0;
    $bestChore = null;
    foreach ($kidChores as $chore) {
        $score = fuzzyMatch($choreName, $chore['title']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestChore = $chore;
        }
    }
    
    if ($bestScore > 40) return $bestChore;
    return null;
}

// Router
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$path = parse_url($uri, PHP_URL_PATH);
$path = preg_replace('#^/voice\.php#', '', $path);
if (empty($path)) $path = '/';

// ============ HEALTH CHECK ============
if ($path === '/status') {
    echo json_encode(['ok' => true, 'service' => 'graber-hub-voice']);
    exit;
}

// ============ CHORE DONE ============
// POST /voice.php/chore-done
// Body: { "kid": "Aurora", "chore": "make bed" }
// OR: { "text": "Aurora finished make bed" }  (for IFTTT text ingredient)
if ($path === '/chore-done' && $method === 'POST') {
    $data = loadData();
    $d = today();
    $chores = $data['chores'][$d] ?? [];
    
    $kidName = '';
    $choreName = '';
    
    if (isset($body['kid']) && isset($body['chore'])) {
        $kidName = $body['kid'];
        $choreName = $body['chore'];
    } elseif (isset($body['text'])) {
        // Parse "Aurora finished make bed" or "Aurora did make bed" or "Aurora make bed"
        $text = trim($body['text']);
        // Try patterns: "Kid finished/did/completed chore" or "Kid chore"
        if (preg_match('/^(\w+)\s+(?:finished|did|completed|done with|checked off)\s+(.+)$/i', $text, $m)) {
            $kidName = $m[1];
            $choreName = $m[2];
        } elseif (preg_match('/^(\w+)\s+(.+)$/i', $text, $m)) {
            $kidName = $m[1];
            $choreName = $m[2];
        } else {
            echo json_encode(['ok' => false, 'speech' => "Sorry, I didn't understand that. Try saying something like: Aurora finished make bed."]);
            exit;
        }
    } else {
        echo json_encode(['ok' => false, 'speech' => "I need to know which kid and which chore."]);
        exit;
    }
    
    // Find the kid
    $kid = findKid($kidName, $data['kids']);
    if (!$kid) {
        echo json_encode(['ok' => false, 'speech' => "I don't know a kid named $kidName. I know Aurora, Deandre, Alexia, and Truett."]);
        exit;
    }
    
    // Find the chore
    $chore = findChore($choreName, $chores, $kid['id']);
    if (!$chore) {
        // List remaining chores
        $remaining = array_values(array_filter($chores, function($c) use ($kid) {
            return $c['kid_id'] === $kid['id'] && !$c['done'];
        }));
        if (count($remaining) === 0) {
            echo json_encode(['ok' => true, 'speech' => $kid['name'] . " already finished all chores today! Amazing!"]);
            exit;
        }
        $choreList = implode(', ', array_map(function($c) { return $c['title']; }, $remaining));
        echo json_encode(['ok' => false, 'speech' => "I couldn't find that chore for " . $kid['name'] . ". The remaining chores are: $choreList"]);
        exit;
    }
    
    // Toggle it done
    foreach ($data['chores'][$d] as &$c) {
        if ($c['id'] === $chore['id']) {
            $c['done'] = true;
            break;
        }
    }
    saveData($data);
    
    // Count remaining
    $remaining = array_values(array_filter($data['chores'][$d], function($c) use ($kid) {
        return $c['kid_id'] === $kid['id'] && !$c['done'];
    }));
    $doneCount = count(array_filter($data['chores'][$d], function($c) use ($kid) {
        return $c['kid_id'] === $kid['id'] && $c['done'];
    }));
    $totalCount = $doneCount + count($remaining);
    
    // Build encouraging response
    $encouragements = [
        "Awesome job!", "Way to go!", "Nailed it!", "Fantastic!", 
        "You rock!", "Super star!", "Incredible!", "Keep it up!"
    ];
    $enc = $encouragements[array_rand($encouragements)];
    
    if (count($remaining) === 0) {
        $speech = $enc . " " . $kid['name'] . " finished " . $chore['title'] . 
                  " and that's ALL chores done! $doneCount out of $totalCount complete! You are a champion!";
    } else {
        $left = count($remaining);
        $speech = $enc . " " . $kid['name'] . " finished " . $chore['title'] . 
                  ". $doneCount out of $totalCount done, $left more to go!";
    }
    
    echo json_encode([
        'ok' => true,
        'speech' => $speech,
        'kid' => $kid['name'],
        'chore' => $chore['title'],
        'done' => $doneCount,
        'total' => $totalCount,
        'remaining' => count($remaining),
    ]);
    exit;
}

// ============ CHORES LEFT ============
// POST /voice.php/chores-left
// Body: { "kid": "Aurora" } OR { "text": "Aurora" }
if ($path === '/chores-left' && $method === 'POST') {
    $data = loadData();
    $d = today();
    $chores = $data['chores'][$d] ?? [];
    
    $kidName = $body['kid'] ?? $body['text'] ?? '';
    $kid = findKid($kidName, $data['kids']);
    
    if (!$kid) {
        // If no kid specified, give overview
        $speech = "Here's the chore report. ";
        foreach ($data['kids'] as $k) {
            $kidChores = array_filter($chores, function($c) use ($k) { return $c['kid_id'] === $k['id']; });
            $done = count(array_filter($kidChores, function($c) { return $c['done']; }));
            $total = count($kidChores);
            if ($total === 0) {
                $speech .= $k['name'] . " has no chores today. ";
            } elseif ($done === $total) {
                $speech .= $k['name'] . " finished all $total chores! ";
            } else {
                $speech .= $k['name'] . " has done $done out of $total. ";
            }
        }
        echo json_encode(['ok' => true, 'speech' => trim($speech)]);
        exit;
    }
    
    $kidChores = array_filter($chores, function($c) use ($kid) { return $c['kid_id'] === $kid['id']; });
    $done = count(array_filter($kidChores, function($c) { return $c['done']; }));
    $total = count($kidChores);
    $remaining = array_values(array_filter($kidChores, function($c) { return !$c['done']; }));
    
    if ($total === 0) {
        $speech = $kid['name'] . " has no chores today.";
    } elseif (count($remaining) === 0) {
        $speech = $kid['name'] . " already finished all $total chores! Champion!";
    } else {
        $choreList = implode(', ', array_map(function($c) { return $c['title']; }, $remaining));
        $speech = $kid['name'] . " has done $done out of $total. Still need to do: $choreList.";
    }
    
    echo json_encode(['ok' => true, 'speech' => $speech]);
    exit;
}

// ============ WHAT'S NEXT (Schedule) ============
// GET /voice.php/whats-next
if ($path === '/whats-next') {
    $data = loadData();
    $d = today();
    $schedule = $data['schedules'][$d] ?? [];
    
    if (empty($schedule)) {
        echo json_encode(['ok' => true, 'speech' => "No schedule set for today."]);
        exit;
    }
    
    usort($schedule, function($a, $b) { return strcmp($a['time_start'], $b['time_start']); });
    
    $now = date('H:i');
    $current = null;
    $next = null;
    
    foreach ($schedule as $i => $block) {
        $end = $block['time_end'] ?: $block['time_start'];
        if ($now >= $block['time_start'] && $now < $end) {
            $current = $block;
            if (isset($schedule[$i + 1])) $next = $schedule[$i + 1];
            break;
        } elseif ($now < $block['time_start']) {
            $next = $block;
            break;
        }
    }
    
    $speech = "";
    if ($current) {
        $speech = "Right now it's " . $current['title'] . " time";
        if ($next) {
            $speech .= ". Next up is " . $next['title'] . " at " . formatTime($next['time_start']);
        }
    } elseif ($next) {
        $speech = "The next thing on the schedule is " . $next['title'] . " at " . formatTime($next['time_start']);
    } else {
        $speech = "The schedule for today is all done!";
    }
    
    echo json_encode(['ok' => true, 'speech' => $speech . "."]);
    exit;
}

function formatTime($t) {
    $parts = explode(':', $t);
    $h = (int)$parts[0];
    $m = $parts[1];
    $ampm = $h >= 12 ? 'PM' : 'AM';
    if ($h === 0) $h = 12;
    elseif ($h > 12) $h -= 12;
    return $h . ':' . $m . ' ' . $ampm;
}

// ============ IFTTT WEBHOOK FORMAT ============
// POST /voice.php/ifttt
// Body: { "value1": "Aurora finished make bed" }
// This is the format IFTTT sends from Google Assistant
if ($path === '/ifttt' && $method === 'POST') {
    $text = $body['value1'] ?? $body['text'] ?? '';
    
    if (empty($text)) {
        echo json_encode(['ok' => false, 'speech' => "I didn't catch that."]);
        exit;
    }
    
    // Route to chore-done with text parsing
    $body['text'] = $text;
    
    // Re-use chore-done logic
    $data = loadData();
    $d = today();
    $chores = $data['chores'][$d] ?? [];
    
    // Parse text
    $kidName = '';
    $choreName = '';
    
    if (preg_match('/^(\w+)\s+(?:finished|did|completed|done with|checked off)\s+(.+)$/i', $text, $m)) {
        $kidName = $m[1];
        $choreName = $m[2];
    } elseif (preg_match('/^how\s+many.*?(?:chores?|left|remaining).*?(\w+)$/i', $text, $m)) {
        // "How many chores does Aurora have left"
        $kidName = $m[1];
        $kid = findKid($kidName, $data['kids']);
        if ($kid) {
            $kidChores = array_filter($chores, function($c) use ($kid) { return $c['kid_id'] === $kid['id']; });
            $done = count(array_filter($kidChores, function($c) { return $c['done']; }));
            $total = count($kidChores);
            $remaining = array_values(array_filter($kidChores, function($c) { return !$c['done']; }));
            if (count($remaining) === 0) {
                echo json_encode(['ok' => true, 'speech' => $kid['name'] . " finished all $total chores! Champion!"]);
            } else {
                $choreList = implode(', ', array_map(function($c) { return $c['title']; }, $remaining));
                echo json_encode(['ok' => true, 'speech' => $kid['name'] . " has $done out of $total done. Remaining: $choreList."]);
            }
            exit;
        }
    } elseif (preg_match('/^(?:what|schedule|next).*$/i', $text)) {
        // Schedule query — redirect
        $schedule = $data['schedules'][$d] ?? [];
        usort($schedule, function($a, $b) { return strcmp($a['time_start'], $b['time_start']); });
        $now = date('H:i');
        $current = null; $next = null;
        foreach ($schedule as $i => $block) {
            $end = $block['time_end'] ?: $block['time_start'];
            if ($now >= $block['time_start'] && $now < $end) {
                $current = $block;
                if (isset($schedule[$i + 1])) $next = $schedule[$i + 1];
                break;
            } elseif ($now < $block['time_start']) { $next = $block; break; }
        }
        $speech = "";
        if ($current) { $speech = "Right now it's " . $current['title']; if ($next) $speech .= ". Next is " . $next['title']; }
        elseif ($next) { $speech = "Next up is " . $next['title'] . " at " . formatTime($next['time_start']); }
        else { $speech = "Schedule is all done for today!"; }
        echo json_encode(['ok' => true, 'speech' => $speech]);
        exit;
    } elseif (preg_match('/^(\w+)\s+(.+)$/i', $text, $m)) {
        $kidName = $m[1];
        $choreName = $m[2];
    }
    
    if (empty($kidName)) {
        echo json_encode(['ok' => false, 'speech' => "Sorry, I didn't understand. Try: Aurora finished make bed."]);
        exit;
    }
    
    $kid = findKid($kidName, $data['kids']);
    if (!$kid) {
        echo json_encode(['ok' => false, 'speech' => "I don't know $kidName. I know Aurora, Deandre, Alexia, and Truett."]);
        exit;
    }
    
    $chore = findChore($choreName, $chores, $kid['id']);
    if (!$chore) {
        $remaining = array_values(array_filter($chores, function($c) use ($kid) { return $c['kid_id'] === $kid['id'] && !$c['done']; }));
        if (count($remaining) === 0) {
            echo json_encode(['ok' => true, 'speech' => $kid['name'] . " already finished all chores!"]);
            exit;
        }
        $choreList = implode(', ', array_map(function($c) { return $c['title']; }, $remaining));
        echo json_encode(['ok' => false, 'speech' => "Couldn't find that chore for " . $kid['name'] . ". Remaining: $choreList"]);
        exit;
    }
    
    foreach ($data['chores'][$d] as &$c) {
        if ($c['id'] === $chore['id']) { $c['done'] = true; break; }
    }
    saveData($data);
    
    $remaining = array_values(array_filter($data['chores'][$d], function($c) use ($kid) { return $c['kid_id'] === $kid['id'] && !$c['done']; }));
    $doneCount = count(array_filter($data['chores'][$d], function($c) use ($kid) { return $c['kid_id'] === $kid['id'] && $c['done']; }));
    $totalCount = $doneCount + count($remaining);
    
    $encs = ["Awesome!", "Way to go!", "Nailed it!", "You rock!", "Super star!"];
    $enc = $encs[array_rand($encs)];
    
    if (count($remaining) === 0) {
        $speech = "$enc " . $kid['name'] . " finished " . $chore['title'] . " and ALL chores are done! $doneCount out of $totalCount! Champion!";
    } else {
        $left = count($remaining);
        $speech = "$enc " . $kid['name'] . " checked off " . $chore['title'] . ". $doneCount of $totalCount done, $left to go!";
    }
    
    echo json_encode(['ok' => true, 'speech' => $speech]);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'not found', 'path' => $path]);
