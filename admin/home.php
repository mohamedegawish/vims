<style>
    /* تحسينات عامة للصفحة */
    body {
        font-family: 'Tajawal', sans-serif;
    }
    h1 {
        font-weight: 700;
        text-align: right;
        margin-bottom: 1.5rem;
    }
    .info-box {
        border-radius: 10px;
        margin-bottom: 15px;
        text-align: right;
        transition: all 0.3s ease;
    }
    .info-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .info-box-icon {
        border-radius: 10px 0 0 10px !important;
    }
    .info-box-content {
        padding: 10px;
    }
    .info-box-text {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .info-box-number {
        font-size: 1.8rem;
        font-weight: 700;
        margin-top: 5px;
    }
    #website-cover {
        width: 100%;
        height: 30em;
        object-fit: cover;
        object-position: center center;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    hr.border-primary {
        border-top: 2px solid #007bff;
        opacity: 1;
    }
</style>

<h1>مرحباً بكم في <?php echo $_settings->info('name') ?></h1>
<hr class="border-primary">

<div class="row">
    <!-- بطاقة الفئات -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-3">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-th-list"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">إجمالي الفئات</span>
                <span class="info-box-number text-right">
                    <?php 
                        echo $conn->query("SELECT * FROM `category_list` where delete_flag= 0 and `status` = 1 ")->num_rows;
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- بطاقة السياسات النشطة -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-3">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">السياسات النشطة</span>
                <span class="info-box-number text-right">
                    <?php 
                        echo $conn->query("SELECT * FROM `policy_list` where `status` = 1 ")->num_rows;
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- بطاقة السياسات غير النشطة -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-3">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-maroon elevation-1"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">السياسات غير النشطة</span>
                <span class="info-box-number text-right">
                    <?php 
                        echo $conn->query("SELECT * FROM `policy_list` where `status` = 0 ")->num_rows;
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- بطاقة العملاء -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-3">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-user-tie"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">إجمالي العملاء</span>
                <span class="info-box-number text-right">
                    <?php 
                        echo $conn->query("SELECT * FROM `client_list` ")->num_rows;
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- بطاقة المركبات المؤمنة -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-3">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-car"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">المركبات المؤمنة</span>
                <span class="info-box-number text-right">
                    <?php 
                        echo $conn->query("SELECT * FROM `insurance_list` where `status` = 1 and date(expiration_date) > '".(date("Y-m-d"))."' ")->num_rows;
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- صورة الغلاف -->
<div class="row">
    <div class="col-md-12">
        <img src="<?= validate_image($_settings->info('cover')) ?>" alt="صورة الغلاف" class="img-fluid border w-100" id="website-cover">
    </div>
</div>

<!-- إضافة خط Tajawal -->
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">