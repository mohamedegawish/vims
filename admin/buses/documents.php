<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة حفظ البيانات
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_document'])){
    $id = $_POST['id'] ?? '';
    $bus_id = (int)$_POST['bus_id'];
    $document_type = $conn->real_escape_string($_POST['document_type']);
    $document_number = $conn->real_escape_string($_POST['document_number']);
    $issue_date = $conn->real_escape_string($_POST['issue_date']);
    $expiry_date = $conn->real_escape_string($_POST['expiry_date']);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // معالجة رفع الملف
    $file_path = '';
    if(!empty($_FILES['file_path']['name'])){
        $upload_dir = 'uploads/documents/';
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
        // إضافة مستند جديد
        $sql = "INSERT INTO `bus_documents` (`bus_id`, `document_type`, `document_number`, 
                `issue_date`, `expiry_date`, `file_path`, `notes`) 
                VALUES ('$bus_id', '$document_type', '$document_number', 
                '$issue_date', '$expiry_date', '$file_path', '$notes')";
    } else {
        // تحديث المستند الموجود
        $sql = "UPDATE `bus_documents` SET 
                `bus_id` = '$bus_id',
                `document_type` = '$document_type',
                `document_number` = '$document_number',
                `issue_date` = '$issue_date',
                `expiry_date` = '$expiry_date',
                `notes` = '$notes'";
        
        if(!empty($file_path)){
            $sql .= ", `file_path` = '$file_path'";
        }
        
        $sql .= " WHERE `id` = '$id'";
    }

    if($conn->query($sql)){
        $_SESSION['success'] = empty($id) ? 'تمت إضافة المستند بنجاح' : 'تم تحديث المستند بنجاح';
    } else {
        $_SESSION['error'] = 'حدث خطأ في الحفظ: ' . $conn->error;
    }
    
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/documents";</script>';
    exit;
}

// معالجة حذف المستند
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM `bus_documents` WHERE `id` = '$id'");
    $_SESSION['success'] = 'تم حذف المستند بنجاح';
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/documents";</script>';
    exit;
}

