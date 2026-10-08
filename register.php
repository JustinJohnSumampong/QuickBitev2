<?php
require_once "config/database.php";
require_once "includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if (!$name || !$username || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        $err = "Enter valid information. Password must be at least 6 characters.";
    } elseif ($pass !== $pass2) {
        $err = "Passwords do not match.";
    } else {
        $s = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $s->execute([$email]);
        if ($s->fetch()) {
            $err = "Email already registered.";
        } else {
            $s = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $s->execute([$username]);
            if ($s->fetch()) {
                $err = "Username already taken. Please choose another.";
            } else {
                $h = password_hash($pass, PASSWORD_DEFAULT);
                $cols = "name, username, email, password, role, status";
                $s = $pdo->prepare("INSERT INTO users($cols) VALUES(?,?,?,?, 'customer',1)");
                $s->execute([$name, $username, $email, $h]);
                flash('success', 'Registration successful. Please login.');
                redirect('login.php');
            }
        }
    }
}

$page_title = "Welcome to Quickbite — Sign Up";
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title) ?></title>
<link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= $base_url ?>/assets/css/style.css" rel="stylesheet">
<style>
  :root{--pink-1:#ffe4e1;--pink-2:#ffd2cf;--pink-3:#f3b7b4;--peach:#ff9a3d;--line:#c46f68;}
  html,body{height:100%;margin:0}
  body.login-body{background:var(--pink-1);font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .login-wrap{min-height:100vh;display:grid;grid-template-columns:1fr 1fr}
  @media (max-width:860px){.login-wrap{grid-template-columns:1fr}}

  .login-brand{position:relative;overflow:hidden;
    background:
      radial-gradient(ellipse at 30% 20%, #ffd6d2 0%, transparent 55%),
      radial-gradient(ellipse at 75% 85%, #f8b8b4 0%, transparent 60%),
      linear-gradient(180deg, #ffe4e1 0%, #ffcbc7 100%);
  }
  .login-brand::after{content:"";position:absolute;inset:0;
    background-image:
      radial-gradient(2px 2px at 20% 30%, rgba(255,255,255,.5) 50%, transparent 51%),
      radial-gradient(1.5px 1.5px at 70% 55%, rgba(255,255,255,.4) 50%, transparent 51%),
      radial-gradient(2.5px 2.5px at 45% 80%, rgba(255,255,255,.45) 50%, transparent 51%);
    background-size:120px 120px, 150px 150px, 200px 200px;opacity:.6;pointer-events:none}

  .brand-center{position:relative;z-index:2;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem}
  .brand-logo{max-width:440px;width:100%;height:auto;display:block;filter:drop-shadow(0 10px 18px rgba(0,0,0,.12))}

  .doodle{position:absolute;z-index:1;opacity:.55}
  .doodle path,.doodle circle,.doodle line,.doodle rect{stroke:#9c524b;stroke-width:8;fill:none;stroke-linecap:round;stroke-linejoin:round}
  .d-pizza{top:62%;left:4%;width:180px;transform:rotate(-18deg)}
  .d-burger{bottom:-25px;left:-20px;width:220px}
  .d-coffee{bottom:12%;left:22%;width:120px}
  .d-truck{bottom:6%;right:5%;width:300px}
  .d-ring{position:absolute;border:5px solid #222;border-radius:50%;z-index:1;opacity:.8}
  .d-ring.r-teal{border-color:#4f8f9e}
  .d-ring.r1{top:68%;left:24%;width:42px;height:42px;border-width:6px}
  .d-ring.r2{bottom:24%;left:31%;width:54px;height:54px;border-width:6px}
  .d-ring.r3{top:74%;left:17%;width:46px;height:46px;border-width:6px}
  .d-ring.r4{top:8%;right:18%;width:38px;height:38px;border-color:#4f8f9e;border-width:5px}

  .login-card-side{display:flex;align-items:center;justify-content:center;padding:3rem 1.5rem;background:linear-gradient(160deg,#ffd2cf 0%,#ffbeba 45%,#ffd6cf 100%);position:relative;overflow:hidden}
  .login-card-side::before{content:"";position:absolute;top:-200px;right:-200px;width:520px;height:520px;border-radius:50%;background:rgba(255,255,255,.22);filter:blur(60px)}

  .login-card{position:relative;z-index:2;background:#fff;border-radius:36px;box-shadow:0 30px 80px rgba(90,40,40,.18), 0 8px 20px rgba(90,40,40,.06);padding:2.6rem 2.4rem;width:100%;max-width:520px;border:1px solid rgba(0,0,0,.04)}
  .login-welcome{font-size:2.5rem;font-weight:800;color:#111;margin-bottom:.3rem;text-align:center;letter-spacing:-.02em;font-family:Georgia,'Times New Roman',serif}
  .form-label-qb{font-family:Georgia,'Times New Roman',serif;font-size:1.2rem;color:#111;margin-bottom:.35rem;display:inline-block}
  .qb-input{width:100%;padding:.9rem 1rem;border:3px solid #111;border-radius:14px;font-size:1rem;box-shadow:4px 4px 0 #111;background:#fff;transition:.12s}
  .qb-input:focus{outline:0;box-shadow:6px 6px 0 #111;transform:translate(-1px,-1px)}
  .qb-input::placeholder{color:#8a8a8a}
  .pass-wrap{position:relative}
  .pass-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:transparent;border:0;color:#666;font-size:1.1rem;padding:4px 8px;cursor:pointer}
  .pass-toggle:hover{color:#111}

  .btn-login{display:block;width:100%;border:3px solid #111;background:#ff9a3d;color:#111;border-radius:999px;padding:.95rem 1rem;font-weight:900;letter-spacing:.08em;font-size:1.35rem;box-shadow:6px 6px 0 #111;transition:.15s;margin-bottom:1.1rem;margin-top:.5rem}
  .btn-login:hover{transform:translate(-2px,-2px);box-shadow:8px 8px 0 #111;color:#111}
  .btn-login:active{transform:translate(1px,1px);box-shadow:3px 3px 0 #111}

  .signup-line{text-align:center;font-family:Georgia,'Times New Roman',serif;font-size:1.15rem;color:#111;margin:0}
  .signup-line a{color:#0b5ed7;font-weight:700;text-decoration:underline;margin-left:.15rem}

  .err-box{border-radius:18px;border:3px solid #b91c1c;background:#fee2e2;color:#7f1d1d;padding:.75rem 1rem;margin-bottom:1.25rem;font-weight:600;display:flex;align-items:center;gap:.6rem;box-shadow:3px 3px 0 #b91c1c}
</style>
</head>
<body class="login-body">

<div class="login-wrap">

  <section class="login-brand">
    <span class="d-ring r1"></span>
    <span class="d-ring r2 r-teal"></span>
    <span class="d-ring r3"></span>

    <svg class="doodle d-pizza" viewBox="0 0 180 180" aria-hidden="true">
      <path d="M30 150 L150 150 L90 30 Z"/>
      <circle cx="72" cy="120" r="8"/>
      <circle cx="108" cy="118" r="10"/>
      <circle cx="90" cy="92" r="7"/>
      <circle cx="80" cy="70" r="4"/>
      <circle cx="102" cy="72" r="4"/>
      <path d="M45 148 Q60 156 80 150 M98 150 Q118 156 135 148"/>
    </svg>

    <svg class="doodle d-burger" viewBox="0 0 240 200" aria-hidden="true">
      <path d="M15 80 C30 30 210 30 225 80 Z"/>
      <circle cx="60" cy="60" r="3"/>
      <circle cx="100" cy="52" r="3"/>
      <circle cx="140" cy="54" r="3"/>
      <circle cx="180" cy="62" r="3"/>
      <rect x="10" y="88" width="220" height="10" rx="4"/>
      <path d="M20 108 Q40 98 60 108 T100 108 T140 108 T180 108 T220 108 L220 120 Q200 110 180 120 T140 120 T100 120 T60 120 T20 120 Z"/>
      <rect x="10" y="128" width="220" height="10" rx="4"/>
      <path d="M12 146 C28 192 212 192 228 146 Z"/>
    </svg>

    <svg class="doodle d-coffee" viewBox="0 0 140 160" aria-hidden="true">
      <rect x="30" y="20" width="78" height="110" rx="6"/>
      <path d="M108 45 H125 Q145 45 145 75 Q145 105 125 105 H108"/>
      <line x1="40" y1="20" x2="40" y2="5"/>
      <line x1="60" y1="20" x2="60" y2="-5"/>
      <line x1="80" y1="20" x2="80" y2="5"/>
      <circle cx="69" cy="90" r="12"/>
      <path d="M69 78 L69 102 M57 90 L81 90"/>
    </svg>

    <svg class="doodle d-truck" viewBox="0 0 300 150" aria-hidden="true">
      <rect x="12" y="34" width="130" height="76" rx="6"/>
      <rect x="142" y="56" width="100" height="54" rx="6"/>
      <line x1="0" y1="54" x2="-32" y2="54"/>
      <line x1="-2" y1="70" x2="-40" y2="70"/>
      <line x1="-4" y1="86" x2="-46" y2="86"/>
      <line x1="20" y1="60" x2="70" y2="60"/>
      <rect x="96" y="50" width="34" height="30" rx="4"/>
      <circle cx="62" cy="120" r="22"/>
      <circle cx="62" cy="120" r="8" fill="#ffd2cf" stroke-width="0"/>
      <circle cx="200" cy="120" r="22"/>
      <circle cx="200" cy="120" r="8" fill="#ffd2cf" stroke-width="0"/>
    </svg>

    <div class="brand-center">
      <img class="brand-logo" src="<?= $base_url ?>/assets/img/logo.png?v=2" alt="QuickBite — Food Delivered Fast">
    </div>
  </section>

  <section class="login-card-side">
    <span class="d-ring r4"></span>
    <div class="login-card">
      <h1 class="login-welcome">Welcome to Quickbite</h1>

      <?php if (isset($err)): ?>
        <div class="err-box"><i class="fa-solid fa-circle-exclamation"></i><?= e($err) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="on" novalidate>
        <div class="mb-3">
          <label class="form-label-qb" for="name">Full name:</label>
          <input id="name" class="qb-input" name="name" type="text" placeholder="Enter your full name." autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label-qb" for="username">Username:</label>
          <input id="username" class="qb-input" name="username" type="text" placeholder="Choose a username." autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label-qb" for="email">Email:</label>
          <input id="email" class="qb-input" name="email" type="email" placeholder="Enter your email." autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label-qb" for="password">Password:</label>
          <div class="pass-wrap">
            <input id="password" class="qb-input" name="password" type="password" placeholder="Create your password." autocomplete="new-password" required>
            <button type="button" class="pass-toggle" aria-label="Show password" onclick="togglePass('password', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label-qb" for="password_confirm">Confirm password:</label>
          <div class="pass-wrap">
            <input id="password_confirm" class="qb-input" name="password_confirm" type="password" placeholder="Confirm your password:" autocomplete="new-password" required>
            <button type="button" class="pass-toggle" aria-label="Show password" onclick="togglePass('password_confirm', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <button class="btn-login" type="submit">CREATE ACCOUNT</button>

        <p class="signup-line">Already have an account?<a href="<?= $base_url ?>/login.php">Log in.</a></p>
      </form>
    </div>
  </section>
</div>

<script>
function togglePass(id, btn){
  const input = document.getElementById(id);
  const i = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    i.className = 'fa-solid fa-eye-slash';
    btn.setAttribute('aria-label','Hide password');
  } else {
    input.type = 'password';
    i.className = 'fa-solid fa-eye';
    btn.setAttribute('aria-label','Show password');
  }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_url ?>/assets/js/app.js"></script>
</body>
</html>
