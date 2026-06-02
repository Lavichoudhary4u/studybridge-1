<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "includes/db.php";  // database connection

// Fetch available mock tests
$sql = "SELECT * FROM mock_tests ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mock Tests - StudyBridge</title>

  <!-- Bootstrap + Icons + AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root {
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --accent:#ffd166;
      --muted:#6c757d;
    }

    body {
      background: linear-gradient(180deg,#f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      color:#1f2a37;
      min-height:100vh;
      display:flex;
      flex-direction:column;
    }

    /* Navbar */
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.12);
    }
    .navbar-brand {
      font-weight:700;
      color:#fff !important;
      letter-spacing:0.5px;
    }

    /* Page Container */
    .page {
      width:100%;
      max-width:1000px;
      margin:40px auto;
      padding:0 18px 60px;
      box-sizing:border-box;
      flex:1;
    }

    .header {
      text-align:center;
      margin-bottom:40px;
    }
    .header h2 {
      font-weight:700;
      font-size:1.8rem;
      color:#12263b;
    }
    .header p {
      color:var(--muted);
      font-size:1rem;
    }

    /* Mock Test List */
    .mock-card {
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(6px);
      border-radius: 14px;
      padding: 18px 20px;
      margin-bottom:16px;
      box-shadow: 0 10px 28px rgba(18,33,64,0.06);
      transition: all 0.25s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-decoration:none;
      color:#12263b;
    }
    .mock-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 36px rgba(18,33,64,0.08);
      background: #fff;
      text-decoration:none;
    }
    .mock-title {
      font-weight:700;
      font-size:1.1rem;
      color:#12263b;
    }
    .mock-meta {
      color:var(--muted);
      font-size:0.95rem;
    }

    .mock-subject {
      background: linear-gradient(135deg,#06beb6,#48b1bf);
      padding:6px 10px;
      border-radius:8px;
      font-size:0.85rem;
      color:#fff;
      font-weight:600;
      margin-left:8px;
    }

    .mock-date {
      font-size:0.9rem;
      color:var(--muted);
    }

    /* Empty State */
    .empty-state {
      background:#fff;
      border-radius:14px;
      padding:30px;
      text-align:center;
      box-shadow:0 8px 20px rgba(18,33,64,0.05);
    }

    /* Footer */
    footer {
      background:linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      text-align:center;
      padding:20px 0;
      margin-top:auto;
      font-size:0.9rem;
    }
    footer a {
      color:var(--accent);
      text-decoration:none;
      margin:0 5px;
    }
    footer a:hover {
      color:#fff;
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

<!-- Page -->
<div class="page">
  <div class="header" data-aos="fade-down">
    <h2>📝 Available Mock Tests</h2>
    <p>Choose a test to start practicing and track your progress</p>
  </div>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <a href="take_test.php?id=<?php echo $row['id']; ?>" class="mock-card" data-aos="fade-up">
        <div>
          <div class="mock-title"><?php echo htmlspecialchars($row['title']); ?></div>
          <div class="mock-meta">Created on: <?php echo date("d M Y", strtotime($row['created_at'])); ?></div>
        </div>
        <div>
          <span class="mock-subject"><?php echo htmlspecialchars($row['subject']); ?></span>
        </div>
      </a>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty-state" data-aos="fade-up">
      <h5>⚠ No Mock Tests Available</h5>
      <p class="text-muted mb-3">Currently, there are no mock tests available. Please check back later!</p>
      <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
  <?php endif; ?>
</div>

<!-- Footer -->
<footer>
  © <?php echo date("Y"); ?> StudyBridge. All rights reserved.
  <br>
  <a href="upload_notes.php">Upload Notes</a> | 
  <a href="view_videos.php">Videos</a> | 
  <a href="my_results.php">My Results</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 700, once: true, offset: 80 });</script>
</body>
</html>
