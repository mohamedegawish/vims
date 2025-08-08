<style>
    .img-thumb-path{
        height:100px;
        width:80px;
        object-fit:scale-down;
        object-position:center center;
    }
</style>
<div class="card card-outline card-primary rounded-0 shadow">
	<div class="card-header">
		<h3 class="card-title">قائمة التأمينات</h3>
		<?php if($_settings->userdata('type') == 1): ?>
		<div class="card-tools">
			<a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span>  إضافة تأمين جديد</a>
		</div>
		<?php endif; ?>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-hover table-striped">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="15%">
					<col width="30%">
					<col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr class="bg-gradient-primary text-light">
						<th>#</th>
						<th>تاريخ التسجيل</th>
						<th>كود المرجع</th>
						<th>العميل</th>
						<th>رقم تسجيل المركبة</th>
						<th>الحالة</th>
						<th>الإجراء</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$i = 1;
						$clients = $conn->query("SELECT *,CONCAT(code,' - ',lastname,', ', firstname,' ', middlename) as fullname FROM client_list where id in (SELECT client_id from `insurance_list`) ");
						$client_arr = array_column($clients->fetch_all(MYSQLI_ASSOC),'fullname','id');
						$clients = $conn->query("SELECT *,CONCAT(code,' - ',lastname,', ', firstname,' ', middlename) as fullname FROM client_list where id in (SELECT client_id from `insurance_list`) ");
						$client_arr = array_column($clients->fetch_all(MYSQLI_ASSOC),'fullname','id');
						$qry = $conn->query("SELECT * from `insurance_list` order by date(registration_date) asc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td class=""><?php echo date("Y-m-d H:i",strtotime($row['registration_date'])) ?></td>
							<td class=""><p class="m-0 truncate-1"><?php echo $row['code'] ?></p></td>
							<td class=""><p class="m-0 truncate-1"><?php echo ucwords(isset($client_arr[$row['client_id']]) ? $client_arr[$row['client_id']] : '') ?></p></td>
							<td class=""><p class="m-0 truncate-1"><?php echo $row['registration_no'] ?></p></td>
							<td class="text-center">
								<?php 
                                    $row['status'] = isset($row['status']) ? $row['status'] : '';
                                    if(isset($row['expiration_date']) && strtotime($row['expiration_date']) < time()):
                                        echo '<span class="rounded-pill badge badge-danger bg-gradient-danger px-3">منتهي</span>';
                                    else:
                                        switch ($row['status']){
                                            case 1:
                                                echo '<span class="rounded-pill badge badge-success bg-gradient-teal px-3">نشط</span>';
                                                break;
                                            case 0:
                                                echo '<span class="rounded-pill badge badge-danger bg-gradient-danger px-3">غير نشط</span>';
                                                break;
                                            default:
                                                echo '<span class="rounded-pill badge badge-light bg-gradient-light border px-3">غير متاح</span>';
                                                break;
                                        }
                                    endif;
								?>
							</td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		إجراء
				                    <span class="sr-only">تبديل القائمة المنسدلة</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
								  	<a class="dropdown-item" href="./?page=insurances/view_insurance&id=<?= $row['id'] ?>" data-id ="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> عرض</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> تعديل</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> حذف</a>
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
			uni_modal("إضافة تأمين جديد","insurances/manage_insurance.php",'mid-large')
		})
		$('.view_data').click(function(){
			uni_modal("تفاصيل التأمين","insurances/view_insurance.php?id="+$(this).attr('data-id'),'mid-large')
		})
        $('.edit_data').click(function(){
			uni_modal("تحديث تفاصيل التأمين","insurances/manage_insurance.php?id="+$(this).attr('data-id'),'mid-large')
		})
		$('.delete_data').click(function(){
			_conf("هل أنت متأكد من حذف هذا التأمين بشكل دائم؟","delete_insurance",[$(this).attr('data-id')])
		})
		$('.table td, .table th').addClass('py-1 px-2 align-middle')
		$('.table').dataTable({
            columnDefs: [
                { orderable: false, targets: 5 }
            ],
        });
	})
	function delete_insurance($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_insurance",
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