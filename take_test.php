<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/db.php"; // DB connection

$test_id = $_GET['id'] ?? 0;
if (!$test_id) {
    die("Invalid Test");
}

// Fetch test info
$stmt = $conn->prepare("SELECT * FROM mock_tests WHERE id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();

if (!$test) {
    die("Test not found.");
}

// Fetch questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$questions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($test['title']); ?> - StudyBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">StudyBridge</a>
    <div>
      <a href="logout.php" class="btn btn-warning btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="mb-4">📝 <?php echo htmlspecialchars($test['title']); ?></h2>

  <form action="submit_test.php" method="post">
    <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

    <?php if ($questions->num_rows > 0): ?>
      <?php $q_no = 1; ?>
      <?php while ($q = $questions->fetch_assoc()): ?>
        <div class="card mb-4 shadow-sm p-3">
          <h5>Q<?php echo $q_no++; ?>. <?php echo htmlspecialchars($q['question']); ?></h5>
          <div class="form-check">
            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" class="form-check-input">
            <label class="form-check-label"><?php echo htmlspecialchars($q['option_a']); ?></label>
          </div>
          <div class="form-check">
            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B" class="form-check-input">
            <label class="form-check-label"><?php echo htmlspecialchars($q['option_b']); ?></label>
          </div>
          <div class="form-check">
            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C" class="form-check-input">
            <label class="form-check-label"><?php echo htmlspecialchars($q['option_c']); ?></label>
          </div>
          <div class="form-check">
            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D" class="form-check-input">
            <label class="form-check-label"><?php echo htmlspecialchars($q['option_d']); ?></label>
          </div>
        </div>
      <?php endwhile; ?>
      <button type="submit" class="btn btn-success">Submit Test</button>
    <?php else: ?>
      <p class="text-muted">No questions available for this test.</p>
    <?php endif; ?>
  </form>
</div>

</body>
</html>
