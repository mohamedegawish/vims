<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error', 'msg'=>'يجب تسجيل الدخول أولاً']);
    exit;
}

// معالجة طلبات Ajax - مطابقة تماماً للصفحات الأخرى التي تعمل
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])){
    ob_start();
    header('Content-Type: application/json');
    $response = ['status'=>'error', 'msg'=>'طلب غير معروف'];
    
    try {
        // معالجة الحذف
        if(isset($_POST['delete_bus'])){
            $id = (int)$_POST['id'];
            if($id > 0){
                $conn->query("UPDATE buses SET delete_flag = 1 WHERE id = {$id}");
                $response = [
                    'status' => $conn->affected_rows > 0 ? 'success' : 'error',
                    'msg' => $conn->affected_rows > 0 ? 'تم الحذف بنجاح' : 'فشل في الحذف'
                ];
            }
        }
        // معالجة الحفظ (إضافة/تعديل)
        elseif(isset($_POST['save_bus'])){
            $id = (int)$_POST['id'];
            $data = [];
            foreach($_POST as $k => $v){
                if(!in_array($k, ['id','ajax','save_bus'])){
                    $data[$k] = $conn->real_escape_string($v);
                }
            }
            
            if($id == 0){
                $fields = implode(',', array_keys($data));
                $values = "'".implode("','", array_values($data))."'";
                $sql = "INSERT INTO buses ({$fields}, date_created) VALUES ({$values}, NOW())";
            }else{
                $updates = [];
                foreach($data as $field => $value){
                    $updates[] = "{$field} = '{$value}'";
                }
                $sql = "UPDATE buses SET ".implode(',', $updates).", date_updated = NOW() WHERE id = {$id}";
            }
            
            if($conn->query($sql)){
                $response = [
                    'status' => 'success',
                    'msg' => 'تم الحفظ بنجاح',
                    'id' => $id == 0 ? $conn->insert_id : $id
                ];
            }else{
                $response = [
                    'status' => 'error',
                    'msg' => 'فشل في الحفظ: '.$conn->error
                ];
            }
        }
        // جلب بيانات الباص
        elseif(isset($_POST['get_bus'])){
            $id = (int)$_POST['id'];
            $qry = $conn->query("SELECT * FROM buses WHERE id = {$id} AND delete_flag = 0");
            if($qry->num_rows > 0){
                $response = [
                    'status' => 'success',
                    'data' => $qry->fetch_assoc()
                ];
            }else{
                $response = [
                    'status' => 'error',
                    'msg' => 'لم يتم العثور على الباص'
                ];
            }
        }
        
        echo json_encode($response);
    } catch(Exception $e) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'حدث خطأ في الخادم: '.$e->getMessage()
        ]);
    }
    ob_end_flush();
    exit;
}

