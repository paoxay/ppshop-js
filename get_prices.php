<?php
// 1. ຕັ້ງຄ່າ Header (No Cache & JSON)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json; charset=utf-8');

// 2. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
// ⚠️⚠️ ແກ້ໄຂຂໍ້ມູນ DB ຂອງເຈົ້າຢູ່ບ່ອນນີ້ ⚠️⚠️
$host = 'localhost';
$dbname = 'ppshop-js'; 
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "price_text" => "Database Error"]);
    exit;
}

// 3. ຮັບຄ່າຄົ້ນຫາ (ຈາກ URL ?game=...)
$searchGame = isset($_GET['game']) ? trim($_GET['game']) : '';

// 4. Logic ຄົ້ນຫາ (Smart Search)
// ຕັດຍະຫວ່າງ, ເຄື່ອງໝາຍ +, ແລະ %20 ອອກໃຫ້ໝົດ ເພື່ອໃຫ້ທຽບກັນໄດ້ 100%
$cleanSearch = str_replace([' ', '+', '%20'], '', $searchGame);

if ($cleanSearch) {
    // ຄົ້ນຫາໂດຍການຕັດຍະຫວ່າງໃນ DB ອອກຄືກັນ ແລ້ວທຽບກັນ
    $sql = "SELECT * FROM game_packages 
            WHERE REPLACE(REPLACE(game_name, ' ', ''), '+', '') LIKE ? 
            ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = ["%$cleanSearch%"];
} else {
    // ຖ້າບໍ່ພິມຫຍັງມາ ໃຫ້ດຶງໝົດ (ຫຼືຈະປ່ຽນເປັນບໍ່ສະແດງກໍໄດ້)
    $sql = "SELECT * FROM game_packages ORDER BY game_name ASC, sort_order ASC, amount ASC";
    $params = [];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. ປະມວນຜົນຂໍ້ມູນ
$finalTextList = [];

if (empty($results)) {
    // ກໍລະນີບໍ່ພົບຂໍ້ມູນ
    echo json_encode([
        "success" => false,
        "game_name" => "Not Found",
        "price_text" => "❌ ບໍ່ພົບຂໍ້ມູນເກມທີ່ຄົ້ນຫາ: " . htmlspecialchars($searchGame)
    ], JSON_UNESCAPED_UNICODE);
} else {
    
    $groupedData = [];
    foreach ($results as $row) {
        $gameName = trim($row['game_name']);
        
        // 🔥 Logic ເລືອກຊື່ (Custom Name vs Original Name)
        // ຖ້າມີ custom_name (ທີ່ແກ້ໃນ UI) ໃຫ້ໃຊ້ໂຕນັ້ນ, ຖ້າບໍ່ມີ ໃຫ້ໃຊ້ package_name ເດີມ
        $displayName = !empty($row['custom_name']) ? $row['custom_name'] : $row['package_name'];
        
        // 🔥 Logic ປັດເສດລາຄາ (Round Up 1000)
        $rawAmount = $row['amount'];
        $roundedAmount = ceil($rawAmount / 1000) * 1000;
        $price = number_format($roundedAmount);

        if (!isset($groupedData[$gameName])) {
            $groupedData[$gameName] = [];
        }

        // 🔥 Format ຂໍ້ຄວາມສຳລັບ Bot (Minimal Style)
        // 💎 ຊື່ແພັກເກັດ : ລາຄາ ₭
        $groupedData[$gameName][] = "💎 {$displayName} : {$price}₭";
    }

    // ລວມຂໍ້ຄວາມທຸກເກມທີ່ຄົ້ນຫາເຈິ
    foreach ($groupedData as $name => $items) {
        $header = "🎮 {$name}"; // ໃສ່ Emoji ເກມ
        $body = implode("\n", $items); // ລວມລາຍການດ້ວຍການລົງແຖວ
        $finalTextList[] = $header . "\n" . $body;
    }
    
    // ຖ້າເຈິຫຼາຍເກມ ໃຫ້ຂັ້ນດ້ວຍເສັ້ນປະ
    $msg = implode("\n\n➖➖➖➖➖➖➖➖➖➖\n\n", $finalTextList);

    // 6. ສົ່ງອອກ JSON (Object ດຽວ ງ່າຍສຳລັບ Botcake)
    echo json_encode([
        "success" => true,
        "game_name" => $searchGame, // ສົ່ງຄຳຄົ້ນຫາກັບໄປ
        "price_text" => $msg        // ✅ ເອົາໂຕນີ້ໄປໃຊ້ໃນ Bot
    ], JSON_UNESCAPED_UNICODE);
}
?>