<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// Handle Add Period
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_period'])) {
    $name = $_POST['name'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $type = $_POST['type'];

    $conn->query("INSERT INTO time_slots (period_name, start_time, end_time, type) VALUES ('$name', '$start', '$end', '$type')");
    echo "<script>window.location='manage_periods.php';</script>";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM time_slots WHERE period_id=$id");
    echo "<script>window.location='manage_periods.php';</script>";
}

// Fetch Periods ordered by time
$periods = $conn->query("SELECT * FROM time_slots ORDER BY start_time ASC");
?>

<div class="container-fluid">
    <div class="row">
        <!-- Form -->
        <div class="col-md-4">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white"><h5 class="m-0">Add Time Slot</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Name </label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label>Start</label>
                                <input type="time" name="start" class="form-control" required>
                            </div>
                            <div class="col">
                                <label>End</label>
                                <input type="time" name="end" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Type</label>
                            <select name="type" class="form-select">
                                <option value="class">Class</option>
                                <option value="break">Break</option>
                                <option value="break">-</option>
                            </select>
                        </div>
                        <button type="submit" name="add_period" class="btn btn-success w-100">Save Period</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="col-md-8">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white"><h5 class="m-0 fw-bold">Time Slots (Grid Columns)</h5></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $periods->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['period_name']; ?></td>
                                <td><?php echo date("g:i A", strtotime($row['start_time'])) . " - " . date("g:i A", strtotime($row['end_time'])); ?></td>
                                <td><span class="badge bg-<?php echo ($row['type']=='break')?'warning':'info'; ?>"><?php echo ucfirst($row['type']); ?></span></td>
                                <td><a href="manage_periods.php?delete=<?php echo $row['period_id']; ?>" class="btn btn-sm btn-danger">Delete</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>