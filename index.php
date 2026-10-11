<?php
// ========================================================
// 1. الاتصال بقاعدة البيانات والتهيئة التلقائية
// ========================================================
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "store_db";

// إنشاء الاتصال بالسيرفر
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("فشل الاتصال بالسيرفر: " . mysqli_connect_error());
}

// إنشاء قاعدة البيانات تلقائياً وتحديدها
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, $db_name);

// إنشاء جدول المنتجات إن لم يكن موجوداً
$createTableQuery = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($conn, $createTableQuery);

// التأكد من وجود عمود الصورة image في حال كان الجدول موجوداً مسبقاً
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL");
}

// التأكد من وجود مجلد رفع الصور
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ========================================================
// 2. إدارة العمليات (إضافة - تعديل السعر - حذف)
// ========================================================

// [أولاً]: إضافة منتج جديد مع صورة
if (isset($_POST['add_product'])) {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $price = (float)$_POST['price'];
    $image_name = null;

    // معالجة رفع صورة المنتج إن وُجدت
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_exts)) {
            $image_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        }
    }

    if (!empty($name) && $price > 0) {
        $insertQuery = "INSERT INTO products (name, price, image) VALUES ('$name', '$price', " . ($image_name ? "'$image_name'" : "NULL") . ")";
        mysqli_query($conn, $insertQuery);
        header("Location: index.php?msg=added");
        exit();
    }
}

// [ثانياً]: تعديل سعر المنتج
if (isset($_POST['update_price'])) {
    $id        = (int)$_POST['id'];
    $new_price = (float)$_POST['new_price'];

    if ($id > 0 && $new_price > 0) {
        mysqli_query($conn, "UPDATE products SET price = '$new_price' WHERE id = $id");
        header("Location: index.php?msg=updated");
        exit();
    }
}

// [ثالثاً]: حذف المنتج مع صورته من السيرفر
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // جلب اسم الصورة لحذفها من المجلد وتوفير المساحة
    $img_res = mysqli_query($conn, "SELECT image FROM products WHERE id = $id");
    if ($img_row = mysqli_fetch_assoc($img_res)) {
        if (!empty($img_row['image']) && file_exists($upload_dir . $img_row['image'])) {
            unlink($upload_dir . $img_row['image']);
        }
    }

    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header("Location: index.php?msg=deleted");
    exit();
}

// [رابعاً]: جلب المنتج المطلوب تعديل سعره (إذا ضغط المستخدم على زر تعديل)
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM products WHERE id = $edit_id");
    $edit_product = mysqli_fetch_assoc($edit_query);
}

