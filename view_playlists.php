<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "includes/db.php"; // DB connection

$subject = $_GET['subject'] ?? '';
if (!$subject) {
    die("Invalid subject");
}

// Fetch playlists from DB
$stmt = $conn->prepare("SELECT * FROM videos WHERE subject = ?");
$stmt->bind_param("s", $subject);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($subject); ?> Playlists - StudyBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-1: #1e3c72;
      --primary-2: #2a5298;
      --accent: #ffd166;
      --muted: #6c757d;
    }

    body {
      background: linear-gradient(180deg, #f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, "Helvetica Neue", Arial;
      color: #243142;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      padding-bottom: 60px;
    }

    .navbar {
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.18);
      padding: 0.7rem 1rem;
    }

    .navbar-brand {
      font-weight: 700;
      color: #fff !important;
      letter-spacing: 0.3px;
    }

    .page-title {
      font-weight: 700;
      color: var(--primary-2);
      text-align: center;
      margin-bottom: 24px;
    }

    .card {
      border: none;
      border-radius: 14px;
      background: linear-gradient(180deg, #fff, #fdfdfd);
      box-shadow: 0 8px 24px rgba(34,50,80,0.06);
      transition: all 0.25s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 36px rgba(34,50,80,0.12);
    }

    .card h4 {
      font-weight: 700;
      color: #12263b;
      margin-bottom: 12px;
    }

    iframe {
      border-radius: 12px;
      width: 100%;
      height: 320px;
      border: none;
    }

    .no-videos {
      text-align: center;
      color: var(--muted);
      font-size: 1.05rem;
      margin-top: 40px;
    }

    .footer {
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      color: #fff;
      text-align: center;
      padding: 20px 0;
      margin-top: 60px;
    }

    .footer a {
      color: #ffd166;
      text-decoration: none;
    }

    .footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="view_videos.php">
        <i class="fa-solid fa-arrow-left"></i> Back to Subjects
      </a>
      <div class="d-flex">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
      </div>
    </div>
  </nav>

  <!-- Main Page -->
  <div class="container mt-5">
    <h2 class="page-title"><i class="fa-solid fa-play"></i> <?php echo htmlspecialchars($subject); ?> - Study Playlists</h2>

    <?php if ($result->num_rows > 0): ?>
      <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-md-6 mb-4" data-aos="fade-up">
            <div class="card p-4 h-100">
              <h4><i class="fa-solid fa-circle-play text-primary"></i> <?php echo htmlspecialchars($row['title']); ?></h4>
              <iframe src="<?php echo htmlspecialchars($row['youtube_link']); ?>" allowfullscreen></iframe>
              <p class="text-muted mt-2"><i class="fa-solid fa-book"></i> Subject: <?php echo htmlspecialchars($row['subject']); ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="no-videos">⚠ No playlists available for <strong><?php echo htmlspecialchars($subject); ?></strong> yet.</p>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>© <?php echo date("Y"); ?> StudyBridge | <a href="dashboard.php">Back to Dashboard</a></p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
