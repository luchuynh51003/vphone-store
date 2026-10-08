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
