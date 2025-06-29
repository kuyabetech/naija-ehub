<?php
session_start();
require_once('config/db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: auth/login.php');
  exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$tab = $_POST['tab'] ?? 'personal';

// Log user activity function
function log_user_activity($conn, $user_id, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
    $stmt->close();
}

// Handle Personal Info Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'personal') {
    $full_name = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'] ?? null;
    $address = trim($_POST['address'] ?? '');
    // Handle avatar upload
    $avatar_path = null;
    if (isset($_FILES['avatarUpload']) && $_FILES['avatarUpload']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatarUpload']['name'], PATHINFO_EXTENSION);
        $avatar_path = 'uploads/avatars/' . $user_id . '_' . time() . '.' . $ext;
        if (!is_dir('Uploads/avatars')) {
            mkdir('Uploads/avatars', 0755, true);
        }
        move_uploaded_file($_FILES['avatarUpload']['tmp_name'], $avatar_path);
    }
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, dob = ?, address = ?, avatar = COALESCE(?, avatar) WHERE id = ?");
    $stmt->bind_param("ssssssi", $full_name, $email, $phone, $dob, $address, $avatar_path, $user_id);
    $stmt->execute();
    $stmt->close();
    $message = "Personal information updated successfully.";
    $tab = 'personal';
    log_user_activity($conn, $user_id, 'Updated personal information');
}

// Handle Security Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'security') {
    $current = $_POST['currentPassword'];
    $new = $_POST['newPassword'];
    $confirm = $_POST['confirmPassword'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hash);
    $stmt->fetch();
    $stmt->close();
    if (!password_verify($current, $hash)) {
        $message = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $message = "New passwords do not match.";
    } elseif (strlen($new) < 8 || !preg_match('/\d/', $new) || !preg_match('/[A-Za-z]/', $new)) {
        $message = "Password must be at least 8 characters, with letters and numbers.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Password updated successfully.";
        log_user_activity($conn, $user_id, 'Changed password');
    }
    $tab = 'security';
}

// Handle 2FA Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === '2fa') {
    $enable_2fa = isset($_POST['enable2FA']) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $enable_2fa, $user_id);
    $stmt->execute();
    $stmt->close();
    $message = $enable_2fa ? "Two-factor authentication enabled." : "Two-factor authentication disabled.";
    $tab = 'security';
    log_user_activity($conn, $user_id, $enable_2fa ? 'Enabled 2FA' : 'Disabled 2FA');
}

// Handle Preferences Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preferences') {
    $theme = $_POST['theme'] ?? 'light';
    $emailNotif = isset($_POST['emailNotifications']) ? 1 : 0;
    $smsNotif = isset($_POST['smsNotifications']) ? 1 : 0;
    $pushNotif = isset($_POST['pushNotifications']) ? 1 : 0;
    $language = $_POST['language'] ?? 'en';
    $conn->query("CREATE TABLE IF NOT EXISTS user_preferences (
        user_id INT PRIMARY KEY,
        theme VARCHAR(20),
        email_notifications TINYINT(1),
        sms_notifications TINYINT(1),
        push_notifications TINYINT(1),
        language VARCHAR(10),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $stmt = $conn->prepare("REPLACE INTO user_preferences (user_id, theme, email_notifications, sms_notifications, push_notifications, language) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiis", $user_id, $theme, $emailNotif, $smsNotif, $pushNotif, $language);
    $stmt->execute();
    $stmt->close();
    $message = "Preferences updated successfully.";
    $tab = 'preferences';
    log_user_activity($conn, $user_id, 'Updated preferences');
}

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email, phone, dob, address, avatar, two_factor_enabled FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $dob, $address, $avatar, $two_factor_enabled);
$stmt->fetch();
$stmt->close();

// Fetch preferences
$theme = 'light';
$emailNotif = 1;
$smsNotif = 0;
$pushNotif = 1;
$language = 'en';
$prefs = $conn->prepare("SELECT theme, email_notifications, sms_notifications, push_notifications, language FROM user_preferences WHERE user_id = ?");
$prefs->bind_param("i", $user_id);
$prefs->execute();
$prefs->bind_result($theme, $emailNotif, $smsNotif, $pushNotif, $language);
$prefs->fetch();
$prefs->close();

