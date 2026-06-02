<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM notes WHERE user_id = '$user_id' ORDER BY uploaded_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Notes - StudyBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .status-badge { font-size: 0.85rem; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">📚 StudyBridge</a>
    <a href="logout.php" class="btn btn-warning btn-sm">Logout</a>
  </div>
</nav>

<div class="container mt-5">
  <h3 class="mb-4 text-center">📂 My Uploaded Notes</h3>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Title</th>
        <th>Category</th>
        <th>Status</th>
        <th>Uploaded At</th>
        <th>File</th>
      </tr>
    </thead>
    <tbody>
      <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td>
              <?php if($row['status'] == 'approved'): ?>
                <span class="badge bg-success status-badge">Approved</span>
              <?php elseif($row['status'] == 'pending'): ?>
                <span class="badge bg-warning text-dark status-badge">Pending</span>
              <?php else: ?>
                <span class="badge bg-danger status-badge">Rejected</span>
              <?php endif; ?>
            </td>
            <td><?php echo date("d M Y", strtotime($row['uploaded_at'])); ?></td>
            <td><a href="<?php echo $row['file_path']; ?>" class="btn btn-sm btn-primary" download>Download</a></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="text-center">No notes uploaded yet 📭</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
