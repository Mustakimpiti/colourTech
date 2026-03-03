<?php
/**
 * ColourTech Industries - General Contact Inquiries
 */
session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle   = 'General Contact Inquiries';
$breadcrumbs = ['General Contact' => ''];

function parseMessageFields(string $message): array {
    $fields = ['product'=>'','company'=>'','country'=>'','application'=>'','phone'=>'','file'=>'','message'=>''];
    $map    = ['Product'=>'product','Company'=>'company','Country'=>'country',
               'Used Application'=>'application','Phone'=>'phone','File'=>'file'];
    $lines = explode("\n", $message); $msgLines = [];
    foreach ($lines as $line) {
        $matched = false;
        foreach ($map as $label => $key) {
            if (stripos($line, $label.':') === 0) { $fields[$key] = trim(substr($line, strlen($label)+1)); $matched = true; break; }
        }
        if (!$matched) $msgLines[] = $line;
    }
    $fields['message'] = trim(implode("\n", $msgLines));
    return $fields;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { setFlashMessage('error','Invalid security token.'); redirect('inquiries-contact.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int)($_POST['id']??0); $status = sanitize($_POST['status']??'');
        if ($id && in_array($status,['new','read','replied','archived'])) {
            $pdo->prepare("UPDATE contact_inquiries SET status=? WHERE id=?")->execute([$status,$id]);
            logActivity($_SESSION['admin_id'],'updated','contact_inquiries',$id,"Updated status to: $status");
            setFlashMessage('success','Status updated!');
        }
        redirect('inquiries-contact.php');
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id']??0);
        if ($id) { $r=$pdo->prepare("SELECT * FROM contact_inquiries WHERE id=?"); $r->execute([$id]); $inq=$r->fetch();
            if ($inq) { $pdo->prepare("DELETE FROM contact_inquiries WHERE id=?")->execute([$id]);
                logActivity($_SESSION['admin_id'],'deleted','contact_inquiries',$id,"Deleted inquiry from: ".$inq['name']);
                setFlashMessage('success','Inquiry deleted!'); } }
        redirect('inquiries-contact.php');
    }
    if ($action === 'bulk_action') {
        $ba = sanitize($_POST['bulk_action']??'');
        $ids = array_map('intval', json_decode($_POST['bulk_selected_ids']??'[]',true)?:[]);
        if (!empty($ids) && !empty($ba)) {
            $ph = implode(',',array_fill(0,count($ids),'?'));
            if ($ba==='delete') { $pdo->prepare("DELETE FROM contact_inquiries WHERE id IN ($ph)")->execute($ids); setFlashMessage('success',count($ids).' deleted!'); }
            elseif (in_array($ba,['new','read','replied','archived'])) { $pdo->prepare("UPDATE contact_inquiries SET status=? WHERE id IN ($ph)")->execute(array_merge([$ba],$ids)); setFlashMessage('success',count($ids).' updated to "'.$ba.'"!'); }
        } else { setFlashMessage('warning','Select at least one inquiry and an action.'); }
        redirect('inquiries-contact.php');
    }
}

$statusFilter = sanitize($_GET['status']??'');
$searchFilter = sanitize($_GET['search']??'');
$where  = ["subject NOT LIKE 'Sample Request:%'","subject NOT LIKE 'PDF Download:%'"]; $params=[];
if (!empty($statusFilter)) { $where[]="status=?"; $params[]=$statusFilter; }
if (!empty($searchFilter)) { $where[]="(name LIKE ? OR email LIKE ? OR subject LIKE ?)"; $t="%$searchFilter%"; array_push($params,$t,$t,$t); }
$whereSQL='WHERE '.implode(' AND ',$where);
$stmt=$pdo->prepare("SELECT * FROM contact_inquiries $whereSQL ORDER BY created_at DESC"); $stmt->execute($params);
$inquiries=$stmt->fetchAll();

$total    =(int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE subject NOT LIKE 'Sample Request:%' AND subject NOT LIKE 'PDF Download:%'")->fetchColumn();
$newCount =(int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status='new' AND subject NOT LIKE 'Sample Request:%' AND subject NOT LIKE 'PDF Download:%'")->fetchColumn();
$read     =(int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status='read' AND subject NOT LIKE 'Sample Request:%' AND subject NOT LIKE 'PDF Download:%'")->fetchColumn();
$replied  =(int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status='replied' AND subject NOT LIKE 'Sample Request:%' AND subject NOT LIKE 'PDF Download:%'")->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4><i class="fas fa-envelope me-2" style="color:#6c757d;"></i>General Contact Inquiries</h4>
            <p class="mb-0">Messages submitted through the website contact form</p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3"><div class="stats-card">
        <div class="stats-icon" style="background:linear-gradient(135deg,#6c757d,#495057)"><i class="fas fa-inbox"></i></div>
        <h3><?php echo $total; ?></h3><p>Total</p>
    </div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="stats-card">
        <div class="stats-icon" style="background:linear-gradient(135deg,#dc3545,#c82333)"><i class="fas fa-bell"></i></div>
        <h3><?php echo $newCount; ?></h3><p>New</p>
    </div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="stats-card">
        <div class="stats-icon" style="background:linear-gradient(135deg,#0dcaf0,#0bacca)"><i class="fas fa-eye"></i></div>
        <h3><?php echo $read; ?></h3><p>Read</p>
    </div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="stats-card">
        <div class="stats-icon" style="background:linear-gradient(135deg,#198754,#157347)"><i class="fas fa-reply"></i></div>
        <h3><?php echo $replied; ?></h3><p>Replied</p>
    </div></div>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" action="inquiries-contact.php" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="search" placeholder="Name, email, subject..." value="<?php echo htmlspecialchars($searchFilter); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <option value="new"      <?php echo $statusFilter==='new'?'selected':''; ?>>New</option>
                <option value="read"     <?php echo $statusFilter==='read'?'selected':''; ?>>Read</option>
                <option value="replied"  <?php echo $statusFilter==='replied'?'selected':''; ?>>Replied</option>
                <option value="archived" <?php echo $statusFilter==='archived'?'selected':''; ?>>Archived</option>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Filter</button>
            <a href="inquiries-contact.php" class="btn btn-secondary ms-2"><i class="fas fa-times me-2"></i>Clear</a>
        </div>
    </form>
</div></div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">
        <?php echo (!empty($statusFilter)||!empty($searchFilter))?'Filtered ('.count($inquiries).')':'All General Contact ('.count($inquiries).')'; ?>
    </h5></div>
    <div class="card-body">
        <form method="POST" id="bulkForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="bulk_action">
            <input type="hidden" name="bulk_selected_ids" id="bulkSelectedIds">
            <div class="d-flex align-items-center gap-3 mb-3" id="bulkActionBar" style="display:none !important;">
                <span class="text-muted" id="selectedCount">0 selected</span>
                <select class="form-select form-select-sm" name="bulk_action" style="width:auto;">
                    <option value="">-- Action --</option>
                    <option value="read">Mark as Read</option><option value="replied">Mark as Replied</option>
                    <option value="archived">Mark as Archived</option><option value="new">Mark as New</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-danger" onclick="return prepareBulkSubmit()"><i class="fas fa-check me-1"></i>Apply</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead><tr>
                    <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                    <th width="40">#</th><th>Sender</th><th>Subject</th><th>Message Preview</th>
                    <th>Date</th><th>Status</th><th width="120">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if(empty($inquiries)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No contact inquiries found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($inquiries as $i=>$inq): ?>
                        <?php
                        $p = parseMessageFields($inq['message']??'');
                        $sc = ['new'=>['bg-danger','New'],'read'=>['bg-info','Read'],'replied'=>['bg-success','Replied'],'archived'=>['bg-secondary','Archived']];
                        $cfg = $sc[$inq['status']]??['bg-secondary',ucfirst($inq['status'])];
                        $prev = !empty($p['message'])?$p['message']:strip_tags($inq['message']);
                        ?>
                        <tr class="<?php echo $inq['status']==='new'?'table-warning':''; ?>">
                            <td><input type="checkbox" class="form-check-input row-checkbox" value="<?php echo $inq['id']; ?>"></td>
                            <td><?php echo $i+1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($inq['name']); ?></strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" class="text-muted small"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($inq['email']); ?></a>
                                <?php if($inq['phone']): ?><br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($inq['phone']); ?></small><?php endif; ?>
                            </td>
                            <td><?php echo $inq['subject']?htmlspecialchars($inq['subject']):'<span class="text-muted">—</span>'; ?></td>
                            <td><span class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($prev,0,70,'...')); ?></span></td>
                            <td><small><?php echo date('d M Y',strtotime($inq['created_at'])); ?></small><br><small class="text-muted"><?php echo date('h:i A',strtotime($inq['created_at'])); ?></small></td>
                            <td><span class="badge <?php echo $cfg[0]; ?>"><?php echo $cfg[1]; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewInquiry(<?php echo htmlspecialchars(json_encode(array_merge($inq,['_parsed'=>$p]))); ?>)" title="View"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-info"    onclick="changeStatus(<?php echo $inq['id']; ?>,'<?php echo $inq['status']; ?>')" title="Status"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"  onclick="deleteInquiry(<?php echo $inq['id']; ?>,'<?php echo htmlspecialchars($inq['name']); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Inquiry Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label text-muted small">Name</label><p class="fw-bold mb-0" id="vName">—</p></div>
                <div class="col-md-6"><label class="form-label text-muted small">Status</label><p class="mb-0" id="vStatus">—</p></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label text-muted small">Email</label><p class="mb-0" id="vEmail">—</p></div>
                <div class="col-md-6"><label class="form-label text-muted small">Phone</label><p class="mb-0" id="vPhone">—</p></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label text-muted small">Subject</label><p class="mb-0" id="vSubject">—</p></div>
                <div class="col-md-6"><label class="form-label text-muted small">Date</label><p class="mb-0" id="vDate">—</p></div>
            </div>
            <div class="mb-3"><label class="form-label text-muted small">Message</label><div class="p-3 bg-light rounded" id="vMessage" style="white-space:pre-wrap;min-height:60px;">—</div></div>
            <div><label class="form-label text-muted small">IP Address</label><p class="mb-0 text-muted small" id="vIP">—</p></div>
        </div>
        <div class="modal-footer justify-content-between">
            <a href="#" id="vReplyBtn" class="btn btn-success"><i class="fas fa-reply me-2"></i>Reply via Email</a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="vMarkReadBtn" onclick="markAsReadFromModal()"><i class="fas fa-check me-2"></i>Mark as Read</button>
            </div>
        </div>
    </div></div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="sId">
            <div class="modal-header"><h5 class="modal-title">Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><label class="form-label">New Status</label>
                <select class="form-select" name="status" id="sNewStatus">
                    <option value="new">New</option><option value="read">Read</option>
                    <option value="replied">Replied</option><option value="archived">Archived</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="dId">
</form>
<form method="POST" id="markReadForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="update_status"><input type="hidden" name="status" value="read"><input type="hidden" name="id" id="mrId">
</form>

<script>
let currentId=null;
const SL={new:'<span class="badge bg-danger">New</span>',read:'<span class="badge bg-info">Read</span>',replied:'<span class="badge bg-success">Replied</span>',archived:'<span class="badge bg-secondary">Archived</span>'};
function viewInquiry(d){currentId=d.id;const p=d._parsed||{};
    document.getElementById('vName').textContent=d.name||'—';
    document.getElementById('vStatus').innerHTML=SL[d.status]||d.status;
    document.getElementById('vEmail').innerHTML=`<a href="mailto:${d.email}">${d.email}</a>`;
    document.getElementById('vPhone').textContent=d.phone||'—';
    document.getElementById('vSubject').textContent=d.subject||'—';
    document.getElementById('vIP').textContent=d.ip_address||'—';
    document.getElementById('vMessage').textContent=p.message||d.message||'—';
    document.getElementById('vReplyBtn').href=`mailto:${d.email}?subject=Re: ${encodeURIComponent(d.subject||'Your Inquiry')}`;
    const dt=new Date(d.created_at.replace(' ','T'));
    document.getElementById('vDate').textContent=dt.toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true});
    document.getElementById('vMarkReadBtn').style.display=d.status==='new'?'inline-block':'none';
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}
function markAsReadFromModal(){document.getElementById('mrId').value=currentId;document.getElementById('markReadForm').submit();}
function changeStatus(id,s){document.getElementById('sId').value=id;document.getElementById('sNewStatus').value=s;new bootstrap.Modal(document.getElementById('statusModal')).show();}
function deleteInquiry(id,name){if(confirm(`Delete inquiry from "${name}"?\nThis cannot be undone.`)){document.getElementById('dId').value=id;document.getElementById('deleteForm').submit();}}
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('selectAll').addEventListener('change',function(){document.querySelectorAll('.row-checkbox').forEach(cb=>cb.checked=this.checked);updateBulkBar();});
    document.querySelectorAll('.row-checkbox').forEach(cb=>cb.addEventListener('change',updateBulkBar));
});
function updateBulkBar(){const n=document.querySelectorAll('.row-checkbox:checked').length;document.getElementById('selectedCount').textContent=n+' selected';document.getElementById('bulkActionBar').style.setProperty('display',n>0?'flex':'none','important');}
function prepareBulkSubmit(){const a=document.querySelector('[name="bulk_action"]').value;if(!a){alert('Please select a bulk action.');return false;}const ids=Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb=>cb.value);if(!ids.length){alert('Please select at least one inquiry.');return false;}document.getElementById('bulkSelectedIds').value=JSON.stringify(ids);if(a==='delete')return confirm('Delete selected? This cannot be undone.');return true;}
</script>
<?php include 'includes/footer.php'; ?>