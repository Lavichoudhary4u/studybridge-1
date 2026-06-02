<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Study Videos - StudyBridge</title>

  <!-- Bootstrap + Icons + AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root {
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --accent:#ffd166;
    }

    body {
      background: linear-gradient(180deg, #f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      color: #1f2a37;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Navbar */
    .navbar {
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.15);
    }
    .navbar-brand {
      font-weight: 700;
      color: #fff !important;
      letter-spacing: 0.5px;
    }

    /* Main container */
    .page {
      width: 100%;
      max-width: 1000px;
      margin: 50px auto;
      padding: 0 20px 60px;
      flex: 1;
    }

    .title-box {
      text-align: center;
      margin-bottom: 40px;
    }
    .title-box h2 {
      font-weight: 700;
      font-size: 1.8rem;
      color: #12263b;
    }
    .title-box p {
      color: #6c757d;
      font-size: 1rem;
    }

    /* Subject buttons */
    .subject-card {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(6px);
      border-radius: 14px;
      padding: 40px 20px;
      box-shadow: 0 10px 28px rgba(18,33,64,0.06);
      font-weight: 600;
      transition: all 0.25s ease-in-out;
      color: #12263b;
      border: 2px solid transparent;
      font-size: 1.1rem;
    }

    .subject-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 16px 36px rgba(18,33,64,0.08);
    }

    .reasoning { border-color: #6f42c1; color: #6f42c1; }
    .maths     { border-color: #198754; color: #198754; }
    .gk        { border-color: #ffc107; color: #b38300; }
    .english   { border-color: #dc3545; color: #dc3545; }

    .subject-card:hover.reasoning { background: #6f42c1; color: #fff; }
    .subject-card:hover.maths     { background: #198754; color: #fff; }
    .subject-card:hover.gk        { background: #ffc107; color: #fff; }
    .subject-card:hover.english   { background: #dc3545; color: #fff; }

    /* Footer */
    footer {
      margin-top: auto;
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      color: #fff;
      padding: 25px 0;
      text-align: center;
      font-size: 0.9rem;
    }
    footer a {
      color: #ffd166;
      text-decoration: none;
      margin: 0 8px;
    }
    footer a:hover {
      color: #fff;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-graduation-cap me-2"></i>StudyBridge</a>
    <div class="ms-auto">
      <a href="dashboard.php" class="btn btn-light btn-sm me-2">Dashboard</a>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Page Content -->
<div class="page">
  <div class="title-box" data-aos="fade-down">
    <h2>🎥 Choose a Subject</h2>
    <p>Select your preferred subject to explore playlists and study materials</p>
  </div>

  <div class="row text-center g-4">
    <div class="col-md-3" data-aos="fade-up" data-aos-delay="50">
      <a href="view_playlists.php?subject=Reasoning" class="text-decoration-none">
        <div class="subject-card reasoning">🧠 Reasoning</div>
      </a>
    </div>
    <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
      <a href="view_playlists.php?subject=Maths" class="text-decoration-none">
        <div class="subject-card maths">📐 Maths</div>
      </a>
    </div>
    <div class="col-md-3" data-aos="fade-up" data-aos-delay="150">
      <a href="view_playlists.php?subject=GK" class="text-decoration-none">
        <div class="subject-card gk">🌍 GK</div>
      </a>
    </div>
    <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
      <a href="view_playlists.php?subject=English" class="text-decoration-none">
        <div class="subject-card english">📖 English</div>
      </a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  © <?php echo date("Y"); ?> StudyBridge. All rights reserved. | 
  <a href="upload_notes.php">Upload Notes</a> • 
  <a href="mock_test_list.php">Mock Tests</a> • 
  <a href="chat.php">Chat</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 700, once: true, offset: 80 });</script>
</body>
</html>
