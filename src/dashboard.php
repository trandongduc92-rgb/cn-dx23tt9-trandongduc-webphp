<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'];
$user_role = $_SESSION['user']['role'];

try {

if($user_role == 'ceo'){

    $total = $conn->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $doing = $conn->query("SELECT COUNT(*) FROM tasks WHERE progress > 0 AND progress < 100")->fetchColumn();
    $done  = $conn->query("SELECT COUNT(*) FROM tasks WHERE progress = 100")->fetchColumn();

}

elseif($user_role == 'manager'){

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            t.assigned_to = :id
            OR u.manager_id = :id
            OR u.leader_id IN (
                SELECT id FROM users WHERE manager_id = :id
            )
    ");
    $stmt->execute(['id' => $user_id]);
    $total = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            (
                t.assigned_to = :id
                OR u.manager_id = :id
                OR u.leader_id IN (
                    SELECT id FROM users WHERE manager_id = :id
                )
            )
        AND t.progress > 0 AND t.progress < 100
    ");
    $stmt->execute(['id' => $user_id]);
    $doing = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            (
                t.assigned_to = :id
                OR u.manager_id = :id
                OR u.leader_id IN (
                    SELECT id FROM users WHERE manager_id = :id
                )
            )
        AND t.progress = 100
    ");
    $stmt->execute(['id' => $user_id]);
    $done = $stmt->fetchColumn();
}

elseif($user_role == 'leader'){

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            t.assigned_to = :id
            OR u.leader_id = :id
    ");
    $stmt->execute(['id' => $user_id]);
    $total = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            (t.assigned_to = :id OR u.leader_id = :id)
        AND t.progress > 0 AND t.progress < 100
    ");
    $stmt->execute(['id' => $user_id]);
    $doing = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE 
            (t.assigned_to = :id OR u.leader_id = :id)
        AND t.progress = 100
    ");
    $stmt->execute(['id' => $user_id]);
    $done = $stmt->fetchColumn();
}

else{

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = :id");
    $stmt->execute(['id' => $user_id]);
    $total = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = :id AND progress > 0 AND progress < 100");
    $stmt->execute(['id' => $user_id]);
    $doing = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = :id AND progress = 100");
    $stmt->execute(['id' => $user_id]);
    $done = $stmt->fetchColumn();
}

} catch (Exception $e){
    $total = $doing = $done = 0;
}

