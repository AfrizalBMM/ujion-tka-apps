<div class="fixed inset-0 z-50 hidden" data-confirm-modal aria-hidden="true">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-confirm-modal-overlay></div>

    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm text-textSecondary dark:text-slate-300 font-bold" data-confirm-modal-title>Konfirmasi</div>
                    <div class="mt-2" data-confirm-modal-message>Yakin?</div>
                </div>
                <button type="button" class="btn-secondary px-3" data-confirm-modal-cancel aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="mt-4 hidden" data-confirm-modal-prompt-wrap>
                <label class="text-xs font-bold text-textSecondary dark:text-slate-300" data-confirm-modal-prompt-label>
                    Ketik konfirmasi
                </label>
                <input
                    type="text"
                    class="input mt-2"
                    autocomplete="off"
                    data-confirm-modal-prompt-input
                />
                <div class="mt-2 text-xs text-textSecondary dark:text-slate-400" data-confirm-modal-prompt-help>
                    Untuk melanjutkan, ketik teks yang diminta.
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" class="btn-secondary" data-confirm-modal-cancel>Batal</button>
                <button type="button" class="btn-danger" data-confirm-modal-confirm>Ya, lanjut</button>
            </div>
        </div>
    </div>
</div>
