(() => {
    const scanForm = document.getElementById('qr-scan-form');
    if (!scanForm) return;

    const input = document.getElementById('qr_payload');
    const submitButton = scanForm.querySelector('[data-scan-submit]');
    const state = document.querySelector('[data-scanner-state]');
    const overlay = document.querySelector('[data-result-overlay]');
    const manualModal = document.getElementById('manual-attendance-modal');
    const recentPanel = document.getElementById('recent-attendance-panel');
    const recentScans = document.querySelector('[data-recent-scans]');
    const openSurfaces = new Set();
    let dismissTimer;
    let submitting = false;

    const focusScanner = () => {
        if (openSurfaces.size) return;
        input.focus({ preventScroll: true });
    };

    const ready = () => {
        state.className = 'scanner-state is-ready';
        state.querySelector('[data-state-icon]').className = 'ti ti-scan';
        state.querySelector('[data-state-title]').textContent = 'Siap menerima scanner';
        state.querySelector('[data-state-message]').textContent = 'Arahkan scanner ke QR Code pada ID Card pegawai.';
    };

    const showResult = (status, message, employee = null) => {
        clearTimeout(dismissTimer);
        const success = status === 'success';
        const duplicate = status === 'already_attended';
        const tone = success ? 'success' : (duplicate ? 'warning' : 'error');
        const title = success ? 'Kehadiran berhasil dicatat' : (duplicate ? 'Kehadiran sudah tercatat' : message);
        const icon = success ? 'ti-circle-check' : (duplicate ? 'ti-alert-triangle' : 'ti-circle-x');
        overlay.className = `scanner-result-overlay is-${tone}`;
        overlay.querySelector('[data-result-icon]').className = `ti ${icon}`;
        overlay.querySelector('[data-result-title]').textContent = title;
        overlay.querySelector('[data-result-message]').textContent = employee ? '' : (
            message.startsWith('QR Code') ? 'Gunakan QR Code ID Card YAPISTA atau kartu pegawai lama.' : ''
        );
        const person = overlay.querySelector('[data-result-person]');
        person.hidden = !employee;
        // Clear previous identity even for an invalid scan, so feedback cannot show stale data.
        person.querySelector('[data-result-name]').textContent = employee?.full_name || '';
        person.querySelector('[data-result-number]').textContent = employee?.employee_number || '';
        person.querySelector('[data-result-unit]').textContent = employee?.institution || '';
        person.querySelector('[data-result-position]').textContent = employee?.position || '';
        person.querySelector('[data-result-time]').textContent = employee?.scanned_at ? `Tercatat ${employee.scanned_at} WIB` : '';
        overlay.hidden = false;

        const summary = [employee?.full_name, employee ? `NUP ${employee.employee_number}` : '', title, employee?.scanned_at ? `${employee.scanned_at} WIB` : ''].filter(Boolean);
        document.querySelector('[data-last-summary]').textContent = summary.join(' - ');
        document.querySelector('[data-last-icon]').className = `ti ${icon} text-${success ? 'success' : (duplicate ? 'warning' : 'danger')}`;
        ready();
        dismissTimer = window.setTimeout(() => { overlay.hidden = true; }, 3000);
    };

    const addRecentScan = (employee) => {
        if (!employee) return;
        recentScans.querySelector('[data-recent-empty]')?.remove();
        const item = document.createElement('div');
        item.className = 'list-group-item scanner-recent-item';
        const name = document.createElement('strong');
        name.textContent = employee.full_name || '-';
        const meta = document.createElement('small');
        meta.textContent = `NUP ${employee.employee_number || 'Belum diisi'} - ${employee.institution || 'Unit belum diisi'}`;
        const result = document.createElement('div');
        result.className = 'd-flex flex-wrap align-items-center gap-2 mt-2';
        const badge = document.createElement('span');
        badge.className = 'badge bg-light-success text-success';
        badge.textContent = 'QR Code';
        const time = document.createElement('small');
        time.textContent = employee.scanned_at ? `${employee.scanned_at} WIB` : 'Baru saja';
        result.append(badge, time);
        item.append(name, meta, result);
        recentScans.prepend(item);
        while (recentScans.children.length > 5) recentScans.lastElementChild.remove();
    };

    const updateMetrics = () => {
        const participants = Number(document.querySelector('[data-metric="participants"]').textContent || 0);
        const attendedElement = document.querySelector('[data-metric="attended"]');
        const attended = Number(attendedElement.textContent || 0) + 1;
        attendedElement.textContent = attended;
        document.querySelector('[data-metric="absent"]').textContent = Math.max(participants - attended, 0);
        document.querySelector('[data-metric="percentage"]').textContent = `${participants ? Math.round((attended / participants) * 1000) / 10 : 0}%`;
    };

    // Track opening/closing transitions as well as visible surfaces, preserving Bootstrap's focus trap.
    [[manualModal, 'modal'], [recentPanel, 'offcanvas']].forEach(([surface, type]) => {
        surface.addEventListener(`show.bs.${type}`, () => {
            openSurfaces.add(surface);
            overlay.hidden = true;
        });
        surface.addEventListener(`hidden.bs.${type}`, () => {
            openSurfaces.delete(surface);
            requestAnimationFrame(focusScanner);
        });
    });
    manualModal.addEventListener('shown.bs.modal', () => {
        (manualModal.querySelector('.is-invalid') || manualModal.querySelector('select'))?.focus();
    });

    if (manualModal.dataset.reopen === 'true') {
        bootstrap.Modal.getOrCreateInstance(manualModal).show();
    } else {
        focusScanner();
    }
    window.addEventListener('pageshow', () => {
        input.value = '';
        focusScanner();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        scanForm.requestSubmit();
    });

    scanForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting || openSurfaces.size) return;
        submitting = true;
        submitButton.disabled = true;
        scanForm.setAttribute('aria-busy', 'true');
        clearTimeout(dismissTimer);
        overlay.hidden = true;
        state.className = 'scanner-state is-processing';
        state.querySelector('[data-state-icon]').className = 'ti ti-loader scanner-spin';
        state.querySelector('[data-state-title]').textContent = 'Memproses QR Code...';
        state.querySelector('[data-state-message]').textContent = 'Mohon tunggu.';
        const body = new FormData(scanForm);
        input.value = '';

        try {
            const response = await fetch(scanForm.action, {
                method: 'POST', body,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if ([401, 419].includes(response.status) || response.redirected) {
                showResult('rejected', 'Sesi telah berakhir. Muat ulang halaman untuk masuk kembali.');
                return;
            }
            // Never echo exception bodies, submitted payloads or unexpected server error details.
            if (![200, 409, 422].includes(response.status)) throw new Error('Unexpected response');
            const data = await response.json();
            const status = data.status || (data.success ? 'success' : 'rejected');
            showResult(status, data.message || 'QR Code tidak dikenali.', data.employee);
            if (status === 'success') {
                updateMetrics();
                addRecentScan(data.employee);
            }
        } catch {
            showResult('rejected', 'Koneksi bermasalah. Periksa kehadiran terbaru sebelum mencoba kembali.');
        } finally {
            submitting = false;
            submitButton.disabled = false;
            scanForm.setAttribute('aria-busy', 'false');
            focusScanner();
        }
    });
})();
