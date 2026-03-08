<?php
/**
 * Graber Hub Photo Frame API
 * Fetches photos from a Google Photos shared album, caches URLs, serves to dashboard
 * 
 * Endpoints:
 *   GET  /photos.php/list        — Get cached photo URLs (fetches from album if stale)
 *   GET  /photos.php/refresh     — Force re-fetch from Google Photos album
 *   GET  /photos.php/settings    — Get photo frame settings
 *   POST /photos.php/settings    — Update photo frame settings
 *   GET  /photos.php/proxy/{idx} — Proxy a photo through the Pi (avoids CORS)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$SETTINGS_FILE = '/home/pi/graber-hub/photo-settings.json';
$CACHE_FILE = '/home/pi/graber-hub/photo-cache.json';
$CACHE_MAX_AGE = 3600; // Re-fetch album every hour

function loadSettings() {
    global $SETTINGS_FILE;
    if (!file_exists($SETTINGS_FILE)) {
        $defaults = [
            'album_url' => '',
            'mode' => 'dashboard', // dashboard | photos | auto
            'transition_seconds' => 30,
            'auto_schedule' => [
                'photos_start' => '20:00',  // 8 PM
                'photos_end' => '07:00',    // 7 AM
            ],
            'show_clock' => true,
            'show_weather' => true,
            'show_date' => true,
            'ken_burns' => true,
        ];
        file_put_contents($SETTINGS_FILE, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    return json_decode(file_get_contents($SETTINGS_FILE), true);
}

function saveSettings($settings) {
    global $SETTINGS_FILE;
    file_put_contents($SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
}

function loadCache() {
    global $CACHE_FILE;
    if (!file_exists($CACHE_FILE)) return null;
    return json_decode(file_get_contents($CACHE_FILE), true);
}

function saveCache($data) {
    global $CACHE_FILE;
    file_put_contents($CACHE_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function fetchAlbumPhotos($albumUrl) {
    if (empty($albumUrl)) return [];
    
    // Follow redirects to get the actual album page
    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (X11; Linux armv7l) AppleWebKit/537.36 Chrome/96\r\n",
            'follow_location' => true,
            'max_redirects' => 5,
            'timeout' => 15,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    
    $html = @file_get_contents($albumUrl, false, $ctx);
    if ($html === false) return [];
    
    // Extract lh3.googleusercontent.com URLs from the page
    $urls = [];
    
    // Pattern 1: Direct image URLs in the page source
    if (preg_match_all('#(https://lh3\.googleusercontent\.com/pw/[A-Za-z0-9_\-]+)#', $html, $matches)) {
        $urls = array_unique($matches[1]);
    }
    
    // Pattern 2: Also check for /p/ style URLs  
    if (preg_match_all('#(https://lh3\.googleusercontent\.com/[A-Za-z0-9_\-/]+)(?=["\s=])#', $html, $matches)) {
        $urls = array_unique(array_merge($urls, $matches[1]));
    }
    
    // Clean URLs and add size parameter for high quality
    $cleaned = [];
    foreach ($urls as $url) {
        // Remove any existing size params
        $url = preg_replace('#=[whsmcr]\d+.*$#', '', $url);
        // Skip tiny thumbnails (profile pics etc)
        if (strlen($url) < 60) continue;
        // Add high-res size param (1920x1080 for the DAKboard)
        $cleaned[] = $url . '=w3840-h2160-no';
    }
    
    return array_values(array_unique($cleaned));
}

// Router
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$path = parse_url($uri, PHP_URL_PATH);
$path = preg_replace('#^/photos\.php#', '', $path);
if (empty($path)) $path = '/';

// ============ SETTINGS ============
if ($path === '/settings' && $method === 'GET') {
    echo json_encode(loadSettings());
    exit;
}

if ($path === '/settings' && $method === 'POST') {
    $settings = loadSettings();
    if (isset($body['album_url'])) $settings['album_url'] = $body['album_url'];
    if (isset($body['mode'])) $settings['mode'] = $body['mode'];
    if (isset($body['transition_seconds'])) $settings['transition_seconds'] = (int)$body['transition_seconds'];
    if (isset($body['show_clock'])) $settings['show_clock'] = (bool)$body['show_clock'];
    if (isset($body['show_weather'])) $settings['show_weather'] = (bool)$body['show_weather'];
    if (isset($body['show_date'])) $settings['show_date'] = (bool)$body['show_date'];
    if (isset($body['ken_burns'])) $settings['ken_burns'] = (bool)$body['ken_burns'];
    if (isset($body['auto_schedule'])) {
        if (isset($body['auto_schedule']['photos_start'])) $settings['auto_schedule']['photos_start'] = $body['auto_schedule']['photos_start'];
        if (isset($body['auto_schedule']['photos_end'])) $settings['auto_schedule']['photos_end'] = $body['auto_schedule']['photos_end'];
    }
    saveSettings($settings);
    
    // If album URL changed, clear cache to force re-fetch
    if (isset($body['album_url'])) {
        saveCache(null);
    }
    
    echo json_encode($settings);
    exit;
}

// ============ LIST PHOTOS ============
if ($path === '/list' && $method === 'GET') {
    $settings = loadSettings();
    $cache = loadCache();
    
    // Check if cache is fresh enough
    if ($cache && isset($cache['fetched_at']) && (time() - $cache['fetched_at']) < $CACHE_MAX_AGE) {
        echo json_encode([
            'photos' => $cache['photos'],
            'count' => count($cache['photos']),
            'fetched_at' => $cache['fetched_at'],
            'album_url' => $settings['album_url'],
            'cached' => true,
        ]);
        exit;
    }
    
    // Fetch fresh
    $photos = fetchAlbumPhotos($settings['album_url']);
    if (count($photos) > 0 || empty($settings['album_url'])) {
        saveCache(['photos' => $photos, 'fetched_at' => time()]);
    } elseif ($cache) {
        // Fetch failed but we have old cache — use it
        $photos = $cache['photos'];
    }
    
    echo json_encode([
        'photos' => $photos,
        'count' => count($photos),
        'fetched_at' => time(),
        'album_url' => $settings['album_url'],
        'cached' => false,
    ]);
    exit;
}

// ============ REFRESH ============
if ($path === '/refresh') {
    $settings = loadSettings();
    $photos = fetchAlbumPhotos($settings['album_url']);
    saveCache(['photos' => $photos, 'fetched_at' => time()]);
    echo json_encode([
        'photos' => $photos,
        'count' => count($photos),
        'refreshed' => true,
    ]);
    exit;
}

// ============ PROXY ============
// Serves an actual image (not JSON) — proxies through Pi to avoid CORS issues
if (preg_match('#^/proxy/(\d+)$#', $path, $m)) {
    $idx = (int)$m[1];
    $cache = loadCache();
    if (!$cache || !isset($cache['photos'][$idx])) {
        http_response_code(404);
        echo json_encode(['error' => 'photo not found']);
        exit;
    }
    
    $url = $cache['photos'][$idx];
    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0\r\n",
            'follow_location' => true,
            'timeout' => 10,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    
    $img = @file_get_contents($url, false, $ctx);
    if ($img === false) {
        http_response_code(502);
        echo json_encode(['error' => 'failed to fetch photo']);
        exit;
    }
    
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    echo $img;
    exit;
}

// ============ CURRENT MODE ============
// Returns what mode the dashboard should be in right now
if ($path === '/current-mode') {
    $settings = loadSettings();
    $mode = $settings['mode'];
    
    if ($mode === 'auto') {
        $now = date('H:i');
        $start = $settings['auto_schedule']['photos_start'];
        $end = $settings['auto_schedule']['photos_end'];
        
        // Handle overnight schedule (e.g., 20:00 to 07:00)
        if ($start > $end) {
            // Photos mode if after start OR before end
            $mode = ($now >= $start || $now < $end) ? 'photos' : 'dashboard';
        } else {
            $mode = ($now >= $start && $now < $end) ? 'photos' : 'dashboard';
        }
    }
    
    echo json_encode(['mode' => $mode, 'settings' => $settings]);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'not found']);
