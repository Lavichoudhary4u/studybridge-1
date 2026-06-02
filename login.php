<?php
session_start();
include("includes/db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, role, password FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === "admin") {
                header("Location: admin_panel.php");
                exit;
            }

            $check = $conn->prepare("SELECT qualification, phone, dob, institution, avatar FROM users WHERE id = ? LIMIT 1");
            $check->bind_param("i", $user['id']);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();

            $missing = false;
            $fields = ['qualification', 'phone', 'dob', 'institution', 'avatar'];
            foreach ($fields as $f) {
                if (empty($row[$f])) {
                    $missing = true;
                    break;
                }
            }

            if ($missing) {
                $_SESSION['must_complete_profile'] = true;
                header("Location: profile_update.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $message = "❌ Invalid password!";
        }
    } else {
        $message = "⚠️ No account found with this email!";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - StudyBridge</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root{
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --accent:#ffd166;
      --muted:#6c757d;
    }
    body{
      font-family:'Segoe UI',Roboto,"Helvetica Neue",Arial;
      background: linear-gradient(180deg,#f6f8fb 0%,#eef3f9 100%);
      min-height:100vh;
      display:flex;
      flex-direction:column;
      color:#243142;
    }

    /* Navbar */
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow:0 6px 18px rgba(34,50,80,0.18);
      padding:0.7rem 1rem;
    }
    .navbar-brand {
      color:#fff !important;
      font-weight:700;
      letter-spacing:0.4px;
    }

    /* Split layout */
    .auth-wrap{
      width:100%;
      max-width:1100px;
      margin:40px auto;
      padding:28px;
    }
    .split-card{
      display:flex;
      border-radius:14px;
      overflow:hidden;
      box-shadow:0 12px 36px rgba(18,33,64,0.08);
      background:#fff;
      min-height:420px;
    }

    /* Left: Form */
    .split-left{
      flex:1 1 420px;
      padding:38px;
      display:flex;
      flex-direction:column;
      justify-content:center;
    }
    .brand-title{
      font-weight:800;
      color:var(--primary-2);
      font-size:1.3rem;
      margin-bottom:6px;
    }
    .lead-small{ color:var(--muted); }
    .form-control{ border-radius:10px; }
    .btn-login{
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      border:none;
      border-radius:10px;
      padding:10px 14px;
      font-weight:700;
      color:#fff;
    }

    /* Right: Info panel */
    .split-right{
      width:420px;
      flex:0 0 420px;
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      display:flex;
      flex-direction:column;
      justify-content:center;
      padding:36px;
      position:relative;
      overflow:hidden;
    }
    .blob{
      position:absolute;
      border-radius:50%;
      filter:blur(6px);
      opacity:0.15;
    }
    .blob.one{width:200px;height:200px;top:-40px;left:-40px;background:#fff;}
    .blob.two{width:120px;height:120px;bottom:-30px;right:-20px;background:#fff;}
    .right-content{position:relative;z-index:2;}
    .right-content h3{font-weight:700;margin-bottom:8px;}
    .quote-box{
      background:rgba(255,255,255,0.12);
      border-radius:10px;
      padding:12px;
      font-style:italic;
      color:#fff;
      margin-top:16px;
      /* ADDED: smooth opacity transition to match register.php */
      transition: opacity 0.5s ease;
      opacity: 1;
      min-height:50px;
      display:flex;
      align-items:center;
    }

    /* Responsive */
    @media(max-width:991px){
      .split-card{flex-direction:column;}
      .split-right{width:100%;order:1;padding:28px;}
      .split-left{order:2;padding:28px;}
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">📚 StudyBridge</a>
    </div>
  </nav>

  <!-- Main -->
  <main class="auth-wrap" data-aos="fade-up">
    <div class="split-card">

      <!-- Left -->
      <div class="split-left" data-aos="fade-right">
        <div class="brand-title">Welcome back to StudyBridge</div>
        <div class="lead-small mb-4">Sign in to continue learning and sharing notes.</div>

        <?php if ($message != ""): ?>
          <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-envelope text-primary"></i></span>
              <input type="email" name="email" class="form-control border-start-0" placeholder="you@domain.com" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-primary"></i></span>
              <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="forgot_password.php" class="text-muted small">Forgot password?</a>
            <button type="submit" class="btn-login">LOGIN</button>
          </div>

          <div class="text-center text-muted small">Don’t have an account? <a href="register.php">Create one</a></div>
        </form>
      </div>

      <!-- Right -->
      <div class="split-right" data-aos="fade-left">
        <div class="blob one"></div>
        <div class="blob two"></div>

        <div class="right-content">
          <h3>Fuel your preparation 🚀</h3>
          <p>Access top-quality notes, study videos, and mock tests all in one place.</p>
          <div class="quote-box" id="rotatingQuote">"Consistency is the key to success."</div>
        </div>
      </div>

    </div>
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({ duration:700, once:true, offset:60 });

    // rotating quote — updated to match register.php animation:
    (function(){
      const quotes = [
        "Success doesn’t come to you, you go to it.",
        "Small steps every day lead to big results.",
        "Believe in yourself, you are capable of more than you know.",
        "Consistency is the key to success.",
        "Don’t watch the clock; do what it does. Keep going."
      ];
      let i = 0;
      const el = document.getElementById('rotatingQuote');

      // initial display (wrapped in quotes for consistency)
      el.textContent = "“" + quotes[0] + "”";

      setInterval(()=>{
        // fade out
        el.style.opacity = 0;
        // after 500ms (matches CSS transition) swap quote and fade in
        setTimeout(()=>{
          i = (i + 1) % quotes.length;
          el.textContent = "“" + quotes[i] + "”";
          el.style.opacity = 1;
        }, 500);
      }, 5000);
    })();
  </script>
</body>
</html>