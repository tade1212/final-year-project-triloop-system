<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Fetch available graduation years for the filter dropdown
$years_query = $conn->query("SELECT DISTINCT academic_year FROM graduated_students ORDER BY academic_year DESC");

// 2. Handle Filters
$selected_year = $_GET['year'] ?? "";
$search_name = $_GET['search'] ?? "";

$where_clauses = ["1=1"];
if (!empty($selected_year)) {
    $where_clauses[] = "academic_year = '" . $conn->real_escape_string($selected_year) . "'";
}
if (!empty($search_name)) {
    $s = $conn->real_escape_string($search_name);
    $where_clauses[] = "(full_name LIKE '%$s%' OR student_id LIKE '%$s%')";
}

$where_sql = implode(" AND ", $where_clauses);

// 3. Fetch Graduated Students
$sql = "SELECT * FROM graduated_students WHERE $where_sql ORDER BY academic_year DESC, final_avg DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0"><i class="fas fa-user-graduate me-2 text-primary"></i> Graduated Students <span class="text-muted fw-light">/ Archive</span></h3>
        <button class="btn btn-outline-primary shadow-sm px-4 no-print" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print Archive List
        </button>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4 bg-white no-print">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Filter by Academic Year</label>
                    <select name="year" class="form-select">
                        <option value="">-- All Graduation Years --</option>
                        <?php while($y = $years_query->fetch_assoc()): ?>
                            <option value="<?php echo $y['academic_year']; ?>" <?php echo ($selected_year == $y['academic_year']) ? 'selected' : ''; ?>>
                                Class of <?php echo $y['academic_year']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">Search Student Name or ID</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Type name or student ID..." value="<?php echo htmlspecialchars($search_name); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter Results</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Student ID</th>
                            <th>Full Name</th>
                            <th class="text-center">Section</th>
                            <th class="text-center">Year Graduated</th>
                            <th class="text-center">Final Avg (%)</th>
                            <th class="text-center pe-4">Graduation Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?php echo $row['student_id']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td class="text-center">Grade 12-<?php echo $row['section_at_grad']; ?></td>
                                <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info px-3"><?php echo $row['academic_year']; ?></span></td>
                                <td class="text-center fw-bold text-success"><?php echo number_format($row['final_avg'], 1); ?>%</td>
                                <td class="text-center text-muted pe-4"><?php echo date("d M Y", strtotime($row['graduation_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                                    No records found in the graduation archive.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { border: 1px solid #dee2e6 !important; }
    }
</style>

<?php include 'footer.php'; ?>