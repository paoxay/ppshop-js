<?php
// order_api.php
header('Content-Type: application/json; charset=utf-8');
$host = 'localhost'; $dbname = 'ppshop-js'; $username = 'root'; $password = ''; // ⚠️ ແກ້ໄຂ DB ບ່ອນນີ້

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Connect Error']); exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ດຶງລາຍຊື່ເກມ (Unique)
if ($action == 'get_games') {
    $stmt = $pdo->query("SELECT DISTINCT game_name FROM game_packages ORDER BY game_name ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

// 2. ດຶງແພັກເກັດຕາມຊື່ເກມ
if ($action == 'get_packages' && isset($_GET['game'])) {
    $game = $_GET['game'];
    $stmt = $pdo->prepare("SELECT id, package_name, custom_name, amount FROM game_packages WHERE game_name = ? ORDER BY sort_order ASC, amount ASC");
    $stmt->execute([$game]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ປັບແຕ່ງຂໍ້ມູນກ່ອນສົ່ງ (ຄຳນວນລາຄາ)
    foreach ($packages as &$pkg) {
        $displayName = !empty($pkg['custom_name']) ? $pkg['custom_name'] : $pkg['package_name'];
        $pkg['display'] = $displayName . " (₭" . number_format($pkg['amount']) . ")";
    }
    echo json_encode($packages);
    exit;
}

// 3. ບັນທຶກອໍເດີ້
if ($action == 'save_order') {
    try {
        $game_name = $_POST['game_name'];
        $package_id = $_POST['package_id'];
        $uid = $_POST['uid'];
        $slip_no = $_POST['slip_no'];
        
        // ດຶງຂໍ້ມູນແພັກເກັດເພື່ອເອົາລາຄາ ແລະ ຊື່
        $stmtPkg = $pdo->prepare("SELECT package_name, custom_name, amount FROM game_packages WHERE id = ?");
        $stmtPkg->execute([$package_id]);
        $pkgData = $stmtPkg->fetch(PDO::FETCH_ASSOC);

        if (!$pkgData) throw new Exception("ບໍ່ພົບຂໍ້ມູນແພັກເກັດ");

        $finalPackName = !empty($pkgData['custom_name']) ? $pkgData['custom_name'] : $pkgData['package_name'];
        $price = $pkgData['amount'];

        // ຈັດການອັບໂຫລດຮູບ
        $slipPath = null;
        if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
            $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($ext), $allowed)) throw new Exception("ອະນຸຍາດສະເພາະໄຟລ໌ຮູບພາບເທົ່ານັ້ນ");
            
            $newName = "slip_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $uploadDir . $newName)) {
                $slipPath = $uploadDir . $newName;
            }
        }

        // ບັນທຶກລົງ DB
        $sql = "INSERT INTO orders (game_name, package_name, price, uid, slip_no, slip_image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$game_name, $finalPackName, $price, $uid, $slip_no, $slipPath]);

        echo json_encode(['success' => true, 'message' => 'ບັນທຶກຂໍ້ມູນສຳເລັດ!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 4. ດຶງປະຫວັດອໍເດີ້ (History)
if ($action == 'get_history') {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ຄຳນວນຍອດລວມ (ສະເພາະທີ່ບໍ່ໄດ້ຍົກເລີກ)
    $stmtTotal = $pdo->query("SELECT SUM(price) as total FROM orders WHERE status != 'cancelled'");
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    echo json_encode(['orders' => $orders, 'total_revenue' => $total]);
    exit;
}

// 5. ລົບອໍເດີ້
if ($action == 'delete_order') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ລົບບໍ່ໄດ້']);
    }
    exit;
}

// 6. ແກ້ໄຂສະຖານະ ແລະ ຂໍ້ມູນບາງຢ່າງ
if ($action == 'update_order') {
    $id = $_POST['id'];
    $uid = $_POST['uid'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET uid = ?, price = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$uid, $price, $status, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ອັບເດດບໍ່ໄດ້']);
    }
    exit;
}
?>