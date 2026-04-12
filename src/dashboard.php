<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// check login
if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit();
}

// connect DB
include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'];

// ===== THỐNG KÊ =====
try {
    $total = $conn->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $doing = $conn->query("SELECT COUNT(*) FROM tasks WHERE status='doing'")->fetchColumn();
    $done  = $conn->query("SELECT COUNT(*) FROM tasks WHERE status='done'")->fetchColumn();
} catch (Exception $e){
    $total = $doing = $done = 0;
}

// ===== TASK CỦA TÔI =====
try {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE assigned_to = ?");
    $stmt->execute([$user_id]);
    $myTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e){
    $myTasks = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Dashboard - NAM LONG</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .logo {
    text-align: center;
    margin-bottom: 25px;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
}

.logo i {
    font-size: 1.2rem;
    display: block;
    margin-bottom: 5px;
    color: #a8edea;
}
body { 
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    font-size: 0.85rem;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    height: 100vh;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    color: #fff;
    position: fixed;
    width: 170px;
    padding-top: 20px;
}

.sidebar h4 {
    font-size: 1rem;
    text-align: center;
    margin-bottom: 25px;
}

.sidebar a {
    color: #ccc;
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 8px;
    margin: 5px 10px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}

/* CONTENT */
.content-wrapper {
    width: 65%;
    margin-left: 190px;
    padding: 20px;
}

/* CARD */
.card {
    border-radius: 15px;
    border: none;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
}

.card-stats {
    border-radius: 15px;
    transition: 0.3s;
}

.card-stats:hover {
    transform: translateY(-5px);
}

/* BUTTON */
.btn-create {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 8px 15px;
    transition: 0.3s;
}

.btn-create:hover {
    opacity: 0.9;
}

/* TABLE */
table th {
    font-weight: 600;
    font-size: 0.85rem;
}

table td {
    font-size: 0.85rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(102,126,234,0.1);
}

/* TITLE */
h3 {
    text-align: center;
    color: #fff;
    margin-bottom: 20px;
}
/* SIDEBAR PROFESSIONAL */
.sidebar {
    height: 100vh;
    width: 190px;
    position: fixed;
    background: #0f172a;
    padding: 20px 10px;
    display: flex;
    flex-direction: column;
}

/* LOGO */
.logo {
    text-align: center;
    color: #fff;
    margin-bottom: 30px;
}

.logo i {
    font-size: 1.4rem;
    color: #38bdf8;
    display: block;
    margin-bottom: 5px;
}

.logo span {
    font-size: 0.85rem;
    letter-spacing: 2px;
}

/* MENU */
.menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    margin: 6px 5px;
    border-radius: 8px;
    color: #94a3b8;
    text-decoration: none;
    transition: 0.25s;
    font-size: 0.85rem;
}

.menu a i {
    font-size: 1rem;
}

/* HOVER */
.menu a:hover {
    background: #1e293b;
    color: #fff;
}

/* ACTIVE */
.menu a.active {
    background: linear-gradient(135deg,#38bdf8,#6366f1);
    color: #fff;
}

/* LOGOUT */
.logout {
    margin-top: auto;
    color: #f87171 !important;
}

.logout:hover {
    background: rgba(248,113,113,0.1);
}
</style>
</head>

<body>

<div class="sidebar">

    <div class="logo">
        <i class="bi bi-building"></i>
        <span>NAM LONG</span>
    </div>

    <div class="menu">
        <a href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="task_list.php">
            <i class="bi bi-list-task"></i>
            <span>Công việc</span>
        </a>

        <a href="task_create.php">
            <i class="bi bi-plus-square"></i>
            <span>Tạo task</span>
        </a>

        <a href="logout.php" class="logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

</div>

<div class="content-wrapper">

<h3>Xin chào, 
    <span style="
        background: linear-gradient(45deg,#007cf0,#00dfd8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight:600;
    ">
        <?= htmlspecialchars($user_name) ?>
    </span>
</h3>

<!-- THỐNG KÊ -->
<div class="row g-3 mb-3">

    <div class="col-md-4">
        <div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#36d1dc,#5b86e5);">
            <h6>Tổng công việc</h6>
            <h2><?= $total ?></h2>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-stats p-3 text-dark" style="background: linear-gradient(135deg,#f7971e,#ffd200);">
            <h6>Đang làm</h6>
            <h2><?= $doing ?></h2>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#00c9ff,#92fe9d);">
            <h6>Hoàn thành</h6>
            <h2><?= $done ?></h2>
        </div>
    </div>

</div>

<!-- TASK -->
<div class="card p-3">
    <h5 class="text-center mb-2">Công việc của tôi</h5>

    <?php if(count($myTasks) == 0): ?>
        <p class="text-muted text-center">Chưa có công việc nào</p>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Tiêu đề</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($myTasks as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td>
                        <?php
                        if($t['status']=='done'){
                            echo '<span class="badge bg-success">Hoàn thành</span>';
                        } elseif($t['status']=='doing'){
                            echo '<span class="badge bg-warning text-dark">Đang làm</span>';
                        } else {
                            echo '<span class="badge bg-secondary">Chưa làm</span>';
                        }
                        ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($t['created_at'] ?? date('Y-m-d'))) ?></td>
                    <td>
                        <?= !empty($t['deadline']) 
                            ? date('d/m/Y', strtotime($t['deadline'])) 
                            : '<span class="text-muted">Chưa có</span>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

</div>

</body>
</html>
