<?php
// 1. ຕັ້ງຄ່າການສະແດງຜົນ Error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ຕັ້ງຄ່າ Header
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json; charset=utf-8');

// 2. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
// ⚠️⚠️ ຢ່າລືມໃສ່ລະຫັດຜ່ານ DB ຂອງເຈົ້າຢູ່ບ່ອນນີ້ ⚠️⚠️
$host = 'localhost';
$dbname = 'ppshop-js'; 
$username = 'root';
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "price_text" => "❌ ຕິດຕໍ່ຖານຂໍ້ມູນບໍ່ໄດ້: " . $e->getMessage()]);
    exit;
}

// 3. ຮັບຄ່າຄົ້ນຫາ
$searchGame = isset($_GET['game']) ? trim($_GET['game']) : '';

// 4. Logic ຄົ້ນຫາ
$cleanSearch = str_replace([' ', '+', '%20'], '', $searchGame);

if ($cleanSearch) {
    $sql = "SELECT * FROM game_packages 
            WHERE REPLACE(REPLACE(game_name, ' ', ''), '+', '') LIKE ? 
            ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = ["%$cleanSearch%"];
} else {
    $sql = "SELECT * FROM game_packages ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = [];
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(["success" => false, "price_text" => "❌ SQL Error: " . $e->getMessage()]);
    exit;
}

// 5. ປະມວນຜົນຂໍ້ມູນ
$finalTextList = [];
$finalCardList = []; 

// ⚙️ ຕັ້ງຄ່າເປີເຊັນບວກເພີ່ມສຳລັບບັດເຕີມເງິນ
$percent_add = 60; 

if (empty($results)) {
    echo json_encode([
        "success" => false,
        "game_name" => "Not Found",
        "price_text" => "❌ ບໍ່ພົບຂໍ້ມູນເກມທີ່ຄົ້ນຫາ: " . htmlspecialchars($searchGame)
    ], JSON_UNESCAPED_UNICODE);
} else {
    
    $groupedData = [];
    $groupedDataCard = []; 

    foreach ($results as $row) {
        $gameName = trim($row['game_name']);
        $displayName = !empty($row['custom_name']) ? $row['custom_name'] : $row['package_name'];
        
        // --- 1. ລາຄາປົກກະຕິ (ປັດເສດ 1000) ---
        $rawAmount = $row['amount'];
        $roundedAmount = ceil($rawAmount / 1000) * 1000;
        $price = number_format($roundedAmount);

        // --- 2. ລາຄາບັດ (+60% ແລະ ປັດເສດ 1000) ---
        // ຄຳນວນ: ລາຄາປົກກະຕິ + 60%
        $rawCardAmount = $roundedAmount + ($roundedAmount * ($percent_add / 100));
        
        // 🔥 ສູດປັດເສດໃໝ່: ປັດຂຶ້ນໃຫ້ເຕັມ 1000 (ບໍ່ໃຫ້ມີເສດຮ້ອຍ)
        // ຕົວຢ່າງ: 12,800 -> 13,000
        $cardAmountRounded = ceil($rawCardAmount / 1000) * 1000;
        
        $cardPrice = number_format($cardAmountRounded);

        // ຈັດເກັບຂໍ້ມູນ
        if (!isset($groupedData[$gameName])) {
            $groupedData[$gameName] = [];
        }
        if (!isset($groupedDataCard[$gameName])) {
            $groupedDataCard[$gameName] = [];
        }

        $groupedData[$gameName][] = "💎 {$displayName} : {$price}₭";
        $groupedDataCard[$gameName][] = "💎 {$displayName} : {$cardPrice}₭";
    }

    // ສ້າງຂໍ້ຄວາມ ລາຄາປົກກະຕິ
    foreach ($groupedData as $name => $items) {
        $header = "🎮 {$name}";
        $body = implode("\n", $items);
        $finalTextList[] = $header . "\n" . $body;
    }
    $msgNormal = implode("\n\n➖➖➖➖➖➖➖➖➖➖\n\n", $finalTextList);

    // ສ້າງຂໍ້ຄວາມ ລາຄາບັດ
    foreach ($groupedDataCard as $name => $items) {
        $header = "🎮 {$name}";
        $body = implode("\n", $items);
        $finalCardList[] = $header . "\n" . $body;
    }
    $msgCard = implode("\n\n➖➖➖➖➖➖➖➖➖➖\n\n", $finalCardList);

    // ລວມຂໍ້ຄວາມ
$fullMessage = "🏷️ ປະຈຸບັນ (ລາຄາໂອນ)\n" . $msgNormal . "\n\n💳 ລາຄາບັດເຕີມເງິນ\n" . $msgCard;

    // 6. ສົ່ງອອກ JSON
    echo json_encode([
        "success" => true,
        "game_name" => $searchGame,
        "price_text" => $fullMessage
    ], JSON_UNESCAPED_UNICODE);
}
?>