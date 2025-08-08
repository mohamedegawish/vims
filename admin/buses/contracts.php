<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة حفظ البيانات
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_contract'])){
    $id = $_POST['id'] ?? '';
    $bus_id = (int)$_POST['bus_id'];
    $contract_type = $conn->real_escape_string($_POST['contract_type']);
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $client_contact = $conn->real_escape_string($_POST['client_contact']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $route_details = $conn->real_escape_string($_POST['route_details']);
    $payment_terms = $conn->real_escape_string($_POST['payment_terms']);
    $amount = (float)$_POST['amount'];
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // معالجة رفع الملف
    $file_path = '';
    if(!empty($_FILES['file_path']['name'])){
        $upload_dir = 'uploads/contracts/';
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['file_path']['name'];
        $file_tmp = $_FILES['file_path']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
        
        if(in_array($file_ext, $allowed_ext)){
            $new_file_name = uniqid().'.'.$file_ext;
            move_uploaded_file($file_tmp, $upload_dir.$new_file_name);
            $file_path = $upload_dir.$new_file_name;
        }
    } elseif(!empty($_POST['old_file_path'])){
        $file_path = $_POST['old_file_path'];
    }

    if(empty($id)){
        // إضافة عقد جديد
        $sql = "INSERT INTO `contracts` (`bus_id`, `contract_type`, `client_name`, 
                `client_contact`, `start_date`, `end_date`, `route_details`, 
                `payment_terms`, `amount`, `file_path`, `notes`) 
                VALUES ('$bus_id', '$contract_type', '$client_name', 
                '$client_contact', '$start_date', '$end_date', '$route_details', 
                '$payment_terms', '$amount', '$file_path', '$notes')";
    } else {
        // تحديث العقد الموجود
        $sql = "UPDATE `contracts` SET 
                `bus_id` = '$bus_id',
                `contract_type` = '$contract_type',
                `client_name` = '$client_name',
                `client_contact` = '$client_contact',
                `start_date` = '$start_date',
                `end_date` = '$end_date',
                `route_details` = '$route_details',
                `payment_terms` = '$payment_terms',
                `amount` = '$amount',
                `notes` = '$notes'";
        
        if(!empty($file_path)){
            $sql .= ", `file_path` = '$file_path'";
        }
        
        $sql .= " WHERE `id` = '$id'";
    }

    if($conn->query($sql)){
        $_SESSION['success'] = empty($id) ? 'تمت إضافة العقد بنجاح' : 'تم تحديث العقد بنجاح';
    } else {
        $_SESSION['error'] = 'حدث خطأ في الحفظ: ' . $conn->error;
    }
    
    echo '<script>window.location.href = "'.$_SERVER['PHP_SELF'].'";</script>';
    exit;
}

// معالجة حذف العقد
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM `contracts` WHERE `id` = '$id'");
    $_SESSION['success'] = 'تم حذف العقد بنجاح';
    echo '<script>window.location.href = "'.$_SERVER['PHP_SELF'].'";</script>';
    exit;
}

