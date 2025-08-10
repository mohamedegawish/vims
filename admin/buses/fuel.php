<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة حفظ البيانات
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_fuel'])){
    $id = $_POST['id'] ?? '';
    $bus_id = (int)$_POST['bus_id'];
    $fuel_type = $conn->real_escape_string($_POST['fuel_type']);
    $station_name = $conn->real_escape_string($_POST['station_name'] ?? '');
    $quantity = (float)$_POST['quantity'];
    $price_per_unit = (float)$_POST['price_per_unit'];
    $total_cost = $quantity * $price_per_unit;
    $fuel_date = $conn->real_escape_string($_POST['fuel_date']);
    $driver_name = $conn->real_escape_string($_POST['driver_name'] ?? '');
    $current_km = (int)$_POST['current_km'] ?? 0;
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    // معالجة رفع الفاتورة
    $receipt_path = '';
    if(!empty($_FILES['receipt_path']['name'])){
        $upload_dir = 'uploads/fuel_receipts/';
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['receipt_path']['name'];
        $file_tmp = $_FILES['receipt_path']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('pdf', 'jpg', 'jpeg', 'png');
        
        if(in_array($file_ext, $allowed_ext)){
            $new_file_name = uniqid().'.'.$file_ext;
            move_uploaded_file($file_tmp, $upload_dir.$new_file_name);
            $receipt_path = $upload_dir.$new_file_name;
        }
    } elseif(!empty($_POST['old_receipt_path'])){
        $receipt_path = $_POST['old_receipt_path'];
    }

    if(empty($id)){
        // إضافة سجل وقود جديد
        $sql = "INSERT INTO `fuel_records` (`bus_id`, `fuel_type`, `station_name`, `quantity`, 
                `price_per_unit`, `total_cost`, `fuel_date`, `receipt_path`, `driver_name`, 
                `current_km`, `notes`) 
                VALUES ('$bus_id', '$fuel_type', '$station_name', '$quantity', 
                '$price_per_unit', '$total_cost', '$fuel_date', '$receipt_path', '$driver_name', 
                '$current_km', '$notes')";
    } else {
        // تحديث سجل الوقود الموجود
        $sql = "UPDATE `fuel_records` SET 
                `bus_id` = '$bus_id',
                `fuel_type` = '$fuel_type',
                `station_name` = '$station_name',
                `quantity` = '$quantity',
                `price_per_unit` = '$price_per_unit',
                `total_cost` = '$total_cost',
                `fuel_date` = '$fuel_date',
                `driver_name` = '$driver_name',
                `current_km` = '$current_km',
                `notes` = '$notes'";
        
        if(!empty($receipt_path)){
            $sql .= ", `receipt_path` = '$receipt_path'";
        }
        
        $sql .= " WHERE `id` = '$id'";
    }

    if($conn->query($sql)){
        $_SESSION['success'] = empty($id) ? 'تمت إضافة سجل الوقود بنجاح' : 'تم تحديث سجل الوقود بنجاح';
    } else {
        $_SESSION['error'] = 'حدث خطأ في الحفظ: ' . $conn->error;
    }
    
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/fuel";</script>';
    exit;
}

// معالجة حذف سجل الوقود
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM `fuel_records` WHERE `id` = '$id'");
    $_SESSION['success'] = 'تم حذف سجل الوقود بنجاح';
    echo '<script>window.location.href = "'.base_url.'admin/index.php?page=buses/fuel";</script>';
    exit;
}

// جلب بيانات سجل الوقود للتعديل
$fuel_data = array();
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $qry = $conn->query("SELECT * FROM `fuel_records` WHERE `id` = '$id'");
    $fuel_data = $qry->fetch_assoc();
}

// جلب قائمة الباصات
$buses = $conn->query("SELECT `id`, `bus_number`, `plate_number` FROM `buses` WHERE `delete_flag` = 0 ORDER BY `bus_number`");

