import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';

vi.mock('bootstrap/js/dist/modal', () => ({}));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));

// Small event-capable DOM doubles keep these tests independent of Bootstrap.
function element(properties = {}) {
    const attributes = new Map();
    const classes = new Set();
    return Object.assign(new EventTarget(), {
        hidden: false,
        value: '',
        setAttribute: (name, value) => attributes.set(name, value),
        getAttribute: (name) => attributes.get(name),
        removeAttribute: (name) => attributes.delete(name),
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
            contains: (name) => classes.has(name),
            toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
        },
        scrollIntoView: vi.fn(),
        focus() { this.dispatchEvent(new Event('focus')); },
    }, properties);
}

function key(input, value) {
    const event = new Event('keydown', { cancelable: true });
    Object.defineProperty(event, 'key', { value });
    input.dispatchEvent(event);
    return event;
}

describe('overview filters and searchable dropdowns', () => {
    let document;
    let course;
    let instructor;
    let sessions;
    let options;
    let menu;
    let clear;
    let noResults;
    let dropdown;

    beforeEach(() => {
        vi.resetModules();
        course = element();
        instructor = element();
        sessions = [
            element({ dataset: { course: 'Laravel basics', instructor: 'Alex Example' } }),
            element({ dataset: { course: 'Laravel advanced', instructor: 'Zoe Example' } }),
            element({ dataset: { course: 'PHP basics', instructor: 'Alex Example' } }),
        ];
        options = ['Laravel basics', 'Laravel advanced', 'PHP basics'].map((textContent, index) =>
            element({ textContent, id: `course-option-${index}` }));
        noResults = element({ hidden: true });
        clear = element({ hidden: true });
        menu = element({
            querySelectorAll: () => options,
            querySelector: () => noResults,
        });
        dropdown = element({
            querySelector: (selector) => selector === 'input' ? course : selector === '[data-clear-search]' ? clear : menu,
            contains: (target) => [course, clear, menu, ...options].includes(target),
        });
        document = element({
            getElementById: (id) => ({ 'course-filter': course, 'instructor-filter': instructor })[id] ?? null,
            querySelectorAll: (selector) => selector === '[data-searchable-dropdown]' ? [dropdown] : sessions,
        });
        vi.stubGlobal('document', document);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('shows all sessions initially and combines trimmed, case-insensitive partial searches', async () => {
        await import('../../resources/js/app.js');
        expect(sessions.map((session) => session.hidden)).toEqual([false, false, false]);
        course.value = '  LARAVEL  ';
        course.dispatchEvent(new Event('input'));
        expect(sessions.map((session) => session.hidden)).toEqual([false, false, true]);
        instructor.value = ' alex ';
        instructor.dispatchEvent(new Event('input'));
        expect(sessions.map((session) => session.hidden)).toEqual([false, true, true]);
        course.value = '';
        course.dispatchEvent(new Event('input'));
        expect(sessions.map((session) => session.hidden)).toEqual([false, true, false]);
        instructor.value = 'nobody';
        instructor.dispatchEvent(new Event('input'));
        expect(sessions.every((session) => session.hidden)).toBe(true);
    });

    it('applies existing filter values on load and supports pages with only one filter', async () => {
        course.value = 'PHP';
        document.getElementById = (id) => id === 'course-filter' ? course : null;
        await import('../../resources/js/app.js');
        expect(sessions.map((session) => session.hidden)).toEqual([true, true, false]);
    });

    it('opens on focus and click, filters suggestions, and reports no matches', async () => {
        await import('../../resources/js/app.js');
        course.focus();
        expect(menu.classList.contains('show')).toBe(true);
        expect(course.getAttribute('aria-expanded')).toBe('true');
        expect(clear.hidden).toBe(true);
        course.value = '  PHP  ';
        course.dispatchEvent(new Event('input'));
        expect(options.map((option) => option.hidden)).toEqual([true, true, false]);
        expect(noResults.hidden).toBe(true);
        expect(clear.hidden).toBe(false);
        course.value = 'missing';
        course.dispatchEvent(new Event('input'));
        expect(noResults.hidden).toBe(false);
        expect(() => key(course, 'ArrowDown')).not.toThrow();
        expect(course.getAttribute('aria-activedescendant')).toBeUndefined();
        key(course, 'Escape');
        course.dispatchEvent(new Event('click'));
        expect(menu.classList.contains('show')).toBe(true);
    });

    it('selects a suggestion by click and updates session visibility', async () => {
        await import('../../resources/js/app.js');
        course.focus();
        options[2].dispatchEvent(new Event('click'));
        expect(course.value).toBe('PHP basics');
        expect(sessions.map((session) => session.hidden)).toEqual([true, true, false]);
        expect(menu.classList.contains('show')).toBe(false);
        expect(course.getAttribute('aria-expanded')).toBe('false');
        expect(clear.hidden).toBe(false);
    });

    it('navigates visible suggestions with wrapping and selects with Enter', async () => {
        await import('../../resources/js/app.js');
        course.value = 'laravel';
        course.dispatchEvent(new Event('input'));
        expect(key(course, 'Enter').defaultPrevented).toBe(false);
        expect(key(course, 'ArrowDown').defaultPrevented).toBe(true);
        expect(course.getAttribute('aria-activedescendant')).toBe(options[0].id);
        expect(options[0].getAttribute('aria-selected')).toBe('true');
        key(course, 'ArrowDown');
        expect(course.getAttribute('aria-activedescendant')).toBe(options[1].id);
        key(course, 'ArrowDown');
        expect(course.getAttribute('aria-activedescendant')).toBe(options[0].id);
        key(course, 'ArrowUp');
        expect(course.getAttribute('aria-activedescendant')).toBe(options[1].id);
        expect(key(course, 'Enter').defaultPrevented).toBe(true);
        expect(course.value).toBe('Laravel advanced');
        expect(sessions.map((session) => session.hidden)).toEqual([true, false, true]);
        expect(course.getAttribute('aria-activedescendant')).toBeUndefined();
        expect(options.every((option) => option.getAttribute('aria-selected') === 'false')).toBe(true);
    });

    it('clears the search, restores suggestions, and preserves the other filter', async () => {
        await import('../../resources/js/app.js');
        instructor.value = 'Alex';
        instructor.dispatchEvent(new Event('input'));
        options[1].dispatchEvent(new Event('click'));
        clear.dispatchEvent(new Event('click'));
        expect(course.value).toBe('');
        expect(clear.hidden).toBe(true);
        expect(options.every((option) => !option.hidden)).toBe(true);
        expect(menu.classList.contains('show')).toBe(true);
        expect(sessions.map((session) => session.hidden)).toEqual([false, true, false]);
    });

    it.each(['Escape', 'Tab', 'outside click', 'focus leaves'])('closes and resets accessibility state when %s', async (action) => {
        await import('../../resources/js/app.js');
        key(course, 'ArrowDown');
        if (action === 'outside click') document.dispatchEvent(new Event('click'));
        else if (action === 'focus leaves') dropdown.dispatchEvent(new Event('focusout'));
        else key(course, action);
        expect(menu.classList.contains('show')).toBe(false);
        expect(course.getAttribute('aria-expanded')).toBe('false');
        expect(course.getAttribute('aria-activedescendant')).toBeUndefined();
        expect(options.every((option) => option.getAttribute('aria-selected') === 'false')).toBe(true);
    });

    it('keeps focus during menu clicks and stays open when focus moves inside the dropdown', async () => {
        await import('../../resources/js/app.js');
        course.focus();
        const mouse = new Event('mousedown', { cancelable: true });
        menu.dispatchEvent(mouse);
        expect(mouse.defaultPrevented).toBe(true);
        const focus = new Event('focusout');
        Object.defineProperty(focus, 'relatedTarget', { value: clear });
        dropdown.dispatchEvent(focus);
        expect(menu.classList.contains('show')).toBe(true);
    });
});
