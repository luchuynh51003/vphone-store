    </div>
</div>

<div class="modal fade" id="adminDeleteConfirmModal" tabindex="-1" aria-labelledby="adminDeleteConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px"><i class="fa-solid fa-trash"></i></div>
                <h5 class="fw-bold" id="adminDeleteConfirmTitle">Xác nhận xóa</h5>
                <p class="text-secondary" id="adminDeleteConfirmMessage"></p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" id="adminDeleteConfirmSubmit">Xóa</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js?v=<?= file_exists(__DIR__ . '/../../assets/js/main.js') ? filemtime(__DIR__ . '/../../assets/js/main.js') : '20261021_v1' ?>"></script>

<?php
// Kiểm tra quyền hạn sử dụng Trợ lý AI:
// - Super Admin & Admin thường có sẵn toàn quyền.
// - Nhân viên (role = 2) BẮT BUỘC phải được Admin cấp quyền 'ai_assistant' mới có boxchat!
$canUseAi = isCurrentAdmin() || currentUserCan('ai_assistant');

if ($canUseAi):
    $admUserRole = (int)($_SESSION['user']['role'] ?? 0);
    $admStaffRole = strtolower(trim((string)($_SESSION['user']['staff_role'] ?? '')));

    // Xác định DUY NHẤT 1 chức vụ tương ứng cho người này
    if (isCurrentAdmin()) {
        $assignedAiRole = 'admin';
        $assignedAiName = 'V-Phone Executive AI';
        $assignedAiBadge = 'Quản Trị Viên';
        $assignedAiIcon = 'fa-solid fa-crown';
        $assignedAiGradient = 'linear-gradient(135deg, #ef4444, #8b5cf6)';
    } else {
        if ($admStaffRole === 'warehouse') {
            $assignedAiRole = 'warehouse';
            $assignedAiName = 'V-Phone Warehouse AI';
            $assignedAiBadge = 'Quản Lý Kho';
            $assignedAiIcon = 'fa-solid fa-boxes-stacked';
            $assignedAiGradient = 'linear-gradient(135deg, #f59e0b, #d97706)';
        } elseif ($admStaffRole === 'warranty') {
            $assignedAiRole = 'warranty';
            $assignedAiName = 'V-Phone Tech & Care AI';
            $assignedAiBadge = 'Kỹ Thuật Viên';
            $assignedAiIcon = 'fa-solid fa-shield-halved';
            $assignedAiGradient = 'linear-gradient(135deg, #10b981, #059669)';
        } elseif ($admStaffRole === 'support') {
            $assignedAiRole = 'support';
            $assignedAiName = 'V-Phone Support AI';
            $assignedAiBadge = 'Chuyên Viên CSKH';
            $assignedAiIcon = 'fa-solid fa-headset';
            $assignedAiGradient = 'linear-gradient(135deg, #ec4899, #8b5cf6)';
        } else {
            $assignedAiRole = 'sales';
            $assignedAiName = 'V-Phone Sales AI';
            $assignedAiBadge = 'Nhân Viên Bán Hàng';
            $assignedAiIcon = 'fa-solid fa-briefcase';
            $assignedAiGradient = 'linear-gradient(135deg, #2563eb, #06b6d4)';
        }
    }
?>

<!-- NÚT BẤM NỔI GỌN GÀNG Ở GÓC PHẢI - KHI NÀO CẦN BẤM MỚI MỞ RA -->
<div id="roleAiFloatingTrigger" class="role-ai-floating-trigger" title="Nhấp để mở Trợ lý AI <?= $assignedAiBadge ?> (Phím tắt: Esc để đóng)">
    <div class="role-ai-trigger-orb" id="roleAiTriggerOrb" style="background: <?= $assignedAiGradient ?>;">
        <i class="<?= $assignedAiIcon ?>"></i>
        <span class="role-ai-trigger-pulse"></span>
    </div>
    <div class="role-ai-trigger-label">
        <span class="role-ai-trigger-name"><?= $assignedAiName ?></span>
        <span class="role-ai-trigger-badge">
            <span class="role-ai-trigger-dot"></span> <?= $assignedAiBadge ?>
        </span>
    </div>
</div>

