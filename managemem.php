<?php
require 'auth_check_admin.php';

// Manual verify user
if (isset($_GET['verify_id'])) {
    $vid = (int)$_GET['verify_id'];
    $vs  = $conn->prepare("UPDATE registration SET is_verified=1, verification_token=NULL WHERE Id=?");
    $vs->bind_param("i", $vid);
    $vs->execute();
    header("Location: managemem.php?msg=verified"); exit;
}

// Pagination
$per_page = 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// Search
$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
$types  = '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $where = "WHERE Username LIKE ? OR Email LIKE ? OR Flatno LIKE ? OR MobileNo LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

// Total count
$count_sql = "SELECT COUNT(*) FROM registration $where";
if ($params) {
    $cs = $conn->prepare($count_sql);
    $cs->bind_param($types, ...$params);
    $cs->execute();
    $total = $cs->get_result()->fetch_row()[0];
} else {
    $total = $conn->query("SELECT COUNT(*) FROM registration")->fetch_row()[0];
}
$total_pages = max(1, (int)ceil($total / $per_page));

// Fetch rows
$data_sql = "SELECT * FROM registration $where LIMIT ? OFFSET ?";
$dp = $params;
$dt = $types . 'ii';
$dp[] = $per_page;
$dp[] = $offset;
$ds = $conn->prepare($data_sql);
$ds->bind_param($dt, ...$dp);
$ds->execute();
$query_run = $ds->get_result();

$page_title = "Manage Members";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.search-form { display:flex; gap:10px; }
.search-form input { padding:10px 15px; border:2px solid #0EA5C9; border-radius:8px; outline:none; font-size:14px; width:280px; }
.search-form button { padding:10px 18px; background:#0EA5C9; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
.search-form a { padding:10px 15px; background:#eee; color:#555; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; display:flex; align-items:center; }
</style>

<div class="main-container" id="main-container">
    <?php if (isset($_GET['msg']) && $_GET['msg']==='verified'): ?>
    <div class="flash-success" style="max-width:900px;margin:0 auto 20px;">User verified and activated successfully!</div>
<?php endif; ?>
<h2 class="page-title">Manage Members</h2>

    <div class="top-bar">
        <p style="color:#666;font-size:14px;">Showing <strong><?= $query_run->num_rows ?></strong> of <strong><?= $total ?></strong> members</p>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by name, email, flat..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <?php if ($search): ?><a href="managemem.php">Clear</a><?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="content-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Flat No.</th><th>Mobile</th><th>Family</th><th>Status</th><th>Update</th><th>Delete</th></tr>
            </thead>
            <tbody>
            <?php if ($query_run->num_rows > 0): ?>
                <?php while ($row = $query_run->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['Id'] ?></td>
                    <td><?= htmlspecialchars($row['Username']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Flatno']) ?></td>
                    <td><?= htmlspecialchars($row['MobileNo']) ?></td>
                    <td><?= htmlspecialchars($row['No_of_family_members'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($row['is_verified'])): ?>
                            <span class="badge badge-success">✓ Active</span>
                        <?php else: ?>
                            <a href="managemem.php?verify_id=<?= $row['Id'] ?>"
                               style="background:#F59E0B;color:white;padding:5px 10px;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;"
                               onclick="return confirm('Manually activate this account?')">Activate</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form action="updateuser.php" method="POST" style="display:inline;">
                            <input type="hidden" name="Id" value="<?= $row['Id'] ?>">
                            <button type="submit" name="edit" class="Table_btn">Update</button>
                        </form>
                    </td>
                    <td>
                        <form action="deleteuser.php" method="POST" style="display:inline;"
                              onsubmit="return confirm('Delete member <?= htmlspecialchars(addslashes($row['Username'])) ?>? This cannot be undone.');">
                            <input type="hidden" name="Id" value="<?= $row['Id'] ?>">
                            <?= csrf_field() ?>
                            <button type="submit" name="delete" class="Table_btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">
                    <?= $search ? "No members found matching \"" . htmlspecialchars($search) . "\"." : "No members registered." ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">&laquo; Prev</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active-page"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
