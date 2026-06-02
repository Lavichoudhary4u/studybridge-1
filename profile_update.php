<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$message = "";
$errors = [];

// Fetch user
$stmt = $conn->prepare("SELECT name, email, qualification, avatar, password, phone, dob, institution FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows !== 1) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
$user = $res->fetch_assoc();

// Helper: base avatars directory (absolute)
$baseDir = rtrim(__DIR__, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';

// Ensure uploads/avatars exists
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        $errors[] = "Server error: cannot create avatars folder. Please create folder <strong>uploads/avatars</strong> and make it writable.";
    }
}

/*
 * CONFIG: Allowed upload size (set here)
 * - Set to 10MB, 32MB, 50MB etc. default below is 50 MB
 */
$MAX_UPLOAD_BYTES = 50 * 1024 * 1024; // 50 MB

// Helper: convert php.ini size values like "50M" to bytes
function ini_size_to_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (int)$val;
    switch($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}

// Determine server php.ini limits so we can warn if user tries to accept more than server allows
$upload_max_filesize_bytes = ini_size_to_bytes(ini_get('upload_max_filesize') ?: '2M');
$post_max_size_bytes = ini_size_to_bytes(ini_get('post_max_size') ?: '8M');
$server_limit_bytes = min($upload_max_filesize_bytes, $post_max_size_bytes);

// If configured desired max is larger than server allowed, set effective max and record a note
if ($MAX_UPLOAD_BYTES > $server_limit_bytes) {
    // We won't silently accept more than server allows — present an error if user tries to use bigger than server limit.
    $effective_max_bytes = $server_limit_bytes;
    $server_note = "Note: PHP configuration limits uploads to " . round($server_limit_bytes/1024/1024, 2) . " MB. Increase upload_max_filesize & post_max_size in php.ini to allow larger files.";
} else {
    $effective_max_bytes = $MAX_UPLOAD_BYTES;
    $server_note = "";
}

/**
 * Resize and optimize image using GD.
 * Attempts to save under $target_max_bytes by resizing dimensions and lowering JPEG quality.
 * Returns new temp filepath on success, or false on failure.
 *
 * @param string $src_tmp   - uploaded tmp file path
 * @param string $mime_type - mime type from getimagesize()
 * @param int $target_max_bytes
 * @return string|false
 */
