<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ------------------ Load user's avatar (NEW) ------------------
// Fetch avatar filename from DB for the logged-in user
$user_id = intval($_SESSION['user_id']);
$avatar_path = '';
$avatar_row = mysqli_query($conn, "SELECT avatar FROM users WHERE id = $user_id LIMIT 1");
if ($avatar_row && mysqli_num_rows($avatar_row) > 0) {
    $ar = mysqli_fetch_assoc($avatar_row);
    if (!empty($ar['avatar'])) {
        $candidate = 'uploads/avatars/' . basename($ar['avatar']);
        if (file_exists($candidate)) {
            $avatar_path = $candidate;
        }
    }
}
// ---------------------------------------------------------------

// ------------------ Motivational Quotes ------------------
$quotes = [
    "Success doesn’t come to you, you go to it.",
    "Small steps every day lead to big results.",
    "Believe in yourself, you are capable of more than you know.",
    "Consistency is the key to success.",
    "Don’t watch the clock; do what it does. Keep going."
];
$quote_of_the_day = $quotes[array_rand($quotes)];

// ------------------ Quick Stats ------------------
$notes_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notes WHERE status='approved'"))['total'];
$videos_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM videos"))['total'];
$mock_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mock_tests"))['total'];

// ------------------ Announcements ------------------
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - StudyBridge</title>

  <!-- Bootstrap + icons + AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    /* Page base */
    :root{
      --primary-1:#1e3c72;
      --primary-2:#2a5298;
      --accent:#ffd166;
      --muted:#6c757d;
      --glass: rgba(255,255,255,0.7);
    }
    body{
      background: linear-gradient(180deg,#f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, "Helvetica Neue", Arial;
      color:#243142;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      margin:0;
      padding:0;
      min-height:100vh;
      display:flex;
      flex-direction:column;
    }

    /* NAV */
    .navbar {
      background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
      box-shadow: 0 6px 18px rgba(34,50,80,0.18);
      padding:0.7rem 1rem;
    }
    .navbar-brand { font-weight: 700; letter-spacing:0.4px; color:#fff !important; }
    .nav-link { color: rgba(255,255,255,0.92) !important; margin-right:0.35rem; }
    .nav-link:hover { color: var(--accent) !important; }
    .profile-btn { border-radius:999px; padding:0.35rem 0.8rem; display:flex; align-items:center; gap:8px; }

    /* small avatar in navbar dropdown button */
    .nav-avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; display:inline-block; vertical-align:middle; }

    /* container layout */
    .page {
      width:100%;
      max-width:1200px;
      margin: 28px auto;
      padding: 0 18px 60px;
      box-sizing:border-box;
      flex:1;
    }

    /* Hero */
    .hero {
      display:flex;
      gap:18px;
      align-items:center;
      justify-content:space-between;
      background: linear-gradient(90deg, rgba(255,255,255,0.85), rgba(255,255,255,0.95));
      border-radius:14px;
      padding:18px;
      box-shadow: 0 8px 22px rgba(40,55,80,0.06);
      margin-bottom:18px;
      position:relative;
      overflow:hidden;
    }
    .hero .left {
      display:flex;
      gap:16px;
      align-items:center;
    }
    .avatar {
      width:84px;
      height:84px;
      border-radius:14px;
      background:linear-gradient(135deg,#fff,#f1f6ff);
      box-shadow: 0 6px 18px rgba(32,45,70,0.08);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:32px;
      color:var(--primary-2);
      font-weight:700;
    }
    .hero h1 { margin:0; font-size:1.35rem; color:#12263b; }
    .hero p.lead { margin:4px 0 0; color:var(--muted); }

    /* hero CTA cluster */
    .hero .ctas { display:flex; gap:10px; align-items:center; }
    .stat-chip {
      background:linear-gradient(180deg, rgba(255,255,255,0.95), rgba(250,250,250,0.95));
      padding:10px 12px;
      border-radius:10px;
      box-shadow: 0 4px 10px rgba(18,33,64,0.04);
      display:inline-flex;
      gap:10px;
      align-items:center;
      min-width:120px;
      justify-content:center;
      font-weight:600;
    }
    .stat-chip .icon { font-size:18px; color:var(--primary-2); }

    /* layout columns: left info + right timeline */
    .grid {
      display:grid;
      grid-template-columns: 1fr 420px;
      gap:22px;
      align-items:start;
      margin-top:16px;
    }

    /* left column boxes */
    .card-block {
      background:#fff;
      border-radius:12px;
      padding:16px;
      box-shadow:0 8px 24px rgba(34,50,80,0.05);
      margin-bottom:16px;
    }

    .quote-box {
      display:flex;
      gap:14px;
      align-items:flex-start;
      background: linear-gradient(180deg,#fef9f1,#fff);
      border-left:4px solid #ffb703;
      padding:14px;
      border-radius:10px;
      box-shadow: 0 4px 12px rgba(34,50,80,0.02);
    }
    .quote-box .q { font-style:italic; color:#234; }

    /* timeline / feature list (stacked but pretty) */
    .features {
      display:flex;
      flex-direction:column;
      gap:12px;
    }
    .feature-item {
      display:flex;
      gap:14px;
      align-items:flex-start;
      padding:14px;
      border-radius:12px;
      background:linear-gradient(180deg, rgba(255,255,255,0.95), #fff);
      box-shadow: 0 6px 20px rgba(18,33,64,0.04);
      transition: transform 220ms ease, box-shadow 220ms ease;
    }
    .feature-item:hover { transform: translateY(-6px); box-shadow: 0 14px 36px rgba(18,33,64,0.08); }
    .feature-icon {
      width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center;
      font-size:22px; color:#fff;
    }
    .feature-body h5 { margin:0 0 6px 0; font-weight:700; }
    .feature-body p { margin:0; color:var(--muted); font-size:0.95rem; }

    .bg-upload { background: linear-gradient(135deg,#6b7cff,#814ef4); box-shadow: inset 0 -6px 20px rgba(0,0,0,0.06); }
    .bg-view   { background: linear-gradient(135deg,#08a05f,#19c37a); }
    .bg-videos { background: linear-gradient(135deg,#ff6b6b,#ff3b3b); }
    .bg-mock   { background: linear-gradient(135deg,#ffb86b,#ffd166); color:#222; }
    .bg-chat   { background: linear-gradient(135deg,#06beb6,#048a8a); }
    .bg-admin  { background: linear-gradient(135deg,#2b2b2b,#4b4b4b); }

    /* small badges and buttons */
    .btn-sm-brand {
      background: linear-gradient(90deg,var(--primary-1),var(--primary-2));
      color:#fff;
      border-radius:10px;
      padding:8px 12px;
      border:none;
      box-shadow:0 6px 16px rgba(40,60,100,0.12);
    }

    /* timeline right column */
    .right-panel {
      position:sticky;
      top:20px;
      align-self:start;
      display:flex;
      flex-direction:column;
      gap:14px;
    }
    .announce-list { max-height:280px; overflow:auto; padding-right:6px; }

    /* footer */
    footer {
      margin-top:30px;
      background:linear-gradient(135deg,var(--primary-1),var(--primary-2));
      color:#fff;
      padding:28px 0;
    }
    footer a { color:rgba(255,255,255,0.92); text-decoration:none; }
    footer a:hover { color:var(--accent); }

    /* responsive */
    @media (max-width:991px){
      .grid { grid-template-columns: 1fr; }
      .right-panel { position:relative; top:auto; }
      .avatar { width:64px; height:64px; font-size:26px; }
    }
  </style>
</head>
<body>
  <!-- NAV -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">StudyBridge</a>
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
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link text-warning fw-bold" href="admin_panel.php">Admin Panel</a></li>
          <?php endif; ?>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <div class="me-2 d-none d-md-block">
            <div class="stat-chip">
              <span class="icon"><i class="fa-solid fa-book"></i></span>
              <small style="color:#555">Notes <strong style="margin-left:8px;"><?php echo $notes_count; ?></strong></small>
            </div>
          </div>

          <!-- Profile dropdown: shows small avatar (if set) and "Edit Profile" link -->
          <div class="dropdown">
            <a class="btn btn-outline-light profile-btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <?php
                // show user's uploaded avatar if available
                $avatar_shown = false;
                if (!empty($avatar_path)) {
                    echo '<img src="'.htmlspecialchars($avatar_path).'" class="nav-avatar" alt="avatar">';
                    $avatar_shown = true;
                }
                // show first-letter if no avatar
                if (!$avatar_shown) {
                    echo '<span style="display:inline-block;width:28px;height:28px;border-radius:50%;background:#fff;color:var(--primary-2);text-align:center;line-height:28px;font-weight:700;">'.strtoupper(substr($_SESSION['name'],0,1)).'</span>';
                }
                echo htmlspecialchars($_SESSION['name']);
              ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <!-- CHANGED: Edit Profile link added here -->
              <li><a class="dropdown-item" href="profile_update.php">Edit Profile</a></li>
              <li><a class="dropdown-item" href="my_results.php">My Results</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
          </div>

        </div>
      </div>
    </div>
  </nav>

  <!-- Main page -->
  <div class="page">

    <!-- Hero -->
    <div class="hero" data-aos="zoom-in">
      <div class="left">
        <?php if (!empty($avatar_path)): ?>
          <div class="avatar"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:14px;"></div>
        <?php else: ?>
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'],0,1)); ?></div>
        <?php endif; ?>
        <div>
          <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋</h1>
          <p class="lead">A smart place to share notes, learn from curated playlists and improve with mock tests — built for aspirants like you.</p>
          <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
            <div class="stat-chip"><span class="icon"><i class="fa-solid fa-file-lines"></i></span><small>Notes <strong style="margin-left:6px;"><?php echo $notes_count; ?></strong></small></div>
            <div class="stat-chip"><span class="icon"><i class="fa-solid fa-video"></i></span><small>Videos <strong style="margin-left:6px;"><?php echo $videos_count; ?></strong></small></div>
            <div class="stat-chip"><span class="icon"><i class="fa-solid fa-pen-to-square"></i></span><small>Mocks <strong style="margin-left:6px;"><?php echo $mock_count; ?></strong></small></div>
          </div>
        </div>
      </div>

      <div class="ctas" data-aos="fade-left">
        <a href="upload_notes.php" class="btn btn-sm-brand">Upload Notes</a>
        <a href="mock_test_list.php" class="btn btn-outline-dark" style="border-radius:10px;">Take a Mock</a>
      </div>
    </div>

    <!-- Grid: left main + right panel -->
    <div class="grid">

      <!-- LEFT: main column -->
      <div>

        <!-- Quote -->
        <div class="card-block" data-aos="fade-up">
          <div class="quote-box">
            <div style="font-size:18px; color:#ff7a00;"><i class="fa-solid fa-lightbulb"></i></div>
            <div>
              <div style="font-weight:700; margin-bottom:6px;">Tip of the Day</div>
              <div class="q"><?php echo $quote_of_the_day; ?></div>
            </div>
          </div>
        </div>

        <!-- Announcements expanded (desktop) -->
        <div class="card-block" data-aos="fade-up">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <strong>📢 Announcements</strong>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
              <a href="post_announcement.php" class="btn btn-sm btn-primary">+ Post</a>
            <?php endif; ?>
          </div>

          <div class="announce-list">
            <?php if (mysqli_num_rows($announcements) > 0): ?>
              <ul class="list-group">
                <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div style="max-width:78%;">
                      <div style="font-weight:700;"><?php echo htmlspecialchars($a['title']); ?></div>
                      <div style="color:var(--muted); font-size:0.95rem;"><?php echo htmlspecialchars($a['message']); ?></div>
                    </div>
                    <div style="text-align:right; font-size:0.82rem; color:var(--muted);">
                      <?php echo date("d M Y", strtotime($a['created_at'])); ?><br>
                      <small><?php echo date("h:i A", strtotime($a['created_at'])); ?></small>
                    </div>
                  </li>
                <?php endwhile; ?>
              </ul>
            <?php else: ?>
              <div class="text-muted">No announcements yet — check back soon.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Features stacked — improved visual list -->
        <div class="card-block" data-aos="fade-up">
          <div style="margin-bottom:10px;"><strong>Quick Actions</strong><div style="color:var(--muted); font-size:0.92rem;">Jump straight to a section</div></div>

          <div class="features">

            <a href="upload_notes.php" class="text-decoration-none">
              <div class="feature-item" data-aos="fade-up" data-aos-delay="40">
                <div class="feature-icon bg-upload"><i class="fa-solid fa-file-arrow-up"></i></div>
                <div class="feature-body">
                  <h5>Upload Notes</h5>
                  <p>Share PDFs or docs. Admin approval ensures high quality — your uploads are private until approved.</p>
                </div>
              </div>
            </a>

            <a href="view_notes.php" class="text-decoration-none">
              <div class="feature-item" data-aos="fade-up" data-aos-delay="80">
                <div class="feature-icon bg-view"><i class="fa-solid fa-folder-open"></i></div>
                <div class="feature-body">
                  <h5>View & Download Notes</h5>
                  <p>Browse categorized notes by subject. Download and save for offline revision.</p>
                </div>
              </div>
            </a>

            <a href="view_videos.php" class="text-decoration-none">
              <div class="feature-item" data-aos="fade-up" data-aos-delay="120">
                <div class="feature-icon bg-videos"><i class="fa-solid fa-play"></i></div>
                <div class="feature-body">
                  <h5>Study Videos</h5>
                  <p>Curated playlist links organized by subject — pick the teacher or playlist you prefer.</p>
                </div>
              </div>
            </a>

            <a href="mock_test_list.php" class="text-decoration-none">
              <div class="feature-item" data-aos="fade-up" data-aos-delay="160">
                <div class="feature-icon bg-mock"><i class="fa-solid fa-list-check"></i></div>
                <div class="feature-body">
                  <h5>Mock Tests</h5>
                  <p>Take timed mock tests, submit answers and view your personal results history.</p>
                </div>
              </div>
            </a>

            <a href="chat.php" class="text-decoration-none">
              <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon bg-chat"><i class="fa-solid fa-comments"></i></div>
                <div class="feature-body">
                  <h5>One-to-One Chat</h5>
                  <p>Discuss problems, share quick notes & links with classmates privately.</p>
                </div>
              </div>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
              <a href="admin_panel.php" class="text-decoration-none">
                <div class="feature-item" data-aos="fade-up" data-aos-delay="240">
                  <div class="feature-icon bg-admin"><i class="fa-solid fa-shield-halved"></i></div>
                  <div class="feature-body">
                    <h5>Admin Panel</h5>
                    <p>Approve / reject notes and manage announcements & reported content.</p>
                  </div>
                </div>
              </a>
            <?php endif; ?>

          </div>
        </div>

      </div>

      <!-- RIGHT: sticky helpful panel -->
      <aside class="right-panel">
        <div class="card-block" data-aos="fade-left">
          <strong>Today</strong>
          <div style="margin-top:8px;color:var(--muted);font-size:0.95rem;">Quick stats & shortcuts</div>
          <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
            <div class="stat-chip"><i class="fa-solid fa-book icon"></i><div style="margin-left:8px;">Notes <strong><?php echo $notes_count; ?></strong></div></div>
            <div class="stat-chip"><i class="fa-solid fa-video icon"></i><div style="margin-left:8px;">Videos <strong><?php echo $videos_count; ?></strong></div></div>
            <div class="stat-chip"><i class="fa-solid fa-pen-to-square icon"></i><div style="margin-left:8px;">Mocks <strong><?php echo $mock_count; ?></strong></div></div>
          </div>
          <div style="margin-top:12px;">
            <a href="upload_notes.php" class="btn btn-sm btn-primary w-100">Upload Notes</a>
          </div>
        </div>

        <div class="card-block" data-aos="fade-left" style="background:linear-gradient(180deg,#fff,#fbfcff);">
          <strong>Need Help?</strong>
          <div style="color:var(--muted); margin-top:8px; font-size:0.95rem;">Contact admin or visit support for issues with uploads or tests.</div>
          <div style="margin-top:12px;">
            <a href="mailto:lavi7590@gamil.com" class="btn btn-outline-primary w-100">Contact Support</a>
          </div>
        </div>

        <div class="card-block" data-aos="fade-left">
          <strong>Shortcuts</strong>
          <ul style="padding-left:16px; margin-top:8px; color:var(--muted);">
            <li style="margin-bottom:6px;"><a href="view_notes.php">Browse Notes</a></li>
            <li style="margin-bottom:6px;"><a href="view_videos.php">Study Videos</a></li>
            <li style="margin-bottom:6px;"><a href="mock_test_list.php">Start Mock</a></li>
          </ul>
        </div>

      </aside>

    </div> <!-- end grid -->
  </div> <!-- end page -->

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <h5>StudyBridge</h5>
          <p style="color:rgba(255,255,255,0.9);">A community for aspirants — share notes, learn, practice and connect.</p>
        </div>
        <div class="col-md-4">
          <h5>Quick Links</h5>
          <ul style="list-style:none; padding-left:0; color:rgba(255,255,255,0.95);">
            <li><a href="upload_notes.php">Upload Notes</a></li>
            <li><a href="view_notes.php">View Notes</a></li>
            <li><a href="mock_test_list.php">Mock Tests</a></li>
            <li><a href="chat.php">Chat</a></li>
          </ul>
        </div>
        <div class="col-md-4">
          <h5>Contact</h5>
          <p style="color:rgba(255,255,255,0.95);">Email: lavi7590@gamil.com<br>Phone: +91 75910 41715</p>
          <div style="margin-top:8px;">
            <a href="https://facebook.com" target="_blank" class="me-2 text-white"><i class="fab fa-facebook fa-lg"></i></a>
            <a href="https://twitter.com" target="_blank" class="me-2 text-white"><i class="fab fa-x-twitter fa-lg"></i></a>
            <a href="https://instagram.com" target="_blank" class="me-2 text-white"><i class="fab fa-instagram fa-lg"></i></a>
            <a href="https://linkedin.com" target="_blank" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
          </div>
        </div>
      </div>

      <div style="text-align:center; margin-top:18px; color:rgba(255,255,255,0.85);">© <?php echo date("Y"); ?> StudyBridge. All rights reserved.</div>
    </div>
  </footer>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script> AOS.init({ duration:700, once:true, offset:80 }); </script>
</body>
</html>
