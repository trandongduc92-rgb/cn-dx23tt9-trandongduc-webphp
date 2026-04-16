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

try {
if($user_id >= 9){

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE assigned_to = ? AND progress > 0 AND progress < 100
    ");
    $stmt->execute([$user_id]);
    $doing = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE assigned_to = ? AND progress = 100
    ");
    $stmt->execute([$user_id]);
    $done = $stmt->fetchColumn();

} else {

    $total = $conn->query("SELECT COUNT(*) FROM tasks")->fetchColumn();

    $doing = $conn->query("
        SELECT COUNT(*) FROM tasks 
        WHERE progress > 0 AND progress < 100
    ")->fetchColumn();

    $done  = $conn->query("
        SELECT COUNT(*) FROM tasks 
        WHERE progress = 100
    ")->fetchColumn();
}
} catch (Exception $e){
    $total = $doing = $done = 0;
}

try {
    $stmt = $conn->prepare("
    SELECT t.*, 
    (SELECT COUNT(*) FROM task_updates tu WHERE tu.task_id = t.id) as update_count
    FROM tasks t
    WHERE assigned_to = ?
");
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

.content-wrapper {
    width: 65%;
    margin-left: 190px;
    padding: 20px;
}

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

h3 {
    text-align: center;
    color: #fff;
    margin-bottom: 20px;
}

.sidebar {
    height: 100vh;
    width: 190px;
    position: fixed;
    background: #0f172a;
    padding: 20px 10px;
    display: flex;
    flex-direction: column;
}

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

.menu a:hover {
    background: #1e293b;
    color: #fff;
}

.menu a.active {
    background: linear-gradient(135deg,#38bdf8,#6366f1);
    color: #fff;
}

.logout {
    margin-top: auto;
    color: #f87171 !important;
}

.logout:hover {
    background: rgba(248,113,113,0.1);
}

.modal-content {
    border-radius: 15px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff;
}

.modal-title {
    font-weight: 600;
}

textarea {
    border-radius: 10px;
}

#noPermissionModal .modal-content {
    border-radius: 20px;
    overflow: hidden;
    animation: scaleUp 0.3s ease;
}

#noPermissionModal .modal-header {
    background: linear-gradient(135deg,#ff6a6a,#ff4757);
    color: #fff;
}

#noPermissionModal .modal-body {
    padding: 25px;
}

#noPermissionModal .btn {
    border-radius: 10px;
}

@keyframes scaleUp {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

td div {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

<?php if($user_id >= 9): ?>
<a href="#" onclick="showNoPermission()">
    <i class="bi bi-list-task"></i>
    <span>Công việc</span>
</a>

<a href="#" onclick="showNoPermission()">
    <i class="bi bi-plus-square"></i>
    <span>Tạo task</span>
</a>
<?php else: ?>
<a href="task_list.php">
    <i class="bi bi-list-task"></i>
    <span>Công việc</span>
</a>

<a href="task_create.php">
    <i class="bi bi-plus-square"></i>
    <span>Tạo task</span>
</a>
<?php endif; ?>

        <a href="logout.php" class="logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

</div>

<div class="content-wrapper">
<h3>Xin chào, 
    <span style="background: linear-gradient(45deg,#007cf0,#00dfd8);-webkit-background-clip: text;-webkit-text-fill-color: transparent;font-weight:600;">
        <?= htmlspecialchars($user_name) ?>
    </span>
</h3>

<div class="row g-3 mb-3">
<div class="col-md-4"><div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#36d1dc,#5b86e5);"><h6>Tổng công việc</h6><h2><?= $total ?></h2></div></div>
<div class="col-md-4"><div class="card card-stats p-3 text-dark" style="background: linear-gradient(135deg,#f7971e,#ffd200);"><h6>Đang làm</h6><h2><?= $doing ?></h2></div></div>
<div class="col-md-4"><div class="card card-stats p-3 text-white" style="background: linear-gradient(135deg,#00c9ff,#92fe9d);"><h6>Hoàn thành</h6><h2><?= $done ?></h2></div></div>
</div>

<div class="card p-3">
<h5 class="text-center mb-2">Công việc của tôi</h5>

<?php if(count($myTasks)==0): ?>
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
<th>Cập nhật</th>
</tr>
</thead>

<div class="modal fade" id="noPermissionModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<div class="modal-header">
    <h5 class="modal-title">
        <i class="bi bi-exclamation-triangle-fill"></i> Không có quyền
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-center">
    <p style="font-size:15px;">
        Bạn <b>không có quyền truy cập</b> chức năng này.
    </p>
    <p class="text-muted">
        Vui lòng liên hệ <b>Admin</b> để được cấp quyền.
    </p>
</div>

<div class="modal-footer">
    <button class="btn btn-danger" data-bs-dismiss="modal">Đã hiểu</button>
</div>

</div>
</div>
</div>

<tbody>
<?php foreach($myTasks as $t): ?>
<tr onclick="toggleHistory(<?= $t['id'] ?>)" style="cursor:pointer;">
<td><?= htmlspecialchars($t['title']) ?></td>

<td>
<?php
if($t['progress']==0){
echo '<span class="badge bg-secondary">Chưa làm</span>';
}elseif($t['progress']<100){
echo '<span class="badge bg-warning text-dark">Đang làm</span>';
}else{
echo '<span class="badge bg-success">Hoàn thành</span>';
}
?>
<div class="progress mt-1" style="height:6px;">
<div class="progress-bar <?= $t['progress']==100 ? 'bg-success' : ($t['progress']>0 ? 'bg-warning' : 'bg-secondary') ?>" style="width: <?= $t['progress'] ?>%"></div>
</div>
</td>

<td><?= date('d/m/Y', strtotime($t['created_at'] ?? date('Y-m-d'))) ?></td>

<td>
<?= !empty($t['deadline']) ? date('d/m/Y', strtotime($t['deadline'])) : '<span class="text-muted">Chưa có</span>' ?>
</td>

<td>
<form method="POST" action="update_task.php" class="d-flex gap-1">
<input type="hidden" name="task_id" value="<?= $t['id'] ?>">
<input type="number" name="progress" value="<?= $t['progress'] ?? 0 ?>" min="0" max="100" class="form-control form-control-sm progress-input" style="width:70px">

<button type="button" class="btn btn-sm btn-success"
onclick="event.stopPropagation(); openModal(<?= $t['id'] ?>, this)">
✔
</button>
</form>
</td>
</tr>

<tr class="history-row" id="history-<?= $t['id'] ?>" style="display:none;">
<td colspan="5">
<div style="padding:10px 15px; background:#f8fafc; border-radius:10px;">
        
<?php
$stmt = $conn->prepare("
SELECT tu.*, u.name 
FROM task_updates tu
JOIN users u ON tu.user_id = u.id
WHERE tu.task_id = ?
ORDER BY tu.created_at DESC
");
$stmt->execute([$t['id']]);
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if(count($updates) == 0): ?>
<small class="text-muted">Chưa có cập nhật</small>
<?php else: ?>
<?php foreach($updates as $u): ?>
<div style="font-size:0.8rem; margin-bottom:5px;">
<b><?= htmlspecialchars($u['name']) ?></b>
→ <?= $u['progress'] ?>%
<span class="text-muted">(<?= date('d/m H:i', strtotime($u['created_at'])) ?>)</span>
<br>
<i><?= htmlspecialchars($u['note']) ?></i>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
</td>
</tr>

<?php endforeach; ?>
</tbody>
</table>
</div>

<?php endif; ?>
</div>
</div>

<div class="modal fade" id="updateModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5 class="modal-title">Cập nhật công việc</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form method="POST" action="update_task.php">
<div class="modal-body">
<input type="hidden" name="task_id" id="task_id_modal">
<input type="hidden" name="progress" id="progress_modal">
<textarea name="note" class="form-control" rows="4" required></textarea>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
<button type="submit" class="btn btn-success">Lưu</button>
</div>
</form>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showNoPermission(){
    let modalEl = document.getElementById('noPermissionModal');
    if(!modalEl) return;
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    let modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function toggleHistory(taskId){
    let row = document.getElementById("history-" + taskId);
    if(!row) return;
    if(row.style.display === "none" || row.style.display === ""){
        row.style.display = "table-row";
    } else {
        row.style.display = "none";
    }
}
</script>

</body>
</html>
