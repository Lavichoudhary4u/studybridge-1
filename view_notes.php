<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch only approved notes
$query = "SELECT notes.*, users.name 
          FROM notes 
          JOIN users ON notes.user_id = users.id 
          WHERE notes.status = 'approved'
          ORDER BY notes.uploaded_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Notes - StudyBridge</title>

  <!-- Bootstrap, FontAwesome, AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root{
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --muted: #6c757d;
      --card-bg: rgba(255,255,255,0.92);
    }
    body {
      background: linear-gradient(180deg,#f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      color: #1f2a37;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAV */
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.12);
    }
    .navbar-brand { font-weight: 700; color: #fff !important; letter-spacing:0.3px; }
    .nav-link { color: rgba(255,255,255,0.92) !important; }
    .nav-link:hover { color: #ffd166 !important; }

    /* Page wrapper */
    .page {
      width:100%;
      max-width:1200px;
      margin: 30px auto;
      padding: 0 18px 60px;
      box-sizing:border-box;
      flex:1;
    }

    /* header */
    .header-row {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:18px;
    }
    .title {
      font-size:1.4rem;
      font-weight:700;
      color:#12263b;
    }
    .sub {
      color:var(--muted);
      font-size:0.95rem;
    }

    /* notes grid */
    .notes-grid {
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap:18px;
    }

    .note-card {
      background: var(--card-bg);
      border-radius:12px;
      padding:16px;
      box-shadow: 0 10px 30px rgba(18,33,64,0.04);
      transition: transform 220ms ease, box-shadow 220ms ease;
      display:flex;
      flex-direction:column;
      height:100%;
    }
    .note-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 18px 42px rgba(18,33,64,0.08);
    }
    .note-title { font-weight:700; color:#12263b; margin-bottom:6px; }
    .note-meta { color:var(--muted); font-size:0.92rem; margin-bottom:12px; }
    .note-footer { margin-top:auto; display:flex; gap:8px; align-items:center; }

    .btn-download {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      border: none;
      border-radius:10px;
      padding:8px 10px;
      font-weight:600;
      box-shadow: 0 8px 20px rgba(30,60,120,0.08);
    }
    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 30px rgba(30,60,120,0.12);
    }

    /* empty state */
    .empty {
      background: #fff;
      border-radius:12px;
      padding:30px;
      text-align:center;
      box-shadow: 0 10px 30px rgba(18,33,64,0.04);
    }

    /* footer */
    footer {
      margin-top:30px;
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      padding:24px 0;
    }
    footer a { color: rgba(255,255,255,0.92); text-decoration:none; }
    footer a:hover { color:#ffd166; }

    @media (max-width:575px){
      .header-row { flex-direction:column; align-items:flex-start; gap:8px; }
    }
  </style>
</head>
<body>
  <!-- NAV -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-book-open me-2"></i>StudyBridge</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="upload_notes.php">Upload</a></li>
          <li class="nav-item"><a class="nav-link" href="view_notes.php">Notes</a></li>
          <li class="nav-item"><a class="nav-link" href="view_videos.php">Videos</a></li>
          <li class="nav-item"><a class="nav-link" href="mock_test_list.php">Mock Tests</a></li>
          <li class="nav-item"><a class="nav-link" href="chat.php">Chat</a></li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <a href="upload_notes.php" class="btn btn-light btn-sm">Upload Notes</a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- CONTENT -->
  <div class="page" data-aos="fade-up">
    <div class="header-row">
      <div>
        <div class="title">📘 Approved Notes</div>
        <div class="sub">Browse and download notes approved by admins</div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="upload_notes.php" class="btn btn-sm btn-light"><i class="fa-solid fa-file-arrow-up me-2"></i>Upload Notes</a>
        <a href="dashboard.php" class="btn btn-sm" style="background:linear-gradient(90deg,var(--primary-1),var(--primary-2)); color:#fff;">Back to Dashboard</a>
      </div>
    </div>

    <div class="notes-grid">
      <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
          <div class="note-card" data-aos="fade-up" data-aos-delay="50">
            <div class="note-title"><?php echo htmlspecialchars($row['title']); ?></div>
            <div class="note-meta">📂 <?php echo htmlspecialchars($row['category']); ?> &nbsp; • &nbsp; 👤 <?php echo htmlspecialchars($row['name']); ?></div>
            <div class="small text-muted mb-3">📅 <?php echo date("d M Y", strtotime($row['uploaded_at'])); ?></div>

            <div class="note-body mb-3" style="color:var(--muted); font-size:0.95rem;">
              <!-- optional description field if you have one; keep empty if not -->
              <?php if (!empty($row['description'])): ?>
                <?php echo nl2br(htmlspecialchars($row['description'])); ?>
              <?php else: ?>
                <span style="opacity:0.85;">No description provided.</span>
              <?php endif; ?>
            </div>

            <div class="note-footer">
              <a href="<?php echo $row['file_path']; ?>" class="btn-download w-100" download>
                <i class="fa-solid fa-download me-2"></i> Download
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty" data-aos="fade-up">
          <h5>No approved notes available yet 📭</h5>
          <p class="text-muted">Try uploading your notes or check back later. Admins approve uploads before they appear here.</p>
          <a href="upload_notes.php" class="btn btn-light mt-2"><i class="fa-solid fa-file-arrow-up me-2"></i>Upload a Note</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="row align-items-start">
        <div class="col-md-4 mb-3">
          <h5>StudyBridge</h5>
          <p style="color:rgba(255,255,255,0.9); margin:0;">Share notes, learn from curated playlists and practise with mock tests — all in one community.</p>
        </div>
        <div class="col-md-4 mb-3">
          <h5>Quick Links</h5>
          <ul style="list-style:none; padding-left:0; margin:0;">
            <li><a href="upload_notes.php">Upload Notes</a></li>
            <li><a href="mock_test_list.php">Mock Tests</a></li>
            <li><a href="chat.php">Chat</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-3">
          <h5>Contact</h5>
          <div style="color:rgba(255,255,255,0.95);">Email: lavi7590@gamil.com<br>Phone: +91 75910 41715</div>
        </div>
      </div>

      <div style="text-align:center; margin-top:12px; color:rgba(255,255,255,0.9);">© <?php echo date("Y"); ?> StudyBridge. All rights reserved.</div>
    </div>
  </footer>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script> AOS.init({ duration:700, once:true, offset:80 }); </script>
</body>
</html>
