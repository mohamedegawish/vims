<?php
require_once('../../config.php');
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
    #cimg{
        max-height: 20vh;
        max-width: 100%;
        object-fit: scale-down;
    }
</style>

<div class="container-fluid">
    <form action="" id="bus-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bus_number" class="control-label">رقم الباص</label>
                    <input type="text" name="bus_number" id="bus_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($bus_number) ? $bus_number : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="plate_number" class="control-label">رقم اللوحة</label>
                    <input type="text" name="plate_number" id="plate_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($plate_number) ? $plate_number : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="model" class="control-label">الموديل</label>
                    <input type="text" name="model" id="model" class="form-control form-control-sm rounded-0" value="<?php echo isset($model) ? $model : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="capacity" class="control-label">السعة</label>
                    <input type="number" name="capacity" id="capacity" class="form-control form-control-sm rounded-0" value="<?php echo isset($capacity) ? $capacity : '' ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="chassis_number" class="control-label">رقم الشاسيه</label>
                    <input type="text" name="chassis_number" id="chassis_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($chassis_number) ? $chassis_number : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="engine_number" class="control-label">رقم المحرك</label>
                    <input type="text" name="engine_number" id="engine_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($engine_number) ? $engine_number : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="color" class="control-label">اللون</label>
                    <input type="text" name="color" id="color" class="form-control form-control-sm rounded-0" value="<?php echo isset($color) ? $color : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="status" class="control-label">الحالة</label>
                    <select name="status" id="status" class="form-control form-control-sm rounded-0" required>
                        <option value="working" <?= isset($status) && $status == 'working' ? 'selected' : '' ?>>يعمل</option>
                        <option value="stopped" <?= isset($status) && $status == 'stopped' ? 'selected' : '' ?>>متوقف</option>
                        <option value="maintenance" <?= isset($status) && $status == 'maintenance' ? 'selected' : '' ?>>صيانة</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="control-label">تاريخ البدء</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm rounded-0" value="<?php echo isset($start_date) ? $start_date : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="ownership_type" class="control-label">نوع الملكية</label>
                    <select name="ownership_type" id="ownership_type" class="form-control form-control-sm rounded-0" required>
                        <option value="company" <?= isset($ownership_type) && $ownership_type == 'company' ? 'selected' : '' ?>>شركة</option>
                        <option value="rented" <?= isset($ownership_type) && $ownership_type == 'rented' ? 'selected' : '' ?>>مستأجر</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="owner_name" class="control-label">اسم المالك (إذا كان مستأجرًا)</label>
                    <input type="text" name="owner_name" id="owner_name" class="form-control form-control-sm rounded-0" value="<?php echo isset($owner_name) ? $owner_name : '' ?>">
                </div>
                <div class="form-group">
                    <label for="owner_contact" class="control-label">اتصال المالك</label>
                    <input type="text" name="owner_contact" id="owner_contact" class="form-control form-control-sm rounded-0" value="<?php echo isset($owner_contact) ? $owner_contact : '' ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="notes" class="control-label">ملاحظات</label>
            <textarea name="notes" id="notes" cols="30" rows="3" class="form-control form-control-sm rounded-0"><?php echo isset($notes) ? $notes : '' ?></textarea>
        </div>
    </form>
</div>

<script>
$(function(){
    $('#bus-form').submit(function(e){
        e.preventDefault();
        var _this = $(this)
        $('.pop-msg').remove()
        var el = $('<div>')
            el.addClass("pop-msg alert")
            el.hide()
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=save_bus",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error:err=>{
                console.log(err)
                alert_toast("حدث خطأ.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp =='object' && resp.status == 'success'){
                    if(typeof alert_toast === 'function'){
                        alert_toast(resp.msg || 'تم التعديل بنجاح', 'success');
                    }
                    $('.modal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1200);
                }else if(resp.status == 'failed' && !!resp.msg){
                    el.addClass("alert-danger")
                    el.text(resp.msg)
                    _this.prepend(el)
                }else{
                    el.addClass("alert-danger")
                    el.text("حدث خطأ غير متوقع.")
                    _this.prepend(el)
                }
                el.show('slow')
                end_loader();
            }
        })
    })
})
</script>