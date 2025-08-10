<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة حفظ البيانات
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maintenance'])){
    $id = $_POST['id'] ?? '';
    $bus_id = (int)$_POST['bus_id'];
    $maintenance_type = $conn->real_escape_string($_POST['maintenance_type']);
    $work_details = $conn->real_escape_string($_POST['work_details']);
    $work_date = $conn->real_escape_string($_POST['work_date']);
    $work_km = (int)$_POST['work_km'];
    $cost = (float)$_POST['cost'];
    $workshop_name = $conn->real_escape_string($_POST['workshop_name']);
    $workshop_contact = $conn->real_escape_string($_POST['workshop_contact'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // معالجة رفع الفاتورة
    $invoice_path = '';
    if(!empty($_FILES['invoice_path']['name'])){
        $upload_dir = 'uploads/maintenance/';
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['invoice_path']['name'];
        $file_tmp = $_FILES['invoice_path']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
        
        if(in_array($file_ext, $allowed_ext)){
            $new_file_name = uniqid().'.'.$file_ext;
            move_uploaded_file($file_tmp, $upload_dir.$new_file_name);
            $invoice_path = $upload_dir.$new_file_name;
        }
    } elseif(!empty($_POST['old_invoice_path'])){
        $invoice_path = $_POST['old_invoice_path'];
    }

    if(empty($id)){
        // إضافة صيانة جديدة
        $sql = "INSERT INTO `maintenance_work` (`bus_id`, `maintenance_type`, `work_details`, 
                `work_date`, `work_km`, `cost`, `workshop_name`, `workshop_contact`, 
                `invoice_path`, `notes`) 
                VALUES ('$bus_id', '$maintenance_type', '$work_details', 
                '$work_date', '$work_km', '$cost', '$workshop_name', '$workshop_contact', 
                '$invoice_path', '$notes')";
    } else {
        // تحديث الصيانة الموجود
        $sql = "UPDATE `maintenance_work` SET 
                `bus_id` = '$bus_id',
                `maintenance_type` = '$maintenance_type',
                `work_details` = '$work_details',
                `work_date` = '$work_date',
                `work_km` = '$work_km',
                `cost` = '$cost',
                `workshop_name` = '$workshop_name',
                `workshop_contact` = '$workshop_contact',
                `notes` = '$notes'";
        
        if(!empty($invoice_path)){
            $sql .= ", `invoice_path` = '$invoice_path'";
        }
        
        $sql .= " WHERE `id` = '$id'";
    }

    if($conn->query($sql)){
        $_SESSION['success'] = empty($id) ? 'تمت إضافة سجل الصيانة بنجاح' : 'تم تحديث سجل الصيانة بنجاح';
    } else {
        $_SESSION['error'] = 'حدث خطأ في الحفظ: ' . $conn->error;
    }
    
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/maintenance";</script>';
    exit;
}

// معالجة حذف الصيانة
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM `maintenance_work` WHERE `id` = '$id'");
    $_SESSION['success'] = 'تم حذف سجل الصيانة بنجاح';
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/maintenance";</script>';
    exit;
}

// جلب بيانات الصيانة للتعديل
$maintenance_data = array();
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $qry = $conn->query("SELECT * FROM `maintenance_work` WHERE `id` = '$id'");
    $maintenance_data = $qry->fetch_assoc();
}

// جلب بيانات الصيانة للعرض
$view_data = array();
if(isset($_GET['view'])){
    $id = (int)$_GET['view'];
    $qry = $conn->query("SELECT m.*, b.bus_number, b.plate_number FROM `maintenance_work` m JOIN `buses` b ON m.bus_id = b.id WHERE m.id = '$id'");
    if($qry){
        $view_data = $qry->fetch_assoc();
        // تجهيز رابط الفاتورة للعرض إن وجد
        if(!empty($view_data['invoice_path'])){
            $rel = ltrim($view_data['invoice_path'], '/');
            $candidates = [
                [ base_app.$rel, base_url.$rel ],
                [ base_app.'admin/'.$rel, base_url.'admin/'.$rel ],
            ];
            foreach($candidates as $c){
                if(is_file($c[0])){ $view_data['invoice_url'] = $c[1]; break; }
            }
        }
    }
}