// جلب قائمة السائقين
$drivers = $conn->query("SELECT `id`, CONCAT(`firstname`, ' ', `lastname`) as name FROM `drivers` WHERE `status` = 1 ORDER BY `name`");

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
    .receipt-icon {
        font-size: 24px;
        color: #28a745;
        cursor: pointer;
    }
    .badge-fuel {
        background-color: #6c757d;
        color: white;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">سجلات تعبئة الوقود</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-primary" data-toggle="modal" data-target="#fuelModal">
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
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباص</th>
                        <th>نوع التعبئة</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                        <th>التاريخ</th>
                        <th>السائق</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT f.*, b.bus_number, b.plate_number 
                                        FROM `fuel_records` f 
                                        JOIN `buses` b ON f.bus_id = b.id 
                                        WHERE b.delete_flag = 0 
                                        ORDER BY f.fuel_date DESC");
                    while($row = $qry->fetch_assoc()):
                        $fuel_type_badge = ($row['fuel_type'] == 'station') ? 
                            '<span class="badge badge-primary">محطة وقود</span>' : 
                            '<span class="badge badge-fuel">نقدي</span>';
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['bus_number'].' ('.$row['plate_number'].')' ?></td>
                        <td><?php echo $fuel_type_badge ?></td>
                        <td><?php echo $row['quantity'].' لتر' ?></td>
                        <td><?php echo number_format($row['price_per_unit'], 2).' ر.س' ?></td>
                        <td><?php echo number_format($row['total_cost'], 2).' ر.س' ?></td>
                        <td><?php echo $row['fuel_date'] ?></td>
                        <td><?php echo $row['driver_name'] ?></td>
                        <td align="center">
                            <?php if(!empty($row['receipt_path'])): ?>
                                <?php 
                                    $receipt_url = '';
                                    $rel = ltrim($row['receipt_path'], '/');
                                    $candidates = [
                                        [ base_app.$rel, base_url.$rel ],
                                        [ base_app.'admin/'.$rel, base_url.'admin/'.$rel ],
                                        [ base_app.'admin/buses/'.$rel, base_url.'admin/buses/'.$rel ],
                                    ];
                                    foreach($candidates as $c){
                                        if(is_file($c[0])){ $receipt_url = $c[1]; break; }
                                    }
                                ?>
                                <?php if(!empty($receipt_url)): ?>
                                <a href="<?php echo $receipt_url ?>" target="_blank" class="mr-2">
                                    <span class="fas fa-receipt receipt-icon" title="عرض الفاتورة"></span>
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                إجراءات
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="<?php echo base_url.'admin/index.php?page=buses/fuel&edit='.$row['id'] ?>">
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

<!-- نافذة إضافة/تعديل سجل الوقود -->
<div class="modal fade" id="fuelModal" tabindex="-1" role="dialog" aria-labelledby="fuelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fuelModalLabel">
                    <?php echo isset($fuel_data['id']) ? 'تعديل سجل الوقود' : 'إضافة سجل وقود جديد'; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $fuel_data['id'] ?? ''; ?>">
                <input type="hidden" name="old_receipt_path" value="<?php echo $fuel_data['receipt_path'] ?? ''; ?>">
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
                                            <?php echo isset($fuel_data['bus_id']) && $fuel_data['bus_id'] == $bus['id'] ? 'selected' : '' ?>>
                                            <?php echo $bus['bus_number'].' ('.$bus['plate_number'].')' ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="fuel_type" class="control-label">نوع التعبئة</label>
                                    <select name="fuel_type" id="fuel_type" class="form-control form-control-sm rounded-0" required>
                                        <option value="station" <?php echo isset($fuel_data['fuel_type']) && $fuel_data['fuel_type'] == 'station' ? 'selected' : '' ?>>محطة وقود</option>
                                        <option value="cash" <?php echo isset($fuel_data['fuel_type']) && $fuel_data['fuel_type'] == 'cash' ? 'selected' : '' ?>>نقدي</option>
                                    </select>
                                </div>
                                <div class="form-group" id="stationNameGroup">
                                    <label for="station_name" class="control-label">اسم المحطة</label>
                                    <input type="text" name="station_name" id="station_name" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $fuel_data['station_name'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="quantity" class="control-label">الكمية (لتر)</label>
                                    <input type="number" step="0.01" name="quantity" id="quantity" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $fuel_data['quantity'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_per_unit" class="control-label">السعر للتر (ج.م)</label>
                                    <input type="number" step="0.01" name="price_per_unit" id="price_per_unit" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $fuel_data['price_per_unit'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="fuel_date" class="control-label">تاريخ التعبئة</label>
                                    <input type="date" name="fuel_date" id="fuel_date" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $fuel_data['fuel_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="driver_name" class="control-label">اسم السائق</label>
                                    <select name="driver_name" id="driver_name" class="form-control form-control-sm rounded-0">
                                        <option value="">اختر السائق</option>
                                        <?php while($driver = $drivers->fetch_assoc()): ?>
                                        <option value="<?php echo $driver['name'] ?>" 
                                            <?php echo isset($fuel_data['driver_name']) && $fuel_data['driver_name'] == $driver['name'] ? 'selected' : '' ?>>
                                            <?php echo $driver['name'] ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="current_km" class="control-label">عدد الكيلومترات</label>
                                    <input type="number" name="current_km" id="current_km" class="form-control form-control-sm rounded-0" 
                                           value="<?php echo $fuel_data['current_km'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="receipt_path" class="control-label">رفع فاتورة الوقود</label>
                                    <input type="file" name="receipt_path" id="receipt_path" class="form-control form-control-sm rounded-0" accept=".pdf,.jpg,.jpeg,.png">
                                    <?php if(isset($fuel_data['receipt_path']) && !empty($fuel_data['receipt_path'])): ?>
                                        <small class="text-muted">الملف الحالي: <?php echo basename($fuel_data['receipt_path']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="control-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm rounded-0" rows="3"><?php echo $fuel_data['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="save_fuel" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // تهيئة جدول البيانات مع نمط responsive
    $('#list').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json'
        }
    });

    // معالجة حذف سجل الوقود
    $('.delete_data').click(function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف هذا السجل؟", "delete_fuel", [id]);
    });

    // إضافة كلاس للجدول
    $('.table td, .table th').addClass('py-1 px-2 align-middle');

    // إعادة تعبئة النموذج عند فتحه للتعديل
    $('#fuelModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var isEditParam = new URLSearchParams(window.location.search).has('edit');
        var isEditTrigger = button && button.attr('href') && button.attr('href').includes('edit=');
        var isEdit = isEditParam || isEditTrigger;

        if(!isEdit){
            // إعادة تعيين النموذج للإضافة
            $('#fuelModal form')[0].reset();
            $('#fuelModal .modal-title').text('إضافة سجل وقود جديد');
            $('#fuelModal input[name="id"]').val('');
            $('#fuelModal input[name="old_receipt_path"]').val('');
            $('#fuelModal #fuel_date').val(new Date().toISOString().split('T')[0]);
        } else {
            $('#fuelModal .modal-title').text('تعديل سجل الوقود');
        }
    });

    // فتح نافذة التعديل تلقائياً عند وجود باراميتر ?edit=
    <?php if(isset($fuel_data['id']) && !empty($fuel_data['id'])): ?>
        $('#fuelModal').modal('show');
    <?php endif; ?>

    // إظهار/إخفاء حقل اسم المحطة حسب نوع التعبئة
    $('#fuel_type').change(function(){
        if($(this).val() == 'station'){
            $('#stationNameGroup').show();
            $('#station_name').attr('required', true);
        } else {
            $('#stationNameGroup').hide();
            $('#station_name').removeAttr('required');
        }
    }).trigger('change');

    // حساب الإجمالي تلقائياً
    $('#quantity, #price_per_unit').keyup(function(){
        var quantity = parseFloat($('#quantity').val()) || 0;
        var price = parseFloat($('#price_per_unit').val()) || 0;
        var total = quantity * price;
        $('#total_cost').val(total.toFixed(2));
    });
});

// دالة حذف سجل الوقود
function delete_fuel(id){
    start_loader();
    $.ajax({
        url: '<?php echo base_url?>admin/index.php?page=buses/fuel&delete='+id,
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