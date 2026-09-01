(() => {
    const source = document.getElementById('employee-position-options');
    if (!source) return;

    const unit = document.getElementById('institution_id');
    const position = document.getElementById('position_id');
    const help = document.getElementById('position-help');
    const options = Array.from(source.content.querySelectorAll('option'));
    const initialUnit = unit.value;
    const initialPosition = position.value;

    const updatePositions = (selected = '') => {
        const matches = options.filter((option) => option.dataset.institutionId === unit.value);
        const empty = unit.value !== '' && matches.length === 0;
        const placeholder = !unit.value ? 'Pilih unit kerja terlebih dahulu'
            : (empty ? 'Belum ada jabatan pada unit kerja ini.' : 'Pilih jabatan');
        position.replaceChildren(new Option(placeholder, ''), ...matches.map((option) => {
            const copy = option.cloneNode(true);
            copy.defaultSelected = unit.value === initialUnit && copy.value === initialPosition;
            copy.selected = copy.value === selected;
            return copy;
        }));
        position.value = matches.some((option) => option.value === selected) ? selected : '';
        position.disabled = !unit.value || empty;
        help.textContent = empty ? 'Belum ada jabatan pada unit kerja ini.'
            : 'Pilihan jabatan menyesuaikan unit kerja yang dipilih.';
    };

    unit.addEventListener('change', () => updatePositions());
    // Preserve server-rendered edit/old input and browser back-forward restoration.
    updatePositions(initialPosition);
    window.addEventListener('pageshow', () => updatePositions(position.value));
    unit.form?.addEventListener('reset', () => requestAnimationFrame(() => updatePositions(initialPosition)));
})();
