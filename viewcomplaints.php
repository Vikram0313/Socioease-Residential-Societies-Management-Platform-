<?php
require 'auth_check_admin.php';

$result = $conn->query("SELECT * FROM combox ORDER BY ID ASC");

$page_title = "View Complaints";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.img-modal { display:none; position:fixed; z-index:10000; padding-top:100px; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.9); }
.img-modal-content { margin:auto; display:block; max-width:80%; max-height:70%; border-radius:10px; }
.img-modal-close { position:absolute; top:30px; right:40px; color:white; font-size:40px; cursor:pointer; }
.btn-small { padding:8px 14px; border-radius:6px; text-decoration:none; color:#fff; font-weight:600; font-size:13px; transition:0.2s; display:inline-block; }
.blue  { background:#0275d8; }
.green { background:#5cb85c; }
.btn-small:hover { filter:brightness(1.1); }
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Member Complaints</h2>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>ID</th><th>Member</th><th>Title</th><th>Complaint</th><th>Image</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()):
                $sc = ['Pending'=>'badge-pending','In Progress'=>'badge-info','Resolved'=>'badge-success'][$row['status']] ?? 'badge-pending';
            ?>
                <tr>
                    <td><?= $row['ID'] ?></td>
                    <td style="font-size:12px;color:#666;"><?= htmlspecialchars($row['username'] ?? '') ?></td>
                    <td><strong><?= htmlspecialchars($row['Title']) ?></strong></td>
                    <td style="text-align:left;max-width:300px;"><?= htmlspecialchars($row['complaint']) ?></td>
                    <td>
                        <?php if (!empty($row['image_path'])): ?>
                            <img src="<?= htmlspecialchars($row['image_path']) ?>" width="60" height="40"
                                 style="cursor:pointer;border-radius:5px;object-fit:cover;"
                                 onclick="openImgModal('<?= htmlspecialchars($row['image_path']) ?>')">
                        <?php else: echo "<span style='color:#ccc;font-size:11px;'>No Image</span>"; endif; ?>
                    </td>
                    <td><span class="badge <?= $sc ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <a class="btn-small blue" href="update_complaint.php?id=<?= $row['ID'] ?>&s=In+Progress">Accept</a>
                        <?php elseif ($row['status'] === 'In Progress'): ?>
                            <a class="btn-small green" href="update_complaint.php?id=<?= $row['ID'] ?>&s=Resolved">Resolve</a>
                        <?php else: ?>
                            <span style="color:#777;font-style:italic;font-size:12px;">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="imgModal" class="img-modal">
    <span class="img-modal-close" onclick="document.getElementById('imgModal').style.display='none'">&times;</span>
    <img class="img-modal-content" id="modalImage">
</div>

<?php
$extra_js = "
function openImgModal(src){document.getElementById('modalImage').src=src;document.getElementById('imgModal').style.display='block';}
window.onclick=function(e){if(e.target===document.getElementById('imgModal'))document.getElementById('imgModal').style.display='none';}
";
require 'footer.php';
?>
