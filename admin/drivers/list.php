<?php
ob_start();

// التحقق من الصلاحيات
if($_settings->userdata('type') != 1){
    echo '<script>alert("ليس لديك صلاحية الوصول لهذه الصفحة");location.replace("./")</script>';
    exit;
}

// اتصال بقاعدة البيانات (افتراضي)
$conn = $_settings->conn;

// معالجة طلبات AJAX
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
    try {
        $action = $_POST['action'];
        $response = array('status' => 'error', 'msg' => '');
        
        switch($action){
            case 'save_driver':
                // التحقق من الحقول المطلوبة
                $required = ['firstname', 'lastname', 'gender', 'contact', 'status'];
                foreach($required as $field){
                    if(empty($_POST[$field])) throw new Exception("الرجاء إدخال جميع الحقول المطلوبة");
                }
                
                // إعداد البيانات
                $data = array(
                    'driver_type_id' => $_POST['driver_type_id'] ?? null,
                    'firstname' => $_POST['firstname'],
                    'middlename' => $_POST['middlename'] ?? null,
                    'lastname' => $_POST['lastname'],
                    'gender' => $_POST['gender'],
                    'dob' => !empty($_POST['dob']) ? $_POST['dob'] : null,
                    'national_id' => $_POST['national_id'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'contact' => $_POST['contact'],
                    'license_number' => $_POST['license_number'] ?? null,
                    'license_expiry' => !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
                    'license_type' => $_POST['license_type'] ?? null,
                    'employment_date' => !empty($_POST['employment_date']) ? $_POST['employment_date'] : null,
                    'salary' => !empty($_POST['salary']) ? $_POST['salary'] : null,
                    'salary_type' => $_POST['salary_type'] ?? 'monthly',
                    'status' => $_POST['status'],
                    'notes' => $_POST['notes'] ?? null
                );
                
                // معالجة صورة السائق
                if(!empty($_FILES['image']['name'])){
                    $image = uploadDriverImage($_FILES['image'], 'drivers');
                    if($image['status'] == 'success'){
                        $data['image_path'] = $image['path'];
                    } else {
                        throw new Exception($image['message']);
                    }
                }
                
                // معالجة صورة الرخصة
                if(!empty($_FILES['license_image']['name'])){
                    $license = uploadDriverImage($_FILES['license_image'], 'licenses');
                    if($license['status'] == 'success'){
                        $data['license_image'] = $license['path'];
                    } else {
                        throw new Exception($license['message']);
                    }
                }
                
                // بناء استعلام الإدراج أو التحديث
                if(empty($_POST['id'])){
                    // إدراج جديد
                    $fields = implode(", ", array_keys($data));
                    $placeholders = implode(", ", array_fill(0, count($data), '?'));
                    $values = array_values($data);
                    
                    $sql = "INSERT INTO drivers ($fields) VALUES ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $types = str_repeat('s', count($data));
                    $stmt->bind_param($types, ...$values);
                } else {
                    // تحديث موجود
                    $id = $_POST['id'];
                    $set = array();
                    foreach($data as $field => $value){
                        $set[] = "$field = ?";
                    }
                    $values = array_values($data);
                    $values[] = $id;
                    
                    $sql = "UPDATE drivers SET ".implode(", ", $set)." WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $types = str_repeat('s', count($data)).'i';
                    $stmt->bind_param($types, ...$values);
                }
                
                if(!$stmt->execute()) throw new Exception("حدث خطأ في قاعدة البيانات: ".$stmt->error);
                
                $driver_id = empty($_POST['id']) ? $stmt->insert_id : $_POST['id'];
                $response = array('status' => 'success', 'msg' => 'تم حفظ بيانات السائق بنجاح', 'id' => $driver_id);
                break;
                
            case 'delete_driver':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE drivers SET delete_flag = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if(!$stmt->execute()) throw new Exception("حدث خطأ أثناء حذف السائق: ".$stmt->error);
                
                $response = array('status' => 'success', 'msg' => 'تم حذف السائق بنجاح');
                break;
                
            case 'get_driver':
                $id = $_POST['id'];
                $stmt = $conn->prepare("SELECT d.*, dt.name as driver_type FROM drivers d 
                                     LEFT JOIN driver_types dt ON d.driver_type_id = dt.id 
                                     WHERE d.id = ? AND d.delete_flag = 0");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($result->num_rows === 0) throw new Exception("السائق غير موجود");
                
                $response = array('status' => 'success', 'data' => $result->fetch_assoc());
                break;
                
            case 'get_driver_details':
                $id = $_POST['id'];
                $stmt = $conn->prepare("SELECT d.*, dt.name as driver_type FROM drivers d 
                                      LEFT JOIN driver_types dt ON d.driver_type_id = dt.id 
                                      WHERE d.id = ? AND d.delete_flag = 0");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $driver = $stmt->get_result()->fetch_assoc();
                
                if(!$driver) throw new Exception("السائق غير موجود");
                
                ob_start();
                ?>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="<?= $_settings->validate_image($driver['image_path'] ?? 'uploads/no-image-available.png') ?>" 
                                 class="img-thumbnail" style="max-width:200px;">
                            <?php if(!empty($driver['license_image'])): ?>
                            <div class="mt-3">
                                <h5>رخصة القيادة</h5>
                                <img src="<?= $_settings->validate_image($driver['license_image']) ?>" 
                                     class="img-thumbnail" style="max-width:200px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h4>معلومات السائق</h4>
                            <table class="table table-bordered">
                                <?php 
                                $driver_info = array(
                                    'النوع' => $driver['driver_type'] ?? '--',
                                    'الاسم الكامل' => $driver['firstname'].' '.$driver['middlename'].' '.$driver['lastname'],
                                    'رقم الهوية' => $driver['national_id'] ?? '--',
                                    'تاريخ الميلاد' => !empty($driver['dob']) ? date("Y-m-d", strtotime($driver['dob'])) : '--',
                                    'النوع' => $driver['gender'] == 'male' ? 'ذكر' : 'أنثى',
                                    'رقم الهاتف' => $driver['contact'] ?? '--',
                                    'العنوان' => nl2br($driver['address'] ?? '--'),
                                    'رقم الرخصة' => $driver['license_number'] ?? '--',
                                    'نوع الرخصة' => $driver['license_type'] ?? '--',
                                    'تاريخ انتهاء الرخصة' => !empty($driver['license_expiry']) ? date("Y-m-d", strtotime($driver['license_expiry'])) : '--',
                                    'تاريخ التعيين' => !empty($driver['employment_date']) ? date("Y-m-d", strtotime($driver['employment_date'])) : '--',
                                    'الراتب' => $driver['salary'] ? number_format($driver['salary'], 2).' ('.($driver['salary_type'] == 'monthly' ? 'شهري' : 'بالرحلة').')' : '--',
                                    'الحالة' => getStatusBadge($driver['status']),
                                    'ملاحظات' => nl2br($driver['notes'] ?? '--')
                                );
                                
                                foreach($driver_info as $label => $value): ?>
                                <tr><th><?= htmlspecialchars($label) ?></th><td><?= $value ?></td></tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
                $response = array('status' => 'success', 'html' => ob_get_clean());
                break;
                
            default:
                throw new Exception("إجراء غير معروف");
        }
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'msg' => $e->getMessage()), JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

function getStatusBadge($status){
    $statuses = array(
        '1' => array('label' => 'نشط', 'class' => 'success'),
        '0' => array('label' => 'غير نشط', 'class' => 'danger')
    );
    
    $status = strval($status);
    $info = $statuses[$status] ?? array('label' => 'غير معروف', 'class' => 'secondary');
    
    return '<span class="badge bg-'.$info['class'].'">'.$info['label'].'</span>';
}

function uploadDriverImage($file, $folder){
    $allowed = array('jpg', 'jpeg', 'png');
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if(!in_array(strtolower($ext), $allowed)){
        return array('status' => 'error', 'message' => 'نوع الملف غير مسموح به');
    }
    
    if($file['size'] > 2 * 1024 * 1024){ // 2MB
        return array('status' => 'error', 'message' => 'حجم الملف كبير جداً');
    }
    
    $upload_dir = 'uploads/'.$folder.'/';
    if(!is_dir($upload_dir)){
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = time().'_'.uniqid().'.'.$ext;
    $path = $upload_dir.$filename;
    
    if(move_uploaded_file($file['tmp_name'], $path)){
        return array('status' => 'success', 'path' => $path);
    } else {
        return array('status' => 'error', 'message' => 'فشل في رفع الملف');
    }
}
?>

<style>
    .driver-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 50%;
    }
    .driver-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .modal-footer {
        display: block;
    }
    body {
        background-color: #f5f5f5;
    }
    .card-header {
        background-color: #343a40;
        color: white;
    }
    .table th {
        background-color: #495057;
        color: white;
    }
    .img-thumb-path {
        height: 100px;
        width: 80px;
        object-fit: scale-down;
        object-position: center center;
    }
    .badge-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    #driverForm .form-control {
        border-radius: 5px;
    }
    .required:after {
        content: " *";
        color: red;
    }
    .dropdown-menu {
        right: 0;
        left: auto;
    }
    .btn-save {
        background-color: #007bff; /* لون أزرق */
        color: white;
    }
    .btn-save:hover {
        background-color: #0069d9;
        color: white;
    }
    #driverTable {
        width: 100% !important;
    }
    .loader-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 2s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">قائمة السائقين</h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> إضافة سائق جديد
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-hover table-striped" id="driverTable">
                <thead>
                    <tr class="bg-gradient-primary text-light">
                        <th>#</th>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>رقم الهاتف</th>
                        <th>رقم الرخصة</th>
                        <th>نوع السائق</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT d.*, dt.name as driver_type FROM `drivers` d 
                                        LEFT JOIN driver_types dt ON d.driver_type_id = dt.id
                                        WHERE d.delete_flag = 0 ORDER BY `lastname`, `firstname` ASC");
                    while($row = $qry->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td class="text-center">
                            <img src="<?= $_settings->validate_image($row['image_path'] ?? 'uploads/no-image-available.png') ?>" 
                                 class="img-thumbnail driver-img" alt="صورة السائق">
                        </td>
                        <td><?= htmlspecialchars($row['firstname'].' '.$row['lastname']) ?></td>
                        <td><?= htmlspecialchars($row['contact'] ?? '--') ?></td>
                        <td><?= htmlspecialchars($row['license_number'] ?? '--') ?></td>
                        <td><?= htmlspecialchars($row['driver_type'] ?? '--') ?></td>
                        <td class="text-center"><?= getStatusBadge($row['status']) ?></td>
                        <td align="center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> عرض التفاصيل
                                    </a>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                        <span class="fa fa-edit text-primary"></span> تعديل
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                        <span class="fa fa-trash text-danger"></span> حذف
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form لإدارة السائق -->
<div class="modal fade" id="driverModal" tabindex="-1" role="dialog" aria-labelledby="driverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverModalLabel">إدارة بيانات السائق</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="driverForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="driver_type_id">نوع السائق</label>
                            <select class="form-control" id="driver_type_id" name="driver_type_id">
                                <option value="">-- اختر نوع السائق --</option>
                                <?php 
                                $types = $conn->query("SELECT * FROM driver_types WHERE delete_flag = 0");
                                while($type = $types->fetch_assoc()):
                                ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="status" class="required">الحالة</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="1">نشط</option>
                                <option value="0">غير نشط</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="firstname" class="required">الاسم الأول</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="middlename">الاسم الأوسط</label>
                            <input type="text" class="form-control" id="middlename" name="middlename">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="lastname" class="required">الاسم الأخير</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="gender" class="required">النوع</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="dob">تاريخ الميلاد</label>
                            <input type="date" class="form-control" id="dob" name="dob">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="national_id">رقم الهوية</label>
                            <input type="text" class="form-control" id="national_id" name="national_id">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="contact" class="required">رقم الهاتف</label>
                            <input type="text" class="form-control" id="contact" name="contact" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="employment_date">تاريخ التعيين</label>
                            <input type="date" class="form-control" id="employment_date" name="employment_date">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="salary">الراتب</label>
                            <input type="number" step="0.01" class="form-control" id="salary" name="salary">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="salary_type">نوع الراتب</label>
                            <select class="form-control" id="salary_type" name="salary_type">
                                <option value="monthly">شهري</option>
                                <option value="per_trip">بالرحلة</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="license_number">رقم الرخصة</label>
                            <input type="text" class="form-control" id="license_number" name="license_number">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="license_type">نوع الرخصة</label>
                            <input type="text" class="form-control" id="license_type" name="license_type">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="license_expiry">تاريخ انتهاء الرخصة</label>
                            <input type="date" class="form-control" id="license_expiry" name="license_expiry">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="image">صورة السائق</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="license_image">صورة الرخصة</label>
                        <input type="file" class="form-control" id="license_image" name="license_image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="address">العنوان</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">ملاحظات</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-save" id="saveDriverBtn">
                        <i class="fas fa-save"></i> حفظ البيانات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لعرض التفاصيل -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">تفاصيل السائق</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewDetails">
                جاري تحميل البيانات...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    var driverTable = $('#driverTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        },
        "responsive": true,
        "autoWidth": false,
        "processing": true,
        "serverSide": false
    });

    // Reset form when modal is closed
    $('.modal').on('hidden.bs.modal', function(){
        $(this).find('form')[0].reset();
    });

    // Create new driver
    $('#create_new').click(function(){
        $('#driverForm')[0].reset();
        $('#driverForm input[name="id"]').val('');
        $('#driverModalLabel').text('إضافة سائق جديد');
        $('#driverModal').modal('show');
    });

    // View driver details
    $(document).on('click', '.view_data', function(){
        var id = $(this).data('id');
        $('#viewModal').modal('show');
        loadDriverDetails(id);
    });

    // Edit driver
    $(document).on('click', '.edit_data', function(){
        var id = $(this).data('id');
        start_loader();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {action: 'get_driver', id: id},
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    $('#driverForm input[name="id"]').val(resp.data.id);
                    $('#driverForm select[name="driver_type_id"]').val(resp.data.driver_type_id || '');
                    $('#driverForm input[name="firstname"]').val(resp.data.firstname || '');
                    $('#driverForm input[name="middlename"]').val(resp.data.middlename || '');
                    $('#driverForm input[name="lastname"]').val(resp.data.lastname || '');
                    $('#driverForm select[name="gender"]').val(resp.data.gender || 'male');
                    $('#driverForm input[name="dob"]').val(resp.data.dob || '');
                    $('#driverForm input[name="national_id"]').val(resp.data.national_id || '');
                    $('#driverForm input[name="contact"]').val(resp.data.contact || '');
                    $('#driverForm input[name="license_number"]').val(resp.data.license_number || '');
                    $('#driverForm input[name="license_type"]').val(resp.data.license_type || '');
                    $('#driverForm input[name="license_expiry"]').val(resp.data.license_expiry || '');
                    $('#driverForm input[name="employment_date"]').val(resp.data.employment_date || '');
                    $('#driverForm input[name="salary"]').val(resp.data.salary || '');
                    $('#driverForm select[name="salary_type"]').val(resp.data.salary_type || 'monthly');
                    $('#driverForm select[name="status"]').val(resp.data.status || '1');
                    $('#driverForm textarea[name="address"]').val(resp.data.address || '');
                    $('#driverForm textarea[name="notes"]').val(resp.data.notes || '');
                    $('#driverModalLabel').text('تعديل بيانات السائق');
                    $('#driverModal').modal('show');
                }else{
                    alert_toast(resp.msg, 'error');
                }
                end_loader();
            },
            error: function(xhr, status, error){
                console.error(error);
                alert_toast('حدث خطأ أثناء جلب البيانات', 'error');
                end_loader();
            }
        });
    });

    // Delete driver
    $(document).on('click', '.delete_data', function(){
        var id = $(this).data('id');
        _conf("هل أنت متأكد من حذف هذا السائق؟ جميع البيانات المرتبطة به سيتم حذفها أيضاً.", function(){
            deleteDriver(id);
        });
    });

    // Save driver form
    $('#driverForm').submit(function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'save_driver');
        
        // تعطيل زر الحفظ أثناء المعالجة
        $('#saveDriverBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    alert_toast(resp.msg, 'success');
                    $('#driverModal').modal('hide');
                    
                    // إعادة تحميل البيانات بعد الحفظ
                    reloadDriverData();
                }else{
                    alert_toast(resp.msg, 'error');
                }
                $('#saveDriverBtn').prop('disabled', false).html('<i class="fas fa-save"></i> حفظ البيانات');
            },
            error: function(xhr, status, error){
                console.error(error);
                alert_toast('حدث خطأ أثناء حفظ البيانات', 'error');
                $('#saveDriverBtn').prop('disabled', false).html('<i class="fas fa-save"></i> حفظ البيانات');
            }
        });
    });

    // Function to load driver details
    function loadDriverDetails(id) {
        start_loader();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {action: 'get_driver_details', id: id},
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    $('#viewDetails').html(resp.html);
                }else{
                    $('#viewDetails').html('<div class="alert alert-danger">' + resp.msg + '</div>');
                }
                end_loader();
            },
            error: function(xhr, status, error){
                console.error(error);
                $('#viewDetails').html('<div class="alert alert-danger">حدث خطأ أثناء جلب البيانات</div>');
                end_loader();
            }
        });
    }

    // Function to reload driver data
    function reloadDriverData() {
        start_loader();
        $.ajax({
            url: window.location.href,
            method: 'GET',
            dataType: 'html',
            success: function(data){
                // استخراج جدول السائقين من البيانات المرتجعة
                var newTable = $(data).find('#driverTable').html();
                $('#driverTable').html(newTable);
                
                // إعادة تهيئة DataTable
                driverTable.destroy();
                driverTable = $('#driverTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
                    },
                    "responsive": true,
                    "autoWidth": false,
                    "processing": true,
                    "serverSide": false
                });
                
                end_loader();
            },
            error: function(xhr, status, error){
                console.error(error);
                alert_toast('حدث خطأ أثناء تحديث البيانات', 'error');
                end_loader();
            }
        });
    }

    // Function to delete driver
    function deleteDriver(id) {
        start_loader();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {action: 'delete_driver', id: id},
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    alert_toast(resp.msg, 'success');
                    reloadDriverData();
                }else{
                    alert_toast(resp.msg, 'error');
                }
                end_loader();
            },
            error: function(xhr, status, error){
                console.error(error);
                alert_toast('حدث خطأ أثناء حذف البيانات', 'error');
                end_loader();
            }
        });
    }
});

// Helper functions
function alert_toast(msg, type) {
    var bg = type === 'error' ? 'bg-danger' : type === 'success' ? 'bg-success' : 'bg-info';
    var toast = $('<div class="alert-toast" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">' +
                 '<div class="alert alert-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info') + 
                 ' alert-dismissible fade show" role="alert">' +
                 msg +
                 '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                 '<span aria-hidden="true">&times;</span>' +
                 '</button>' +
                 '</div>' +
                 '</div>');
    
    $('body').append(toast);
    setTimeout(function(){
        toast.fadeOut('slow', function(){
            $(this).remove();
        });
    }, 3000);
}

function _conf(msg, callback) {
    if(confirm(msg)){
        if(typeof callback === 'function'){
            callback();
        }
    }
}

function start_loader() {
    $('body').append('<div class="loader-container"><div class="loader"></div></div>');
}

function end_loader() {
    $('.loader-container').fadeOut('fast', function(){
        $(this).remove();
    });
}
</script>