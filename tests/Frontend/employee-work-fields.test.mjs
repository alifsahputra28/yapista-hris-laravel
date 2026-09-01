import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/assets/js/employee-work-fields.js', import.meta.url), 'utf8');

function form(unitValue = '', positionValue = '') {
    const events = new Map();
    const option = (value, unit) => ({
        value, dataset: { institutionId: unit }, defaultSelected: false,
        cloneNode() { return { ...this }; },
    });
    const options = [option('11', '1'), option('12', '1'), option('21', '2')];
    const unit = {
        value: unitValue,
        addEventListener: (name, callback) => events.set(name, callback),
        form: { addEventListener: (name, callback) => events.set(name, callback) },
    };
    const position = { value: positionValue, disabled: false, replaceChildren(...children) { this.options = children; } };
    const help = { textContent: '' };
    const nodes = {
        'institution_id': unit, 'position_id': position, 'position-help': help,
        'employee-position-options': { content: { querySelectorAll: () => options } },
    };
    runInNewContext(source, {
        document: { getElementById: (id) => nodes[id] },
        Option: class { constructor(text, value) { this.text = text; this.value = value; } },
        window: { addEventListener: (name, callback) => events.set(name, callback) },
        requestAnimationFrame: (callback) => callback(),
    });
    return { unit, position, help, fire: (name) => events.get(name)(), values: () => position.options.map(o => o.value) };
}

test('position starts disabled and is populated with only the chosen unit', () => {
    const f = form();
    assert.equal(f.position.disabled, true);
    assert.equal(f.position.options[0].text, 'Pilih unit kerja terlebih dahulu');
    f.unit.value = '1';
    f.fire('change');
    assert.equal(f.position.disabled, false);
    assert.deepEqual(f.values(), ['', '11', '12']);
    assert.equal(f.position.value, '');
});

test('unit change resets an existing selection and never retains another unit position', () => {
    const f = form('1', '11');
    assert.equal(f.position.value, '11');
    f.unit.value = '2';
    f.fire('change');
    assert.deepEqual(f.values(), ['', '21']);
    assert.equal(f.position.value, '');
    f.unit.value = '';
    f.fire('change');
    assert.equal(f.position.disabled, true);
    assert.equal(f.position.value, '');
});

test('empty unit displays an explanation and disables the select', () => {
    const f = form('3');
    assert.equal(f.position.disabled, true);
    assert.equal(f.help.textContent, 'Belum ada jabatan pada unit kerja ini.');
    assert.deepEqual(f.values(), ['']);
});

test('valid edit/old values persist but a mismatched old position is cleared', () => {
    assert.equal(form('1', '12').position.value, '12');
    assert.equal(form('1', '21').position.value, '');
});

test('browser back-forward and native reset synchronize the dependent select', () => {
    const f = form('1', '11');
    f.unit.value = '2';
    f.position.value = '21';
    f.fire('pageshow');
    assert.deepEqual(f.values(), ['', '21']);
    assert.equal(f.position.value, '21');
    f.unit.value = '1';
    f.fire('reset');
    assert.equal(f.position.value, '11');
    assert.equal(f.position.options.find(o => o.value === '11').defaultSelected, true);
});
