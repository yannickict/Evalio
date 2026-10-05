import { afterEach, beforeEach, expect, it, vi } from 'vite-plus/test';

vi.mock('bootstrap/js/dist/modal', () => ({}));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));

let form;
let select;
let refresh;
let storage;
let windowMock;

beforeEach(() => {
    vi.resetModules();
    form = Object.assign(new EventTarget(), { requestSubmit: vi.fn() });
    select = Object.assign(new EventTarget(), { id: 'question-4-type', form, focus: vi.fn() });
    refresh = { form, hidden: false };
    storage = new Map();
    windowMock = { scrollY: 900, scrollTo: vi.fn() };
    vi.stubGlobal('window', windowMock);
    vi.stubGlobal('requestAnimationFrame', (callback) => callback());
    vi.stubGlobal('sessionStorage', {
        getItem: (key) => storage.get(key) ?? null,
        setItem: (key, value) => storage.set(key, value),
        removeItem: (key) => storage.delete(key),
    });
    vi.stubGlobal('document', {
        activeElement: select,
        getElementById: (id) => ({ 'refresh-answer-types': refresh, 'question-4-type': select })[id] ?? null,
        querySelectorAll: () => [select],
    });
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

it('automatically refreshes answer types and remembers the editor position on draft submissions', async () => {
    await import('../../resources/js/app.js');
    expect(refresh.hidden).toBe(true);
    select.dispatchEvent(new Event('change'));
    expect(form.requestSubmit).toHaveBeenCalledWith(refresh);
    form.dispatchEvent(new Event('submit'));
    expect(JSON.parse(storage.get('questionnaire-editor-scroll'))).toEqual({ top: 900, focusId: select.id });
});

it('restores scroll and focus once after the server refresh', async () => {
    storage.set('questionnaire-editor-scroll', JSON.stringify({ top: 850, focusId: select.id }));
    await import('../../resources/js/app.js');
    expect(select.focus).toHaveBeenCalledWith({ preventScroll: true });
    expect(windowMock.scrollTo).toHaveBeenCalledWith({ top: 850, behavior: 'instant' });
    expect(storage.has('questionnaire-editor-scroll')).toBe(false);
});

it('clears stored position when saving the finished questionnaire', async () => {
    await import('../../resources/js/app.js');
    form.dispatchEvent(new Event('submit'));
    const saveEvent = new Event('submit');
    Object.defineProperty(saveEvent, 'submitter', { value: { hasAttribute: () => true } });
    form.dispatchEvent(saveEvent);
    expect(storage.has('questionnaire-editor-scroll')).toBe(false);
});
