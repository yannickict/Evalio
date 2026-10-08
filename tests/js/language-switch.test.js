import { afterEach, expect, it, vi } from 'vite-plus/test';
import { initializeLanguageSwitcher } from '../../resources/js/language-switch';

afterEach(() => vi.unstubAllGlobals());

function setup(fields = [], editorRefresh = null, storage = new Map()) {
    const switchForm = {
        action: '/language', dataset: { error: 'Try again' },
        addEventListener: vi.fn((name, handler) => { switchForm.submit = handler; }),
    };
    const participant = fields.length ? { elements: fields } : null;
    const elements = { 'language-switch': switchForm, 'questionnaire-form': participant, 'refresh-answer-types': editorRefresh };
    const window = {
        location: { pathname: '/questionnaire', search: '?code=123456' },
        sessionStorage: {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
            removeItem: (key) => storage.delete(key),
        },
        fetch: vi.fn(async () => ({ ok: true })), alert: vi.fn(),
    };
    initializeLanguageSwitcher({ getElementById: (id) => elements[id] ?? null }, window);
    return { switchForm, window, storage };
}

it('preserves selected answers, free text and comments across a language reload without saving other fields', async () => {
    const fields = [
        { name: 'answers[1]', type: 'radio', value: '11', checked: false },
        { name: 'answers[1]', type: 'radio', value: '12', checked: true },
        { name: 'answers[2]', type: 'textarea', value: 'My original words' },
        { name: 'answers_comment[1]', type: 'textarea', value: 'Comment' },
        { name: '_token', type: 'hidden', value: 'csrf-secret' },
    ];
    const first = setup(fields);
    const event = { preventDefault: vi.fn() };
    await first.switchForm.submit(event);
    expect(event.preventDefault).not.toHaveBeenCalled();
    expect([...first.storage.values()][0]).not.toContain('csrf-secret');

    const restored = fields.map((field) => ({ ...field, value: field.type === 'radio' ? field.value : '', checked: false }));
    setup(restored, null, first.storage);
    expect(restored[0].checked).toBe(false);
    expect(restored[1].checked).toBe(true);
    expect(restored[2].value).toBe('My original words');
    expect(restored[3].value).toBe('Comment');
    expect(first.storage.size).toBe(0);
});

it('keeps drafts isolated by feedback code', () => {
    const storage = new Map([['evalio-language-draft:/questionnaire?code=654321', JSON.stringify([{ name: 'answers[1]', value: 'Other participant' }])]]);
    const fields = [{ name: 'answers[1]', type: 'textarea', value: '' }];
    setup(fields, null, storage);
    expect(fields[0].value).toBe('');
    expect(storage.size).toBe(1);
});

it('sets the language before refreshing the complete editor draft', async () => {
    const data = { set: vi.fn() };
    vi.stubGlobal('FormData', class { constructor() { return data; } });
    const refresh = { form: { requestSubmit: vi.fn() } };
    const { switchForm, window } = setup([], refresh);
    const event = { preventDefault: vi.fn(), submitter: { value: 'en' } };
    await switchForm.submit(event);
    expect(event.preventDefault).toHaveBeenCalled();
    expect(data.set).toHaveBeenCalledWith('locale', 'en');
    expect(window.fetch).toHaveBeenCalledWith('/language', expect.objectContaining({
        method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' },
    }));
    expect(refresh.form.requestSubmit).toHaveBeenCalledWith(refresh);
});

it('keeps the editor draft in place if changing the language fails', async () => {
    vi.stubGlobal('FormData', class { set() {} });
    const refresh = { form: { requestSubmit: vi.fn() } };
    const { switchForm, window } = setup([], refresh);
    window.fetch.mockResolvedValue({ ok: false });
    await switchForm.submit({ preventDefault: vi.fn(), submitter: { value: 'en' } });
    expect(refresh.form.requestSubmit).not.toHaveBeenCalled();
    expect(window.alert).toHaveBeenCalledWith('Try again');
});

it('still allows language switching when draft storage is unavailable', async () => {
    const { switchForm, window } = setup([{ name: 'answers[1]', type: 'textarea', value: 'Draft' }]);
    window.sessionStorage.setItem = () => { throw new Error('Storage blocked'); };
    const event = { preventDefault: vi.fn() };
    await expect(switchForm.submit(event)).resolves.toBeUndefined();
    expect(event.preventDefault).not.toHaveBeenCalled();
});
