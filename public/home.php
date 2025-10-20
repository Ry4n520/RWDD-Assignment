<?php /* include '../src/auth.php'; */
// Dynamic homepage: fetch latest trading item, event, and community post
session_start();
include __DIR__ . '/../src/db.php';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
$currentUserId = $currentUserId ? (int)$currentUserId : 0;

// Check if user is admin
$isAdmin = false;
if ($currentUserId) {
  $r = mysqli_query($conn, "SELECT usertype FROM accounts WHERE UserID = " . $currentUserId . " LIMIT 1");
  if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (isset($row['usertype']) && strtolower($row['usertype']) === 'admin') {
      $isAdmin = true;
    }
  }
}

// helpers
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function excerpt($text, $len = 140) {
  $text = trim(strip_tags((string)$text));
  if (mb_strlen($text) <= $len) return $text;
  return mb_substr($text, 0, $len - 1) . '…';
}

// Admin view: fetch all accounts
$allAccounts = [];
if ($isAdmin && isset($conn)) {
  $sql = "SELECT UserID, username, email, usertype, datejoined FROM accounts ORDER BY datejoined DESC";
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $allAccounts[] = $row;
    }
  }
}

$latestTrading = [];
if (isset($conn)) {
  $sql = "SELECT t.ItemID,
                 t.Name AS ItemName,
                 t.DateAdded,
                 a.username,
                 (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath
          FROM tradinglist t
          JOIN accounts a ON t.UserID = a.UserID
          ORDER BY t.DateAdded DESC
          LIMIT 3";
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $latestTrading[] = $row;
    }
  }
}

$latestEvent = [];
if (isset($conn)) {
  $sql = "SELECT e.EventID,
                 e.Name AS EventName,
                 e.Address,
                 e.Date,
                 e.Organizer,
                 (SELECT COUNT(*) FROM eventparticipants epc WHERE epc.EventID = e.EventID) AS ParticipantCount
          FROM events e
          ORDER BY e.Date DESC
          LIMIT 3";
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $latestEvent[] = $row;
    }
  }
}