// جلب بيانات العقد للتعديل
$contract_data = array();
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $qry = $conn->query("SELECT * FROM `contracts` WHERE `id` = '$id'");
    $contract_data = $qry->fetch_assoc();
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
    .status-active { color: #28a745; }
    .status-completed { color: #6c757d; }
    .status-cancelled { color: #dc3545; }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">قائمة عقود الباصات</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-primary" data-toggle="modal" data-target="#contractModal">
                <span class="fas fa-plus"></span> إضافة جديد
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="list">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="15%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباص</th>
                        <th>نوع العقد</th>
                        <th>العميل</th>
                        <th>تاريخ البدء</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT c.*, b.bus_number, b.plate_number 
                                        FROM `contracts` c 
                                        JOIN `buses` b ON c.bus_id = b.id 
                                        WHERE b.delete_flag = 0 
                                        ORDER BY c.date_created DESC");
                    while($row = $qry->fetch_assoc()):
                        // تحديد أيقونة الملف
                        $file_icon = '';
                        if(!empty($row['file_path'])){
                            $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
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
                        
                        // تحديد حالة العقد
                        $status = '';
                        $current_date = date('Y-m-d');
                        if($row['end_date'] < $current_date){
                            $status = '<span class="status-completed">منتهي</span>';
                        } elseif($row['start_date'] > $current_date){
                            $status = '<span class="status-active">قادم</span>';
                        } else {
                            $status = '<span class="status-active">نشط</span>';
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['bus_number'].' ('.$row['plate_number'].')' ?></td>
                        <td><?php echo $row['contract_type'] ?></td>
                        <td><?php echo $row['client_name'] ?></td>
                        <td><?php echo $row['start_date'] ?></td>
                        <td><?php echo $row['end_date'] ?></td>
                        <td><?php echo $status ?></td>
                        <td align="center">
                            <?php echo $file_icon ?>
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                إجراءات
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <?php if(!empty($row['file_path'])): ?>
                                <a class="dropdown-item" href="<?php echo base_url.$row['file_path'] ?>" target="_blank">
                                    <span class="fa fa-eye text-dark"></span> عرض الملف
                                </a>
                                <div class="dropdown-divider"></div>
                                <?php endif; ?>
                                <a class="dropdown-item" href="?edit=<?php echo $row['id'] ?>" data-toggle="modal" data-target="#contractModal">
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

<!-- نافذة إضافة/تعديل العقد -->
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-labelledby="contractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contractModalLabel">
                    <?php echo isset($contract_data['id']) ? 'تعديل العقد' : 'إضافة عقد جديد'; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $contract_data['id'] ?? ''; ?>">
                <input type="hidden" name="old_file_path" value="<?php echo $contract_data['file_path'] ?? ''; ?>">
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
                                            <?php echo isset($contract_data['bus_id']) && $contract_data['bus_id'] == $bus['id'] ? 'selected' : '' ?>>
                                            <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="contract_type" class="control-label">نوع العقد</label>
                                    <select name="contract_type" id="contract_type" class="form-control form-control-sm rounded-0" required>
                                        <option value="">اختر نوع العقد</option>
                                        <option value="university" <?php echo isset($contract_data['contract_type']) && $contract_data['contract_type'] == 'university' ? 'selected' : '' ?>>جامعة</option>
                                        <option value="school" <?php echo isset($contract_data['contract_type']) && $contract_data['contract_type'] == 'school' ? 'selected' : '' ?>>مدرسة</option>
                                        <option value="company" <?php echo isset($contract_data['contract_type']) && $contract_data['contract_type'] == 'company' ? 'selected' : '' ?>>شركة</option>
                                        <option value="wedding" <?php echo isset($contract_data['contract_type']) && $contract_data['contract_type'] == 'wedding' ? 'selected' : '' ?>>زفاف</option>
                                        <option value="other" <?php echo isset($contract_data['contract_type']) && $contract_data['contract_type'] == 'other' ? 'selected' : '' ?>>أخرى</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="client_name" class="control-label">اسم العميل</label>
                                    <input type="text" name="client_name" id="client_name" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $contract_data['client_name'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="client_contact" class="control-label">اتصال العميل</label>
                                    <input type="text" name="client_contact" id="client_contact" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $contract_data['client_contact'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date" class="control-label">تاريخ البدء</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $contract_data['start_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date" class="control-label">تاريخ الانتهاء</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $contract_data['end_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="amount" class="control-label">المبلغ</label>
                                    <input type="number" step="0.01" name="amount" id="amount" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $contract_data['amount'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="file_path" class="control-label">رفع الملف</label>
                                    <input type="file" name="file_path" id="file_path" class="form-control form-control-sm rounded-0" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <?php if(isset($contract_data['file_path']) && !empty($contract_data['file_path'])): ?>
                                        <small class="text-muted">الملف الحالي: <?php echo basename($contract_data['file_path']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="route_details" class="control-label">تفاصيل المسار</label>
                                    <textarea name="route_details" id="route_details" class="form-control form-control-sm rounded-0" rows="2" required><?php echo $contract_data['route_details'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="payment_terms" class="control-label">شروط الدفع</label>
                                    <textarea name="payment_terms" id="payment_terms" class="form-control form-control-sm rounded-0" rows="2" required><?php echo $contract_data['payment_terms'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="control-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm rounded-0" rows="3"><?php echo $contract_data['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="save_contract" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // تهيئة جدول البيانات
    $('#list').dataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json"
        }
    });

    // معالجة حذف العقد
    $('.delete_data').click(function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف هذا العقد؟", "delete_contract", [id]);
    });

    // إضافة كلاس للجدول
    $('.table td, .table th').addClass('py-1 px-2 align-middle');

    // إعادة تعبئة النموذج عند فتحه للتعديل
    $('#contractModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var isEdit = button.attr('href') && button.attr('href').includes('edit=');
        
        if(!isEdit){
            // إعادة تعيين النموذج للإضافة
            $('#contractModal form')[0].reset();
            $('#contractModal .modal-title').text('إضافة عقد جديد');
            $('#contractModal input[name="id"]').val('');
            $('#contractModal input[name="old_file_path"]').val('');
        }
    });
});

// دالة حذف العقد
function delete_contract(id){
    start_loader();
    $.ajax({
        url: '?delete='+id,
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