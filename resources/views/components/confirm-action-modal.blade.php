<div class="modal fade" id="confirm-action-modal" tabindex="-1" aria-labelledby="confirm-action-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="confirm-action-modal-title">Konfirmasi Tindakan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-confirm-action-message>Tindakan ini memerlukan konfirmasi.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" data-confirm-action-submit>Ya, lanjutkan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modalElement = document.getElementById('confirm-action-modal');
            const messageElement = modalElement?.querySelector('[data-confirm-action-message]');
            const titleElement = modalElement?.querySelector('.modal-title');
            const confirmButton = modalElement?.querySelector('[data-confirm-action-submit]');
            let pendingForm = null;

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (! modalElement || ! form.matches('form[data-confirm-message]') || form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                messageElement.textContent = form.dataset.confirmMessage;
                titleElement.textContent = form.dataset.confirmTitle || 'Konfirmasi Tindakan';
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            });

            confirmButton?.addEventListener('click', () => {
                if (! pendingForm) {
                    return;
                }

                const form = pendingForm;
                form.dataset.confirmed = 'true';
                form.submit();
                bootstrap.Modal.getOrCreateInstance(modalElement).hide();
            });

            modalElement?.addEventListener('hidden.bs.modal', () => {
                pendingForm = null;
            });
        })();
    </script>
@endpush
