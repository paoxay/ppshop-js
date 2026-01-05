<?php
// 1. ຕັ້ງຄ່າໃຫ້ຣັນເບື້ອງຫຼັງ (Background Process)
ignore_user_abort(true); // ໃຫ້ Script ເຮັດວຽກຕໍ່ໄປ ເຖິງວ່າຈະປິດ Browser ແລ້ວ
set_time_limit(0);       // ບໍ່ຈຳກັດເວລາໃນການຣັນ

// 2. ສົ່ງຂໍ້ຄວາມບອກ Browser ວ່າຮັບຄຳສັ່ງແລ້ວ ແລະ ຕັດການເຊື່ອມຕໍ່ທັນທີ
ob_start();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>Updating...</title>
    <style>body{font-family:sans-serif;text-align:center;padding-top:50px;background:#f4f4f4;}</style>
</head>
<body>
    <h1 style="color:green;">✅ ລະບົບກຳລັງອັບເດດຢູ່ເບື້ອງຫຼັງ!</h1>
    <p>ທ່ານສາມາດປິດໜ້ານີ້ໄດ້ເລີຍ. ລະບົບຈະເຮັດວຽກຕໍ່ຈົນສຳເລັດ.</p>
    <p>👉 <a href="update_log.txt" target="_blank">ກົດບ່ອນນີ້ເພື່ອເບິ່ງ Log ການອັບເດດ</a></p>
</body>
</html>
<?php
$size = ob_get_length();
header("Content-Length: $size");
header('Connection: close'); // ສັ່ງໃຫ້ Browser ຢຸດໂຫຼດ
ob_end_flush();
@ob_flush();
flush();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // ສຳລັບ Nginx/PHP-FPM
}

// =========================================================
// ທາງລຸ່ມນີ້ແມ່ນການເຮັດວຽກຂອງລະບົບ (ຜູ້ໃຊ້ຈະບໍ່ເຫັນຜົນລັບໜ້າຈໍແລ້ວ)
// =========================================================

// ເລີ່ມຕົ້ນຂຽນ Log ໃໝ່ (ລ້າງ Log ເກົ່າທຸກຄັ້ງທີ່ຣັນໃໝ່)
file_put_contents('update_log.txt', "--- ເລີ່ມຕົ້ນການອັບເດດ: " . date('Y-m-d H:i:s') . " ---\n");

// ຟັງຊັນບັນທຶກ Log ລົງໄຟລ໌ ແທນການ Echo
function sendMsg($msg, $type = 'normal') {
    $time = date('H:i:s');
    $logMessage = "[$time] [$type] $msg" . PHP_EOL;
    // ບັນທຶກຕໍ່ທ້າຍໄຟລ໌ update_log.txt
    file_put_contents('update_log.txt', $logMessage, FILE_APPEND);
}

// 3. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
// ⚠️⚠️ ແກ້ໄຂຂໍ້ມູນ DB ຂອງເຈົ້າຢູ່ບ່ອນນີ້ ⚠️⚠️
$host = 'localhost';
$dbname = 'ppshop-js'; 
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sendMsg("❌ Database Connection failed: " . $e->getMessage(), "error");
    exit;
}

// 4. ຟັງຊັນຍິງ API
function callAPI($url) {
    // ⚠️⚠️ ຢ່າລືມອັບເດດ Token ຖ້າມັນໝົດອາຍຸ ⚠️⚠️
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY0NDYzNjVjNTJmMGZiMDU3YmU1ZDkxZCIsImltYWdlIjoiMmI0MWFjNjQtMzM2ZS00YmQwLWFmMjMtY2MxN2Y2Nzc1ODBkLnBuZyIsInVzZXJOYW1lIjoicGFveGFpMTk5NiIsImZ1bGxOYW1lIjoi4LuA4Lqb4Lq74LqyIOC7hOC6iuC6jeC6sOC6quC6suC6mSIsInJvbGUiOiJBRE1JTiIsImlhdCI6MTc2NDcxOTAxN30.X_YRHqog9VwtQKTX6Py3Oiv2Dh-9dTNkj4LhpoYNKtM';
    $encrypted = 'U2FsdGVkX1/Ey7TJrDxfjsnKiwtgAcinmtpZVeDYWubuMj7u5Z1SegOE02fq1x5j';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60, // ເພີ່ມເວລາ Timeout ເປັນ 60 ວິ
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'authorization: Bearer ' . $token,
            'x-encrypted: ' . $encrypted,
            'origin: https://admin.ppshope.com',
            'referer: https://admin.ppshope.com/'
        ),
    ));
    $response = curl_exec($curl);
    
    if(curl_errno($curl)){
        sendMsg("Curl Error: " . curl_error($curl), "error");
    }
    
    curl_close($curl);
    return json_decode($response, true);
}

