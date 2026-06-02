<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");

include "includes/db.php";
$user_id = $_SESSION['user_id'];

// Fetch all other users
$users = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat - StudyBridge</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
:root {
  --primary-1:#1e3c72;
  --primary-2:#2a5298;
  --accent:#ffd166;
  --muted:#6c757d;
}

body {
  background: linear-gradient(180deg, #f6f8fb 0%, #eef3f9 100%);
  font-family: 'Segoe UI', Roboto, Arial, sans-serif;
  color:#1f2a37;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

/* Navbar */
.navbar {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  box-shadow: 0 6px 18px rgba(34,50,80,0.12);
}
.navbar-brand {
  font-weight:700;
  color:#fff !important;
  letter-spacing:0.5px;
}

/* Chat Layout */
#chat-container {
  height: 600px;
  border-radius: 16px;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(6px);
  display: flex;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(18,33,64,0.1);
  border: 1px solid rgba(0,0,0,0.05);
}

/* Sidebar User List */
#user-list {
  width: 260px;
  background: linear-gradient(135deg,var(--primary-1),var(--primary-2));
  color: #fff;
  overflow-y: auto;
}
.user-item {
  padding: 14px 18px;
  cursor: pointer;
  transition: 0.2s;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.user-item:hover {
  background: rgba(255,255,255,0.15);
}
.user-item.active {
  background: rgba(255,255,255,0.25);
  font-weight: bold;
  border-left: 5px solid var(--accent);
}

/* Unread badge */
.unread-badge {
  background: #ff4757;
  color: white;
  border-radius: 50%;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: bold;
  display: none;
}

/* Chat area */
#chat-box {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f5f7fb;
}

/* Chat header */
#chat-header {
  padding: 15px 20px;
  background: #fff;
  border-bottom: 1px solid #ddd;
  font-weight: 600;
  color:#12263b;
  display:flex;
  align-items:center;
  gap:10px;
}
#chat-header i {
  color: var(--primary-2);
}

/* Messages */
#messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  scroll-behavior: smooth;
}

/* Message bubbles */
.msg {
  padding: 10px 15px;
  border-radius: 16px;
  margin-bottom: 10px;
  max-width: 70%;
  word-wrap: break-word;
  font-size: 15px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  position: relative;
  animation: fadeInUp 0.3s ease;
}
.msg-sent {
  background: #dcf8c6;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
}
.msg-recv {
  background: #fff;
  align-self: flex-start;
  border-bottom-left-radius: 4px;
  border: 1px solid #eee;
}
@keyframes fadeInUp {
  from {opacity: 0; transform: translateY(10px);}
  to {opacity: 1; transform: translateY(0);}
}

/* Input box */
#input-box {
  display: flex;
  border-top: 1px solid #ddd;
  background: #fff;
}
#input-box input {
  flex: 1;
  border: none;
  padding: 15px;
  outline: none;
  font-size: 15px;
}
#input-box button {
  border: none;
  background: var(--primary-2);
  color: #fff;
  padding: 0 24px;
  font-weight: 600;
  transition: 0.3s;
}
#input-box button:hover {
  background: var(--primary-1);
}

/* Scrollbar */
#user-list::-webkit-scrollbar,
#messages::-webkit-scrollbar {
  width: 8px;
}
#user-list::-webkit-scrollbar-thumb,
#messages::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.4);
  border-radius: 4px;
}
#messages::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.2);
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-graduation-cap me-2"></i>StudyBridge</a>
    <div class="ms-auto">
      <a href="dashboard.php" class="btn btn-light btn-sm me-2">Dashboard</a>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Chat Section -->
<div class="container mt-4 mb-5">
  <h3 class="text-center mb-3" data-aos="fade-down">💬 Chat With Your Classmates</h3>
  <div id="chat-container" data-aos="zoom-in">

    <!-- Sidebar Users -->
    <div id="user-list">
      <?php while($u = $users->fetch_assoc()): ?>
        <div class="user-item" data-id="<?php echo $u['id']; ?>" data-name="<?php echo htmlspecialchars($u['name']); ?>">
          <span><?php echo htmlspecialchars($u['name']); ?></span>
          <span class="unread-badge" id="badge-<?php echo $u['id']; ?>">0</span>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Chat Box -->
    <div id="chat-box">
      <div id="chat-header"><i class="fa-solid fa-comments"></i> Select a user to start chat</div>
      <div id="messages"></div>
      <div id="input-box">
        <input type="text" id="message" placeholder="Type a message...">
        <button id="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>

  </div>
</div>

<script>
let receiver_id = 0;

$(".user-item").click(function(){
  $(".user-item").removeClass("active");
  $(this).addClass("active");
  receiver_id = $(this).data('id');
  let receiver_name = $(this).data('name');
  $("#chat-header").html('<i class="fa-solid fa-user"></i> ' + receiver_name);
  $("#badge-"+receiver_id).hide().text("0");
  $("#messages").html("");
  fetchMessages(true);
});

$("#send-btn").click(function(){
  let msg = $("#message").val();
  if(msg && receiver_id){
    $.post("send_message.php", {receiver_id: receiver_id, message: msg}, function(){
      $("#message").val("");
      fetchMessages(true);
    });
  }
});

let lastCounts = {};

function fetchMessages(forceScroll=false){
  if(!receiver_id) return;
  $.getJSON("fetch_messages.php", {receiver_id: receiver_id}, function(data){
    $("#messages").html("");
    data.forEach(m => {
      let cls = (m.sender_id == <?php echo $user_id; ?>) ? "msg msg-sent" : "msg msg-recv";
      let name = (m.sender_id != <?php echo $user_id; ?>) ? m.sender_name : "You";
      $("#messages").append('<div class="'+cls+'"><strong>'+name+':</strong> '+m.message+'</div>');
    });
    if(forceScroll){
      $("#messages").scrollTop($("#messages")[0].scrollHeight);
    }
  });
}

function checkUnread(){
  $(".user-item").each(function(){
    let uid = $(this).data('id');
    $.getJSON("fetch_messages.php", {receiver_id: uid}, function(data){
      let unread = 0;
      if(uid != receiver_id){ 
        unread = data.filter(m => m.sender_id == uid).length;
      }
      if(unread > 0){
        $("#badge-"+uid).show().text(unread);
      } else {
        $("#badge-"+uid).hide();
      }
    });
  });
}

setInterval(() => {
  fetchMessages();
  checkUnread();
}, 2000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 700, once: true, offset: 80 });</script>
</body>
</html>
