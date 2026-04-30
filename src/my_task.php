<form method="POST" action="update_task.php">
    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
    <select name="status">
        <option value="pending">Chưa làm</option>
        <option value="doing">Đang làm</option>
        <option value="done">Hoàn thành</option>
    </select>
    <button>Update</button>
</form>
