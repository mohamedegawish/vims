<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة حفظ البيانات
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_status'])){
    $id = $_POST['id'] ?? '';
    $bus_id = (int)$_POST['bus_id'];
    $status_date = $conn->real_escape_string($_POST['status_date']);
    $status = $conn->real_escape_string($_POST['status']);
    $current_km = (int)$_POST['current_km'];
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // التحقق من عدم تكرار تسجيل لنفس الباص في نفس اليوم
    $check_query = $conn->query("SELECT id FROM `bus_status` WHERE bus_id = '$bus_id' AND status_date = '$status_date' AND id != '$id'");
    if($check_query->num_rows > 0){
        $_SESSION['error'] = 'تم تسجيل حالة لهذا الباص لهذا اليوم بالفعل';
        echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/daily_status";</script>';
        exit;
    }

    if(empty($id)){
        // إضافة حالة جديدة
        $sql = "INSERT INTO `bus_status` (`bus_id`, `status_date`, `status`, `current_km`, `notes`) 
                VALUES ('$bus_id', '$status_date', '$status', '$current_km', '$notes')";
    } else {
        // تحديث الحالة الموجود
        $sql = "UPDATE `bus_status` SET 
                `bus_id` = '$bus_id',
                `status_date` = '$status_date',
                `status` = '$status',
                `current_km` = '$current_km',
                `notes` = '$notes'
                WHERE `id` = '$id'";
    }

    if($conn->query($sql)){
        $_SESSION['success'] = empty($id) ? 'تمت إضافة الحالة اليومية بنجاح' : 'تم تحديث الحالة اليومية بنجاح';
        
        // تحديث حالة الباص في جدول الباصات
        $update_bus_sql = "UPDATE `buses` SET `status` = '$status' WHERE `id` = '$bus_id'";
        $conn->query($update_bus_sql);
    } else {
        $_SESSION['error'] = 'حدث خطأ في الحفظ: ' . $conn->error;
    }
    
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/daily_status";</script>';
    exit;
}

// معالجة حذف الحالة
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM `bus_status` WHERE `id` = '$id'");
    $_SESSION['success'] = 'تم حذف الحالة اليومية بنجاح';
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/daily_status";</script>';
    exit;
}