// Fetch recent activity
$activities = [];
$activity_stmt = $conn->prepare("SELECT action, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_stmt->bind_result($action, $created_at);
while ($activity_stmt->fetch()) {
    $activities[] = ['action' => $action, 'created_at' => $created_at];
}
$activity_stmt->close();
$conn->close();

// Calculate profile completion
$profile_completion = 20; // Base for having an account
$profile_completion += $full_name ? 20 : 0;
$profile_completion += $email ? 20 : 0;
$profile_completion += $phone ? 20 : 0;
$profile_completion += $dob ? 10 : 0;
$profile_completion += $address ? 10 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub Profile - Manage your account, security, and preferences.">
  <title>My Profile | Naija eHub</title>
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/profile.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h2>My Profile</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()">Accessibility</button>
        </div>
      </header>

      <!-- Accessibility Panel -->
      <div class="accessibility-panel" id="accessibility-panel">
        <h4>Accessibility Options</h4>
        <label>
          Font Size:
          <select onchange="adjustFontSize(this.value)">
            <option value="1" <?php echo $theme === '1' ? 'selected' : ''; ?>>Normal</option>
            <option value="1.2" <?php echo $theme === '1.2' ? 'selected' : ''; ?>>Large</option>
            <option value="1.4" <?php echo $theme === '1.4' ? 'selected' : ''; ?>>Extra Large</option>
          </select>
        </label>
        <label>
          High Contrast:
          <input type="checkbox" onchange="toggleHighContrast(this.checked)" <?php echo $theme === 'high' ? 'checked' : ''; ?>>
        </label>
      </div>

      <!-- Profile Completion -->
      <section class="profile-completion">
        <h3>Profile Completion: <?php echo $profile_completion; ?>%</h3>
        <div class="completion-meter">
          <div class="completion-bar" style="width: <?php echo $profile_completion; ?>%;"></div>
        </div>
        <p>Complete your profile to unlock all features!</p>
      </section>

      <!-- Profile Tabs -->
      <section class="profile-tabs">
        <button class="tab-btn <?php echo $tab === 'personal' ? 'active' : ''; ?>" data-tab="personal">Personal Info</button>
        <button class="tab-btn <?php echo $tab === 'security' ? 'active' : ''; ?>" data-tab="security">Security</button>
        <button class="tab-btn <?php echo $tab === 'preferences' ? 'active' : ''; ?>" data-tab="preferences">Preferences</button>
      </section>

      <!-- Personal Info Tab -->
      <section class="profile-tab-content <?php echo $tab === 'personal' ? 'active' : ''; ?>" id="personalTab">
        <form id="personalInfoForm" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="personal">
          <input type="hidden" name="tab" value="personal">
          <div class="form-row">
            <div class="form-group">
              <label for="fullName">Full Name *</label>
              <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($full_name); ?>" required aria-required="true">
            </div>
            <div class="form-group">
              <label for="email">Email Address *</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required aria-required="true">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <input type="tel" id="phone" name="phone" pattern="[0-9]{11}" value="<?php echo htmlspecialchars($phone); ?>" required aria-required="true">
              <small>11-digit Nigerian number (e.g., 08012345678)</small>
            </div>
            <div class="form-group">
              <label for="dob">Date of Birth</label>
              <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="address">Residential Address</label>
            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
          </div>
          <div class="form-group">
            <label>Profile Photo</label>
            <div class="avatar-upload">
              <div class="avatar-preview" id="avatarPreview">
                <img src="<?php echo $avatar ? htmlspecialchars($avatar) : 'assets/img/avatar-placeholder.png'; ?>" alt="Profile Photo">
              </div>
              <div class="avatar-upload-controls">
                <input type="file" id="avatarUpload" name="avatarUpload" accept="image/*">
                <label for="avatarUpload" class="btn btn-outline">
                  <i class="fas fa-camera"></i> Change Photo
                </label>
                <button type="button" class="btn btn-text" id="removePhoto">Remove</button>
              </div>
            </div>
          </div>
          <div class="form-footer">
            <button type="reset" class="btn btn-outline">Cancel</button>
            <button type="submit" class="btn btn-primary" onclick="showConfirmModal('Confirm Personal Info Update', 'Are you sure you want to update your personal information?')">Save Changes</button>
          </div>
        </form>
      </section>

      <!-- Security Tab -->
      <section class="profile-tab-content <?php echo $tab === 'security' ? 'active' : ''; ?>" id="securityTab">
        <form id="securityForm" method="post">
          <input type="hidden" name="action" value="security">
          <input type="hidden" name="tab" value="security">
          <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            <p>Keep your account secure with a strong password and 2FA.</p>
          </div>
          <div class="form-group">
            <label for="currentPassword">Current Password *</label>
            <input type="password" id="currentPassword" name="currentPassword" required aria-required="true">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="newPassword">New Password *</label>
              <input type="password" id="newPassword" name="newPassword" required aria-required="true">
              <small>Minimum 8 characters with letters and numbers</small>
            </div>
            <div class="form-group">
              <label for="confirmPassword">Confirm Password *</label>
              <input type="password" id="confirmPassword" name="confirmPassword" required aria-required="true">
            </div>
          </div>
          <div class="password-strength">
            <div class="strength-meter">
              <div class="strength-bar" id="strengthBar"></div>
            </div>
            <span id="strengthText">Password Strength</span>
          </div>
          <div class="form-group">
            <label>Two-Factor Authentication (2FA)</label>
            <form id="twoFaForm" method="post">
              <input type="hidden" name="action" value="2fa">
              <input type="hidden" name="tab" value="security">
              <label class="switch">
                <input type="checkbox" name="enable2FA" <?php echo $two_factor_enabled ? 'checked' : ''; ?>>
                <span class="slider"></span>
                <span>Enable 2FA</span>
              </label>
              <button type="submit" class="btn btn-primary" onclick="showConfirmModal('Confirm 2FA Change', 'Are you sure you want to <?php echo $two_factor_enabled ? 'disable' : 'enable'; ?> 2FA?')">Update 2FA</button>
            </form>
          </div>
          <div class="form-footer">
            <button type="reset" class="btn btn-outline">Cancel</button>
            <button type="submit" class="btn btn-primary" onclick="showConfirmModal('Confirm Password Change', 'Are you sure you want to update your password?')">Update Password</button>
          </div>
        </form>
      </section>

      <!-- Preferences Tab -->
      <section class="profile-tab-content <?php echo $tab === 'preferences' ? 'active' : ''; ?>" id="preferencesTab">
        <form id="preferencesForm" method="post">
          <input type="hidden" name="action" value="preferences">
          <input type="hidden" name="tab" value="preferences">
          <div class="form-group">
            <label>Theme Preference</label>
            <div class="theme-options">
              <label class="theme-option">
                <input type="radio" name="theme" value="light" <?php echo $theme === 'light' ? 'checked' : ''; ?>>
                <div class="theme-preview light">
                  <i class="fas fa-sun"></i>
                  <span>Light Mode</span>
                </div>
              </label>
              <label class="theme-option">
                <input type="radio" name="theme" value="dark" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
                <div class="theme-preview dark">
                  <i class="fas fa-moon"></i>
                  <span>Dark Mode</span>
                </div>
              </label>
              <label class="theme-option">
                <input type="radio" name="theme" value="system" <?php echo $theme === 'system' ? 'checked' : ''; ?>>
                <div class="theme-preview system">
                  <i class="fas fa-desktop"></i>
                  <span>System Default</span>
                </div>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label>Notification Preferences</label>
            <div class="switch-group">
              <label class="switch">
                <input type="checkbox" name="emailNotifications" <?php echo $emailNotif ? 'checked' : ''; ?>>
                <span class="slider"></span>
                <span>Email Notifications</span>
              </label>
              <label class="switch">
                <input type="checkbox" name="smsNotifications" <?php echo $smsNotif ? 'checked' : ''; ?>>
                <span class="slider"></span>
                <span>SMS Notifications</span>
              </label>
              <label class="switch">
                <input type="checkbox" name="pushNotifications" <?php echo $pushNotif ? 'checked' : ''; ?>>
                <span class="slider"></span>
                <span>Push Notifications</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label for="language">Language</label>
            <select id="language" name="language">
              <option value="en" <?php echo $language === 'en' ? 'selected' : ''; ?>>English</option>
              <option value="yo" <?php echo $language === 'yo' ? 'selected' : ''; ?>>Yoruba</option>
              <option value="ig" <?php echo $language === 'ig' ? 'selected' : ''; ?>>Igbo</option>
              <option value="ha" <?php echo $language === 'ha' ? 'selected' : ''; ?>>Hausa</option>
              <option value="pcm" <?php echo $language === 'pcm' ? 'selected' : ''; ?>>Pidgin</option>
            </select>
          </div>
          <div class="form-footer">
            <button type="reset" class="btn btn-outline">Reset Defaults</button>
            <button type="submit" class="btn btn-primary" onclick="showConfirmModal('Confirm Preferences Update', 'Are you sure you want to update your preferences?')">Save Preferences</button>
          </div>
        </form>
      </section>

      <!-- Activity Log -->
      <section class="activity-section">
        <h3>Recent Activity</h3>
        <table class="activity-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($activities)): ?>
              <tr>
                <td colspan="2" style="text-align: center; padding: 1rem; color: #666;">No recent activity.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($activities as $activity): ?>
                <tr>
                  <td><?php echo htmlspecialchars($activity['action']); ?></td>
                  <td><?php echo htmlspecialchars($activity['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmationModal" class="modal-overlay" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modalTitle">Confirm Changes</h3>
        <button class="close-modal" onclick="closeConfirmModal()">×</button>
      </div>
      <div class="modal-body" id="modalMessage"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
        <button class="btn btn-primary" id="confirmAction">Confirm</button>
      </div>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="message" style="margin:1rem auto;max-width:600px;background:#e6f9ed;color:#067c3c;padding:1rem;border-radius:6px;text-align:center;">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <?php include __DIR__ . '../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '../includes/footer.php'; ?>
  <?php include __DIR__ . '../includes/spinner.php'; ?>

  <script>
    // Sidebar Toggle
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    // Accessibility
    function toggleAccessibilityPanel() {
      document.getElementById('accessibility-panel').classList.toggle('active');
    }

    function adjustFontSize(scale) {
      document.documentElement.style.setProperty('--font-size-base', `${scale}rem`);
      localStorage.setItem('font-size', scale);
    }

    function toggleHighContrast(isChecked) {
      document.body.setAttribute('data-contrast', isChecked ? 'high' : 'normal');
      localStorage.setItem('contrast', isChecked ? 'high' : 'normal');
    }

    // Load saved settings
    document.addEventListener('DOMContentLoaded', () => {
      const savedFontSize = localStorage.getItem('font-size') || '1';
      const savedContrast = localStorage.getItem('contrast') || 'normal';
      document.documentElement.style.setProperty('--font-size-base', `${savedFontSize}rem`);
      document.body.setAttribute('data-contrast', savedContrast);
      document.querySelector('input[type="checkbox"]').checked = savedContrast === 'high';
      updateTab('<?php echo $tab; ?>');
    });

    // Tab Switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.profile-tab-content').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(`${btn.dataset.tab}Tab`).classList.add('active');
      });
    });

    function updateTab(tab) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.profile-tab-content').forEach(t => t.classList.remove('active'));
      document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
      document.getElementById(`${tab}Tab`).classList.add('active');
    }

    // Avatar Preview
    document.getElementById('avatarUpload').addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          document.querySelector('#avatarPreview img').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    document.getElementById('removePhoto').addEventListener('click', () => {
      document.querySelector('#avatarPreview img').src = 'assets/img/avatar-placeholder.png';
      document.getElementById('avatarUpload').value = '';
    });

    // Password Strength
    document.getElementById('newPassword').addEventListener('input', (e) => {
      const password = e.target.value;
      const strengthBar = document.getElementById('strengthBar');
      const strengthText = document.getElementById('strengthText');
      let strength = 0;
      if (password.length >= 8) strength += 1;
      if (password.match(/[A-Za-z]/)) strength += 1;
      if (password.match(/\d/)) strength += 1;
      if (password.match(/[^A-Za-z0-9]/)) strength += 1;

      strengthBar.className = 'strength-bar';
      if (strength <= 1) {
        strengthBar.classList.add('weak');
        strengthText.textContent = 'Weak';
      } else if (strength <= 3) {
        strengthBar.classList.add('medium');
        strengthText.textContent = 'Medium';
      } else {
        strengthBar.classList.add('strong');
        strengthText.textContent = 'Strong';
      }
    });

    // Confirmation Modal
    let activeForm = null;
    function showConfirmModal(title, message) {
      activeForm = document.activeElement.closest('form');
      document.getElementById('modalTitle').textContent = title;
      document.getElementById('modalMessage').textContent = message;
      document.getElementById('confirmationModal').style.display = 'flex';
      document.getElementById('confirmAction').onclick = () => {
        if (activeForm) activeForm.submit();
      };
    }

    function closeConfirmModal() {
      document.getElementById('confirmationModal').style.display = 'none';
      activeForm = null;
    }
  </script>
</body>
</html>