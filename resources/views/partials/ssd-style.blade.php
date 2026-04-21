<style>
/* ── Searchable Select Dropdown (SSD) — CRITICAL CSS (must be in <head>) ── */
.ssd-panel { display: none; }
.ssd-wrap { position: relative; }
.ssd-trigger {
    cursor: pointer; text-align: left; user-select: none;
    min-height: 38px; display: flex; align-items: center; gap: 8px;
}
.ssd-trigger .ssd-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ssd-panel {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    min-width: 100%;
    width: max-content;
    max-width: 300px;
    z-index: 300;
    background: #fff;
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.13);
    overflow: hidden;
    animation: ssd-in 0.12s ease;
}
@keyframes ssd-in {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dark .ssd-panel { background: #1e293b; border-color: #334155; }
.ssd-search-wrap {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px 7px;
    border-bottom: 1px solid var(--color-border, #e2e8f0);
    font-size: 12px; color: #94a3b8;
}
.dark .ssd-search-wrap { border-color: #334155; }
.ssd-search {
    flex: 1; border: none; outline: none;
    background: transparent; font-size: 13px; color: inherit; padding: 0;
}
.ssd-list { max-height: 210px; overflow-y: auto; padding: 4px 0; }
.ssd-option {
    padding: 8px 14px; font-size: 13px; cursor: pointer;
    color: var(--color-text, #1e293b); white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
    transition: background 0.1s;
}
.dark .ssd-option { color: #e2e8f0; }
.ssd-option:hover { background: #eff6ff; color: #2563eb; }
.dark .ssd-option:hover { background: #1e3a5f; color: #93c5fd; }
.ssd-option.ssd-selected { background: #dbeafe; color: #2563eb; font-weight: 600; }
.dark .ssd-option.ssd-selected { background: #1e3a5f; color: #60a5fa; }
.ssd-option.ssd-hidden { display: none; }
.ssd-empty { padding: 10px 14px; font-size: 12px; color: #94a3b8; text-align: center; font-style: italic; }
.ssd-wrap.ssd-open .ssd-panel { display: block; }
/* panel yang sudah di-portal ke body tidak lagi dalam .ssd-wrap, pakai class ini */
.ssd-panel[style*="fixed"] { display: block; }
.ssd-wrap.ssd-open .ssd-icon { transform: rotate(180deg); transition: transform 0.15s; }
</style>
