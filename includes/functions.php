<?php
if (session_status() === PHP_SESSION_NONE) {
    // Use a dedicated session name so QuickBite's session is not shared with
    // other apps served from the same host (e.g. /marketplace, /fastbite).
    session_name('quickbite_session');
    session_start();
}

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header("Location: $url"); exit; }
function is_logged_in() { return isset($_SESSION['user']['id'], $_SESSION['user']['role']); }

// Absolute path to login.php relative to the app root, so pages inside
// admin/ and rider/ redirect here instead of the non-existent
// admin/login.php or rider/login.php (which 404'd for logged-out admins).
function login_url() {
    $docroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    if ($docroot !== '' && stripos($appRoot, $docroot) === 0) {
        return rtrim(substr($appRoot, strlen($docroot)), '/') . '/login.php';
    }
    // Fallback: walk up from the current script if it sits in admin/ or rider/.
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (preg_match('#/(admin|rider)$#', $dir)) $dir = substr($dir, 0, strrpos($dir, '/'));
    return $dir . '/login.php';
}

function require_login() { if (!is_logged_in()) redirect(login_url()); }
function require_role($role) {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403); die("Access denied.");
    }
}
function money($n) { return '₱' . number_format((float)$n, 2); }
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }
function flash($key, $msg=null) {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
    $m = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $m;
}
function order_number() { return 'QB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)); }

// Restore the session from the "Remember me" cookie set at login.
if (!is_logged_in() && isset($pdo) && !empty($_COOKIE['qb_remember'])) {
    $s = $pdo->prepare("SELECT * FROM users WHERE remember_token=? AND status=1");
    $s->execute([$_COOKIE['qb_remember']]);
    $u = $s->fetch();
    if ($u) {
        unset($u['password']);
        session_regenerate_id(true);
        $_SESSION['user'] = $u;
    }
}
?>
