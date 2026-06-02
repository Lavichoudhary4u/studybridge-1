<?php
session_start();
include("includes/db.php");

$message = "";
$alert_type = "info";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password_raw === '') {
        $message = "Please fill all fields.";
        $alert_type = "danger";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "⚠️ Email already registered!";
            $alert_type = "warning";
        } else {
            $hash = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->bind_param("sss", $name, $email, $hash);
            if ($stmt->execute()) {
                $message = "✅ Registration successful! You can login now.";
                $alert_type = "success";
            } else {
                $message = "❌ Error: " . htmlspecialchars($conn->error);
                $alert_type = "danger";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register - StudyBridge</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root{
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --muted:#6c757d;
      --accent:#ffd166;
    }
    body {
      background: linear-gradient(180deg, #f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      color: #243142;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.18);
    }
    .navbar-brand { color:#fff !important; font-weight:700; }

    .split-card {
      max-width: 1000px;
      margin: 36px auto;
      border-radius: 12px;
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #fff;
      box-shadow: 0 10px 30px rgba(34,50,80,0.06);
    }

    /* Left promo panel */
    .promo {
      background: linear-gradient(180deg,var(--primary-1),var(--primary-2));
      color: #fff;
      padding: 46px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:18px;
      position: relative;
    }
    .promo h2 { font-size:1.6rem; margin:0 0 6px 0; font-weight:700; }
    .promo p { color: rgba(255,255,255,0.92); margin:0; line-height:1.45; }
    .promo .quote {
      background: rgba(255,255,255,0.1);
      border-radius:10px;
      padding:12px 14px;
      font-style:italic;
      transition: opacity 0.5s ease;
      min-height:50px;
    }
    .decor-circle {
      position:absolute;
      width:180px;
      height:180px;
      right:-40px;
      bottom:-40px;
      border-radius:50%;
      opacity:0.07;
      background:#fff;
      filter:blur(10px);
    }

    /* Right form panel */
    .form-panel {
      padding: 36px 40px;
      display:flex;
      flex-direction:column;
      justify-content:center;
    }
    .form-panel h3 { color: var(--primary-2); font-weight:700; margin-bottom:18px; }
    .form-control:focus {
      box-shadow: 0 6px 18px rgba(40,80,140,0.08);
      border-color: var(--primary-2);
    }
    .btn-brand {
      background: linear-gradient(90deg,var(--primary-1),var(--primary-2));
      color:#fff;
      border:none;
      padding:10px 18px;
      border-radius:8px;
      box-shadow: 0 8px 18px rgba(38,66,115,0.12);
    }
    .btn-brand:hover { opacity:0.95; }
    .muted-link { color: var(--muted); text-decoration: none; }

    /* Responsive */
    @media (max-width:991px) {
      .split-card { grid-template-columns: 1fr; }
      .promo { padding:28px; text-align:center; }
      .form-panel { padding:24px; }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">StudyBridge</a>
  </div>
</nav>

<div class="split-card" data-aos="fade-up" data-aos-duration="700">
  <!-- Left promo (blue) -->
  <div class="promo" data-aos="fade-right" data-aos-duration="800" data-aos-delay="100">
    <h2>Join the StudyBridge Community 🚀</h2>
    <p>Get access to curated notes, educational videos, and challenging mock tests — all created by passionate learners like you.</p>
    <div class="quote" id="quoteBox">“Learning never exhausts the mind.”</div>
    <div class="decor-circle"></div>
  </div>

  <!-- Right form (registration) -->
  <div class="form-panel" data-aos="fade-left" data-aos-duration="800" data-aos-delay="120">
    <h3 data-aos="fade-up" data-aos-delay="140"><i class="fa-solid fa-user-graduate"></i> Register</h3>

    <?php if ($message !== ""): ?>
      <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> py-2" data-aos="fade-down" data-aos-delay="200">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" style="max-width:520px;" data-aos="fade-up" data-aos-delay="220">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo isset($_POST['name'])?htmlspecialchars($_POST['name']):''; ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="d-grid gap-2">
        <button class="btn btn-brand" type="submit">Register</button>
      </div>

      <div class="mt-3 text-center" style="color:var(--muted);">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </form>
  </div>
</div>

<!-- AOS + Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 700, once: true, offset: 80 });

// Rotating quotes
const quotes = [
  "Learning never exhausts the mind.",
  "Education is the passport to the future.",
  "Success doesn’t come to you, you go to it.",
  "Small steps every day lead to big results.",
  "Consistency is the key to success.",
  "Push yourself, because no one else is going to do it for you."
];
let i = 0;
const quoteBox = document.getElementById("quoteBox");
setInterval(() => {
  quoteBox.style.opacity = 0;
  setTimeout(() => {
    i = (i + 1) % quotes.length;
    quoteBox.textContent = "“" + quotes[i] + "”";
    quoteBox.style.opacity = 1;
  }, 500);
}, 5000);
</script>
</body>
</html>