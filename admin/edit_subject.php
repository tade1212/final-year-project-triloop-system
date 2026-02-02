<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_subjects.php");
    exit();
}

$id = intval($_GET['id']);
$success = "";
$error = "";

// --- 1. HANDLE UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['subject_name']);
    $code = strtoupper(trim($_POST['subject_code']));

    $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=? WHERE subject_id=?");
    $stmt->bind_param("ssi", $name, $code, $id);

    if ($stmt->execute()) {
        header("Location: manage_subjects.php?msg=updated");
        exit();
    } else {
        $error = "Error updating: " . $conn->error;
    }
}

// --- 2. FETCH DATA ---
$data = $conn->query("SELECT * FROM subjects WHERE subject_id=$id")->fetch_assoc();

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="m-0 fw-bold">Edit Subject</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" value="<?php echo $data['subject_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" value="<?php echo $data['subject_code']; ?>" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="manage_subjects.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>