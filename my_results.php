<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/db.php";

$user_id = $_SESSION['user_id'];

// Fetch all results of logged-in student
$sql = "
   SELECT r.id, r.score, r.total, r.created_at, 
          m.title, m.subject 
   FROM results r
   JOIN mock_tests m ON r.test_id = m.id
   WHERE r.user_id = ?
   ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$results = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Results - StudyBridge</title>

  <!-- Bootstrap + Icons + AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root {
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --muted:#6c757d;
      --accent:#ffd166;
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
      letter-spacing:0.4px;
      color:#fff !important;
    }

    /* Page Container */
    .page {
      width:100%;
      max-width:1100px;
      margin:40px auto;
      padding:0 18px 60px;
      box-sizing:border-box;
      flex:1;
    }

    .header {
      text-align:center;
      margin-bottom:30px;
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

    /* Table Card */
    .results-card {
      background:rgba(255,255,255,0.9);
      backdrop-filter:blur(6px);
      border-radius:16px;
      box-shadow:0 8px 28px rgba(18,33,64,0.08);
      padding:20px;
      overflow-x:auto;
    }

    table {
      border-radius:10px;
      overflow:hidden;
    }
    thead {
      background:linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
    }
    th {
      font-weight:600;
      text-transform:uppercase;
      font-size:0.9rem;
    }
    tbody tr:hover {
      background-color:rgba(30,60,120,0.04);
      transition:background 0.3s ease;
    }

    .badge {
      font-size:0.85rem;
      padding:6px 10px;
      border-radius:8px;
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
    <h2>📊 My Test Results</h2>
    <p>Your latest mock test performances and progress summary</p>
  </div>

  <?php if ($results->num_rows > 0): ?>
    <div class="results-card" data-aos="fade-up">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Test Title</th>
            <th>Subject</th>
            <th>Score</th>
            <th>Percentage</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          while ($row = $results->fetch_assoc()): 
            $percentage = round(($row['score'] / $row['total']) * 100, 2);
          ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['subject']); ?></span></td>
            <td><strong><?php echo $row['score']; ?> / <?php echo $row['total']; ?></strong></td>
            <td>
              <?php if ($percentage >= 75): ?>
                <span class="badge bg-success"><?php echo $percentage; ?>%</span>
              <?php elseif ($percentage >= 50): ?>
                <span class="badge bg-warning text-dark"><?php echo $percentage; ?>%</span>
              <?php else: ?>
                <span class="badge bg-danger"><?php echo $percentage; ?>%</span>
              <?php endif; ?>
            </td>
            <td><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state" data-aos="fade-up">
      <h5 class="mb-2">⚠ No Tests Attempted Yet</h5>
      <p class="text-muted mb-3">You haven't taken any mock tests so far. Start practicing today to track your progress!</p>
      <a href="mock_test_list.php" class="btn btn-primary">Start a Mock Test</a>
    </div>
  <?php endif; ?>
</div>

<!-- Footer -->
<footer>
  © <?php echo date("Y"); ?> StudyBridge — Empowering Students to Learn, Practice, and Grow.
  <br>
  <a href="upload_notes.php">Upload Notes</a> | 
  <a href="view_videos.php">Videos</a> | 
  <a href="mock_test_list.php">Mock Tests</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 700, once: true, offset: 80 });</script>
</body>
</html>