try {
if($user_role != 'ceo'){

    $stmt = $conn->prepare("
        SELECT t.*, 
        (SELECT COUNT(*) FROM task_updates tu WHERE tu.task_id = t.id) as update_count
        FROM tasks t
        WHERE assigned_to = ?
    ");
    $stmt->execute([$user_id]);
    $myTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $myTasks = [];
}

    $stmt = $conn->prepare("
        SELECT tu.*, u.name 
        FROM task_updates tu
        JOIN users u ON tu.user_id = u.id
        WHERE tu.task_id IN (SELECT id FROM tasks WHERE assigned_to = ?)
        ORDER BY tu.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $allUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $historyMap = [];
    foreach($allUpdates as $u){
        $historyMap[$u['task_id']][] = $u;
    }

} catch (Exception $e){
    $myTasks = [];
    $historyMap = [];
}

try {
if($user_role == 'manager'){

    $stmt = $conn->prepare("
        SELECT t.*, u.name as staff_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE u.manager_id = ?
           OR u.leader_id IN (
                SELECT id FROM users WHERE manager_id = ?
           )
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);

} elseif($user_role == 'leader'){

    $stmt = $conn->prepare("
        SELECT t.*, u.name as staff_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE u.leader_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);

} elseif($user_role == 'ceo'){

    $stmt = $conn->query("
        SELECT t.*, u.name as staff_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC
    ");

} else {

    $staffTasks = [];
}

$staffTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($user_role == 'manager'){

    $stmt = $conn->prepare("
        SELECT tu.*, u.name 
        FROM task_updates tu
        JOIN users u ON tu.user_id = u.id
        WHERE tu.task_id IN (
            SELECT t.id FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            WHERE u.manager_id = ?
               OR u.leader_id IN (
                    SELECT id FROM users WHERE manager_id = ?
               )
        )
    ");
    $stmt->execute([$user_id, $user_id]);

} elseif($user_role == 'leader'){

    $stmt = $conn->prepare("
        SELECT tu.*, u.name 
        FROM task_updates tu
        JOIN users u ON tu.user_id = u.id
        WHERE tu.task_id IN (
            SELECT t.id FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            WHERE u.leader_id = ?
        )
    ");
    $stmt->execute([$user_id]);

} elseif($user_role == 'ceo'){

    $stmt = $conn->query("
        SELECT tu.*, u.name 
        FROM task_updates tu
        JOIN users u ON tu.user_id = u.id
    ");

} else {
    $staffUpdates = [];
}

$staffUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $staffHistoryMap = [];
    foreach($staffUpdates as $u){
        $staffHistoryMap[$u['task_id']][] = $u;
    }

} catch (Exception $e){
    $staffTasks = [];
    $staffHistoryMap = [];
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
body{
    background:#f1f5f9;
}
.shake-modal{
    animation: shake 0.3s;
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}

.sidebar{
    position:fixed;
    top:0;
    left:0;
    height:100%;
    width:230px;
    background: linear-gradient(180deg,#0f172a,#1e293b);
    color:#fff;
    padding-top:20px;
    box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    z-index:999;
}

.sidebar .logo{
    font-size:20px;
    font-weight:bold;
    text-align:center;
    margin-bottom:30px;
    letter-spacing:1px;
}

.sidebar .logo i{
    font-size:24px;
    margin-right:5px;
}

.sidebar .menu a{
    display:flex;
    align-items:center;
    gap:10px;
    color:#cbd5e1;
    text-decoration:none;
    padding:12px 20px;
    transition:0.3s;
    border-left:3px solid transparent;
}

.sidebar .menu a:hover{
    background:#334155;
    color:#fff;
    border-left:3px solid #38bdf8;
}

.sidebar .menu a.active{
    background:#1e293b;
    color:#fff;
    border-left:3px solid #22c55e;
}

.sidebar .menu a i{
    font-size:18px;
}

.content-wrapper{
    margin-left:230px;
    padding:20px;
}

.card-stats{
    border:none;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

.table{
    background:#fff;
    border-radius:10px;
    overflow:hidden;
}
</style>
</head>

<body>
<audio id="errorSound">
    <source src="https://cdn.pixabay.com/download/audio/2022/03/15/audio_115b9b1b6f.mp3?filename=error-126627.mp3">
</audio>

<div class="sidebar">
<div class="logo">
<i class="bi bi-building"></i>
<span>NAM LONG</span>
</div>

<div class="menu">
<a href="dashboard.php" class="active">
<i class="bi bi-speedometer2"></i>
<span>Dashboard</span>
</a>

<?php if($user_role == 'staff'): ?>

<a href="task_list.php?scope=my">
<i class="bi bi-list-task"></i>
<span>Công việc</span>
</a>

<?php elseif($user_role == 'leader' || $user_role == 'manager'): ?>

<a href="task_list.php?scope=team">
<i class="bi bi-list-task"></i>
<span>Công việc</span>
</a>

<a href="<?= $user_role == 'staff' ? '#' : 'task_create.php' ?>">
<i class="bi bi-plus-square"></i>
<span>Tạo task</span>
</a>

<?php else: ?>

<a href="task_list.php?scope=all">
<i class="bi bi-list-task"></i>
<span>Công việc</span>
</a>

<a href="<?= $user_role == 'staff' ? '#' : 'task_create.php' ?>">
<i class="bi bi-plus-square"></i>
<span>Tạo task</span>
</a>

<?php endif; ?>

<a href="logout.php" class="text-danger">
<i class="bi bi-box-arrow-right"></i>
<span>Logout</span>
</a>
</div>
</div>

<div class="content-wrapper">

<h3>Xin chào, <span style="background: linear-gradient(45deg,#007cf0,#00dfd8);-webkit-background-clip: text;-webkit-text-fill-color: transparent;font-weight:600;">
<?= htmlspecialchars($user_name) ?>
</span></h3>

<div class="row g-3 mb-3">
<div class="col-md-4"><div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#36d1dc,#5b86e5);"><h6>Tổng công việc</h6><h2><?= $total ?></h2></div></div>
<div class="col-md-4"><div class="card card-stats p-3 text-dark" style="background: linear-gradient(135deg,#f7971e,#ffd200);"><h6>Đang làm</h6><h2><?= $doing ?></h2></div></div>
<div class="col-md-4"><div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#00c9ff,#92fe9d);"><h6>Hoàn thành</h6><h2><?= $done ?></h2></div></div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
