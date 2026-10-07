import { afterEach, expect, it, vi } from 'vite-plus/test';

vi.mock('bootstrap/js/dist/modal', () => ({ default: {} }));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetModules();
});

it('opens the editor and cancels back to saved values even after a validation error', async () => {
    const fields = [
        { value: 'Draft name', dataset: { originalValue: 'Saved name' }, focus: vi.fn() },
        { value: 'draft@example.com', dataset: { originalValue: 'saved@example.com' } },
    ];
    const form = { hidden: true, querySelectorAll: () => fields };
    const details = { hidden: false };
    const edit = Object.assign(new EventTarget(), { hidden: false, setAttribute: vi.fn(), focus: vi.fn() });
    const cancel = new EventTarget();
    const elements = { 'profile-form': form, 'profile-details': details, 'profile-edit': edit, 'profile-cancel': cancel };
    vi.stubGlobal('document', { getElementById: (id) => elements[id] ?? null });
    await import('../../resources/js/app.js');

    edit.dispatchEvent(new Event('click'));
    expect(form.hidden).toBe(false);
    expect(details.hidden).toBe(true);
    expect(edit.hidden).toBe(true);
    expect(fields[0].focus).toHaveBeenCalled();

    cancel.dispatchEvent(new Event('click'));
    expect(fields.map((field) => field.value)).toEqual(['Saved name', 'saved@example.com']);
    expect(form.hidden).toBe(true);
    expect(details.hidden).toBe(false);
    expect(edit.hidden).toBe(false);
    expect(edit.setAttribute).toHaveBeenLastCalledWith('aria-expanded', 'false');
    expect(edit.focus).toHaveBeenCalled();
});