// جلب قائمة الباصات
$buses = $conn->query("SELECT `id`, `bus_number`, `plate_number` FROM `buses` WHERE `delete_flag` = 0 ORDER BY `bus_number`");

// معالجة رسائل النجاح/الخطأ
if(isset($_SESSION['success'])){
    echo '<script>alert_toast("'.$_SESSION['success'].'", "success")</script>';
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])){
    echo '<script>alert_toast("'.$_SESSION['error'].'", "error")</script>';
    unset($_SESSION['error']);
}
?>

<style>
    .img-avatar{
        width:45px;
        height:45px;
        object-fit:cover;
        object-position:center center;
        border-radius:100%;
    }
    .card-outline {
        border-top: 3px solid #007bff;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .table th {
        background-color: #f8f9fa;
    }
    .dropdown-menu {
        min-width: 10rem;
    }
    .modal-lg {
        max-width: 800px;
    }
    .document-icon {
        font-size: 24px;
    }
    .pdf-icon { color: #ff0000; }
    .doc-icon { color: #295396; }
    .img-icon { color: #28a745; }
    .badge-scheduled { background-color: #17a2b8; }
    .badge-in_progress { background-color: #ffc107; color: #212529; }
    .badge-completed { background-color: #28a745; }
    .badge-cancelled { background-color: #dc3545; }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">قائمة سجلات الصيانة</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-primary" data-toggle="modal" data-target="#maintenanceModal">
                <span class="fas fa-plus"></span> إضافة جديد
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="list">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="15%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباص</th>
                        <th>نوع الصيانة</th>
                        <th>تاريخ الصيانة</th>
                        <th>الكيلومترات</th>
                        <th>التكلفة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT m.*, b.bus_number, b.plate_number 
                                        FROM `maintenance_work` m 
                                        JOIN `buses` b ON m.bus_id = b.id 
                                        WHERE b.delete_flag = 0 
                                        ORDER BY m.work_date DESC");
                    while($row = $qry->fetch_assoc()):
                        // تحديد أيقونة الفاتورة
                        $file_icon = '';
                        $invoice_url = '';
                        if(!empty($row['invoice_path'])){
                            // محاولة تحديد رابط الفاتورة الصحيح حسب مكان الحفظ
                            $rel = ltrim($row['invoice_path'], '/');
                            $candidates = [
                                [ base_app.$rel, base_url.$rel ],
                                [ base_app.'admin/'.$rel, base_url.'admin/'.$rel ],
                            ];
                            foreach($candidates as $c){
                                if(is_file($c[0])){ $invoice_url = $c[1]; break; }
                            }
                            $ext = pathinfo($row['invoice_path'], PATHINFO_EXTENSION);
                            if($ext == 'pdf'){
                                $file_icon = '<span class="fas fa-file-pdf document-icon pdf-icon" title="PDF"></span>';
                            } elseif(in_array($ext, ['doc', 'docx'])){
                                $file_icon = '<span class="fas fa-file-word document-icon doc-icon" title="Word"></span>';
                            } elseif(in_array($ext, ['jpg', 'jpeg', 'png'])){
                                $file_icon = '<span class="fas fa-file-image document-icon img-icon" title="Image"></span>';
                            } else {
                                $file_icon = '<span class="fas fa-file document-icon" title="File"></span>';
                            }
                        }
                        
                        // تنسيق التكلفة
                        $cost_formatted = number_format($row['cost'], 2) . ' ر.س';
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['bus_number'].' ('.$row['plate_number'].')' ?></td>
                        <td><?php echo $row['maintenance_type'] ?></td>
                        <td><?php echo $row['work_date'] ?></td>
                        <td><?php echo number_format($row['work_km']) ?> كم</td>
                        <td><?php echo $cost_formatted ?></td>
                        <td align="center">
                            <?php echo $file_icon ?>
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                إجراءات
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <?php if(!empty($invoice_url)): ?>
                                <a class="dropdown-item" href="<?php echo $invoice_url ?>" target="_blank">
                                    <span class="fa fa-eye text-dark"></span> عرض الفاتورة
                                </a>
                                <div class="dropdown-divider"></div>
                                <?php endif; ?>
                                <a class="dropdown-item" href="<?php echo base_url.'admin/index.php?page=buses/maintenance&view='.$row['id'] ?>">
                                    <span class="fa fa-eye text-info"></span> عرض التفاصيل
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo base_url.'admin/index.php?page=buses/maintenance&edit='.$row['id'] ?>">
                                    <span class="fa fa-edit text-primary"></span> تعديل
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-trash text-danger"></span> حذف
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- نافذة إضافة/تعديل الصيانة -->
<div class="modal fade" id="maintenanceModal" tabindex="-1" role="dialog" aria-labelledby="maintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceModalLabel">
                    <?php echo isset($maintenance_data['id']) ? 'تعديل سجل الصيانة' : 'إضافة سجل صيانة جديد'; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $maintenance_data['id'] ?? ''; ?>">
                <input type="hidden" name="old_invoice_path" value="<?php echo $maintenance_data['invoice_path'] ?? ''; ?>">
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bus_id" class="control-label">الباص</label>
                                    <select name="bus_id" id="bus_id" class="form-control form-control-sm rounded-0" required>
                                        <option value="">اختر الباص</option>
                                        <?php while($bus = $buses->fetch_assoc()): ?>
                                        <option value="<?php echo $bus['id'] ?>" 
                                            <?php echo isset($maintenance_data['bus_id']) && $maintenance_data['bus_id'] == $bus['id'] ? 'selected' : '' ?>>
                                            <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="maintenance_type" class="control-label">نوع الصيانة</label>
                                    <select name="maintenance_type" id="maintenance_type" class="form-control form-control-sm rounded-0" required>
                                        <option value="">اختر نوع الصيانة</option>
                                        <option value="scheduled" <?php echo isset($maintenance_data['maintenance_type']) && $maintenance_data['maintenance_type'] == 'scheduled' ? 'selected' : '' ?>>مجدولة</option>
                                        <option value="emergency" <?php echo isset($maintenance_data['maintenance_type']) && $maintenance_data['maintenance_type'] == 'emergency' ? 'selected' : '' ?>>طارئة</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="work_details" class="control-label">تفاصيل العمل</label>
                                    <textarea name="work_details" id="work_details" class="form-control form-control-sm rounded-0" rows="3" required><?php echo $maintenance_data['work_details'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="work_date" class="control-label">تاريخ الصيانة</label>
                                    <input type="date" name="work_date" id="work_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $maintenance_data['work_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="work_km" class="control-label">عدد الكيلومترات</label>
                                    <input type="number" name="work_km" id="work_km" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $maintenance_data['work_km'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="cost" class="control-label">التكلفة (ر.س)</label>
                                    <input type="number" step="0.01" name="cost" id="cost" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $maintenance_data['cost'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="workshop_name" class="control-label">اسم الورشة</label>
                                    <input type="text" name="workshop_name" id="workshop_name" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $maintenance_data['workshop_name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="workshop_contact" class="control-label">اتصال الورشة</label>
                                    <input type="text" name="workshop_contact" id="workshop_contact" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $maintenance_data['workshop_contact'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="invoice_path" class="control-label">رفع فاتورة الصيانة</label>
                            <input type="file" name="invoice_path" id="invoice_path" class="form-control form-control-sm rounded-0" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if(isset($maintenance_data['invoice_path']) && !empty($maintenance_data['invoice_path'])): ?>
                                <small class="text-muted">الملف الحالي: <?php echo basename($maintenance_data['invoice_path']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="control-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm rounded-0" rows="2"><?php echo $maintenance_data['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="save_maintenance" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة عرض تفاصيل الصيانة -->
<div class="modal fade" id="maintenanceViewModal" tabindex="-1" role="dialog" aria-labelledby="maintenanceViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceViewModalLabel">تفاصيل سجل الصيانة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <?php if(!empty($view_data)): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>الباص:</strong> <?php echo htmlspecialchars($view_data['bus_number'].' ('.$view_data['plate_number'].')'); ?></p>
                            <p><strong>نوع الصيانة:</strong> <?php echo htmlspecialchars($view_data['maintenance_type']); ?></p>
                            <p><strong>تفاصيل العمل:</strong><br><?php echo nl2br(htmlspecialchars($view_data['work_details'])); ?></p>
                            <p><strong>ملاحظات:</strong><br><?php echo nl2br(htmlspecialchars($view_data['notes'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ الصيانة:</strong> <?php echo htmlspecialchars($view_data['work_date']); ?></p>
                            <p><strong>عدد الكيلومترات:</strong> <?php echo number_format((float)$view_data['work_km']); ?> كم</p>
                            <p><strong>التكلفة:</strong> <?php echo number_format((float)$view_data['cost'], 2).' ر.س'; ?></p>
                            <p><strong>اسم الورشة:</strong> <?php echo htmlspecialchars($view_data['workshop_name']); ?></p>
                            <p><strong>اتصال الورشة:</strong> <?php echo htmlspecialchars($view_data['workshop_contact']); ?></p>
                            <?php if(!empty($view_data['invoice_url'])): ?>
                                <p><strong>الفاتورة:</strong> <a href="<?php echo $view_data['invoice_url']; ?>" target="_blank">عرض الملف</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">لا توجد بيانات لعرضها.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
    </div>

<script>
$(document).ready(function(){
    // تهيئة جدول البيانات
    $('#list').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json'
        }
    });

    // معالجة حذف الصيانة
    $('.delete_data').click(function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف سجل الصيانة هذا؟", "delete_maintenance", [id]);
    });

    // إضافة كلاس للجدول
    $('.table td, .table th').addClass('py-1 px-2 align-middle');

    // إعادة تعبئة النموذج عند فتحه للتعديل
    // إذا كان هناك معرّف للتعديل في الحقل المخفي، افتح المودال تلقائياً لعرض البيانات القديمة
    <?php if(isset($maintenance_data['id']) && !empty($maintenance_data['id'])): ?>
        $('#maintenanceModal').modal('show');
        $('#maintenanceModal .modal-title').text('تعديل سجل الصيانة');
    <?php else: ?>
        // عند فتح المودال بدون وضع التعديل، نظّف الحقول
        $('#maintenanceModal').on('show.bs.modal', function () {
            $('#maintenanceModal form')[0].reset();
            $('#maintenanceModal .modal-title').text('إضافة سجل صيانة جديد');
            $('#maintenanceModal input[name="id"]').val('');
            $('#maintenanceModal input[name="old_invoice_path"]').val('');
        });
    <?php endif; ?>

    // فتح مودال العرض تلقائياً إذا وُجدت بيانات العرض
    <?php if(isset($view_data['id']) && !empty($view_data['id'])): ?>
        $('#maintenanceViewModal').modal('show');
    <?php endif; ?>
});

// دالة حذف الصيانة
function delete_maintenance(id){
    start_loader();
    $.ajax({
        url: '<?php echo base_url?>admin/index.php?page=buses/maintenance&delete='+id,
        method: 'GET',
        success: function(){
            window.location.reload();
        },
        error: function(){
            alert_toast("حدث خطأ أثناء الحذف", "error");
            end_loader();
        }
    });
}

// دالة عرض التنبيه
function alert_toast(msg, type){
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }
    toastr[type](msg);
}

// دالة بدء التحميل
function start_loader(){
    $('body').append('<div class="loader" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; display:flex; justify-content:center; align-items:center;"><img src="<?php echo base_url ?>images/loading.gif" style="width:100px; height:100px;"></div>');
}

// دالة إنهاء التحميل
function end_loader(){
    $('.loader').fadeOut('fast', function(){
        $(this).remove();
    });
}

// دالة تأكيد الإجراء
function _conf(msg, func, params){
    // إنشاء نافذة التأكيد إذا لم تكن موجودة
    if($('#confirm_modal').length == 0){
        $('body').append(`
            <div class="modal fade" id="confirm_modal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">تأكيد</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" id="confirm">موافق</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    $('#confirm_modal #confirm').attr('onclick', func+"("+params.join(',')+")");
    $('#confirm_modal .modal-body').html(msg);
    $('#confirm_modal').modal('show');
}
</script>