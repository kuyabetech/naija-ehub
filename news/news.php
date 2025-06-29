<?php
session_start();
require_once('../config/db.php');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News Publishing Services | Naija eHub</title>
  <link rel="stylesheet" href="../css/nin.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>
      <section class="service-header">
        <div class="header-content">
          <h2>News Publishing Services</h2>
          <p>Publish, manage, or view news articles and announcements</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-newspaper"></i>
        </div>
      </section>

      <!-- Service Options -->
      <section class="service-options">
        <div class="option-card active" data-option="publish">
          <i class="fas fa-pen-nib"></i>
          <h4>Publish News</h4>
          <p>Create and publish a new article or announcement</p>
          <button class="btn btn-outline" onclick="window.location.href='publish_news.php'">Start Publishing</button>
        </div>

        <div class="option-card" data-option="manage">
          <i class="fas fa-edit"></i>
          <h4>Manage Articles</h4>
          <p>Edit or remove your published news articles</p>
          <button class="btn btn-outline" onclick="window.location.href='manage_news.php'">Manage Articles</button>
        </div>

        <div class="option-card" data-option="view">
          <i class="fas fa-eye"></i>
          <h4>View News</h4>
          <p>Browse all published news and announcements</p>
          <button class="btn btn-outline" onclick="window.location.href='view_news.php'">View News</button>
        </div>
      </section>

    </main>
  </div>
  <?php include __DIR__ . '/../includes/spinner.php'; ?>
  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../js/script.js"></script>
</body>
</html>
