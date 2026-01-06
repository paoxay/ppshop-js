<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດການເຕີມເກມ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background: #f4f6f9; }
        .card-summary { background: linear-gradient(45deg, #11998e, #38ef7d); color: white; border: none; border-radius: 15px; }
        .table-card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: none; }
        .badge-pending { background-color: #f1c40f; color: #000; }
        .badge-completed { background-color: #2ecc71; }
        .badge-cancelled { background-color: #e74c3c; }
        .slip-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #ddd; }
        .slip-thumb:hover { transform: scale(1.1); transition: 0.2s; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="fas fa-gamepad"></i> PPShop Manager</a>
        <div class="d-flex gap-2">
            <a href="order.php" class="btn btn-outline-light btn-sm"><i class="fas fa-plus"></i> ເຕີມເກມໃໝ່</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-summary p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">ຍອດຂາຍທັງໝົດ (ສຳເລັດ/ລໍຖ້າ)</h6>
                        <h2 class="mb-0 fw-bold" id="totalRevenue">0 ₭</h2>
                    </div>
                    <i class="fas fa-coins fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-primary"><i class="fas fa-history"></i> ປະຫວັດການເຕີມ</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="ຄົ້ນຫາ...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ວັນທີ</th>
                        <th>ເກມ</th>
                        <th>UID</th>
                        <th>ລາຄາ</th>
                        <th>ສະລິບ</th>
                        <th>ສະຖານະ</th>
                        <th class="text-center">ຈັດການ</th>
                    </tr>
                </thead>
                <tbody id="historyTable">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ແກ້ໄຂອໍເດີ້</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label>UID</label>
                    <input type="text" class="form-control" id="editUid">
                </div>
                <div class="mb-3">
                    <label>ລາຄາ</label>
                    <input type="number" class="form-control" id="editPrice">
                </div>
                <div class="mb-3">
                    <label>ສະຖານະ</label>
                    <select class="form-select" id="editStatus">
                        <option value="pending">🟡 Pending (ລໍຖ້າ)</option>
                        <option value="completed">🟢 Completed (ສຳເລັດ)</option>
                        <option value="cancelled">🔴 Cancelled (ຍົກເລີກ)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="saveEdit()">ບັນທຶກ</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        loadHistory();

        // ຟັງຊັນຄົ້ນຫາໃນຕາຕະລາງ
        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#historyTable tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });

    function loadHistory() {
        $.getJSON('order_api.php?action=get_history', function(res) {
            let rows = '';
            $('#totalRevenue').text(new Intl.NumberFormat().format(res.total_revenue) + ' ₭');

            res.orders.forEach(order => {
                let statusBadge = '';
                if(order.status == 'pending') statusBadge = '<span class="badge badge-pending">ລໍຖ້າ</span>';
                else if(order.status == 'completed') statusBadge = '<span class="badge badge-completed">ສຳເລັດ</span>';
                else statusBadge = '<span class="badge badge-cancelled">ຍົກເລີກ</span>';

                let slipImg = order.slip_image ? `<a href="${order.slip_image}" target="_blank"><img src="${order.slip_image}" class="slip-thumb"></a>` : '-';

                rows += `
                    <tr>
                        <td class="small text-muted">${order.created_at}</td>
                        <td>
                            <div class="fw-bold">${order.game_name}</div>
                            <div class="small text-muted">${order.package_name}</div>
                        </td>
                        <td class="text-primary">${order.uid}</td>
                        <td class="fw-bold">${new Intl.NumberFormat().format(order.price)}</td>
                        <td>${slipImg}</td>
                        <td>${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning mb-1" onclick="openEdit(${order.id}, '${order.uid}', ${order.price}, '${order.status}')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger mb-1" onclick="deleteOrder(${order.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#historyTable').html(rows);
        });
    }

    // ລົບອໍເດີ້
    function deleteOrder(id) {
        Swal.fire({
            title: 'ຢືນຢັນການລົບ?',
            text: "ຂໍ້ມູນຈະຫາຍໄປຖາວອນ!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ລົບເລີຍ!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('order_api.php', { action: 'delete_order', id: id }, function(res) {
                    if(res.success) {
                        Swal.fire('ລົບແລ້ວ!', '', 'success');
                        loadHistory();
                    }
                }, 'json');
            }
        });
    }

    // ເປີດ Modal ແກ້ໄຂ
    function openEdit(id, uid, price, status) {
        $('#editId').val(id);
        $('#editUid').val(uid);
        $('#editPrice').val(price);
        $('#editStatus').val(status);
        var myModal = new bootstrap.Modal(document.getElementById('editModal'));
        myModal.show();
    }

    // ບັນທຶກການແກ້ໄຂ
    function saveEdit() {
        $.post('order_api.php', {
            action: 'update_order',
            id: $('#editId').val(),
            uid: $('#editUid').val(),
            price: $('#editPrice').val(),
            status: $('#editStatus').val()
        }, function(res) {
            if(res.success) {
                $('#editModal').modal('hide'); // ປິດ Modal ແບບຖືກວິທີ
                $('.modal-backdrop').remove(); // ລົບ backdrop ທີ່ຄ້າງ
                Swal.fire('ອັບເດດແລ້ວ!', '', 'success');
                loadHistory();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    }
</script>

</body>
</html>