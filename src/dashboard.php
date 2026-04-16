
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

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND progress > 0 AND progress < 100");
    $stmt->execute([$user_id]);
    $doing = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND progress = 100");
    $stmt->execute([$user_id]);
    $done = $stmt->fetchColumn();

} else {

    $total = $conn->query("SELECT COUNT(*) FROM tasks")->fetchColumn();

    $doing = $conn->query("SELECT COUNT(*) FROM tasks WHERE progress > 0 AND progress < 100")->fetchColumn();

    $done  = $conn->query("SELECT COUNT(*) FROM tasks WHERE progress = 100")->fetchColumn();
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

    // FIX N+1
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Dashboard - NAM LONG</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* GIỮ NGUYÊN CSS CỦA BẠN */
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
<h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Không có quyền</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body text-center">
<p>Bạn <b>không có quyền truy cập</b></p>
<p class="text-muted">Liên hệ Admin</p>
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

<td><?= !empty($t['deadline']) ? date('d/m/Y', strtotime($t['deadline'])) : '<span class="text-muted">Chưa có</span>' ?></td>

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

<?php $updates = $historyMap[$t['id']] ?? []; ?>

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
<input type="hidden" id="task_id_modal" name="task_id">
<input type="hidden" id="progress_modal" name="progress">
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
function openModal(taskId, btn){
    let form = btn.closest('form');
    let progress = form.querySelector('.progress-input').value;

    document.getElementById('task_id_modal').value = taskId;
    document.getElementById('progress_modal').value = progress;

    new bootstrap.Modal(document.getElementById('updateModal')).show();
}

function toggleHistory(taskId){
    let row = document.getElementById("history-" + taskId);
    if(!row) return;
    row.style.display = (row.style.display === "table-row") ? "none" : "table-row";
}

function showNoPermission(){
    let modalEl = document.getElementById('noPermissionModal');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    new bootstrap.Modal(modalEl).show();
}

document.addEventListener('hidden.bs.modal', function () {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
});
</script>

</body>
</html>
