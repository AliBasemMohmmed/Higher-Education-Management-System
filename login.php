<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];
  
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  $user = $stmt->fetch();
  
  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    header('Location: index.php');
    exit;
  } else {
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
  }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تسجيل الدخول</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f5f5f5;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-form {
      width: 100%;
      max-width: 400px;
      padding: 15px;
      margin: auto;
    }
  </style>
</head>
<body>
  <div class="login-form">
    <div class="card">
      <div class="card-body">
        <h2 class="text-center mb-4">تسجيل الدخول</h2>
        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">اسم المستخدم</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">دخول</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
