import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';

// Bootstrap has its own DOM behavior; these tests exercise our message handlers.
vi.mock('bootstrap/js/dist/modal', () => ({}));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));

describe('incomplete questionnaire message', () => {
    let form;
    let message;

    beforeEach(() => {
        vi.resetModules();
        form = new EventTarget();
        form.querySelector = vi.fn().mockReturnValue({});
        vi.spyOn(form, 'addEventListener');
        message = { hidden: true };
        vi.stubGlobal('document', {
            querySelectorAll: vi.fn().mockReturnValue([]),
            getElementById: vi.fn((id) => {
                if (id === 'questionnaire-form') return form;
                if (id === 'incomplete-answers') return message;
                return null;
            }),
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('starts hidden and shows on invalid submission without suppressing native validation', async () => {
        await import('../../resources/js/app.js');
        expect(message.hidden).toBe(true);
        // Invalid events do not bubble, so the form must listen in capture mode.
        expect(form.addEventListener).toHaveBeenCalledWith('invalid', expect.any(Function), true);
        const invalid = new Event('invalid', { cancelable: true });
        form.dispatchEvent(invalid);
        expect(message.hidden).toBe(false);
        expect(invalid.defaultPrevented).toBe(false);
    });

    it('keeps the message visible while any required answer is missing', async () => {
        await import('../../resources/js/app.js');
        form.dispatchEvent(new Event('invalid'));
        form.dispatchEvent(new Event('input'));
        expect(form.querySelector).toHaveBeenCalledWith(':invalid');
        expect(message.hidden).toBe(false);
    });

    it('hides the message once all answers are valid and can show it again', async () => {
        await import('../../resources/js/app.js');
        form.dispatchEvent(new Event('invalid'));
        form.querySelector.mockReturnValue(null);
        form.dispatchEvent(new Event('input'));
        expect(message.hidden).toBe(true);
        form.dispatchEvent(new Event('invalid'));
        expect(message.hidden).toBe(false);
    });

    it.each(['no questionnaire', 'empty questionnaire'])('loads safely on pages with %s', async (page) => {
        if (page === 'no questionnaire') form = null;
        message = null;
        await expect(import('../../resources/js/app.js')).resolves.toBeDefined();
    });
});