// جلب بيانات الباصات للعرض
$buses = $conn->query("SELECT * FROM buses WHERE delete_flag = 0 ORDER BY date_created DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الباصات</title>
    <!-- CSS -->
    <link rel="stylesheet" href="<?= base_url ?>plugins/bootstrap/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="<?= base_url ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url ?>plugins/toastr/toastr.min.css">
    <style>
        .img-avatar { width:45px; height:45px; object-fit:cover; border-radius:100%; }
        .card-outline { border-top: 3px solid #007bff; }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; }
        #busForm .form-control, #busDetails .form-control { border-radius: 0; }
        .modal-lg { max-width: 800px; }
        .btn-group-sm .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .detail-label { font-weight: bold; color: #495057; }
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .toast-top-right {
            top: 70px;
            right: 12px;
        }
        .border-bottom { border-bottom: 1px solid #dee2e6 !important; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <div class="content-wrapper">
            <section class="content">
                <div class="container-fluid">
                    <div class="card card-outline card-primary mt-3">
                        <div class="card-header">
                            <h3 class="card-title">قائمة الباصات</h3>
                            <div class="card-tools">
                                <button class="btn btn-flat btn-primary" onclick="resetForm()" data-toggle="modal" data-target="#busModal">
                                    <span class="fas fa-plus"></span> إضافة جديد
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-bordered" id="list">
                                    <thead class="bg-primary text-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th>رقم الباص</th>
                                            <th>رقم اللوحة</th>
                                            <th>الموديل</th>
                                            <th>السعة</th>
                                            <th>الحالة</th>
                                            <th width="15%">إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($buses->num_rows > 0): ?>
                                        <?php $i=1; while($row = $buses->fetch_assoc()): 
                                            $status = $row['status'] == 'working' ? 'success' : 
                                                     ($row['status'] == 'stopped' ? 'danger' : 'warning');
                                            $status_text = $row['status'] == 'working' ? 'يعمل' : 
                                                          ($row['status'] == 'stopped' ? 'متوقف' : 'صيانة');
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['bus_number']) ?></td>
                                            <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                            <td><?= htmlspecialchars($row['model']) ?></td>
                                            <td><?= htmlspecialchars($row['capacity']) ?></td>
                                            <td><span class="badge badge-<?= $status ?>"><?= $status_text ?></span></td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="showDetails(<?= $row['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-primary" onclick="editBus(<?= $row['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger" onclick="deleteBus(<?= $row['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">لا توجد بيانات متاحة</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal إضافة/تعديل الباص -->
    <div class="modal fade" id="busModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة باص جديد</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="busForm" autocomplete="off">
                    <input type="hidden" name="id" value="0">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="save_bus" value="1">
                    
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>رقم الباص</label>
                                        <input type="text" name="bus_number" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>رقم اللوحة</label>
                                        <input type="text" name="plate_number" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>الموديل</label>
                                        <input type="text" name="model" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>السعة</label>
                                        <input type="number" name="capacity" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>رقم الشاسيه</label>
                                        <input type="text" name="chassis_number" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>رقم المحرك</label>
                                        <input type="text" name="engine_number" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>اللون</label>
                                        <input type="text" name="color" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>الحالة</label>
                                        <select name="status" class="form-control" required>
                                            <option value="working">يعمل</option>
                                            <option value="stopped">متوقف</option>
                                            <option value="maintenance">صيانة</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>تاريخ البدء</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>نوع الملكية</label>
                                        <select name="ownership_type" class="form-control" required>
                                            <option value="company">شركة</option>
                                            <option value="rented">مستأجر</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>اسم المالك (إذا كان مستأجرًا)</label>
                                        <input type="text" name="owner_name" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>اتصال المالك</label>
                                        <input type="text" name="owner_contact" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal عرض التفاصيل -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الباص</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="busDetails">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">رقم الباص:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_bus_number"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">رقم اللوحة:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_plate_number"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">الموديل:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_model"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">السعة:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_capacity"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">رقم الشاسيه:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_chassis_number"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">رقم المحرك:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_engine_number"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">اللون:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_color"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">الحالة:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_status"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">تاريخ البدء:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_start_date"></p>
                                    </div>
                                </div>
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">نوع الملكية:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_ownership_type"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">اسم المالك:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_owner_name"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row border-bottom py-2">
                                    <label class="col-sm-4 col-form-label detail-label">اتصال المالك:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext" id="detail_owner_contact"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="detail-label">ملاحظات:</label>
                            <div class="form-control-plaintext border p-2" id="detail_notes" style="min-height: 50px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة التأكيد -->
    <div class="modal fade" id="confirm_modal" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد</h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirm">نعم</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">لا</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?= base_url ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?= base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url ?>plugins/toastr/toastr.min.js"></script>
    <script>
    $(document).ready(function(){
        // تهيئة DataTable
        var table = $('#list').DataTable({
            responsive: true,
            autoWidth: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json'
            }
        });
        
        // معالجة حفظ النموذج
        $('#busForm').submit(function(e){
            e.preventDefault();
            var form = $(this);
            
            // التحقق من الصحة
            if(form[0].checkValidity() === false){
                form[0].reportValidity();
                return false;
            }
            
            start_loader();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: new FormData(form[0]),
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res){
                    if(res.status == 'success'){
                        alert_toast(res.msg, 'success');
                        $('#busModal').modal('hide');
                        setTimeout(() => {
                            location.reload(); // أو table.ajax.reload()
                        }, 1500);
                    }else{
                        alert_toast(res.msg || 'حدث خطأ غير معروف', 'error');
                    }
                    end_loader();
                },
                error: function(xhr){
                    var errorMsg = 'حدث خطأ في الاتصال';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if(res.msg) errorMsg = res.msg;
                    } catch(e) {
                        console.error('Failed to parse response:', xhr.responseText);
                    }
                    alert_toast(errorMsg, 'error');
                    end_loader();
                }
            });
        });
    });

    // تحرير الباص
    function editBus(id){
        uni_modal("تعديل الباص", "buses/manage_bus.php?id="+id, 'mid-large');
    }

    // عرض تفاصيل الباص
    function showDetails(id){
        uni_modal("تفاصيل الباص", "buses/view_bus.php?id="+id, 'mid-large');
    }

    // حذف الباص
    function deleteBus(id){
        _conf("هل أنت متأكد من حذف هذا الباص؟", "confirmDelete", [id]);
    }

    // تأكيد الحذف
    function confirmDelete(id){
        start_loader();
        $.ajax({
            url: _base_url_+"classes/Master.php?f=delete_bus",
            method: "POST",
            data: {id: id},
            dataType: "json",
            error: function(err){
                console.log(err);
                alert_toast("حدث خطأ.",'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    alert_toast(resp.msg,'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }else{
                    alert_toast("حدث خطأ.",'error');
                    end_loader();
                }
            }
        });
    }

    // إعادة تعيين النموذج
    function resetForm(){
        $('#modalTitle').text('إضافة باص جديد');
        $('#busForm')[0].reset();
        $('#busForm input[name="id"]').val('0');
    }

    // دالة عرض التنبيه
    function alert_toast(msg, type){
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000,
            rtl: true
        };
        toastr[type](msg);
    }

    // دالة بدء التحميل
    function start_loader(){
        $('body').append('<div class="loader"><img src="<?= base_url ?>images/loading.gif" style="width:100px; height:100px;"></div>');
    }

    // دالة إنهاء التحميل
    function end_loader(){
        $('.loader').fadeOut('fast', function(){
            $(this).remove();
        });
    }

    // دالة التأكيد
    function _conf(msg, func, params){
        $('#confirm_modal .modal-body').html(msg);
        $('#confirm_modal #confirm').attr('onclick', func+"("+params.join(',')+")");
        $('#confirm_modal').modal('show');
    }
    </script>
</body>
</html>