import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/assets/js/attendance-scanner.js', import.meta.url), 'utf8');

// Minimal DOM contract for scanner interactions, independent of browser acceptance tests.
function harness({ reopen = false } = {}) {
    const nodes = new Map();
    const timers = new Map();
    const requests = [];
    let timerId = 0;
    let focused;
    let respond;
    const node = (key) => {
        if (nodes.has(key)) return nodes.get(key);
        const listeners = new Map();
        const element = {
            textContent: '', className: '', hidden: true, value: '', disabled: false,
            dataset: {}, children: [], attributes: {},
            querySelector: (selector) => selector === '.is-invalid' ? null : node(selector),
            addEventListener: (name, callback) => listeners.set(name, callback),
            emit: (name, event = {}) => listeners.get(name)?.({ preventDefault() {}, ...event }),
            focus: () => { focused = key; },
            setAttribute(name, value) { this.attributes[name] = value; },
            append(...children) { this.children.push(...children); },
            prepend(child) { this.children.unshift(child); child.parent = this; },
            remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); },
            get lastElementChild() { return this.children.at(-1); },
        };
        nodes.set(key, element);
        return element;
    };
    node('manual-attendance-modal').dataset.reopen = String(reopen);
    node('[data-metric="participants"]').textContent = '10';
    node('[data-metric="attended"]').textContent = '0';
    node('[data-metric="absent"]').textContent = '10';
    node('qr-scan-form').action = '/events/1/scan';
    node('qr-scan-form').requestSubmit = () => node('qr-scan-form').emit('submit');
    runInNewContext(source, {
        document: { getElementById: node, querySelector: node, createElement: () => node(Symbol()) },
        window: {
            addEventListener: (...args) => node('window').addEventListener(...args),
            setTimeout: (callback, duration) => { timers.set(++timerId, { callback, duration }); return timerId; },
        },
        clearTimeout: (id) => timers.delete(id),
        requestAnimationFrame: (callback) => callback(),
        FormData: class { constructor() { this.payload = node('qr_payload').value; } },
        bootstrap: { Modal: { getOrCreateInstance: (modal) => ({ show: () => modal.emit('show.bs.modal') }) } },
        fetch: (url, options) => {
            requests.push({ url, options });
            return new Promise((resolve) => { respond = resolve; });
        },
    });
    return {
        node, timers, requests,
        focus: () => focused,
        start: (payload = '0123456789') => { node('qr_payload').value = payload; return node('qr-scan-form').emit('submit'); },
        respond: (data, status = 200) => respond({ status, redirected: false, json: async () => data }),
    };
}

const employee = {
    full_name: 'Synthetic Scanner', employee_number: '0123456789',
    institution: 'Unit UAT', position: 'Peserta UAT', scanned_at: '31 Aug 2026 08:05:00',
};

test('HID submit sends original payload once, masks/clears input, updates success and dismisses after 3 seconds', async () => {
    const h = harness();
    assert.equal(h.focus(), 'qr_payload');
    const pending = h.start('YAPISTA:EMPLOYEE:synthetic-test-only');
    assert.equal(h.requests[0].options.body.payload, 'YAPISTA:EMPLOYEE:synthetic-test-only');
    assert.equal(h.node('qr_payload').value, '');
    assert.equal(h.node('[data-state-title]').textContent, 'Memproses QR Code...');
    await h.node('qr_payload').emit('keydown', { key: 'Enter' });
    assert.equal(h.requests.length, 1);
    h.respond({ status: 'success', employee });
    await pending;
    assert.equal(h.node('[data-result-overlay]').hidden, false);
    assert.equal(h.node('[data-result-name]').textContent, employee.full_name);
    assert.equal(h.node('[data-metric="attended"]').textContent, 1);
    assert.equal(h.node('[data-recent-scans]').children.length, 1);
    assert.equal(h.focus(), 'qr_payload');
    assert.equal(h.timers.size, 1);
    const timer = [...h.timers.values()][0];
    assert.equal(timer.duration, 3000);
    timer.callback();
    assert.equal(h.node('[data-result-overlay]').hidden, true);
    assert.match(h.node('[data-last-summary]').textContent, /Synthetic Scanner/);
    assert.doesNotMatch(h.node('[data-last-summary]').textContent, /YAPISTA:EMPLOYEE/);
});

test('duplicate, nonparticipant and invalid feedback never increment totals or keep stale identity', async () => {
    const h = harness();
    for (const [status, message, identity, http, tone] of [
        ['already_attended', 'Kehadiran sudah tercatat.', employee, 409, 'warning'],
        ['rejected', 'Pegawai tidak terdaftar sebagai peserta kegiatan.', employee, 422, 'error'],
        ['rejected', 'QR Code tidak dikenali.', null, 422, 'error'],
    ]) {
        const pending = h.start();
        h.respond({ status, message, employee: identity }, http);
        await pending;
        assert.equal(h.node('[data-result-overlay]').className, `scanner-result-overlay is-${tone}`);
        assert.equal(h.node('[data-result-person]').hidden, !identity);
        assert.equal(h.node('[data-result-name]').textContent, identity?.full_name || '');
        assert.equal(h.node('[data-metric="attended"]').textContent, '0');
        assert.equal(h.focus(), 'qr_payload');
        assert.equal(h.timers.size, 1);
    }
});

test('modal and offcanvas keep focus until hidden, including a scan response arriving while open', async () => {
    const h = harness();
    for (const [id, type] of [['manual-attendance-modal', 'modal'], ['recent-attendance-panel', 'offcanvas']]) {
        const pending = h.start();
        h.node(id).emit(`show.bs.${type}`);
        h.node('note').focus();
        h.respond({ status: 'success', employee });
        await pending;
        assert.equal(h.focus(), 'note');
        h.node(id).emit(`hidden.bs.${type}`);
        assert.equal(h.focus(), 'qr_payload');
    }
});

test('server validation reopens manual modal without stealing focus on pageshow', () => {
    const h = harness({ reopen: true });
    h.node('manual-attendance-modal').emit('shown.bs.modal');
    assert.equal(h.focus(), 'select');
    h.node('window').emit('pageshow');
    assert.equal(h.focus(), 'select');
    h.node('manual-attendance-modal').emit('hidden.bs.modal');
    assert.equal(h.focus(), 'qr_payload');
});

test('HTTP errors do not expose exception bodies and always release submit/focus', async () => {
    const h = harness();
    for (const http of [419, 500]) {
        const pending = h.start();
        h.respond({ message: 'SQLSTATE sensitive debug' }, http);
        await pending;
        assert.equal(h.node('[data-scan-submit]').disabled, false);
        assert.equal(h.focus(), 'qr_payload');
        assert.doesNotMatch(h.node('[data-result-title]').textContent, /SQLSTATE/);
    }
});

test('rapid completed scans cancel previous dismissal and retain only five recent records', async () => {
    const h = harness();
    for (let index = 0; index < 7; index++) {
        const pending = h.start();
        h.respond({ status: 'success', employee: { ...employee, full_name: `Synthetic ${index}` } });
        await pending;
    }
    assert.equal(h.timers.size, 1);
    assert.equal(h.node('[data-recent-scans]').children.length, 5);
    assert.equal(h.node('[data-recent-scans]').children[0].children[0].textContent, 'Synthetic 6');
    assert.equal(h.node('[data-metric="attended"]').textContent, 7);
});