// جلب بيانات الحالة للتعديل
$status_data = array();
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $qry = $conn->query("SELECT * FROM `bus_status` WHERE `id` = '$id'");
    $status_data = $qry->fetch_assoc();
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
    .badge-available { background-color: #28a745; }
    .badge-reserved { background-color: #17a2b8; }
    .badge-maintenance { background-color: #ffc107; color: #212529; }
    .badge-out_of_service { background-color: #dc3545; }
    .km-indicator {
        font-weight: bold;
        color: #343a40;
    }
    .status-badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
    .calendar-icon {
        cursor: pointer;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">الحالة اليومية للباصات</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-primary" data-toggle="modal" data-target="#statusModal">
                <span class="fas fa-plus"></span> تسجيل حالة جديدة
            </button>
            <button type="button" class="btn btn-flat btn-success ml-2" onclick="generateStatusReport()">
                <span class="fas fa-file-pdf"></span> تقرير PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_date">فلترة حسب التاريخ</label>
                        <div class="input-group">
                            <input type="date" id="filter_date" class="form-control form-control-sm">
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-primary" onclick="filterByDate()">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="resetFilter()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_bus">فلترة حسب الباص</label>
                        <select id="filter_bus" class="form-control form-control-sm">
                            <option value="">كل الباصات</option>
                            <?php while($bus = $buses->fetch_assoc()): ?>
                            <option value="<?php echo $bus['id'] ?>">
                                <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_status">فلترة حسب الحالة</label>
                        <select id="filter_status" class="form-control form-control-sm">
                            <option value="">كل الحالات</option>
                            <option value="available">متاح</option>
                            <option value="reserved">محجوز</option>
                            <option value="maintenance">صيانة</option>
                            <option value="out_of_service">خارج الخدمة</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <table class="table table-hover table-striped table-bordered" id="statusTable">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="15%">
                    <col width="25%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباص</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>الكيلومترات</th>
                        <th>آخر تحديث</th>
                        <th>ملاحظات</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT s.*, b.bus_number, b.plate_number 
                                        FROM `bus_status` s 
                                        JOIN `buses` b ON s.bus_id = b.id 
                                        WHERE b.delete_flag = 0 
                                        ORDER BY s.status_date DESC, s.date_updated DESC");
                    while($row = $qry->fetch_assoc()):
                        $status_badge = '';
                        switch($row['status']){
                            case 'available':
                                $status_badge = '<span class="badge badge-available status-badge">متاح</span>';
                                break;
                            case 'reserved':
                                $status_badge = '<span class="badge badge-reserved status-badge">محجوز</span>';
                                break;
                            case 'maintenance':
                                $status_badge = '<span class="badge badge-maintenance status-badge">صيانة</span>';
                                break;
                            case 'out_of_service':
                                $status_badge = '<span class="badge badge-out_of_service status-badge">خارج الخدمة</span>';
                                break;
                        }
                        
                        $last_update = date('Y-m-d H:i', strtotime($row['date_updated'] ?? $row['date_created']));
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['bus_number'].' ('.$row['plate_number'].')' ?></td>
                        <td><?php echo $row['status_date'] ?></td>
                        <td><?php echo $status_badge ?></td>
                        <td class="km-indicator"><?php echo number_format($row['current_km']) ?> كم</td>
                        <td><?php echo $last_update ?></td>
                        <td><?php echo nl2br($row['notes']) ?></td>
                        <td align="center">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                إجراءات
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="<?php echo base_url.'admin/index.php?page=buses/daily_status&edit='.$row['id'] ?>">
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

<!-- نافذة إضافة/تعديل الحالة -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">
                    <?php echo isset($status_data['id']) ? 'تعديل الحالة اليومية' : 'تسجيل حالة جديدة'; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo $status_data['id'] ?? ''; ?>">
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bus_id" class="control-label">الباص</label>
                                    <select name="bus_id" id="bus_id" class="form-control form-control-sm rounded-0" required>
                                        <option value="">اختر الباص</option>
                                        <?php 
                                        $buses->data_seek(0); // إعادة تعيين مؤشر مجموعة النتائج
                                        while($bus = $buses->fetch_assoc()): ?>
                                        <option value="<?php echo $bus['id'] ?>" 
                                            <?php echo isset($status_data['bus_id']) && $status_data['bus_id'] == $bus['id'] ? 'selected' : '' ?>>
                                            <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="status_date" class="control-label">تاريخ الحالة</label>
                                    <div class="input-group">
                                        <input type="date" name="status_date" id="status_date" class="form-control form-control-sm rounded-0" 
                                               value="<?php echo $status_data['status_date'] ?? date('Y-m-d'); ?>" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text calendar-icon" onclick="$('#status_date').focus()">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="control-label">الحالة</label>
                                    <select name="status" id="status" class="form-control form-control-sm rounded-0" required>
                                        <option value="available" <?php echo isset($status_data['status']) && $status_data['status'] == 'available' ? 'selected' : '' ?>>متاح</option>
                                        <option value="reserved" <?php echo isset($status_data['status']) && $status_data['status'] == 'reserved' ? 'selected' : '' ?>>محجوز</option>
                                        <option value="maintenance" <?php echo isset($status_data['status']) && $status_data['status'] == 'maintenance' ? 'selected' : '' ?>>صيانة</option>
                                        <option value="out_of_service" <?php echo isset($status_data['status']) && $status_data['status'] == 'out_of_service' ? 'selected' : '' ?>>خارج الخدمة</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="current_km" class="control-label">عداد الكيلومترات</label>
                                    <input type="number" name="current_km" id="current_km" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $status_data['current_km'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="control-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm rounded-0" rows="3"><?php echo $status_data['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="save_status" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // تهيئة جدول البيانات مع إمكانية الفرز والبحث
    $('#statusTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json"
        },
        "order": [[2, "desc"]], // الترتيب حسب التاريخ تنازلياً
        "responsive": true,
        "dom": '<"top"lf>rt<"bottom"ip>'
    });

    // معالجة حذف الحالة
    $('.delete_data').click(function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف هذه الحالة اليومية؟", "delete_status", [id]);
    });

    // إعادة تعبئة النموذج عند فتحه للتعديل
    $('#statusModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var isEditParam = new URLSearchParams(window.location.search).has('edit');
        var hasHiddenId = ($('#statusModal input[name="id"]').val() || '').toString().trim() !== '';
        var isEditTrigger = button && button.attr('href') && button.attr('href').includes('edit=');
        var isEdit = isEditParam || hasHiddenId || isEditTrigger;

        if(!isEdit){
            // إعادة تعيين النموذج للإضافة
            $('#statusModal form')[0].reset();
            $('#statusModal .modal-title').text('تسجيل حالة جديدة');
            $('#statusModal input[name="id"]').val('');
            $('#statusModal #status_date').val(new Date().toISOString().split('T')[0]);
        } else {
            $('#statusModal .modal-title').text('تعديل الحالة اليومية');
        }
    });

    // فتح نافذة التعديل تلقائياً عند وجود باراميتر ?edit=
    <?php if(isset($status_data['id']) && !empty($status_data['id'])): ?>
        $('#statusModal').modal('show');
        $('#statusModal .modal-title').text('تعديل الحالة اليومية');
    <?php endif; ?>
});

// دالة حذف الحالة
function delete_status(id){
    start_loader();
    $.ajax({
        url: '<?php echo base_url?>admin/index.php?page=buses/daily_status&delete='+id,
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

// فلترة حسب التاريخ
function filterByDate(){
    var date = $('#filter_date').val();
    var bus = $('#filter_bus').val();
    var status = $('#filter_status').val();
    
    var url = '?';
    if(date) url += 'date=' + date + '&';
    if(bus) url += 'bus=' + bus + '&';
    if(status) url += 'status=' + status;
    
    window.location.href = url;
}

// إعادة تعيين الفلتر
function resetFilter(){
    window.location.href = '?';
}

// إنشاء تقرير PDF
function generateStatusReport(){
    var date = $('#filter_date').val() || 'all';
    var bus = $('#filter_bus').val() || 'all';
    var status = $('#filter_status').val() || 'all';
    
    start_loader();
    window.open('generate_report.php?type=status&date='+date+'&bus='+bus+'&status='+status, '_blank');
    end_loader();
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