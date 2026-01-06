<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແຈ້ງການສັ່ງຊື້ - PPShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background: #eef2f7; }
        .main-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border: none; }
        .form-label { font-weight: 600; color: #495057; }
        .upload-area { 
            border: 2px dashed #cbd5e0; border-radius: 12px; padding: 30px; text-align: center; 
            cursor: pointer; transition: 0.3s; background: #f8fafc; position: relative;
        }
        .upload-area:hover, .upload-area.dragover { border-color: #764ba2; background: #f0f4ff; }
        .preview-img { max-width: 100%; max-height: 200px; border-radius: 10px; display: none; margin-top: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-custom { border-radius: 50px; padding: 12px 30px; font-weight: 600; transition: 0.3s; }
        .btn-primary-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; }
        .btn-primary-gradient:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card main-card">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> ຟອມແຈ້ງສັ່ງຊື້ເກມ</h3>
                    <p class="mb-0 opacity-75 small">ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ</p>
                </div>
                <div class="card-body p-4">
                    <form id="orderForm" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label">🎮 ເລືອກເກມ</label>
                            <select class="form-select form-select-lg" id="gameSelect" name="game_name" required>
                                <option value="" selected disabled>ກະລຸນາເລືອກ...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">💎 ເລືອກແພັກເກັດ</label>
                            <select class="form-select" id="packageSelect" name="package_id" required disabled>
                                <option value="" selected disabled>-- ກະລຸນາເລືອກເກມກ່ອນ --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">🆔 UID / ID ຜູ້ຫຼິ້ນ</label>
                            <input type="text" class="form-control" name="uid" placeholder="ໃສ່ ID ຕົວລະຄອນ..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">🧾 ເລກອ້າງອີງການໂອນ (Transaction ID)</label>
                            <input type="text" class="form-control" name="slip_no" placeholder="ໃສ່ເລກບິນໂອນເງິນ...">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">📸 ຫຼັກຖານການໂອນ (ຄລິກອັບໂຫລດ ຫຼື ວາງຮູບ)</label>
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-2"></i>
                                <p class="mb-0 text-muted small">ລາກຮູບມາໃສ່, ຄລິກເພື່ອເລືອກ, ຫຼືກົດ <strong>Ctrl+V</strong> ເພື່ອວາງ</p>
                                <input type="file" name="slip_image" id="fileInput" accept="image/*" class="d-none">
                                <img id="previewImage" class="preview-img">
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <button type="button" class="btn btn-light btn-custom flex-grow-1 text-danger" onclick="resetForm()">
                                <i class="fas fa-undo me-1"></i> ຍົກເລີກ
                            </button>
                            <button type="submit" class="btn btn-primary-gradient btn-custom flex-grow-1">
                                <i class="fas fa-save me-1"></i> ບັນທຶກອໍເດີ້
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
    
    // 1. ໂຫຼດລາຍຊື່ເກມ ແລະ ເປີດໃຊ້ Select2
    $.getJSON('order_api.php?action=get_games', function(data) {
        let options = '<option value="" selected disabled>ກະລຸນາເລືອກ...</option>';
        data.forEach(game => {
            options += `<option value="${game}">${game}</option>`;
        });
        $('#gameSelect').html(options);

        // 🔥 ເລີ່ມຕົ້ນໃຊ້ງານ Select2 (ຊ່ອງຄົ້ນຫາ)
        $('#gameSelect').select2({
            theme: 'bootstrap-5',
            placeholder: "ພິມຊື່ເກມເພື່ອຄົ້ນຫາ...",
            allowClear: true,
            width: '100%' // ໃຫ້ເຕັມຂອບ
        });
    });

        // 2. ເມື່ອເລືອກເກມ -> ໂຫຼດແພັກເກັດ
        $('#gameSelect').change(function() {
            let gameName = $(this).val();
            let pkgSelect = $('#packageSelect');
            
            pkgSelect.html('<option>ກຳລັງໂຫຼດ...</option>').prop('disabled', true);
            
            $.getJSON('order_api.php?action=get_packages&game=' + encodeURIComponent(gameName), function(data) {
                let options = '<option value="" selected disabled>-- ເລືອກແພັກເກັດ --</option>';
                data.forEach(pkg => {
                    options += `<option value="${pkg.id}">${pkg.display}</option>`;
                });
                pkgSelect.html(options).prop('disabled', false);
            });
        });

        // 3. ຟັງຊັນການອັບໂຫລດ ແລະ Paste ຮູບ
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const previewImg = document.getElementById('previewImage');

        // ຄລິກເພື່ອເລືອກໄຟລ໌
        uploadArea.addEventListener('click', () => fileInput.click());

        // ສະແດງຮູບເມື່ອເລືອກໄຟລ໌
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) showPreview(this.files[0]);
        });

        // ວາງຮູບຈາກ Clipboard (Ctrl+V)
        window.addEventListener('paste', function(e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let index in items) {
                const item = items[index];
                if (item.kind === 'file' && item.type.includes('image/')) {
                    const blob = item.getAsFile();
                    const file = new File([blob], "pasted_image.png", { type: blob.type });
                    
                    // ໃສ່ໄຟລ໌ລົງໃນ Input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;

                    showPreview(blob);
                }
            }
        });

        function showPreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'inline-block';
            }
            reader.readAsDataURL(file);
        }

        // 4. ບັນທຶກຂໍ້ມູນ (AJAX)
        $('#orderForm').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            formData.append('action', 'save_order');

            Swal.fire({
                title: 'ກຳລັງບັນທຶກ...',
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: 'order_api.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    if (res.success) {
                        Swal.fire('ສຳເລັດ!', res.message, 'success').then(() => {
                            resetForm();
                        });
                    } else {
                        Swal.fire('ຜິດພາດ!', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່', 'error');
                }
            });
        });
    });

    function resetForm() {
        $('#orderForm')[0].reset();
        $('#packageSelect').html('<option value="" selected disabled>-- ກະລຸນາເລືອກເກມກ່ອນ --</option>').prop('disabled', true);
        document.getElementById('previewImage').style.display = 'none';
    }
</script>

</body>
</html>