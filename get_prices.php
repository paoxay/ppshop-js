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

// ==========================================
// ⚙️ ຕັ້ງຄ່າຄ່າທຳນຽມ (Config)
// ==========================================
$fee_percent = 60; // ໃສ່ເປີເຊັນທີ່ຕ້ອງການບວກເພີ່ມສຳລັບບັດເຕີມເງິນ (ຕົວຢ່າງ: 20%)

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
    $rawAmount = $row['amount'];

    // --- ຄິດໄລ່ລາຄາໂອນ (Normal) ---
    // ປັດຂຶ້ນເປັນຫຼັກ 1,000
    $roundedNormal = ceil($rawAmount / 1000) * 1000;
    $priceNormal = number_format($roundedNormal); 

    // --- ຄິດໄລ່ລາຄາບັດ (Card) ---
    // ເອົາລາຄາໂອນ ມາບວກເປີເຊັນເພີ່ມ ($fee_percent)
    $cardAmount = $roundedNormal * (1 + ($fee_percent / 100));
    // ປັດຂຶ້ນເປັນຫຼັກ 1,000 ອີກຄັ້ງ
    $roundedCard = ceil($cardAmount / 1000) * 1000;
    $priceCard = number_format($roundedCard);

    if (!isset($groupedData[$gameName])) {
        // ແຍກ array ເກັບສອງແບບ
        $groupedData[$gameName] = [
            'normal' => [],
            'card' => []
        ];
    }

    // ເພີ່ມຂໍ້ມູນ (ໃຊ້ Format : ແລະ ₭ ຕາມທີ່ຕ້ອງການ)
    $groupedData[$gameName]['normal'][] = "   {$packageName} : {$priceNormal} ₭";
    $groupedData[$gameName]['card'][]   = "   {$packageName} : {$priceCard} ₭";
}

// 5. ສ້າງ JSON ຜົນລັບ
$finalOutput = [];

if (empty($groupedData)) {
    $finalOutput = ["status" => "error", "message" => "ບໍ່ພົບຂໍ້ມູນ"];
} else {
    foreach ($groupedData as $name => $types) {
        
        // --- ສ່ວນທີ 1: ລາຄາໂອນ ---
        $normalItems = $types['normal'];
        array_unshift($normalItems, "\n*{$name}*"); // ໃສ່ຫົວຂໍ້
        $blockNormal = implode("\n", $normalItems);

        // --- ສ່ວນທີ 2: ລາຄາບັດ ---
        $cardItems = $types['card'];
        // ໃສ່ຫົວຂໍ້ "ລາຄາບັດເຕິມເງິນ" ແລະ ຊື່ເກມ
        $headerCard = "\n    ລາຄາບັດເຕິມເງິນ\n* {$name} *"; 
        $blockCard = implode("\n", $cardItems);

        // ລວມທັງໝົດເປັນກ້ອນດຽວ
        $fullText = $blockNormal . "\n" . $headerCard . "\n" . $blockCard;

        $finalOutput[] = [
            "game" => $name,
            "items" => [ $fullText ] 
        ];
    }
}

// ສະແດງຜົນ
echo json_encode($finalOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>