<?php
session_start();
include __DIR__ . '/../config/db.php';

$user = $_SESSION['user'];

if($user['role'] == 'ceo'){
    $sql = "SELECT t.*, u.name as receiver 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id";
    $stmt = $conn->query($sql);
}
else{
    $sql = "SELECT t.*, u.name as receiver 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id
            WHERE t.assigned_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user['id']]);
}

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending = [];
$doing = [];
$done = [];

foreach($tasks as $t){
    if($t['status'] == 'pending') $pending[] = $t;
    elseif($t['status'] == 'doing') $doing[] = $t;
    else $done[] = $t;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Quản lý công việc</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
}

.column {
    min-height: 400px;
    padding: 10px;
    border-radius: 10px;
}

.card-task {
    border-radius: 10px;
    padding: 10px;
    margin-bottom: 10px;
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.pending { background: #fff3cd; }
.doing { background: #d1ecf1; }
.done { background: #d4edda; }
</style>
</head>

<body>

<div class="container-fluid mt-4">

<h3 class="mb-4">📋 Quản lý công việc</h3>

<div class="row">

    <div class="col-md-3">
        <h5>⏳ Chưa làm</h5>
        <div class="column">
            <?php foreach($pending as $t): ?>
                <div class="card-task pending">
                    <b><?= $t['title'] ?></b><br>
                    <?= $t['receiver'] ?><br>
                    <small>Deadline: <?= $t['deadline'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-md-3">
        <h5>🔄 Đang làm</h5>
        <div class="column">
            <?php foreach($doing as $t): ?>
                <div class="card-task doing">
                    <b><?= $t['title'] ?></b><br>
                    <?= $t['receiver'] ?><br>
                    <small>Deadline: <?= $t['deadline'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-md-3">
        <h5>✅ Hoàn thành</h5>
        <div class="column">
            <?php foreach($done as $t): ?>
                <div class="card-task done">
                    <b><?= $t['title'] ?></b><br>
                    <?= $t['receiver'] ?><br>
                    <small>Deadline: <?= $t['deadline'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-md-3">
        <h5>📊 Tất cả</h5>
        <div class="column">
            <?php foreach($tasks as $t): ?>
                <div class="card-task">
                    <b><?= $t['title'] ?></b><br>
                    <?= $t['receiver'] ?><br>
                    <small><?= $t['status'] ?> | <?= $t['deadline'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

</div>

</body>
</html>
