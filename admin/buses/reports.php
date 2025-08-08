<?php
require_once(__DIR__.'/../../config.php');
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['userdata'])){
    echo '<script>window.location.href = "'.base_url.'login.php";</script>';
    exit;
}

// معالجة تصدير البيانات إلى Excel
if(isset($_POST['export_excel'])){
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="buses_report_'.date('Y-m-d').'.xls"');
    header('Cache-Control: max-age=0');
    
    // بناء استعلام SQL حسب الفلتر
    $where = "WHERE b.delete_flag = 0";
    
    if(!empty($_POST['status_filter'])){
        $status = $conn->real_escape_string($_POST['status_filter']);
        $where .= " AND b.status = '$status'";
    }
    
    if(!empty($_POST['ownership_filter'])){
        $ownership = $conn->real_escape_string($_POST['ownership_filter']);
        $where .= " AND b.ownership_type = '$ownership'";
    }
    
    if(!empty($_POST['date_from']) && !empty($_POST['date_to'])){
        $date_from = $conn->real_escape_string($_POST['date_from']);
        $date_to = $conn->real_escape_string($_POST['date_to']);
        $where .= " AND b.start_date BETWEEN '$date_from' AND '$date_to'";
    }
    
    $sql = "SELECT 
            b.id,
            b.bus_number,
            b.plate_number,
            b.model,
            b.capacity,
            b.color,
            b.status,
            b.start_date,
            b.ownership_type,
            b.owner_name,
            b.owner_contact,
            COUNT(d.id) AS document_count,
            MAX(d.expiry_date) AS next_expiry
        FROM buses b
        LEFT JOIN bus_documents d ON b.id = d.bus_id
        $where
        GROUP BY b.id
        ORDER BY b.bus_number";
    
    $result = $conn->query($sql);
    
    // بداية ملف Excel
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>رقم الباص</th>';
    echo '<th>رقم اللوحة</th>';
    echo '<th>الموديل</th>';
    echo '<th>السعة</th>';
    echo '<th>اللون</th>';
    echo '<th>الحالة</th>';
    echo '<th>تاريخ البدء</th>';
    echo '<th>نوع الملكية</th>';
    echo '<th>اسم المالك</th>';
    echo '<th>اتصال المالك</th>';
    echo '<th>عدد المستندات</th>';
    echo '<th>أقرب تاريخ انتهاء</th>';
    echo '</tr>';
    
    $i = 1;
    while($row = $result->fetch_assoc()){
        // تحويل الحالة إلى نص
        $status = '';
        if($row['status'] == 'working') $status = 'يعمل';
        elseif($row['status'] == 'stopped') $status = 'متوقف';
        elseif($row['status'] == 'maintenance') $status = 'صيانة';
        
        // تحويل نوع الملكية إلى نص
        $ownership = $row['ownership_type'] == 'company' ? 'شركة' : 'مستأجر';
        
        echo '<tr>';
        echo '<td>'.$i++.'</td>';
        echo '<td>'.$row['bus_number'].'</td>';
        echo '<td>'.$row['plate_number'].'</td>';
        echo '<td>'.$row['model'].'</td>';
        echo '<td>'.$row['capacity'].'</td>';
        echo '<td>'.$row['color'].'</td>';
        echo '<td>'.$status.'</td>';
        echo '<td>'.$row['start_date'].'</td>';
        echo '<td>'.$ownership.'</td>';
        echo '<td>'.$row['owner_name'].'</td>';
        echo '<td>'.$row['owner_contact'].'</td>';
        echo '<td>'.$row['document_count'].'</td>';
        echo '<td>'.$row['next_expiry'].'</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

// جلب البيانات للعرض في الصفحة
$where = "WHERE b.delete_flag = 0";
$filter_applied = false;

if(isset($_POST['filter'])){
    if(!empty($_POST['status_filter'])){
        $status = $conn->real_escape_string($_POST['status_filter']);
        $where .= " AND b.status = '$status'";
        $filter_applied = true;
    }
    
    if(!empty($_POST['ownership_filter'])){
        $ownership = $conn->real_escape_string($_POST['ownership_filter']);
        $where .= " AND b.ownership_type = '$ownership'";
        $filter_applied = true;
    }
    
    if(!empty($_POST['date_from']) && !empty($_POST['date_to'])){
        $date_from = $conn->real_escape_string($_POST['date_from']);
        $date_to = $conn->real_escape_string($_POST['date_to']);
        $where .= " AND b.start_date BETWEEN '$date_from' AND '$date_to'";
        $filter_applied = true;
    }
}

$sql = "SELECT 
        b.id,
        b.bus_number,
        b.plate_number,
        b.model,
        b.capacity,
        b.color,
        b.status,
        b.start_date,
        b.ownership_type,
        b.owner_name,
        b.owner_contact,
        COUNT(d.id) AS document_count,
        MAX(d.expiry_date) AS next_expiry
    FROM buses b
    LEFT JOIN bus_documents d ON b.id = d.bus_id
    $where
    GROUP BY b.id
    ORDER BY b.bus_number";

$buses = $conn->query($sql);
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
    .badge-success { background-color: #28a745; }
    .badge-danger { background-color: #dc3545; }
    .badge-warning { background-color: #ffc107; color: #212529; }
    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    .filter-title {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .expiry-soon {
        background-color: #fff3cd;
    }
    .expired {
        background-color: #f8d7da;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">تقارير الباصات</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-success" data-toggle="collapse" data-target="#filterCollapse">
                <span class="fas fa-filter"></span> تصفية النتائج
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- قسم الفلترة -->
        <div class="collapse <?php echo $filter_applied ? 'show' : ''; ?>" id="filterCollapse">
            <div class="filter-section">
                <form method="post" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>حالة الباص</label>
                                <select name="status_filter" class="form-control form-control-sm">
                                    <option value="">جميع الحالات</option>
                                    <option value="working" <?php echo isset($_POST['status_filter']) && $_POST['status_filter'] == 'working' ? 'selected' : ''; ?>>يعمل</option>
                                    <option value="stopped" <?php echo isset($_POST['status_filter']) && $_POST['status_filter'] == 'stopped' ? 'selected' : ''; ?>>متوقف</option>
                                    <option value="maintenance" <?php echo isset($_POST['status_filter']) && $_POST['status_filter'] == 'maintenance' ? 'selected' : ''; ?>>صيانة</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>نوع الملكية</label>
                                <select name="ownership_filter" class="form-control form-control-sm">
                                    <option value="">جميع الأنواع</option>
                                    <option value="company" <?php echo isset($_POST['ownership_filter']) && $_POST['ownership_filter'] == 'company' ? 'selected' : ''; ?>>شركة</option>
                                    <option value="rented" <?php echo isset($_POST['ownership_filter']) && $_POST['ownership_filter'] == 'rented' ? 'selected' : ''; ?>>مستأجر</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>من تاريخ</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $_POST['date_from'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $_POST['date_to'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" name="filter" class="btn btn-primary btn-sm">
                                <span class="fas fa-search"></span> تطبيق الفلتر
                            </button>
                            <button type="button" onclick="resetFilter()" class="btn btn-secondary btn-sm">
                                <span class="fas fa-undo"></span> إعادة تعيين
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- نتائج التقرير -->
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="reportTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الباص</th>
                        <th>رقم اللوحة</th>
                        <th>الموديل</th>
                        <th>السعة</th>
                        <th>الحالة</th>
                        <th>تاريخ البدء</th>
                        <th>نوع الملكية</th>
                        <th>عدد المستندات</th>
                        <th>أقرب تاريخ انتهاء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($buses->num_rows > 0): ?>
                        <?php $i = 1; while($row = $buses->fetch_assoc()): 
                            // تحديد كلاس الصف حسب تاريخ الانتهاء
                            $row_class = '';
                            if(!empty($row['next_expiry'])){
                                $expiry_date = new DateTime($row['next_expiry']);
                                $today = new DateTime();
                                $interval = $today->diff($expiry_date);
                                
                                if($expiry_date < $today){
                                    $row_class = 'expired';
                                } elseif($interval->days <= 30){
                                    $row_class = 'expiry-soon';
                                }
                            }
                            
                            // تحويل الحالة إلى نص
                            $status = '';
                            if($row['status'] == 'working'){
                                $status = '<span class="badge badge-success">يعمل</span>';
                            }elseif($row['status'] == 'stopped'){
                                $status = '<span class="badge badge-danger">متوقف</span>';
                            }elseif($row['status'] == 'maintenance'){
                                $status = '<span class="badge badge-warning">صيانة</span>';
                            }
                            
                            // تحويل نوع الملكية إلى نص
                            $ownership = $row['ownership_type'] == 'company' ? 'شركة' : 'مستأجر';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $row['bus_number']; ?></td>
                            <td><?php echo $row['plate_number']; ?></td>
                            <td><?php echo $row['model']; ?></td>
                            <td><?php echo $row['capacity']; ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo $row['start_date']; ?></td>
                            <td><?php echo $ownership; ?></td>
                            <td><?php echo $row['document_count']; ?></td>
                            <td><?php echo $row['next_expiry'] ?? '---'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">لا توجد بيانات متاحة</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- زر التصدير -->
        <div class="mt-3 text-right">
            <form method="post" id="exportForm">
                <input type="hidden" name="status_filter" value="<?php echo $_POST['status_filter'] ?? ''; ?>">
                <input type="hidden" name="ownership_filter" value="<?php echo $_POST['ownership_filter'] ?? ''; ?>">
                <input type="hidden" name="date_from" value="<?php echo $_POST['date_from'] ?? ''; ?>">
                <input type="hidden" name="date_to" value="<?php echo $_POST['date_to'] ?? ''; ?>">
                <button type="submit" name="export_excel" class="btn btn-success">
                    <span class="fas fa-file-excel"></span> تصدير إلى Excel
                </button>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // تهيئة جدول البيانات
    $('#reportTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json"
        },
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "pageLength": 25
    });

    // نسخ قيم الفلتر إلى نموذج التصدير
    $('#filterForm').submit(function(){
        $('#exportForm input[name="status_filter"]').val($('#filterForm select[name="status_filter"]').val());
        $('#exportForm input[name="ownership_filter"]').val($('#filterForm select[name="ownership_filter"]').val());
        $('#exportForm input[name="date_from"]').val($('#filterForm input[name="date_from"]').val());
        $('#exportForm input[name="date_to"]').val($('#filterForm input[name="date_to"]').val());
    });
});

// إعادة تعيين الفلتر
function resetFilter(){
    $('#filterForm select').val('');
    $('#filterForm input[type="date"]').val('');
    $('#filterForm').submit();
}
</script>