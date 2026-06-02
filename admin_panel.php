<?php
session_start();
include("includes/db.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle actions: approve/reject notes, delete user, post/unpublish announcement
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve_note') {
        mysqli_query($conn, "UPDATE notes SET status='approved' WHERE id=$id");
    } elseif ($action === 'reject_note') {
        mysqli_query($conn, "UPDATE notes SET status='rejected' WHERE id=$id");
    } elseif ($action === 'delete_user') {
        // CAUTION: this deletes user (no rollback) - keep as-is or change to deactivate flag
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
    } elseif ($action === 'delete_announcement') {
        mysqli_query($conn, "DELETE FROM announcements WHERE id=$id");
    }
    header("Location: admin_panel.php");
    exit;
}

// Overview stats
$notes_total     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM notes"))['t'];
$notes_pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM notes WHERE status='pending'"))['t'];
$notes_approved  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM notes WHERE status='approved'"))['t'];
$videos_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM videos"))['t'];
$mock_count      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM mock_tests"))['t'];
$users_count     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users"))['t'];
$announcements   = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");

// Lists
$pending_notes = mysqli_query($conn, "SELECT notes.*, users.name 
    FROM notes JOIN users ON notes.user_id = users.id WHERE notes.status='pending' ORDER BY notes.uploaded_at DESC LIMIT 100");

$all_users = mysqli_query($conn, "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 500");

$recent_notes = mysqli_query($conn, "SELECT notes.*, users.name 
    FROM notes JOIN users ON notes.user_id = users.id ORDER BY notes.uploaded_at DESC LIMIT 50");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - StudyBridge</title>

  <!-- Bootstrap, icons, AOS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <style>
    :root{
      --p1:#1e3c72; --p2:#2a5298; --muted:#6c757d; --accent:#ffd166;
    }
    body{
      background: linear-gradient(180deg,#f6f8fb 0%, #eef3f9 100%);
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      color:#1f2a37;
      margin:0; min-height:100vh; display:flex; flex-direction:column;
    }
    .navbar{ background: linear-gradient(135deg,var(--p1),var(--p2)); box-shadow:0 6px 18px rgba(34,50,80,0.12); }
    .navbar-brand{ font-weight:700; color:#fff !important; }
    .page{ width:100%; max-width:1200px; margin:36px auto; padding:0 18px 60px; box-sizing:border-box; flex:1; }

    .header{ text-align:center; margin-bottom:18px; }
    .header h3{ font-weight:700; color:#12263b; }
    .header p{ color:var(--muted); margin:6px 0 0; }

    .grid { display:grid; grid-template-columns: 1fr 380px; gap:20px; align-items:start; }
    .card-glass { background:rgba(255,255,255,0.92); border-radius:12px; padding:16px; box-shadow:0 10px 30px rgba(18,33,64,0.06); }

    .stat-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .stat { flex:1; min-width:140px; background:linear-gradient(180deg,#fff,#fbfdff); padding:14px; border-radius:10px; box-shadow:0 6px 18px rgba(18,33,64,0.03); }
    .stat h5 { margin:0; font-weight:700; color:var(--p1); }
    .stat p { margin:6px 0 0; color:var(--muted); font-size:0.95rem; }

    table thead { background: linear-gradient(135deg,var(--p1),var(--p2)); color:#fff; }
    table tbody tr:hover { background: rgba(30,60,120,0.03); }

    .btn-sm { border-radius:8px; }

    .right-panel { position:sticky; top:20px; display:flex; flex-direction:column; gap:12px; }
    .announce-list { max-height:300px; overflow:auto; padding-right:6px; }

    footer{ margin-top:30px; background:linear-gradient(135deg,var(--p1),var(--p2)); color:#fff; padding:20px 0; text-align:center; }
    footer a{ color:var(--accent); text-decoration:none; margin:0 6px; }
    @media (max-width:991px){ .grid{ grid-template-columns:1fr; } .right-panel{ position:relative; top:auto; } }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="#">🛡️ StudyBridge Admin</a>
      <div class="ms-auto">
        <a href="dashboard.php" class="btn btn-light btn-sm me-2">Student Dashboard</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <div class="page" data-aos="fade-up">
    <div class="header">
      <h3>Admin Control Panel</h3>
      <p>Overview, content moderation and user management for StudyBridge.</p>
    </div>

    <div class="grid">

      <!-- LEFT: main -->
      <div>

        <!-- Overview stats -->
        <div class="card-glass mb-3" data-aos="fade-right">
          <div class="stat-row">
            <div class="stat">
              <h5>Notes</h5>
              <p>Total: <strong><?php echo $notes_total; ?></strong> — Pending: <strong><?php echo $notes_pending; ?></strong> — Approved: <strong><?php echo $notes_approved; ?></strong></p>
            </div>
            <div class="stat">
              <h5>Videos</h5>
              <p>Total: <strong><?php echo $videos_count; ?></strong></p>
            </div>
            <div class="stat">
              <h5>Mocks</h5>
              <p>Total: <strong><?php echo $mock_count; ?></strong></p>
            </div>
            <div class="stat">
              <h5>Users</h5>
              <p>Total: <strong><?php echo $users_count; ?></strong></p>
            </div>
          </div>
        </div>

        <!-- Pending notes -->
        <div class="card-glass mb-3" data-aos="fade-up">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong>Pending Notes</strong>
            <small class="text-muted">Review uploads & moderate</small>
          </div>
          <div style="margin-top:12px; overflow:auto;">
            <?php if(mysqli_num_rows($pending_notes) > 0): ?>
              <table class="table table-sm align-middle">
                <thead>
                  <tr><th>Title</th><th>By</th><th>Category</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                  <?php while($n = mysqli_fetch_assoc($pending_notes)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($n['title']); ?></td>
                    <td><?php echo htmlspecialchars($n['name']); ?></td>
                    <td><?php echo htmlspecialchars($n['category']); ?></td>
                    <td><?php echo date("d M Y", strtotime($n['uploaded_at'])); ?></td>
                    <td>
                      <a class="btn btn-success btn-sm" href="admin_panel.php?action=approve_note&id=<?php echo $n['id']; ?>">
                        <i class="fa-solid fa-check"></i> Approve
                      </a>
                      <a class="btn btn-danger btn-sm" href="admin_panel.php?action=reject_note&id=<?php echo $n['id']; ?>">
                        <i class="fa-solid fa-xmark"></i> Reject
                      </a>
                      <a class="btn btn-outline-primary btn-sm" href="<?php echo $n['file_path']; ?>" target="_blank">Open</a>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="text-muted p-3">No pending notes at the moment.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent uploads (last 50) -->
        <div class="card-glass mb-3" data-aos="fade-up">
          <strong>Recent Notes</strong>
          <div style="margin-top:10px; overflow:auto;">
            <table class="table table-sm">
              <thead>
                <tr><th>Title</th><th>By</th><th>Status</th><th>Date</th><th>File</th></tr>
              </thead>
              <tbody>
                <?php while($r = mysqli_fetch_assoc($recent_notes)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['title']); ?></td>
                  <td><?php echo htmlspecialchars($r['name']); ?></td>
                  <td>
                    <?php if($r['status']=='pending') echo '<span class="badge bg-warning text-dark">Pending</span>';
                          elseif($r['status']=='approved') echo '<span class="badge bg-success">Approved</span>';
                          else echo '<span class="badge bg-danger">Rejected</span>'; ?>
                  </td>
                  <td><?php echo date("d M Y", strtotime($r['uploaded_at'])); ?></td>
                  <td><a class="btn btn-sm btn-primary" href="<?php echo $r['file_path']; ?>" download><i class="fa-solid fa-download"></i></a></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- RIGHT: admin side panel -->
      <aside class="right-panel">

        <!-- Users list -->
        <div class="card-glass" data-aos="fade-left">
          <strong>Users</strong>
          <div style="margin-top:10px; max-height:220px; overflow:auto;">
            <?php if(mysqli_num_rows($all_users) > 0): ?>
              <ul class="list-group list-group-flush">
                <?php while($u = mysqli_fetch_assoc($all_users)): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                      <div style="font-weight:700;"><?php echo htmlspecialchars($u['name']); ?> <?php if($u['role']=='admin') echo '<small class="text-warning">(admin)</small>'; ?></div>
                      <div style="font-size:0.9rem; color:var(--muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                    </div>
                    <div style="text-align:right;">
                      <small class="text-muted"><?php echo date("d M Y", strtotime($u['created_at'])); ?></small><br>
                      <?php if($u['id'] != $_SESSION['user_id']): ?>
                        <a href="admin_panel.php?action=delete_user&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger mt-1">Delete</a>
                      <?php else: ?>
                        <span class="badge bg-secondary">You</span>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endwhile; ?>
              </ul>
            <?php else: ?>
              <div class="text-muted">No users found.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Announcements -->
        <div class="card-glass" data-aos="fade-left">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong>Announcements</strong>
            <a href="post_announcement.php" class="btn btn-sm btn-primary">+ Post</a>
          </div>
          <div class="announce-list" style="margin-top:10px;">
            <?php if(mysqli_num_rows($announcements) > 0): ?>
              <ul class="list-group list-group-flush">
                <?php while($a = mysqli_fetch_assoc($announcements)): ?>
                  <li class="list-group-item">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                      <div style="max-width:70%;">
                        <div style="font-weight:700;"><?php echo htmlspecialchars($a['title']); ?></div>
                        <div style="color:var(--muted); font-size:0.92rem;"><?php echo htmlspecialchars($a['message']); ?></div>
                        <div style="font-size:0.78rem; color:var(--muted); margin-top:6px;"><?php echo date("d M Y, h:i A", strtotime($a['created_at'])); ?></div>
                      </div>
                      <div style="text-align:right;">
                        <a class="btn btn-sm btn-outline-danger" href="admin_panel.php?action=delete_announcement&id=<?php echo $a['id']; ?>">Delete</a>
                      </div>
                    </div>
                  </li>
                <?php endwhile; ?>
              </ul>
            <?php else: ?>
              <div class="text-muted">No announcements yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="card-glass" data-aos="fade-left">
          <strong>Quick Actions</strong>
          <div style="margin-top:8px; display:flex; gap:8px; flex-direction:column;">
            <a href="upload_notes.php" class="btn btn-sm btn-outline-primary">Upload Notes (as user)</a>
            <a href="view_notes.php" class="btn btn-sm btn-outline-secondary">View Public Notes</a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-dark">Open Student Dashboard</a>
          </div>
        </div>

      </aside>
    </div>
  </div>

  <footer>
    © <?php echo date("Y"); ?> StudyBridge — Admin Panel &middot; <a href="dashboard.php">Student Dashboard</a>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>AOS.init({ duration:700, once:true, offset:80 });</script>
</body>
</html>
                