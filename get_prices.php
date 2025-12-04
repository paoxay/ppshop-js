<?php
header('Content-Type: application/json; charset=utf-8');

// 1. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
$host = 'localhost';
$dbname = 'ppshop-js'; // ⚠️ ຢ່າລືມແກ້ຊື່ DB
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit;
}

// 2. ຮັບຄ່າຄົ້ນຫາ
$searchGame = isset($_GET['game']) ? $_GET['game'] : null;

// 3. ດຶງຂໍ້ມູນ
if ($searchGame) {
    $sql = "SELECT * FROM game_packages 
            WHERE game_name LIKE ? 
            ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = ["%$searchGame%"];
} else {
    $sql = "SELECT * FROM game_packages 
            ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = [];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. ຈັດກຸ່ມຂໍ້ມູນ
$groupedData = [];

foreach ($results as $row) {
    $gameName = $row['game_name'];
    $packageName = $row['package_name'];
    $rawAmount = $row['amount']; // ລາຄາດິບຈາກຖານຂໍ້ມູນ

    // 🔥 ຟັງຊັນປັດເສດຂຶ້ນເປັນຫຼັກ 1,000 (Round Up to nearest 1000)
    // ຕົວຢ່າງ: 1,297,695 -> 1,298,000
    $roundedAmount = ceil($rawAmount / 1000) * 1000;

    // ຈັດ Format ໃສ່ຈຸດ (,)
    $price = number_format($roundedAmount); 

    if (!isset($groupedData[$gameName])) {
        $groupedData[$gameName] = [];
    }

    // ເພີ່ມຂໍ້ມູນ (ຍະຫວ່າງ 3 ບາດ)
    $groupedData[$gameName][] = "   {$packageName} ລາຄາ {$price} ກີບ";
}

// 5. ສ້າງ JSON ຜົນລັບ
$finalOutput = [];

if (empty($groupedData)) {
    $finalOutput = ["status" => "error", "message" => "ບໍ່ພົບຂໍ້ມູນ"];
} else {
    foreach ($groupedData as $name => $items) {
        
        // ໃສ່ຊື່ເກມໄວ້ເທິງສຸດ
        array_unshift($items, "* {$name} *"); 

        // ລວມເປັນກ້ອນດຽວ
        $oneBlockText = implode("\n", $items);

        $finalOutput[] = [
            "game" => $name,
            "items" => [ $oneBlockText ] 
        ];
    }
}

// ສະແດງຜົນ
echo json_encode($finalOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>