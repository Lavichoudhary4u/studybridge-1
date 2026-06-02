<?php
session_start();
include("includes/db.php");

// Restrict access to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $msg = mysqli_real_escape_string($conn, $_POST['message']);

    if (!empty($title) && !empty($msg)) {
        $query = "INSERT INTO announcements (title, message) VALUES ('$title', '$msg')";
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success text-center'>✅ Announcement posted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ Error posting announcement: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning text-center'>⚠️ Please fill in all fields.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post Announcement - StudyBridge Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f6f9;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
      background: white;
    }
    .navbar {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: bold;
    }
    .btn-custom {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      transition: 0.3s;
    }
    .btn-custom:hover {
      background: linear-gradient(135deg, #764ba2, #667eea);
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="admin_dashboard.php">🛡️ Admin Panel</a>
    <div class="ms-auto">
      <a href="dashboard.php" class="btn btn-light btn-sm">Student Dashboard</a>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Content -->
<div class="container mt-5">
  <div class="col-md-8 mx-auto">
    <div class="card p-4">
      <h3 class="text-center mb-3">📢 Post New Announcement</h3>
      <?php echo $message; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Announcement Title</label>
          <input type="text" name="title" class="form-control" placeholder="Enter title..." required>
        </div>

        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="5" placeholder="Write your announcement here..." required></textarea>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-custom px-5">Post Announcement</button>
          <a href="dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
