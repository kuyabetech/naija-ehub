<header class="main-header">
        <div class="header-left">
          <h3>Welcome back, <span id="userName"><?php echo htmlspecialchars($full_name); ?></span></h3>
          <p id="currentDate"></p>
        </div>
        
        <div class="header-right">
          <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <?php
              // Fetch unread notifications count for the user
              $conn = getDbConnection();
              $notif_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
              $notif_stmt->bind_param("i", $user_id);
              $notif_stmt->execute();
              $notif_stmt->bind_result($unread_count);
              $notif_stmt->fetch();
              $notif_stmt->close();
              $conn->close();
              if ($unread_count > 0) {
                echo '<span class="badge">' . $unread_count . '</span>';
              }
            ?>
          </div>
          <div id="notificationPopup" class="notification-popup" style="display:none;">
            <div class="popup-header">
              <strong>Notifications</strong>
              <button id="closePopup" style="float:right;">&times;</button>
            </div>
            <ul id="notificationList">
              <!-- Notifications will be loaded here -->
            </ul>
          </div>
          <div class="user-avatar">
            <button onclick="window.location.href='/verify-app/profile.php'" style="border: none; background: transparent; cursor: pointer;">
              <img src="/verify-app/assets/img/avatar-placeholder.jpeg" alt="User">
            </button>
          </div>
        </div>
      </header>