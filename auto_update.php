<?php
// 1. ຕັ້ງຄ່າລະບົບ (System Config)
set_time_limit(0); // ໃຫ້ Script ຣັນໄດ້ຕະຫຼອດຈົນຈົບ
error_reporting(E_ALL ^ E_NOTICE); // ປິດແຈ້ງເຕືອນ Notice

// ເຄຍ Buffer ເກົ່າອອກໃຫ້ໝົດ
while (ob_get_level() > 0) {
    ob_end_clean();
}
// ເປີດລະບົບສົ່ງຂໍ້ມູນທັນທີ (Real-time Output)
ob_implicit_flush(true);

// CSS ຕົກແຕ່ງໜ້າຕ່າງ
echo '<style>
    body { background: #1e1e1e; color: #ccc; font-family: "Courier New", monospace; padding: 20px; font-size: 13px; line-height: 1.4; }
    .log { padding: 3px 0; border-bottom: 1px solid #333; }
    .new { color: #00ff00; font-weight: bold; } 
    .update { color: #ffff00; font-weight: bold; } 
    .skip { color: #555; display:none; } /* ປົກກະຕິເຊື່ອງໄວ້ ຢາກເຫັນໃຫ້ລົບ display:none ອອກ */
    .error { color: #ff3333; font-weight: bold; }
    .game-title { color: #00ccff; font-weight: bold; margin-top: 10px; }
    h2 { border-bottom: 2px solid #fff; padding-bottom: 10px; color: #fff; }
</style>';

echo "<h2>🚀 ລະບົບອັບເດດລາຄາ & ລຳດັບສິນຄ້າ (Auto Sync)</h2>";

// ຟັງຊັນສົ່ງຂໍ້ຄວາມ (Log)
function sendMsg($msg, $type = 'normal') {
    echo "<div class='log $type'>$msg</div>";
    if (ob_get_length() > 0) { @ob_flush(); }
    @flush();
}

sendMsg("... ກຳລັງເຊື່ອມຕໍ່ຖານຂໍ້ມູນ ...", "normal");

// 2. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ (Database Connection)
// ⚠️⚠️ ແກ້ໄຂຂໍ້ມູນ DB ຂອງເຈົ້າຢູ່ບ່ອນນີ້ ⚠️⚠️
$host = 'localhost';
$dbname = 'ppshop-js'; 
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='log error'>❌ Database Connection failed: " . $e->getMessage() . "</div>");
}

// 3. ຟັງຊັນຍິງ API
function callAPI($url) {
    // ⚠️⚠️ ຢ່າລືມອັບເດດ Token ຖ້າມັນໝົດອາຍຸ ⚠️⚠️
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY0NDYzNjVjNTJmMGZiMDU3YmU1ZDkxZCIsImltYWdlIjoiMmI0MWFjNjQtMzM2ZS00YmQwLWFmMjMtY2MxN2Y2Nzc1ODBkLnBuZyIsInVzZXJOYW1lIjoicGFveGFpMTk5NiIsImZ1bGxOYW1lIjoi4LuA4Lqb4Lq74LqyIOC7hOC6iuC6jeC6sOC6quC6suC6mSIsInJvbGUiOiJBRE1JTiIsImlhdCI6MTc2NDcxOTAxN30.X_YRHqog9VwtQKTX6Py3Oiv2Dh-9dTNkj4LhpoYNKtM';
    $encrypted = 'U2FsdGVkX1/Ey7TJrDxfjsnKiwtgAcinmtpZVeDYWubuMj7u5Z1SegOE02fq1x5j';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'authorization: Bearer ' . $token,
            'x-encrypted: ' . $encrypted,
            'origin: https://admin.ppshope.com',
            'referer: https://admin.ppshope.com/'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// ---------------------------------------------------------
// 4. ເລີ່ມຂະບວນການ (Main Process)
// ---------------------------------------------------------

sendMsg("... ກຳລັງດຶງລາຍຊື່ເກມຈາກ API ...", "normal");
$gamesList = callAPI('https://server-api-prod.ppshope.com/api/v1/games');

if (isset($gamesList['data'])) {
    
    $updatedCount = 0;
    $insertedCount = 0;
    $totalChecked = 0;

    foreach ($gamesList['data'] as $game) {
        
        // 1. ກວດສອບເກມ Active (ຊັ້ນນອກ)
        if (isset($game['active']) && $game['active'] === true) {
            
            $targetIds = [];

            // ກວດສອບວ່າເປັນເກມດ່ຽວ ຫຼື ມີລູກ (Children)
            if (!empty($game['children'])) {
                foreach ($game['children'] as $child) {
                    if (isset($child['active']) && $child['active'] === true) {
                        $targetIds[] = [ 'id' => $child['_id'], 'name' => $child['name'] ];
                    }
                }
            } else {
                $targetIds[] = [ 'id' => $game['_id'], 'name' => $game['name'] ];
            }

            // ວົນລູບແຕ່ລະເກມຍ່ອຍ
            foreach ($targetIds as $target) {
                $gameId = $target['id'];
                $gameName = $target['name'];

                sendMsg("⏳ ກວດສອບ: $gameName ...", "game-title");
                
                // ດຶງແພັກເກັດ
                $packData = callAPI("https://server-api-prod.ppshope.com/api/v1/packets-admin?gameId=" . $gameId);

                if (isset($packData['data'])) {
                    foreach ($packData['data'] as $packet) {
                        
                        // 2. ກວດສອບວ່າແພັກເກັດ Active ບໍ່?
                        if (isset($packet['active']) && $packet['active'] === true) {

                            $totalChecked++;
                            $api_pack_id = $packet['_id'];
                            $api_game_id = $packet['gameId']['_id'] ?? $gameId;
                            $api_pack_name = $packet['name'];
                            $api_amount = $packet['amount'];
                            
                            // ✅ ດຶງຄ່າ Sort ຈາກ API (ຖ້າບໍ່ມີໃຫ້ເປັນ 999)
                            $api_sort = isset($packet['sort']) ? $packet['sort'] : 999;

                            // ກວດສອບໃນ DB
                            $stmt = $pdo->prepare("SELECT * FROM game_packages WHERE package_id_api = ?");
                            $stmt->execute([$api_pack_id]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($existing) {
                                // 3. ຖ້າມີແລ້ວ -> ກວດສອບການປ່ຽນແປງ (ຊື່, ລາຄາ, ລຳດັບ)
                                if ($existing['amount'] != $api_amount || $existing['package_name'] != $api_pack_name || $existing['sort_order'] != $api_sort) {
                                    
                                    $updateStmt = $pdo->prepare("UPDATE game_packages SET package_name = ?, amount = ?, sort_order = ?, updated_at = NOW() WHERE package_id_api = ?");
                                    $updateStmt->execute([$api_pack_name, $api_amount, $api_sort, $api_pack_id]);
                                    
                                    // ສ້າງຂໍ້ຄວາມແຈ້ງເຕືອນການປ່ຽນແປງ
                                    $changes = [];
                                    if($existing['amount'] != $api_amount) $changes[] = "ລາຄາ ".number_format($existing['amount'])."->".number_format($api_amount);
                                    if($existing['sort_order'] != $api_sort) $changes[] = "ລຳດັບ ".$existing['sort_order']."->".$api_sort;
                                    
                                    sendMsg("  [UPDATE] $api_pack_name | " . implode(", ", $changes), "update");
                                    $updatedCount++;
                                } else {
                                    sendMsg("  [SKIP] $api_pack_name", "skip");
                                }
                            } else {
                                // 4. ຖ້າບໍ່ມີ -> ເພີ່ມໃໝ່ (INSERT)
                                $insertStmt = $pdo->prepare("INSERT INTO game_packages (package_id_api, idgame, game_name, package_name, amount, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                                $insertStmt->execute([$api_pack_id, $api_game_id, $gameName, $api_pack_name, $api_amount, $api_sort]);
                                
                                sendMsg("  [NEW] $api_pack_name | ລາຄາ: ".number_format($api_amount)." | ລຳດັບ: $api_sort", "new");
                                $insertedCount++;
                            }
                        
                        } // End Check Active Packet
                    }
                }
            }
        }
    }

    echo "<br><hr>";
    echo "<h3 style='color:#fff'>✅ ດຳເນີນການສຳເລັດ!</h3>";
    echo "<ul>";
    echo "<li>ກວດສອບທັງໝົດ: <strong>$totalChecked</strong> ລາຍການ</li>";
    echo "<li style='color:yellow'>ອັບເດດຂໍ້ມູນ: <strong>$updatedCount</strong> ລາຍການ</li>";
    echo "<li style='color:#00ff00'>ເພີ່ມສິນຄ້າໃໝ່: <strong>$insertedCount</strong> ລາຍການ</li>";
    echo "</ul>";

} else {
    sendMsg("❌ ບໍ່ສາມາດດຶງຂໍ້ມູນຈາກ API ໄດ້ (Token ອາດຈະໝົດອາຍຸ)", "error");
}
?>