// جلب بيانات المستند للتعديل
$document_data = array();
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $qry = $conn->query("SELECT * FROM `bus_documents` WHERE `id` = '$id'");
    $document_data = $qry->fetch_assoc();
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
    .action-icons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .action-icons a {
        cursor: pointer;
    }
    .preview-modal .modal-body {
        height: 80vh;
    }
    .preview-modal iframe, .preview-modal img {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">قائمة مستندات الباصات</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-primary" data-toggle="modal" data-target="#documentModal">
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
                    <col width="15%">
                    <col width="15%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباص</th>
                        <th>نوع المستند</th>
                        <th>رقم المستند</th>
                        <th>تاريخ الإصدار</th>
                        <th>تاريخ الانتهاء</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT d.*, b.bus_number, b.plate_number 
                                        FROM `bus_documents` d 
                                        JOIN `buses` b ON d.bus_id = b.id 
                                        WHERE b.delete_flag = 0 
                                        ORDER BY d.date_created DESC");
                    while($row = $qry->fetch_assoc()):
                        // تحديد أيقونة الملف
                        $file_icon = '';
                        if(!empty($row['file_path'])){
                            $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
                            if($ext == 'pdf'){
                                $file_icon = 'pdf-icon';
                            } elseif(in_array($ext, ['doc', 'docx'])){
                                $file_icon = 'doc-icon';
                            } elseif(in_array($ext, ['jpg', 'jpeg', 'png'])){
                                $file_icon = 'img-icon';
                            }
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['bus_number'].' ('.$row['plate_number'].')' ?></td>
                        <td><?php echo $row['document_type'] ?></td>
                        <td><?php echo $row['document_number'] ?></td>
                        <td><?php echo $row['issue_date'] ?></td>
                        <td><?php echo $row['expiry_date'] ?></td>
                        <td align="center">
                            <div class="action-icons">
                                <?php if(!empty($row['file_path'])): ?>
                                <a href="#" class="text-primary preview-document" data-file="<?php echo base_url.$row['file_path'] ?>" data-type="<?php echo $ext ?? '' ?>">
                                    <i class="fas fa-eye <?php echo $file_icon ?>"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?edit=<?php echo $row['id'] ?>" data-toggle="modal" data-target="#documentModal" class="text-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0)" class="text-danger delete_data" data-id="<?php echo $row['id'] ?>">
                                    <i class="fas fa-trash"></i>
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

<!-- نافذة إضافة/تعديل المستند -->
<div class="modal fade" id="documentModal" tabindex="-1" role="dialog" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">
                    <?php echo isset($document_data['id']) ? 'تعديل المستند' : 'إضافة مستند جديد'; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $document_data['id'] ?? ''; ?>">
                <input type="hidden" name="old_file_path" value="<?php echo $document_data['file_path'] ?? ''; ?>">
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
                                            <?php echo isset($document_data['bus_id']) && $document_data['bus_id'] == $bus['id'] ? 'selected' : '' ?>>
                                            <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="document_type" class="control-label">نوع المستند</label>
                                    <select name="document_type" id="document_type" class="form-control form-control-sm rounded-0" required>
                                        <option value="">اختر نوع المستند</option>
                                        <option value="license" <?php echo isset($document_data['document_type']) && $document_data['document_type'] == 'license' ? 'selected' : '' ?>>رخصة</option>
                                        <option value="insurance" <?php echo isset($document_data['document_type']) && $document_data['document_type'] == 'insurance' ? 'selected' : '' ?>>تأمين</option>
                                        <option value="inspection" <?php echo isset($document_data['document_type']) && $document_data['document_type'] == 'inspection' ? 'selected' : '' ?>>فحص</option>
                                        <option value="ownership" <?php echo isset($document_data['document_type']) && $document_data['document_type'] == 'ownership' ? 'selected' : '' ?>>ملكية</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="document_number" class="control-label">رقم المستند</label>
                                    <input type="text" name="document_number" id="document_number" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $document_data['document_number'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="issue_date" class="control-label">تاريخ الإصدار</label>
                                    <input type="date" name="issue_date" id="issue_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $document_data['issue_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="expiry_date" class="control-label">تاريخ الانتهاء</label>
                                    <input type="date" name="expiry_date" id="expiry_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $document_data['expiry_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="file_path" class="control-label">رفع الملف</label>
                                    <input type="file" name="file_path" id="file_path" class="form-control form-control-sm rounded-0" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <?php if(isset($document_data['file_path']) && !empty($document_data['file_path'])): ?>
                                        <small class="text-muted">الملف الحالي: <?php echo basename($document_data['file_path']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="control-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm rounded-0" rows="3"><?php echo $document_data['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="save_document" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة معاينة المستند -->
<div class="modal fade preview-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة المستند</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- سيتم ملؤه بالجافاسكريبت -->
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
    $('#list').dataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json"
        }
    });

    // معالجة حذف المستند
    $('.delete_data').click(function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف هذا المستند؟", "delete_document", [id]);
    });

    // إضافة كلاس للجدول
    $('.table td, .table th').addClass('py-1 px-2 align-middle');

    // إعادة تعبئة النموذج عند فتحه للتعديل
    $('#documentModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var isEdit = button.attr('href') && button.attr('href').includes('edit=');
        
        if(!isEdit){
            // إعادة تعيين النموذج للإضافة
            $('#documentModal form')[0].reset();
            $('#documentModal .modal-title').text('إضافة مستند جديد');
            $('#documentModal input[name="id"]').val('');
            $('#documentModal input[name="old_file_path"]').val('');
        }
    });

    // معاينة المستند
    $('.preview-document').click(function(e){
        e.preventDefault();
        var fileUrl = $(this).data('file');
        var fileType = $(this).data('type');
        var modalBody = $('#previewModal .modal-body');
        
        modalBody.empty();
        
        if(fileType === 'pdf'){
            modalBody.html('<iframe src="'+fileUrl+'"></iframe>');
        } else if(['jpg', 'jpeg', 'png'].includes(fileType)){
            modalBody.html('<img src="'+fileUrl+'" alt="Document Preview">');
        } else {
            // للملفات الأخرى مثل Word، نعرض رابط للتحميل
            modalBody.html('<div class="text-center p-5">'+
                '<p class="lead">لا يمكن معاينة هذا النوع من الملفات مباشرة</p>'+
                '<a href="'+fileUrl+'" class="btn btn-primary" download>تحميل الملف</a>'+
                '</div>');
        }
        
        $('#previewModal').modal('show');
    });
});

// دالة حذف المستند
function delete_document(id){
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