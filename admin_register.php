<?php
include("includes/db.php");

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'admin')";
    if (mysqli_query($conn, $query)) {
        $success = true;
        $message = "✅ Admin registered successfully! Redirecting to login...";
        header("refresh:2;url=login.php");
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Admin - StudyBridge</title>

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root{
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --muted:#6c757d;
    }

    body {
      background: linear-gradient(180deg,#f6f8fb 0%,#eef3f9 100%);
      font-family: 'Segoe UI', Roboto, "Helvetica Neue", Arial;
      color:#243142;
      min-height:100vh;
      display:flex;
      flex-direction:column;
    }

    /* Navbar (same as other pages) */
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.18);
      padding:0.7rem 1rem;
    }
    .navbar-brand { color: #fff !important; font-weight: 700; letter-spacing:0.4px; }

    /* Center card container */
    .auth-container {
      max-width:940px;
      margin:40px auto;
      padding: 0 18px;
      width:100%;
    }

    .admin-card {
      display:flex;
      gap:0;
      border-radius:14px;
      overflow:hidden;
      box-shadow:0 12px 36px rgba(18,33,64,0.08);
      background: #fff;
    }

    /* Left info (blue panel) */
    .admin-left {
      flex:0 0 420px;
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      padding:36px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      position:relative;
      min-height:320px;
    }
    .admin-left h2{ margin:0 0 8px 0; font-weight:800; }
    .admin-left p{ color: rgba(255,255,255,0.9); margin:0 0 12px 0; }

    /* Right form */
    .admin-right {
      flex:1;
      padding:34px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .form-wrap {
      width:100%;
      max-width:420px;
    }
    .card-box {
      border-radius:10px;
      padding:18px;
      background: linear-gradient(180deg,#fff,#fbfbff);
      box-shadow: 0 6px 18px rgba(34,50,80,0.04);
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .card-box:hover{ transform: translateY(-4px); box-shadow: 0 18px 40px rgba(18,33,64,0.08); }

    label.form-label { font-weight:600; color:#243142; }
    .form-control { border-radius:10px; }

    .btn-primary {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      border:none;
      border-radius:10px;
      padding:10px 14px;
      font-weight:700;
      color:#fff;
    }

    .small-muted { color:var(--muted); font-size:0.95rem; }

    @media (max-width:991px) {
      .admin-card { flex-direction:column; }
      .admin-left { flex:0 0 auto; width:100%; padding:22px; text-align:center; }
      .admin-right { padding:22px; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="admin_register.php">🛡️ StudyBridge Admin</a>
    </div>
  </nav>

  <main class="auth-container" data-aos="fade-up">
    <div class="admin-card">

      <!-- Left panel (info) -->
      <div class="admin-left" data-aos="fade-right">
        <h2>Admin Registration</h2>
        <p>Create an admin account to manage StudyBridge content and users.</p>
        <div class="small-muted">You will be redirected to login after successful registration.</div>
      </div>

      <!-- Right panel (form) -->
      <div class="admin-right" data-aos="fade-left">
        <div class="form-wrap">
          <div class="card-box">

            <?php if($message != ""): ?>
              <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> text-center mb-3">
                <?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <form method="POST" novalidate>
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>

              <button type="submit" class="btn btn-primary w-100">Register Admin</button>
            </form>

          </div>
        </div>
      </div>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script> AOS.init({ duration:700, once:true, offset:80 }); </script>
</body>
</html>