function optimize_image_to_limit($src_tmp, $mime_type, $target_max_bytes) {
    // target max dimensions (max width/height) — keep reasonable to preserve quality
    $max_dim = 3000; // pixels
    $quality = 90;   // start quality for JPEG

    $info = @getimagesize($src_tmp);
    if ($info === false) return false;
    $width = $info[0];
    $height = $info[1];

    // compute scale if needed
    $scale = 1.0;
    if ($width > $max_dim || $height > $max_dim) {
        $scale = min($max_dim / $width, $max_dim / $height);
    }

    $new_w = max(1, (int)round($width * $scale));
    $new_h = max(1, (int)round($height * $scale));

    // create image resource from source
    switch ($mime_type) {
        case IMAGETYPE_JPEG:
            $src_img = @imagecreatefromjpeg($src_tmp);
            $out_ext = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $src_img = @imagecreatefrompng($src_tmp);
            $out_ext = 'png';
            break;
        case IMAGETYPE_GIF:
            $src_img = @imagecreatefromgif($src_tmp);
            $out_ext = 'gif';
            break;
        default:
            return false;
    }
    if (!$src_img) return false;

    // create destination canvas
    $dst = imagecreatetruecolor($new_w, $new_h);

    // preserve transparency for PNG/GIF
    if ($mime_type === IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
    } elseif ($mime_type === IMAGETYPE_GIF) {
        $trn = imagecolortransparent($src_img);
        if ($trn >= 0) {
            $transparent_color = imagecolorsforindex($src_img, $trn);
            $trn_index = imagecolorallocate($dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
            imagefill($dst, 0, 0, $trn_index);
            imagecolortransparent($dst, $trn_index);
        }
    }

    // resample
    imagecopyresampled($dst, $src_img, 0, 0, 0, 0, $new_w, $new_h, $width, $height);

    // prepare temporary file path
    $tmp_dest = tempnam(sys_get_temp_dir(), 'avt_');
    if ($tmp_dest === false) {
        imagedestroy($src_img);
        imagedestroy($dst);
        return false;
    }

    // Save according to type. For JPEG, we will attempt lowering quality to meet size limit.
    if ($mime_type === IMAGETYPE_JPEG) {
        // attempt loop decreasing quality until within limit or quality hits 60
        $ok = false;
        $cur_quality = $quality;
        while ($cur_quality >= 60) {
            if (imagejpeg($dst, $tmp_dest, $cur_quality)) {
                clearstatcache(true, $tmp_dest);
                if (filesize($tmp_dest) <= $target_max_bytes) {
                    $ok = true;
                    break;
                }
            }
            $cur_quality -= 8; // reduce more if still big
        }
        // if still too big after lowering quality, still return the last attempt (it may be slightly over)
    } elseif ($mime_type === IMAGETYPE_PNG) {
        // PNG quality uses compression level 0 (no) - 9 (max). We'll save with level 6
        imagepng($dst, $tmp_dest, 6);
        $ok = (filesize($tmp_dest) <= $target_max_bytes);
    } elseif ($mime_type === IMAGETYPE_GIF) {
        imagegif($dst, $tmp_dest);
        $ok = (filesize($tmp_dest) <= $target_max_bytes);
    } else {
        $ok = false;
    }

    imagedestroy($src_img);
    imagedestroy($dst);

    return $tmp_dest;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $institution = trim($_POST['institution'] ?? '');

    if ($name === '') $errors[] = "Name cannot be empty.";

    // phone validation
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,32}$/', $phone)) {
        $errors[] = "Please enter a valid phone number (6-32 characters, digits, + - ( ) allowed).";
    }

    // dob validation
    if ($dob !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$d || $d->format('Y-m-d') !== $dob) {
            $errors[] = "Please enter date of birth in YYYY-MM-DD format (or leave empty).";
        }
    } else {
        $dob = null;
    }

    $avatar_filename = $user['avatar'];

    // handle avatar upload
    if (empty($errors) && !empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];

        // If PHP upload reported an error (like exceeding php.ini), capture it
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = "Uploaded file exceeds server allowed size. {$server_note}";
            } else {
                $errors[] = "Error uploading file (code: {$file['error']}).";
            }
        } else {
            // Quick server-side size check: if file bigger than server allows, reject early
            if ($file['size'] > $server_limit_bytes) {
                $errors[] = "Uploaded file is larger than server allows (" . round($server_limit_bytes/1024/1024,2) . " MB). {$server_note}";
            } elseif ($file['size'] > $effective_max_bytes) {
                // If file is bigger than our configured desired max, attempt to optimize it first (resize/compress)
                $imginfo = @getimagesize($file['tmp_name']);
                if ($imginfo === false) {
                    $errors[] = "Uploaded file is not a valid image.";
                } else {
                    // allow only allowed types
                    $allowed_types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
                    if (!isset($allowed_types[$imginfo[2]])) {
                        $errors[] = "Only JPG, PNG or GIF images allowed.";
                    } else {
                        // try to optimize to meet effective_max_bytes
                        $tmp_optimized = optimize_image_to_limit($file['tmp_name'], $imginfo[2], $effective_max_bytes);
                        if ($tmp_optimized === false) {
                            $errors[] = "Image is too large and could not be optimized. Try uploading a smaller image or increase server upload limits.";
                        } else {
                            // final size check
                            if (filesize($tmp_optimized) > $effective_max_bytes) {
                                $errors[] = "Even after optimization the image exceeds the allowed size (" . round($effective_max_bytes/1024/1024,2) . " MB). Please upload a smaller image.";
                                @unlink($tmp_optimized);
                            } else {
                                // accept optimized file — move into avatars
                                try {
                                    $ext = $allowed_types[$imginfo[2]];
                                    $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                                } catch (Exception $e) {
                                    $newName = time() . '_' . uniqid() . '.' . $ext;
                                }

                                if (!is_dir($baseDir)) {
                                    if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                                        $errors[] = "Server error: cannot create avatars folder. Make uploads/avatars writable.";
                                    }
                                }

                                if (empty($errors)) {
                                    $dest = $baseDir . DIRECTORY_SEPARATOR . $newName;
                                    if (!rename($tmp_optimized, $dest)) {
                                        // fallback to copy
                                        if (!copy($tmp_optimized, $dest)) {
                                            $errors[] = "Failed to save optimized file. Check folder permissions.";
                                            @unlink($tmp_optimized);
                                        } else {
                                            @unlink($tmp_optimized);
                                            if (!empty($user['avatar'])) {
                                                $old = $baseDir . DIRECTORY_SEPARATOR . $user['avatar'];
                                                if (file_exists($old) && is_file($old)) { @unlink($old); }
                                            }
                                            $avatar_filename = $newName;
                                        }
                                    } else {
                                        // rename succeeded
                                        if (!empty($user['avatar'])) {
                                            $old = $baseDir . DIRECTORY_SEPARATOR . $user['avatar'];
                                            if (file_exists($old) && is_file($old)) { @unlink($old); }
                                        }
                                        $avatar_filename = $newName;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // If file size <= effective_max_bytes, do normal checks and move
                $imginfo = @getimagesize($file['tmp_name']);
                if ($imginfo === false) {
                    $errors[] = "Uploaded file is not a valid image.";
                } else {
                    $allowed_types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
                    if (!isset($allowed_types[$imginfo[2]])) {
                        $errors[] = "Only JPG, PNG or GIF images allowed.";
                    } else {
                        try {
                            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $allowed_types[$imginfo[2]];
                        } catch (Exception $e) {
                            $newName = time() . '_' . uniqid() . '.' . $allowed_types[$imginfo[2]];
                        }

                        if (!is_dir($baseDir)) {
                            if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                                $errors[] = "Server error: cannot create avatars folder. Make uploads/avatars writable.";
                            }
                        }

                        if (empty($errors)) {
                            $dest = $baseDir . DIRECTORY_SEPARATOR . $newName;
                            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                                $errors[] = "Failed to save uploaded file. Check folder permissions.";
                            } else {
                                if (!empty($user['avatar'])) {
                                    $old = $baseDir . DIRECTORY_SEPARATOR . $user['avatar'];
                                    if (file_exists($old) && is_file($old)) { @unlink($old); }
                                }
                                $avatar_filename = $newName;
                            }
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        if ($dob === null) {
            $u = $conn->prepare("UPDATE users SET name = ?, qualification = ?, avatar = ?, phone = ?, dob = NULL, institution = ? WHERE id = ?");
            $u->bind_param("sssssi", $name, $qualification, $avatar_filename, $phone, $institution, $user_id);
        } else {
            $u = $conn->prepare("UPDATE users SET name = ?, qualification = ?, avatar = ?, phone = ?, dob = ?, institution = ? WHERE id = ?");
            $u->bind_param("ssssssi", $name, $qualification, $avatar_filename, $phone, $dob, $institution, $user_id);
        }

        if ($u->execute()) {
            $message = "✅ Profile updated successfully.";
            $_SESSION['name'] = $name;
            $user['name'] = $name;
            $user['qualification'] = $qualification;
            $user['avatar'] = $avatar_filename;
            $user['phone'] = $phone;
            $user['dob'] = $dob;
            $user['institution'] = $institution;

            if (!empty($_SESSION['must_complete_profile'])) {
                unset($_SESSION['must_complete_profile']);
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $errors[] = "Could not update profile. Try again.";
        }
    }
}

// password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $errors[] = "New passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $p = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $p->bind_param("si", $hash, $user_id);
        if ($p->execute()) {
            $message = "✅ Password changed successfully.";
        } else {
            $errors[] = "Failed to update password.";
        }
    }
}

$avatar_url = (!empty($user['avatar']) && file_exists($baseDir . DIRECTORY_SEPARATOR . $user['avatar']))
    ? 'uploads/avatars/' . $user['avatar']
    : 'https://via.placeholder.com/140x140.png?text=Avatar';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile - StudyBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(180deg,#f6f8fb,#eef3f9); font-family: 'Segoe UI', Roboto, Arial; color:#243142; }
    .card { border-radius:12px; box-shadow:0 10px 30px rgba(34,50,80,0.06); transition: all 0.3s ease; }
    .card:hover { transform: translateY(-3px); box-shadow:0 12px 32px rgba(34,50,80,0.12); }
    .avatar-preview { width:140px; height:140px; border-radius:12px; object-fit:cover; transition: 0.3s; }
    .avatar-preview:hover { transform: scale(1.05); }
    .btn-primary { background: linear-gradient(135deg,#1e3c72,#2a5298); border:none; transition: 0.3s; }
    .btn-primary:hover { opacity:0.9; }
    .small-muted { color: #6c757d; font-size:0.9rem; }
  </style>
</head>
<body>
<nav class="navbar navbar-dark" style="background: linear-gradient(135deg,#1e3c72,#2a5298);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">StudyBridge</a>
  </div>
</nav>

<!-- ✅ Toast for first login -->
<?php if (!empty($_SESSION['must_complete_profile'])): ?>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div id="profileToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          ✅ Please complete your profile before accessing the dashboard.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
  <div class="container mt-3 d-none d-md-block" id="profileFallbackAlert" style="max-width:900px;">
    <div class="alert alert-info text-center mb-0">
      ✅ Please complete your profile before accessing the dashboard.
    </div>
  </div>
  <?php unset($_SESSION['must_complete_profile']); ?>
<?php endif; ?>

<div class="container mt-5">
  <div class="row">
    <div class="col-lg-8">
      <div class="card p-4 mb-4">
        <h4 class="mb-3">Edit Profile</h4>

        <?php if (!empty($server_note)): ?>
          <div class="alert alert-warning">Server info: <?php echo htmlspecialchars($server_note); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
          <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?php echo nl2br(htmlspecialchars($e)); ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="action" value="update_profile">
          <div class="mb-3">
            <label class="form-label">Full name</label>
            <input name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Qualification</label>
            <input name="qualification" class="form-control" value="<?php echo htmlspecialchars($user['qualification'] ?? ''); ?>">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+91 99999 99999">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date of Birth</label>
              <input name="dob" type="date" class="form-control" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">College / School</label>
            <input name="institution" class="form-control" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Profile photo (JPG/PNG/GIF, up to <?php echo round($effective_max_bytes/1024/1024,2); ?> MB)</label>
            <div class="d-flex gap-3 align-items-center">
              <img src="<?php echo $avatar_url; ?>" alt="avatar" class="avatar-preview">
              <div style="flex:1;">
                <input type="file" name="avatar" accept="image/*" class="form-control">
                <div class="form-text">Leave empty to keep current photo.</div>
              </div>
            </div>
          </div>

          <button class="btn btn-primary">Save Profile</button>
        </form>
      </div>

      <div class="card p-4">
        <h4 class="mb-3">Change Password</h4>
        <form method="post" novalidate>
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button class="btn btn-primary">Change Password</button>
        </form>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-4 text-center">
        <img src="<?php echo $avatar_url; ?>" class="avatar-preview mb-3" alt="avatar">
        <h5 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h5>
        <div class="small-muted mb-2"><?php echo htmlspecialchars($user['qualification'] ?? ''); ?></div>
        <div class="small-muted mb-2"><?php echo htmlspecialchars($user['institution'] ?? ''); ?></div>
        <div class="small-muted"><?php echo htmlspecialchars($user['email']); ?></div>
        <div class="small-muted mt-2">Phone: <?php echo htmlspecialchars($user['phone'] ?? '—'); ?></div>
        <div class="small-muted">DOB: <?php echo htmlspecialchars($user['dob'] ?? '—'); ?></div>
        <div class="mt-3">
          <a href="dashboard.php" class="btn btn-outline-secondary w-100">Back to Dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ Toast Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var t = document.getElementById('profileToast');
    if (t) {
      var toast = new bootstrap.Toast(t, { delay: 5000 });
      toast.show();
      var fallback = document.getElementById('profileFallbackAlert');
      if (fallback) fallback.style.display = 'none';
    }
  });
</script>
</body>
</html>