// [خامساً]: جلب قائمة كل المنتجات للعرض
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر المنتجات الذكي | Smart Store</title>
    <!-- خط تجوال الأنيق للواجهة العربية -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========================================================
           لوحة الألوان الدافئة والمميزة (Warm & Cozy Palette)
           ألوان كراميل وترابية مريحة جداً للعين بلمسة متجر حقيقي
           ======================================================== */
        :root {
            --primary: #b85d19;          /* كراميل دافئ مميز */
            --primary-hover: #9c4c11;
            --primary-soft: #fbf1e7;     /* خلفية ناعمة دافئة */

            --amber: #d48b38;            /* عنبر دافئ للأزرار والمؤشرات */
            --amber-hover: #ba7428;
            --amber-soft: #fef7ee;

            --danger: #a84343;           /* أحمر قرميدي ناعم ومريح */
            --danger-hover: #8f3434;
            --danger-soft: #fbf0f0;

            --bg: #fcf9f5;               /* خلفية كريمية دافئة تمتص الإضاءة وتريح العين */
            --card-bg: #ffffff;
            --text-main: #2b2118;        /* بنّي إسبريسو داكن مريح للقراءة */
            --text-muted: #736457;       /* موكا ناعم للنصوص الثانوية */
            --border: #ece2d5;           /* حدود دافئة مريحة */
            --border-focus: #c26f2e;

            --radius: 14px;
            --radius-sm: 8px;
            --shadow: 0 4px 20px rgba(70, 45, 20, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            padding: 25px 15px;
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* شريط الروابط السريع بالأعلى */
        .top-bar {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 12px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: var(--shadow);
        }

        .top-bar-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .links-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pill-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            background: var(--bg);
            color: var(--text-main);
            border: 1px solid var(--border);
        }

        .pill-link:hover {
            background: var(--primary-soft);
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        /* رأس الصفحة */
        .store-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .store-header h1 {
            font-size: 28px;
            color: var(--text-main);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .store-header p {
            color: var(--text-muted);
            font-size: 15px;
            margin-top: 6px;
        }

        /* رسائل الإشعارات */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
        }

        .alert-success { background: var(--primary-soft); color: var(--primary); border: 1px solid #ebd0ba; }
        .alert-warning { background: var(--amber-soft); color: var(--amber); border: 1px solid #f8dfbf; }
        .alert-danger  { background: var(--danger-soft); color: var(--danger); border: 1px solid #f1cfcf; }

        /* البطاقات الرئيسية */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .card-header {
            font-size: 17.5px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--text-main);
            border-bottom: 1px dashed var(--border);
            padding-bottom: 12px;
        }

        /* تنسيق النماذج */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted);
        }

        input[type="text"],
        input[type="number"],
        input[type="file"] {
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14.5px;
            background: #fffdfa;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
            width: 100%;
        }

        input[type="file"] {
            padding: 7px 12px;
            cursor: pointer;
            font-size: 13px;
        }

        input:focus {
            background: #ffffff;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(184, 93, 25, 0.12);
        }

        /* الأزرار */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            gap: 6px;
        }

        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .btn-amber { background: var(--amber); color: #fff; }
        .btn-amber:hover { background: var(--amber-hover); transform: translateY(-1px); }

        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: var(--danger-hover); transform: translateY(-1px); }

        .btn-secondary { background: #f0e9df; color: var(--text-muted); }
        .btn-secondary:hover { background: #e5dccf; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12.5px;
        }

        /* جدول المنتجات */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        thead tr {
            background-color: #faf5ee;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            font-weight: 700;
            font-size: 13.5px;
            color: var(--text-muted);
        }

        tbody tr {
            transition: background 0.15s ease;
        }

        tbody tr:hover {
            background-color: #fdfaf6;
        }

        /* صورة المنتج */
        .product-img-thumb {
            width: 54px;
            height: 54px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--border);
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: block;
            background: #f7f1e8;
        }

        .no-img-thumb {
            width: 54px;
            height: 54px;
            border-radius: 10px;
            background: #f7f1e8;
            color: #b09f8f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border: 1px dashed var(--border);
        }

        /* بادج السعر */
        .price-tag {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 14px;
            display: inline-block;
            border: 1px solid #eddcd0;
        }

        /* كود الـ QR */
        .qr-box {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            background: #ffffff;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .qr-box:hover {
            transform: scale(1.05);
            border-color: var(--primary);
        }

        .qr-img {
            width: 65px;
            height: 65px;
            display: block;
        }

        .qr-hint {
            font-size: 10.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* الأزرار في الجدول */
        .actions-group {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        /* نافذة تكبير الباركود المنبثقة */
        .qr-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(43, 33, 24, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .qr-modal-content {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 28px;
            text-align: center;
            max-width: 340px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid var(--border);
            animation: zoomIn 0.25s ease;
        }

        .qr-modal-img {
            width: 190px;
            height: 190px;
            margin: 15px auto;
            display: block;
            border: 1px solid var(--border);
            padding: 8px;
            border-radius: 12px;
            background: #fff;
        }

        .close-modal-btn {
            margin-top: 15px;
            width: 100%;
        }

        .empty-placeholder {
            text-align: center;
            padding: 45px 15px;
            color: var(--text-muted);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- شريط الروابط السريع والمميز -->
    <div class="top-bar">
        <span class="top-bar-title">🔗 روابط المشروع وقاعدة البيانات:</span>
        <div class="links-group">
            <a href="index.php" class="pill-link" title="تحديث أو فتح الصفحة">
                🌐 رابط المشروع (smart-store)
            </a>
            <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=store_db" class="pill-link" target="_blank" title="لوحة تحكم phpMyAdmin">
                🗄️ قاعدة البيانات (store_db)
            </a>
            <a href="database.sql" download class="pill-link" title="تحميل ملف SQL لرفعه على GitHub">
                💾 تحميل database.sql
            </a>
        </div>
    </div>

    <!-- رأس المتجر -->
    <div class="store-header">
        <h1>☕ متجر المنتجات الذكي</h1>
        <p>كل منتج له صورة مخصصة ورمز QR Code فريد يعرض سعره عند مسحه بالجوال</p>
    </div>

    <!-- تنبيهات العمليات -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">✨ تم إضافة المنتج بنجاح وتوليد باركود السعر!</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-warning">✏️ تم تحديث سعر المنتج بنجاح!</div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-danger">🗑️ تم حذف المنتج والصورة التابعة له بنجاح!</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- نموذج تعديل السعر (يظهر عند الضغط على زر تعديل) -->
    <?php if ($edit_product): ?>
        <div class="card" style="border: 1.5px solid var(--amber); background-color: #fffdfa;">
            <div class="card-header" style="color: var(--amber);">
                <span>✏️ تعديل سعر المنتج: <?= htmlspecialchars($edit_product['name']) ?></span>
                <span class="price-tag">$<?= number_format($edit_product['price'], 2) ?></span>
            </div>
            <form method="POST" action="index.php">
                <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>اسم المنتج:</label>
                        <input type="text" value="<?= htmlspecialchars($edit_product['name']) ?>" disabled style="background:#f5efe6; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>السعر الجديد ($):</label>
                        <input type="number" step="0.01" name="new_price" value="<?= $edit_product['price'] ?>" required autofocus>
                    </div>
                    <div class="form-group" style="display: flex; flex-direction: row; gap: 8px;">
                        <button type="submit" name="update_price" class="btn btn-amber" style="flex: 1;">حفظ السعر</button>
                        <a href="index.php" class="btn btn-secondary">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- نموذج إضافة منتج جديد مع صورة -->
        <div class="card">
            <div class="card-header">
                <span>➕ إضافة منتج جديد إلى المتجر</span>
            </div>
            <form method="POST" action="index.php" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>اسم المنتج:</label>
                        <input type="text" name="name" placeholder="مثال: حبوب قهوة كولومبية..." required>
                    </div>
                    <div class="form-group">
                        <label>السعر ($):</label>
                        <input type="number" step="0.01" name="price" placeholder="مثال: 24.50" required>
                    </div>
                    <div class="form-group">
                        <label>صورة المنتج (اختياري):</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_product" class="btn btn-primary" style="height: 42px; width: 100%;">
                            إضافة المنتج والباركود
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- قائمة وجدول المنتجات -->
    <div class="card">
        <div class="card-header">
            <span>📋 قائمة المنتجات المعروضة</span>
            <span style="font-size: 13.5px; font-weight: 500; color: var(--text-muted);">
                إجمالي المنتجات: <?= mysqli_num_rows($products) ?>
            </span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 70px;">الصورة</th>
                        <th>اسم المنتج</th>
                        <th style="width: 130px;">السعر</th>
                        <th style="width: 120px; text-align: center;">رمز الـ QR</th>
                        <th style="width: 150px; text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($products) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($products)): ?>
                            <?php
                                // نص الباركود: يظهر مباشرة على شاشة الهاتف عند مسحه بالكاميرا
                                $qr_text = "🛍️ متجر المنتجات الذكي\n━━━━━━━━━━━━━━━\n📦 المنتج: " . $row['name'] . "\n💰 السعر: " . number_format($row['price'], 2) . " $\n🏷️ رقم الكود: #" . $row['id'];
                                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_text);
                            ?>
                            <tr>
                                <td style="color: var(--text-muted);"><?= $row['id'] ?></td>
                                
                                <!-- صورة المنتج -->
                                <td>
                                    <?php if (!empty($row['image']) && file_exists('uploads/' . $row['image'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="product-img-thumb">
                                    <?php else: ?>
                                        <div class="no-img-thumb" title="لا توجد صورة">📦</div>
                                    <?php endif; ?>
                                </td>

                                <!-- اسم المنتج -->
                                <td>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                </td>

                                <!-- السعر -->
                                <td>
                                    <span class="price-tag">$<?= number_format($row['price'], 2) ?></span>
                                </td>

                                <!-- رمز QR Code للمنتج -->
                                <td style="text-align: center;">
                                    <div class="qr-box" onclick="openQrModal('<?= htmlspecialchars($row['name']) ?>', '$<?= number_format($row['price'], 2) ?>', '<?= $qr_url ?>')" title="انقر لتكبير الـ QR ومسحه">
                                        <img src="<?= $qr_url ?>" alt="QR Code" class="qr-img">
                                        <span class="qr-hint">📱 امسح السعر</span>
                                    </div>
                                </td>

                                <!-- الإجراءات (تعديل السعر - حذف) -->
                                <td style="text-align: center;">
                                    <div class="actions-group" style="justify-content: center;">
                                        <!-- زر تعديل السعر -->
                                        <a href="index.php?edit=<?= $row['id'] ?>" class="btn btn-amber btn-sm" title="تعديل السعر">
                                            ✏️ تعديل
                                        </a>

                                        <!-- زر الحذف -->
                                        <a href="index.php?delete=<?= $row['id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('هل تريد بالتأكيد حذف المنتج (<?= htmlspecialchars($row['name']) ?>) مع صورته؟');"
                                           title="حذف المنتج">
                                            🗑️ حذف
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-placeholder">
                                    <p style="font-size: 16px; margin-bottom: 6px;">لا توجد أي منتجات في المتجر حتى الآن 📦</p>
                                    <p style="font-size: 13.5px;">أضف أول منتج مع صورته من النموذج بالأعلى ليتم إنشاء الباركود الخاص به فوراً.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- نافذة تكبير الباركود المنبثقة لمسحه بالجوال بسهولة -->
<div class="qr-modal" id="qrModal" onclick="closeQrModal(event)">
    <div class="qr-modal-content">
        <h3 id="modalProductName" style="font-size: 17px; margin-bottom: 4px; color: var(--text-main);"></h3>
        <p style="font-size: 14px; color: var(--primary); font-weight: 700;" id="modalProductPrice"></p>
        <img id="modalQrImg" src="" alt="QR الكبير" class="qr-modal-img">
        <p style="font-size: 12.5px; color: var(--text-muted);">وجه كاميرا هاتفك إلى الرمز أعلاه لعرض السعر والبيانات فوراً 📷</p>
        <button class="btn btn-secondary close-modal-btn" onclick="document.getElementById('qrModal').style.display='none'">إغلاق النافذة</button>
    </div>
</div>

<script>
    // فتح نافذة تكبير الـ QR لمسحه بالهاتف براحة
    function openQrModal(name, price, qrUrl) {
        document.getElementById('modalProductName').innerText = name;
        document.getElementById('modalProductPrice').innerText = 'السعر: ' + price;
        document.getElementById('modalQrImg').src = qrUrl;
        document.getElementById('qrModal').style.display = 'flex';
    }

    // إغلاق النافذة عند الضغط خارج المربع
    function closeQrModal(e) {
        if (e.target.id === 'qrModal') {
            document.getElementById('qrModal').style.display = 'none';
        }
    }
</script>

</body>
</html>