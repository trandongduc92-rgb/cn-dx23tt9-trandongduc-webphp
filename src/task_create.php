<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if(!in_array($_SESSION['user']['role'], ['leader','manager','ceo'])){
    header("Location: dashboard.php?error=no_permission");
    exit();
}
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user'])) { die("Chưa đăng nhập!"); }
$currentUser = $_SESSION['user'];

if ($currentUser['role'] === 'staff') {
    header("Location: dashboard.php?error=no_permission");
    exit();
}

$users = [];

try {
    switch ($currentUser['role']) {

        case 'ceo':
            $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role != 'ceo'");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'manager':
            $stmt = $conn->prepare("
                SELECT id, name, role 
                FROM users 
                WHERE manager_id = ? 
                AND role IN ('leader','staff')
            ");
            $stmt->execute([$currentUser['id']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'leader':
            $stmt = $conn->prepare("
                SELECT id, name, role 
                FROM users 
                WHERE leader_id = ? 
                AND role = 'staff'
            ");
            $stmt->execute([$currentUser['id']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

} catch (PDOException $e) {
    die("Lỗi DB: " . $e->getMessage());
}

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $assigned_to = $_POST['assigned_to'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    $allowed_ids = array_column($users, 'id');

    if (empty($users)) {
        $errorMsg = "Không có nhân viên để giao việc!";
    }
    elseif (!in_array($assigned_to, $allowed_ids)) {
        $errorMsg = "Bạn không có quyền giao việc!";
    } elseif ($title == '') {
        $errorMsg = "Tiêu đề không được để trống!";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO tasks (title, assigned_to, status, created_at, deadline) 
                VALUES (?, ?, 'doing', NOW(), ?)
            ");
            $stmt->execute([$title, $assigned_to, $deadline]);

            $successMsg = "🎉 Tạo task thành công!";

        } catch (PDOException $e) {
            $errorMsg = "❌ Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Tạo Task</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { 
    background: linear-gradient(135deg, #dbeafe, #f1f5f9);
    font-family: 'Segoe UI', sans-serif;
    min-height: 100vh;
}

.wrapper {
    padding: 40px 15px;
    display: flex;
    justify-content: center;
}

.header {
    text-align: left;
    margin-bottom: 20px;
    padding-left: 10px;
}

.header h3 { 
    font-weight: 700; 
    color: #1e3a8a;
}

.header p {
    font-size: 14px;
}

.card {
    width: 100%;
    border-radius: 20px;
    padding: 25px;
    border: none;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

.form-control, .form-select {
    border-radius: 12px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    transition: 0.2s;
}

.form-control:focus, .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
}

.btn-primary { 
    background: linear-gradient(135deg, #2563eb, #1e40af);
    border: none;
    border-radius: 12px;
    padding: 12px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-primary:hover {
    transform: scale(1.05);
    background: linear-gradient(135deg, #1e40af, #1e3a8a);
}

.alert {
    border-radius: 12px;
    font-weight: 500;
}

form {
    width: 100%;
}

.container-box {
    width: 100%;
    max-width: 750px;
}

@media (max-width: 768px) {
    .header {
        text-align: center;
        padding-left: 0;
    }

    .card {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <div class="container-box">

        <div class="header">
            <h3>📊 Task Assignment</h3>
            <p class="text-muted">Phân công nhiệm vụ nhanh chóng, quản lý tiến độ thông minh</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= $successMsg ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= $errorMsg ?></div>
        <?php endif; ?>

        <div class="card">

            <form method="post">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề..." required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Giao cho</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">-- Chọn người nhận --</option>

                        <?php foreach($users as $u): ?>
                            <?php
                                if ($u['role'] == 'manager') $icon = '👔';
                                elseif ($u['role'] == 'leader') $icon = '🧭';
                                elseif ($u['role'] == 'employee') $icon = '👨‍💻';
                                else $icon = '👤';
                            ?>
                            <option value="<?= $u['id'] ?>">
                                <?= $icon ?> <?= htmlspecialchars($u['name']) ?> - <?= strtoupper($u['role']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Deadline</label>
                    <input type="date" name="deadline" class="form-control">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        + Tạo Task
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>
