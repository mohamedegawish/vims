<?php
require_once('../../config.php');

if(!isset($conn)) {
    die("فشل الاتصال بقاعدة البيانات");
}

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `buses` WHERE id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
?>

<style>
    #img-holder{
        max-width: 100%;
        max-height: 40vh;
        object-fit: scale-down;
    }
    .modal-close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 1.5rem;
        cursor: pointer;
    }
</style>

<div class="content py-3">
    <div class="card card-outline card-primary rounded-0 shadow position-relative">
        <span class="modal-close-btn" onclick="window.parent.$('#uni_modal').modal('hide')">&times;</span>
        
        <div class="card-header">
            <h5 class="card-title">تفاصيل الباص - <?= isset($bus_number) ? $bus_number : '' ?></h5>
        </div>
        
        <div class="card-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="border-bottom mb-2">
                            <span class="text-muted">رقم الباص:</span>
                            <h4><?= isset($bus_number) ? $bus_number : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">رقم اللوحة:</span>
                            <h4><?= isset($plate_number) ? $plate_number : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">الموديل:</span>
                            <h4><?= isset($model) ? $model : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">السعة:</span>
                            <h4><?= isset($capacity) ? $capacity : '' ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border-bottom mb-2">
                            <span class="text-muted">رقم الشاسيه:</span>
                            <h4><?= isset($chassis_number) ? $chassis_number : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">رقم المحرك:</span>
                            <h4><?= isset($engine_number) ? $engine_number : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">اللون:</span>
                            <h4><?= isset($color) ? $color : '' ?></h4>
                        </div>
                        <div class="border-bottom mb-2">
                            <span class="text-muted">الحالة:</span>
                            <h4>
                                <?php if(isset($status)): ?>
                                    <?php if($status == 'working'): ?>
                                        <span class="badge badge-success">يعمل</span>
                                    <?php elseif($status == 'stopped'): ?>
                                        <span class="badge badge-danger">متوقف</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">صيانة</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="border-bottom mb-2">
                            <span class="text-muted">ملاحظات:</span>
                            <div class="p-2 border rounded"><?= isset($notes) ? $notes : 'لا توجد ملاحظات' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// إغلاق النافذة عند النقر خارجها
$(document).ready(function(){
    $('#uni_modal').on('click', function(e){
        if($(e.target).is('#uni_modal')) {
            $('#uni_modal').modal('hide');
        }
    });
});
</script>