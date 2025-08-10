<?php /** Align buses list layout with other admin tables (card + DataTable) */ ?>
<style>
    /* Responsive table styles */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Ensure proper mobile display */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .badge {
            font-size: 0.75rem;
        }
    }
    
    /* DataTables responsive styling */
    .dtr-bs-modal .modal-body {
        padding: 1rem;
    }
    
    .dtr-bs-modal .table {
        margin-bottom: 0;
    }
</style>
<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">قائمة الباصات</h3>
        <?php if($_settings->userdata('type') == 1): ?>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> إضافة جديد
            </a>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="list">
                    <thead>
                        <tr class="bg-gradient-primary text-light">
                            <th>#</th>
                            <th>تاريخ الإنشاء</th>
                            <th>رقم الباص</th>
                            <th>رقم اللوحة</th>
                            <th>الموديل</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $i = 1;
                            $qry = $conn->query("SELECT * FROM `buses` WHERE delete_flag = 0 ORDER BY unix_timestamp(date_created) DESC");
                            while($row = $qry->fetch_assoc()):
                                $status_badge = 'badge-light bg-gradient-light border';
                                $status_label = 'غير معروف';
                                switch($row['status']){
                                    case 'working':
                                        $status_badge = 'badge-success bg-gradient-teal';
                                        $status_label = 'يعمل';
                                        break;
                                    case 'stopped':
                                        $status_badge = 'badge-danger bg-gradient-danger';
                                        $status_label = 'متوقف';
                                        break;
                                    case 'maintenance':
                                        $status_badge = 'badge-warning bg-gradient-warning';
                                        $status_label = 'صيانة';
                                        break;
                                }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class=""><?php echo isset($row['date_created']) ? date("Y-m-d H:i", strtotime($row['date_created'])) : '' ?></td>
                            <td class=""><p class="m-0 truncate-1"><?php echo htmlspecialchars($row['bus_number']); ?></p></td>
                            <td class=""><p class="m-0 truncate-1"><?php echo htmlspecialchars($row['plate_number']); ?></p></td>
                            <td class=""><p class="m-0 truncate-1"><?php echo htmlspecialchars($row['model']); ?></p></td>
                            <td class="text-center">
                                <span class="rounded-pill badge <?php echo $status_badge; ?> px-3"><?php echo $status_label; ?></span>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    إجراء
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> عرض</a>
                                    <?php if($_settings->userdata('type') == 1): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> تعديل</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> حذف</a>
                                    <?php endif; ?>
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
 <script>
    $(document).ready(function(){
        $('#create_new').click(function(){
            uni_modal("إضافة باص جديد","buses/manage_bus.php",'mid-large')
        })
        $('.view_data').click(function(){
            uni_modal("تفاصيل الباص","buses/view_bus.php?id="+$(this).attr('data-id'),'mid-large')
        })
        $('.edit_data').click(function(){
            uni_modal("تعديل بيانات الباص","buses/manage_bus.php?id="+$(this).attr('data-id'),'mid-large')
        })
        $('.delete_data').click(function(){
            _conf("هل أنت متأكد من حذف هذا الباص بشكل دائم؟","delete_bus",[$(this).attr('data-id')])
        })
        $('.table td, .table th').addClass('py-1 px-2 align-middle')
        $('.table').dataTable({
            responsive: true,
            autoWidth: false,
            scrollX: true,
            columnDefs: [
                { orderable: false, targets: 6 },
                { responsivePriority: 1, targets: 0 }, // Priority for ID column
                { responsivePriority: 2, targets: 2 }, // Priority for bus number
                { responsivePriority: 3, targets: 3 }, // Priority for plate number
                { responsivePriority: 4, targets: 4 }, // Priority for model
                { responsivePriority: 5, targets: 5 }, // Priority for status
                { responsivePriority: 6, targets: 6 }  // Priority for actions
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            }
        });
    })
    function delete_bus($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_bus",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("حدث خطأ.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("حدث خطأ.",'error');
                    end_loader();
                }
            }
        })
    }
 </script>