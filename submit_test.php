<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/db.php"; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $test_id = $_POST['test_id'];
    $answers = $_POST['answers'] ?? [];

    // Fetch correct answers
    $stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $score = 0;
    $total_questions = $result->num_rows;

    while ($row = $result->fetch_assoc()) {
        $qid = $row['id'];
        $correct = $row['correct_option'];

        if (isset($answers[$qid]) && $answers[$qid] === $correct) {
            $score++;
        }
    }

    // Save result in DB
    $stmt = $conn->prepare("INSERT INTO results (user_id, test_id, score, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $user_id, $test_id, $score, $total_questions);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Test Result - StudyBridge</title>
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

<div class="container mt-5 text-center">
  <h2>✅ Test Submitted</h2>
  <p class="lead">Your Score: <strong><?php echo $score; ?>/<?php echo $total_questions; ?></strong></p>
  <a href="mock_test_list.php" class="btn btn-primary">Back to Tests</a>
</div>

</body>
</html>
