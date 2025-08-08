<style>
  .sidebar a.nav-link.active{
    color:#fff !important;
    background-color: rgba(255,255,255,0.1) !important;
  }
  /* تحسين الخط العربي */
  .nav-sidebar, .nav-link, .brand-text {
    font-family: 'Tajawal', 'Segoe UI', sans-serif !important;
    font-weight: 500;
    font-size: 0.95rem !important;
  }
  .nav-header {
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    padding-right: 10px;
  }
  .nav-link {
    padding: 0.7rem 1rem !important;
  }
  .nav-link p {
    margin-right: 10px !important;
    display: inline-block;
  }
  .nav-icon {
    font-size: 1.1rem !important;
    margin-left: 5px !important;
  }
</style>
<!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand bg-gradient-navy">
        <!-- Brand Logo -->
        <a href="<?php echo base_url ?>admin" class="brand-link bg-transparent text-sm border-0 shadow-sm">
        <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="شعار النظام" class="brand-image img-circle elevation-3 bg-black" style="width: 1.8rem;height: 1.8rem;max-height: unset;object-fit:scale-down;object-position:center center">
        <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
        </a>
        <!-- Sidebar -->
        <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
          <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
          </div>
          <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
          </div>
          <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
          <div class="os-padding">
            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
              <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
                <!-- Sidebar user panel (optional) -->
                <div class="clearfix"></div>
                <!-- Sidebar Menu -->
                <nav class="mt-4">
                   <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item dropdown">
                      <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          لوحة التحكم
                        </p>
                      </a>
                    </li>
                    <!-- في قسم القائمة الرئيسية -->
<li class="nav-header">إدارة النقل</li>
<li class="nav-item">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-bus"></i>
        <p>
            إدارة الباصات
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview" style="display: none;">
        <li class="nav-item">
            <a href="./?page=buses/list" class="nav-link tree-item nav-buses_list">
                <i class="far fa-circle nav-icon"></i>
                <p>قائمة الباصات</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/documents" class="nav-link tree-item nav-buses_documents">
                <i class="far fa-circle nav-icon"></i>
                <p>الوثائق الرسمية</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/contracts" class="nav-link tree-item nav-buses_contracts">
                <i class="far fa-circle nav-icon"></i>
                <p>إدارة العقود</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/maintenance" class="nav-link tree-item nav-buses_maintenance">
                <i class="far fa-circle nav-icon"></i>
                <p>سجل الصيانة</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/fuel" class="nav-link tree-item nav-buses_fuel">
                <i class="far fa-circle nav-icon"></i>
                <p>سجل الوقود</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/daily_status" class="nav-link tree-item nav-buses_daily_status">
                <i class="far fa-circle nav-icon"></i>
                <p>الحالة اليومية</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=buses/reports" class="nav-link tree-item nav-buses_reports">
                <i class="far fa-circle nav-icon"></i>
                <p>التقارير</p>
            </a>
        </li>
    </ul>
</li>
                    <!-- قسم إدارة السائقين الجديد -->
<li class="nav-item">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-user-shield"></i>
        <p>
            إدارة السائقين
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview" style="display: none;">
        <li class="nav-item">
            <a href="./?page=drivers/list" class="nav-link tree-item nav-drivers_list">
                <i class="far fa-circle nav-icon"></i>
                <p>قائمة السائقين</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/profiles" class="nav-link tree-item nav-drivers_profiles">
                <i class="far fa-circle nav-icon"></i>
                <p>الملفات الشخصية</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/custody" class="nav-link tree-item nav-drivers_custody">
                <i class="far fa-circle nav-icon"></i>
                <p>إدارة العهدة</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/salaries" class="nav-link tree-item nav-drivers_salaries">
                <i class="far fa-circle nav-icon"></i>
                <p>أجور السائقين</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/daily_operations" class="nav-link tree-item nav-drivers_daily_operations">
                <i class="far fa-circle nav-icon"></i>
                <p>التشغيل اليومي</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/fuel_logs" class="nav-link tree-item nav-drivers_fuel_logs">
                <i class="far fa-circle nav-icon"></i>
                <p>سجل التموين</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="./?page=drivers/maintenance_requests" class="nav-link tree-item nav-drivers_maintenance_requests">
                <i class="far fa-circle nav-icon"></i>
                <p>طلبات الصيانة</p>
            </a>
        </li>
    </ul>
</li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=clients" class="nav-link nav-clients">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>
                          قائمة العملاء
                        </p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=insurances" class="nav-link nav-insurances">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>
                          التأمينات
                        </p>
                      </a>
                    </li>
                    <li class="nav-header">التقارير</li>
                      <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=reports/date_wise_transaction" class="nav-link nav-reports_date_wise_transaction">
                          <i class="nav-icon fas fa-circle"></i>
                          <p>
                            المعاملات حسب التاريخ
                          </p>
                        </a>
                      </li>
                    <li class="nav-header">الإعدادات</li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=categories" class="nav-link nav-categories">
                        <i class="nav-icon fas fa-list"></i>
                        <p>
                         قائمة الفئات
                        </p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="<?php echo base_url ?>admin/?page=policies" class="nav-link nav-policies">
                        <i class="nav-icon fas fa-table"></i>
                        <p>
                          قائمة السياسات
                        </p>
                      </a>
                    </li>
                    <?php if($_settings->userdata('type') == 1): ?>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>
                          إدارة المستخدمين
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                          إعدادات النظام
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>
                  </ul>
                </nav>
                <!-- /.sidebar-menu -->
              </div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar-corner"></div>
        </div>
        <!-- /.sidebar -->
      </aside>
      <script>
        var page;
    $(document).ready(function(){
      page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
      page = page.replace(/\//gi,'_');

      if($('.nav-link.nav-'+page).length > 0){
             $('.nav-link.nav-'+page).addClass('active')
        if($('.nav-link.nav-'+page).hasClass('tree-item') == true){
            $('.nav-link.nav-'+page).closest('.nav-treeview').siblings('a').addClass('active')
          $('.nav-link.nav-'+page).closest('.nav-treeview').parent().addClass('menu-open')
        }
        if($('.nav-link.nav-'+page).hasClass('nav-is-tree') == true){
          $('.nav-link.nav-'+page).parent().addClass('menu-open')
        }
      }
      
      $('#receive-nav').click(function(){
        $('#uni_modal').on('shown.bs.modal',function(){
          $('#find-transaction [name="tracking_code"]').focus();
        })
        uni_modal("Enter Tracking Number","transaction/find_transaction.php");
      })
    })
  </script>