// ---------------------------------------------------------
// 5. ເລີ່ມຂະບວນການ (Main Process)
// ---------------------------------------------------------

sendMsg("... ກຳລັງດຶງລາຍຊື່ເກມຈາກ API ...", "normal");
$gamesList = callAPI('https://server-api-prod.ppshope.com/api/v1/games');

if (isset($gamesList['data'])) {
    
    $updatedCount = 0;
    $insertedCount = 0;
    $totalChecked = 0;

    foreach ($gamesList['data'] as $game) {
        if (isset($game['active']) && $game['active'] === true) {
            
            $targetIds = [];

            if (!empty($game['children'])) {
                foreach ($game['children'] as $child) {
                    if (isset($child['active']) && $child['active'] === true) {
                        $targetIds[] = [ 'id' => $child['_id'], 'name' => $child['name'] ];
                    }
                }
            } else {
                $targetIds[] = [ 'id' => $game['_id'], 'name' => $game['name'] ];
            }

            foreach ($targetIds as $target) {
                $gameId = $target['id'];
                $gameName = $target['name'];

                sendMsg("⏳ ກວດສອບ: $gameName ...", "game-title");
                
                $packData = callAPI("https://server-api-prod.ppshope.com/api/v1/packets-admin?gameId=" . $gameId);

                if (isset($packData['data'])) {
                    foreach ($packData['data'] as $packet) {
                        
                        if (isset($packet['active']) && $packet['active'] === true) {

                            $totalChecked++;
                            $api_pack_id = $packet['_id'];
                            $api_game_id = $packet['gameId']['_id'] ?? $gameId;
                            $api_pack_name = $packet['name'];
                            $api_amount = $packet['amount'];
                            $api_sort = isset($packet['sort']) ? $packet['sort'] : 999;

                            $stmt = $pdo->prepare("SELECT * FROM game_packages WHERE package_id_api = ?");
                            $stmt->execute([$api_pack_id]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($existing) {
                                if ($existing['amount'] != $api_amount || $existing['package_name'] != $api_pack_name || $existing['sort_order'] != $api_sort) {
                                    
                                    $updateStmt = $pdo->prepare("UPDATE game_packages SET package_name = ?, amount = ?, sort_order = ?, updated_at = NOW() WHERE package_id_api = ?");
                                    $updateStmt->execute([$api_pack_name, $api_amount, $api_sort, $api_pack_id]);
                                    
                                    $changes = [];
                                    if($existing['amount'] != $api_amount) $changes[] = "Price: ".$existing['amount']."->".$api_amount;
                                    if($existing['sort_order'] != $api_sort) $changes[] = "Sort: ".$existing['sort_order']."->".$api_sort;
                                    
                                    sendMsg("  [UPDATE] $api_pack_name | " . implode(", ", $changes), "update");
                                    $updatedCount++;
                                }
                                // ຖ້າບໍ່ມີການປ່ຽນແປງ ບໍ່ຕ້ອງບັນທຶກ Log ເພື່ອປະຢັດພື້ນທີ່
                            } else {
                                $insertStmt = $pdo->prepare("INSERT INTO game_packages (package_id_api, idgame, game_name, package_name, amount, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                                $insertStmt->execute([$api_pack_id, $api_game_id, $gameName, $api_pack_name, $api_amount, $api_sort]);
                                
                                sendMsg("  [NEW] $api_pack_name | Price: $api_amount", "new");
                                $insertedCount++;
                            }
                        }
                    }
                }
                // ພັກຜ່ອນໜ້ອຍໜຶ່ງ ບໍ່ໃຫ້ Server ເຮັດວຽກໜັກເກີນໄປ
                usleep(100000); // 0.1 ວິນາທີ
            }
        }
    }

    sendMsg("✅ ດຳເນີນການສຳເລັດ! ກວດສອບ: $totalChecked, ອັບເດດ: $updatedCount, ໃໝ່: $insertedCount", "success");

} else {
    sendMsg("❌ ບໍ່ສາມາດດຶງຂໍ້ມູນຈາກ API ໄດ້ (Token ອາດຈະໝົດອາຍຸ)", "error");
}
?>