$latestPost = [];
if (isset($conn)) {
  $sql = "SELECT p.PostID,
                 p.Content,
                 p.Created_at,
                 a.username,
                 (SELECT path FROM post_images pi WHERE pi.PostID = p.PostID ORDER BY pi.id ASC LIMIT 1) AS ImagePath
          FROM posts p
          JOIN accounts a ON p.UserID = a.UserID
          ORDER BY p.Created_at DESC
          LIMIT 3";
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $latestPost[] = $row;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>

  <!-- Navbar CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251020c">
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018">

  <!-- Home Page CSS -->
  <link rel="stylesheet" href="assets/css/home.css">

  <!-- JS -->
  <script src="assets/js/navbar.js?v=20251020" defer></script>
</head>
<body>

  <!-- Include Navbar -->
  <?php include 'includes/header.php'; ?>
  <!-- Main Content -->
  <main class="homepage">
    <?php if ($isAdmin): ?>
      <!-- Admin View: Account Management -->
      <section class="admin-content" style="padding:20px">
        <h1 style="margin-bottom:20px">Account Management</h1>
        <p style="margin-bottom:20px;color:#666">Manage user accounts and admin privileges. Toggle the checkbox to promote or demote users.</p>
        
        <div style="overflow-x:auto">
          <table class="accounts-table" style="width:100%;border-collapse:collapse;background:#23233a;border-radius:8px;overflow:hidden">
            <thead>
              <tr style="background:#2a2a3e;color:#90ee90">
                <th style="padding:12px;text-align:left;border-bottom:2px solid #90ee90">User ID</th>
                <th style="padding:12px;text-align:left;border-bottom:2px solid #90ee90">Username</th>
                <th style="padding:12px;text-align:left;border-bottom:2px solid #90ee90">Email</th>
                <th style="padding:12px;text-align:left;border-bottom:2px solid #90ee90">Date Joined</th>
                <th style="padding:12px;text-align:center;border-bottom:2px solid #90ee90">Admin</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($allAccounts)): ?>
                <?php foreach ($allAccounts as $account): ?>
                  <tr style="border-bottom:1px solid #2a2a3e;transition:background 0.2s" onmouseover="this.style.background='rgba(144,238,144,0.1)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:12px;color:#fff"><?= h($account['UserID']) ?></td>
                    <td style="padding:12px;color:#fff;font-weight:600"><?= h($account['username']) ?></td>
                    <td style="padding:12px;color:#ddd"><?= h($account['email']) ?></td>
                    <td style="padding:12px;color:#ddd"><?= h($account['datejoined'] ?? 'N/A') ?></td>
                    <td style="padding:12px;text-align:center">
                      <input type="checkbox" 
                             class="admin-toggle" 
                             data-userid="<?= h($account['UserID']) ?>" 
                             <?= (strtolower($account['usertype'] ?? '') === 'admin') ? 'checked' : '' ?>
                             style="width:20px;height:20px;cursor:pointer;accent-color:#90ee90">
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="padding:20px;text-align:center;color:#999">No accounts found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php else: ?>
      <!-- Regular User View -->
      <section class="main-content" style="padding: 40px 30px; line-height: 1.8;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px; color: #90ee90;">🏡 Welcome to LinkMosaic</h1>
        <p style="font-size: 1.3rem; font-weight: 600; margin-bottom: 30px; color: #c9c9c9;">Connecting Neighbors. Building Communities.</p>
        
        <p style="margin-bottom: 20px; font-size: 1.05rem;">LinkMosaic is a community-based platform designed to bring residents together in a shared digital space. Our mission is to strengthen neighborhood connections by enabling communication, collaboration, and support among community members.</p>
        
        <p style="margin-bottom: 20px; font-weight: 600; font-size: 1.1rem;">Through LinkMosaic, residents can:</p>
        
        <div style="margin-left: 20px; margin-bottom: 25px;">
          <p style="margin-bottom: 15px;">🌱 <strong>Share Experiences & Ideas</strong> – Post updates, share advice, and exchange tips with your neighbors.</p>
          
          <p style="margin-bottom: 15px;">🎉 <strong>Join Events</strong> – Participate in community activities such as clean-up drives, local gatherings, and cultural celebrations.</p>
          
          <p style="margin-bottom: 15px;">🔄 <strong>Trade & Donate Items</strong> – Give unused items a new life by trading or donating them to others in your neighborhood.</p>
          
          <p style="margin-bottom: 15px;">📢 <strong>Stay Informed</strong> – Access the latest community announcements, safety alerts, and local updates all in one place.</p>
        </div>
        
        <p style="margin-bottom: 15px; font-size: 1.05rem;">At its heart, LinkMosaic promotes <strong>sustainable living</strong> and <strong>mutual support</strong>, encouraging eco-friendly practices and active participation. Whether you're new to the area or a long-time resident, this platform helps you connect, contribute, and create a more vibrant, supportive neighborhood.</p>
      </section>

      <section class="content-row">
      <!-- Latest Trading Items -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:12px">
        <h3 style="margin:0">Latest Trades</h3>
        <?php if (!empty($latestTrading)): ?>
          <?php foreach ($latestTrading as $trading): ?>
            <a href="trading.php" style="text-decoration:none;color:inherit">
              <div style="display:flex;gap:12px;align-items:center;padding:8px;border-radius:6px;transition:background 0.2s" onmouseover="this.style.background='rgba(144,238,144,0.1)'" onmouseout="this.style.background='transparent'">
                <img src="<?= h($trading['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Item Image" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #00000022" />
                <div style="flex:1">
                  <div style="font-weight:700;line-height:1.2;font-size:0.95rem"><?= h($trading['ItemName']) ?></div>
                  <div style="opacity:.8;font-size:.85rem">by <?= h($trading['username']) ?></div>
                  <div style="opacity:.7;font-size:.8rem;">Added: <?= h($trading['DateAdded']) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No trading items yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Events -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:12px">
        <h3 style="margin:0">Latest Events</h3>
        <?php if (!empty($latestEvent)): ?>
          <?php foreach ($latestEvent as $event): ?>
            <a href="events.php" style="text-decoration:none;color:inherit">
              <div style="display:flex;gap:12px;align-items:center;padding:8px;border-radius:6px;transition:background 0.2s" onmouseover="this.style.background='rgba(144,238,144,0.1)'" onmouseout="this.style.background='transparent'">
                <img src="https://placehold.co/200x140" alt="Event Image" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #00000022" />
                <div style="flex:1">
                  <div style="font-weight:700;line-height:1.2;font-size:0.95rem"><?= h($event['EventName']) ?></div>
                  <div style="opacity:.85;font-size:.85rem">Date: <?= h($event['Date']) ?></div>
                  <div style="opacity:.75;font-size:.8rem;">Participants: <?= (int)($event['ParticipantCount'] ?? 0) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No events yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Community Posts -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:12px">
        <h3 style="margin:0">Latest Community Posts</h3>
        <?php if (!empty($latestPost)): ?>
          <?php foreach ($latestPost as $post): ?>
            <a href="communities.php" style="text-decoration:none;color:inherit">
              <div style="display:flex;gap:12px;align-items:center;padding:8px;border-radius:6px;transition:background 0.2s" onmouseover="this.style.background='rgba(144,238,144,0.1)'" onmouseout="this.style.background='transparent'">
                <img src="<?= h($post['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Post Image" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #00000022" />
                <div style="flex:1">
                  <div style="font-weight:700;line-height:1.2;font-size:0.95rem">Community Post</div>
                  <div style="opacity:.85;font-size:.85rem">by <?= h($post['username']) ?></div>
                  <div style="opacity:.8;font-size:.8rem"><?= h(excerpt($post['Content'], 80)) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No community posts yet.</p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>
  </main>

  <script>
    // Admin toggle functionality
    document.querySelectorAll('.admin-toggle').forEach(checkbox => {
      checkbox.addEventListener('change', async function() {
        const userId = this.dataset.userid;
        const isAdmin = this.checked;
        
        try {
          const response = await fetch('api/toggle_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, is_admin: isAdmin })
          });
          
          const data = await response.json();
          
          if (data.ok) {
            // Show success message
            const msg = document.createElement('div');
            msg.textContent = isAdmin ? 'User promoted to admin' : 'Admin privileges removed';
            msg.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#90ee90;color:#000;padding:12px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:10000;animation:slideIn 0.3s ease';
            document.body.appendChild(msg);
            setTimeout(() => msg.remove(), 3000);
          } else {
            alert(data.error || 'Failed to update admin status');
            this.checked = !isAdmin; // Revert checkbox
          }
        } catch (err) {
          console.error('Error:', err);
          alert('Network error. Please try again.');
          this.checked = !isAdmin; // Revert checkbox
        }
      });
    });
  </script>
  <style>
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</body>
</html>
