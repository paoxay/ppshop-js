<?php
// 1. ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
$host = 'localhost'; $dbname = 'ppshop-js'; $username = 'root'; $password = ''; // ⚠️ ແກ້ໄຂ DB
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }

// 2. ດຶງຂໍ້ມູນ
$sql = "SELECT * FROM game_packages ORDER BY game_name ASC, sort_order ASC, amount ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. ຈັດກຸ່ມ
$groupedGames = [];
foreach ($results as $row) {
    $gameName = $row['game_name'];
    $displayName = !empty($row['custom_name']) ? $row['custom_name'] : $row['package_name'];
    
    $row['display_name'] = $displayName;
    $groupedGames[$gameName][] = $row;
}
$totalGames = count($groupedGames);
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background: #f4f6f9; }
        .card { border-radius: 12px; border:none; box-shadow: 0 4px 10px rgba(0,0,0,0.05); height: 100%; }
        .card:hover { transform: translateY(-5px); transition: 0.3s; }
        .preview-box { background: #2d3436; color: #fff; padding: 10px; border-radius: 6px; font-size: 12px; font-family: monospace; white-space: pre-wrap; max-height: 150px; overflow-y: auto; }
        .btn-circle { border-radius: 50px; }
        /* ແກ້ໄຂ Modal ໃຫ້ສະແດງຜົນຖືກຕ້ອງ */
        .modal-backdrop { z-index: 1040 !important; }
        .modal { z-index: 1050 !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-primary sticky-top mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-tools"></i> ລະບົບຈັດການຊື່ສິນຄ້າ</a>
            <span class="badge bg-white text-primary rounded-pill"><?php echo $totalGames; ?> ເກມ</span>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="search" class="form-control border-0 py-2" placeholder="ຄົ້ນຫາຊື່ເກມ..." onkeyup="filterGames()">
                </div>
            </div>
        </div>
        
        <div class="row g-3" id="gameGrid">
            <?php 
            $modalIndex = 0; // ✅ ໃຊ້ຕົວເລກແທນຊື່ ເພື່ອບໍ່ໃຫ້ ID ຜິດພາດ
            foreach ($groupedGames as $gameName => $items): 
                $modalIndex++; 
                $modalID = "modal-edit-" . $modalIndex; // ສ້າງ ID ແບບງ່າຍໆ: modal-edit-1, modal-edit-2

                // ສ້າງ Preview Text
                $previewText = "* {$gameName} *\n";
                foreach($items as $item) {
                    $price = number_format(ceil($item['amount']/1000)*1000);
                    $previewText .= "   {$item['display_name']} : {$price} ₭\n";
                }
                
                // ສ້າງ Link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $path = dirname($_SERVER['PHP_SELF']);
                $apiLink = "$protocol://$host$path/get_prices.php?game=" . rawurlencode(trim($gameName));
            ?>
            
            <div class="col-md-6 col-lg-4 game-card" data-name="<?php echo strtolower($gameName); ?>">
                <div class="card">
                    <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center border-0 pt-3">
                        <span class="text-truncate pe-2"><?php echo $gameName; ?></span>
                        
                        <button type="button" class="btn btn-sm btn-warning btn-circle px-3" 
                                data-bs-toggle="modal" 
                                data-bs-target="#<?php echo $modalID; ?>">
                            <i class="fas fa-edit"></i> ແກ້ໄຂ
                        </button>
                    </div>

                    <div class="card-body">
                        <p class="small text-muted mb-1 fw-bold">ຕົວຢ່າງ:</p>
                        <div class="preview-box mb-3"><?php echo $previewText; ?></div>
                        
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control bg-light" value="<?php echo $apiLink; ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyLink('<?php echo $apiLink; ?>', this)">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="<?php echo $modalID; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title text-primary"><i class="fas fa-edit"></i> ແກ້ໄຂ: <?php echo $gameName; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body bg-white">
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40%">ຊື່ເດີມ (Original)</th>
                                                <th width="45%">ຊື່ໃໝ່ (Custom Name)</th>
                                                <th width="15%" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($items as $pkg): ?>
                                            <tr class="border-bottom">
                                                <td class="small text-muted"><?php echo $pkg['package_name']; ?></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                        id="input-<?php echo $pkg['id']; ?>" 
                                                        value="<?php echo $pkg['custom_name']; ?>" 
                                                        placeholder="ຕົວຢ່າງ: 100 💎"
                                                        onkeydown="if(event.key === 'Enter') saveName(<?php echo $pkg['id']; ?>)">
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary btn-circle" onclick="saveName(<?php echo $pkg['id']; ?>)">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ປິດ</button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterGames() {
            let input = document.getElementById('search').value.toLowerCase();
            document.querySelectorAll('.game-card').forEach(c => {
                c.style.display = c.getAttribute('data-name').includes(input) ? '' : 'none';
            });
        }

        function copyLink(text, btn) {
            navigator.clipboard.writeText(text);
            let icon = btn.querySelector('i');
            icon.className = 'fas fa-check text-success';
            setTimeout(() => icon.className = 'far fa-copy', 1500);
        }

        function saveName(id) {
            const input = document.getElementById('input-' + id);
            const val = input.value;
            const btn = input.parentElement.nextElementSibling.querySelector('button');
            const icon = btn.querySelector('i');

            icon.className = 'fas fa-spinner fa-spin'; // ໝຸນຕິ້ວໆ
            
            fetch('save_name.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&custom_name=${encodeURIComponent(val)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    btn.classList.replace('btn-primary', 'btn-success');
                    icon.className = 'fas fa-check';
                    setTimeout(() => {
                        btn.classList.replace('btn-success', 'btn-primary');
                        icon.className = 'fas fa-save';
                    }, 2000);
                } else {
                    alert('ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ!');
                    icon.className = 'fas fa-save';
                }
            })
            .catch(err => {
                alert('Error: ' + err);
                icon.className = 'fas fa-save';
            });
        }
    </script>

</body>
</html>