<!-- CỬA SỔ CHATBOX DUY NHẤT 1 BOX TƯƠNG ỨNG VỚI CHỨC VỤ (GỌN GÀNG, KHÔNG RƯỜM RÀ) -->
<div id="roleAiWindow" class="role-ai-window" data-role="<?= $assignedAiRole ?>" data-is-admin="<?= isCurrentAdmin() ? '1' : '0' ?>" aria-hidden="true">
    <!-- Header (Có thể kéo thả di chuyển toàn khung chat) -->
    <div class="role-ai-header" id="roleAiHeader" title="Nhấp giữ chuột để di chuyển khung chat">
        <div class="role-ai-header-profile">
            <div class="role-ai-header-orb" id="roleAiHeaderOrb" style="background: <?= $assignedAiGradient ?>;">
                <i class="<?= $assignedAiIcon ?>"></i>
            </div>
            <div class="role-ai-header-info">
                <h6 class="role-ai-header-title" id="roleAiHeaderTitle"><?= $assignedAiName ?></h6>
                <div class="role-ai-header-subtitle">
                    <span class="badge rounded-pill bg-white bg-opacity-20 text-white"><?= $assignedAiBadge ?></span>
                    <span>&bull; Trực tuyến 24/7</span>
                </div>
            </div>
        </div>
        <div class="role-ai-header-actions">
            <button type="button" class="role-ai-btn-icon" id="roleAiBtnMinimize" title="Thu nhỏ / Mở rộng" aria-label="Thu nhỏ">
                <i class="fa-solid fa-minus"></i>
            </button>
            <button type="button" class="role-ai-btn-icon" id="roleAiBtnReset" title="Làm mới hội thoại" aria-label="Làm mới">
                <i class="fa-solid fa-rotate-right"></i>
            </button>
            <button type="button" class="role-ai-btn-icon" id="roleAiBtnClose" title="Đóng chatbox (Esc)" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Thân hộp thoại danh sách tin nhắn -->
    <div class="role-ai-body" id="roleAiBody">
        <!-- Render động qua JavaScript -->
    </div>

    <!-- Gợi ý câu hỏi nhanh (Flagship Aesthetic Chips Bar 2026) -->
    <div class="role-ai-chips-bar" id="roleAiChipsBar">
        <div class="role-ai-chips-top">
            <span class="role-ai-chips-label">
                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                <span>Gợi ý nghiệp vụ</span>
            </span>
            <div class="role-ai-chips-actions">
                <button type="button" class="role-ai-chip-arrow prev" id="roleAiChipPrev" title="Cuộn sang trái" aria-label="Trước">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="role-ai-chip-arrow next" id="roleAiChipNext" title="Cuộn sang phải" aria-label="Tiếp">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" class="role-ai-chip-toggle" id="roleAiChipToggle" title="Mở rộng xem tất cả gợi ý" aria-label="Mở rộng danh sách gợi ý">
                    <i class="fa-solid fa-grip"></i>
                </button>
            </div>
        </div>
        <div class="role-ai-chips-wrap" id="roleAiChips">
            <!-- Render động theo đúng chức vụ -->
        </div>
    </div>

    <!-- Khung nhập tin nhắn -->
    <div class="role-ai-footer">
        <form class="role-ai-input-form" id="roleAiInputForm" autocomplete="off">
            <input type="text" class="role-ai-input" id="roleAiInput" placeholder="Nhập câu hỏi nghiệp vụ..." maxlength="400" required>
            <button type="submit" class="role-ai-btn-send" id="roleAiBtnSend" title="Gửi câu hỏi" aria-label="Gửi">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script src="../assets/js/role_ai.js?v=<?= file_exists(__DIR__ . '/../../assets/js/role_ai.js') ? filemtime(__DIR__ . '/../../assets/js/role_ai.js') : '20261022_v3' ?>"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('adminDeleteConfirmModal');
    const confirmButton = document.getElementById('adminDeleteConfirmSubmit');
    let pendingForm = null;

    modal?.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        pendingForm = document.getElementById(trigger.dataset.confirmForm);
        document.getElementById('adminDeleteConfirmMessage').textContent = trigger.dataset.confirmMessage;
        confirmButton.textContent = trigger.dataset.confirmLabel || 'Xóa';
    });

    confirmButton?.addEventListener('click', function () {
        if (pendingForm) pendingForm.requestSubmit();
    });
});
</script>
</body>
</html>