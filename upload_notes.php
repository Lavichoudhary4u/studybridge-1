<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = $_POST['category'];
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed_ext = ["pdf","doc","docx","txt"];
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . "_" . uniqid() . "." . $file_ext;
            $upload_path = "uploads/notes/" . $new_name;

            if (!is_dir("uploads/notes/")) {
                mkdir("uploads/notes/", 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $query = "INSERT INTO notes (user_id, title, file_path, category) 
                          VALUES ('$user_id', '$title', '$upload_path', '$category')";
                if (mysqli_query($conn, $query)) {
                    $message = "✅ Notes uploaded successfully!";
                } else {
                    $message = "❌ Database error: " . mysqli_error($conn);
                }
            } else {
                $message = "❌ Failed to upload file!";
            }
        } else {
            $message = "⚠️ Only PDF, DOC, DOCX, TXT allowed!";
        }
    } else {
        $message = "⚠️ Please select a file to upload!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Notes - StudyBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f6f9fc, #e9eef7);
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .navbar {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .navbar-brand {
      font-weight: bold;
      color: #fff !important;
    }
    .upload-card {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(8px);
      border-radius: 18px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      padding: 35px;
      margin-top: 60px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .upload-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    }
    .btn-primary {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      border: none;
      transition: 0.3s;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #2a5298, #1e3c72);
    }
    .form-control, .form-select {
      border-radius: 10px;
      border: 1px solid #ced4da;
      box-shadow: none;
    }
    footer {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: #fff;
      text-align: center;
      padding: 15px 0;
      margin-top: auto;
      font-size: 14px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">StudyBridge</a>
    <div class="ms-auto">
      <a href="dashboard.php" class="btn btn-light btn-sm me-2">Back to Dashboard</a>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Upload Form -->
<div class="container" data-aos="fade-up">
  <div class="col-md-6 mx-auto">
    <div class="upload-card">
      <h3 class="text-center mb-3"><i class="fa-solid fa-file-arrow-up me-2"></i>Upload Notes</h3>

      <?php if($message != ""): ?>
        <div class="alert alert-info text-center"><?php echo $message; ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" placeholder="Enter note title" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Category</label>
          <select name="category" class="form-select" required>
            <option value="">-- Select Category --</option>
            <option value="Reasoning">Reasoning</option>
            <option value="Maths">Maths</option>
            <option value="GK">GK</option>
            <option value="English">English</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Upload File</label>
          <input type="file" name="file" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Upload</button>
      </form>
    </div>
  </div>
</div>

<footer>
  © <?php echo date("Y"); ?> StudyBridge — Empowering Students to Share & Learn
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 800, once: true });</script>
</body>
</html>
