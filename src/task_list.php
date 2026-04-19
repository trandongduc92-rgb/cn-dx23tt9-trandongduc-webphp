<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

$error_message = "";
$tasks = [];

if($user_role == 'ceo'){

    $stmt = $conn->query("
        SELECT t.*, u.name as employee 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC
    ");

} elseif($user_role == 'manager'){

    $stmt = $conn->prepare("
        SELECT t.*, u.name as employee 
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
        SELECT t.*, u.name as employee 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id
        WHERE u.leader_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);

} elseif($user_role == 'staff'){

    $stmt = $conn->prepare("
        SELECT t.*, u.name as employee 
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id
        WHERE t.assigned_to = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);

} else {

    $error_message = "Bạn không có quyền truy cập chức năng này.";
}

if (isset($stmt)) {
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">

    <h3 class="mb-4">📋 Danh sách công việc</h3>

    <?php if (!empty($error_message)) : ?>
        <div class="alert alert-danger">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <?php if (empty($error_message)) : ?>
    <table class="table table-bordered table-hover text-center align-middle">

        <thead class="table-dark">
            <tr>
                <th>Tiêu đề</th>
                <th>Nhân viên</th>
                <th>Tiến độ</th>
                <th>Trạng thái</th>
                <th>Deadline</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($tasks as $row): ?>

            <?php
            $progress = (int)$row['progress'];
            $today = strtotime(date('Y-m-d'));

            $rowBgColor = '';
            $deadlineText = 'Chưa có';
            $statusBadge = '';

            $deadlineDate = !empty($row['deadline']) ? strtotime($row['deadline']) : null;
            $daysLeft = $deadlineDate ? ceil(($deadlineDate - $today)/86400) : null;

            if($deadlineDate){
                $deadlineText = date('d/m/Y', $deadlineDate);
            }

            if($progress == 0){
                $rowBgColor = '#e2e3e5';
                $statusBadge = '<span class="badge bg-secondary">⏸ Chưa thực hiện</span>';

            } elseif($progress > 0 && $progress < 100){

                if($deadlineDate && $deadlineDate < $today){
                    $rowBgColor = '#f8d7da';
                    $statusBadge = '<span class="badge bg-danger">⚠ Quá hạn</span>';

                } elseif($deadlineDate && $daysLeft <= 3){
                    $rowBgColor = '#fff3cd';
                    $statusBadge = '<span class="badge bg-warning text-dark">⏳ Cận deadline</span>';

                } else {
                    $rowBgColor = '#cbdb34';
                    $statusBadge = '<span class="badge bg-success">🔄 Đang thực hiện</span>';
                }

            } elseif($progress == 100){
                $rowBgColor = '#d4edda';
                $statusBadge = '<span class="badge bg-success">✔ Hoàn thành</span>';
            }

            if($progress == 100){
                $progressColor = 'bg-success';
            } elseif($deadlineDate && $deadlineDate < $today){
                $progressColor = 'bg-danger';
            } elseif($deadlineDate && $daysLeft <= 3){
                $progressColor = 'bg-warning';
            } elseif($progress > 0){
                $progressColor = 'bg-success';
            } else {
                $progressColor = 'bg-secondary';
            }
            ?>

            <tr>
                <td style="background-color:<?= $rowBgColor ?>;">
                    <b><?= htmlspecialchars($row['title']) ?></b>
                </td>

                <td style="background-color:<?= $rowBgColor ?>;">
                    <?= htmlspecialchars($row['employee']) ?>
                </td>

                <td style="width:200px; background-color:<?= $rowBgColor ?>;">
                    <div class="progress">
                        <div class="progress-bar <?= $progressColor ?>" 
                             style="width: <?= $progress ?>%">
                             <?= $progress ?>%
                        </div>
                    </div>
                </td>

                <td style="background-color:<?= $rowBgColor ?>;">
                    <?= $statusBadge ?>
                </td>

                <td style="background-color:<?= $rowBgColor ?>;">
                    <?= $deadlineText ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>
    <?php endif; ?>

</div>
