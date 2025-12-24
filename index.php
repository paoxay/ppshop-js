<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Game Price Manager</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@100..900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .game-header {
            background: linear-gradient(45deg, #0d6efd, #0099ff);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 15px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .preview-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Noto Sans Lao', sans-serif;
            white-space: pre-wrap; /* ຮັກສາການລົງແຖວ */
            font-size: 0.9rem;
            color: #2c3e50;
            max-height: 250px;
            overflow-y: auto;
        }
        .api-link-box {
            background: #2d3436;
            color: #00ff9d;
            font-family: monospace;
            padding: 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            word-break: break-all;
        }
        .badge-active {
            background-color: #00b894;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .copy-btn {
            border-radius: 20px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-gamepad me-2"></i> Game API Manager
            </a>
            <span class="text-white" id="totalGamesBadge">ກຳລັງໂຫຼດ...</span>
        </div>
    </nav>

    <div class="container py-4">
        
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-0" placeholder="ຄົ້ນຫາຊື່ເກມ..." onkeyup="filterGames()">
                </div>
            </div>
        </div>

        <div class="row g-4" id="gameContainer">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">ກຳລັງໂຫຼດຂໍ້ມູນຈາກຖານຂໍ້ມູນ...</p>
            </div>
        </div>

    </div>

    <script>
     // ຕື່ມ ?v=... ໃສ່ທາງຫຼັງເພື່ອໃຫ້ browser ຮູ້ວ່າເປັນໄຟລ໌ໃໝ່ສະເໝີ
        const API_URL = 'get_prices.php?v=' + new Date().getTime();
        
        // Base URL ສຳລັບສ້າງລິ້ງໃຫ້ User Copy
        // ມັນຈະດຶງ Path ປັດຈຸບັນມາຕັດ index.php ອອກ
        const BASE_URL = window.location.href.replace('/index.php', '').replace(window.location.search, '');

        let allGamesData = [];

        // ເຮັດວຽກທັນທີເມື່ອເປີດໜ້າເວັບ
        document.addEventListener('DOMContentLoaded', () => {
            fetchGames();
        });

        // ຟັງຊັນດຶງຂໍ້ມູນ
        async function fetchGames() {
            try {
                const response = await fetch(API_URL);
                const data = await response.json();

                if (Array.isArray(data)) {
                    allGamesData = data;
                    document.getElementById('totalGamesBadge').innerHTML = `<i class="fas fa-check-circle"></i> ພ້ອມໃຊ້ງານ ${data.length} ເກມ`;
                    renderGames(data);
                } else {
                    showError('ບໍ່ພົບຂໍ້ມູນ ຫຼື ຮູບແບບຂໍ້ມູນຜິດພາດ');
                }
            } catch (error) {
                showError('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ API: ' + error);
            }
        }

        // ຟັງຊັນສະແດງຜົນ (Render)
        function renderGames(games) {
            const container = document.getElementById('gameContainer');
            container.innerHTML = '';

            if (games.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-muted"><h5>ບໍ່ພົບຂໍ້ມູນເກມທີ່ຄົ້ນຫາ</h5></div>';
                return;
            }

            games.forEach((item, index) => {
                // ສ້າງ API Link
                const fullApiLink = `${BASE_URL}/get_prices.php?game=${encodeURIComponent(item.game)}`;
                
                // ຕົວຢ່າງຂໍ້ຄວາມ (ແຍກ \n ເປັນ <br> ເພື່ອສະແດງໃນ HTML ແຕ່ຮັກສາ Format)
                // ເຮົາໃຊ້ <pre> ຫຼື style white-space: pre-wrap ຢູ່ແລ້ວ ຈຶ່ງບໍ່ຕ້ອງ replace
                const previewText = item.items[0]; 

                const cardHtml = `
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="card h-100">
                        <div class="game-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-dice d20 me-2"></i> ${item.game}</span>
                            <span class="badge bg-warning text-dark" style="font-size:0.7rem">Active</span>
                        </div>
                        <div class="card-body">
                            <p class="mb-1 text-muted small"><i class="fas fa-eye"></i> ຕົວຢ່າງຂໍ້ຄວາມໃນ Chat:</p>
                            <div class="preview-box mb-3">
                                ${previewText}
                            </div>

                            <p class="mb-1 text-muted small"><i class="fas fa-link"></i> API Link:</p>
                            <div class="api-link-box mb-2" id="link-${index}">
                                ${fullApiLink}
                            </div>
                            
                            <button class="btn btn-outline-primary btn-sm w-100 copy-btn" onclick="copyToClipboard('${fullApiLink}', this)">
                                <i class="far fa-copy"></i> ຄັດລອກລິ້ງ API
                            </button>
                        </div>
                    </div>
                </div>
                `;
                container.innerHTML += cardHtml;
            });
        }

        // ຟັງຊັນຄົ້ນຫາ (Filter)
        function filterGames() {
            const keyword = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allGamesData.filter(item => 
                item.game.toLowerCase().includes(keyword)
            );
            renderGames(filtered);
        }

        // ຟັງຊັນ Copy
        function copyToClipboard(text, btnElement) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="fas fa-check"></i> ຄັດລອກແລ້ວ!';
                btnElement.classList.remove('btn-outline-primary');
                btnElement.classList.add('btn-success');
                
                setTimeout(() => {
                    btnElement.innerHTML = originalText;
                    btnElement.classList.add('btn-outline-primary');
                    btnElement.classList.remove('btn-success');
                }, 2000);
            });
        }

        function showError(msg) {
            document.getElementById('gameContainer').innerHTML = `<div class="col-12 text-center text-danger"><h4>${msg}</h4></div>`;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>