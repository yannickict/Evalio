import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';

const { show, getOrCreateInstance } = vi.hoisted(() => {
    const show = vi.fn();
    return { show, getOrCreateInstance: vi.fn(() => ({ show })) };
});
vi.mock('bootstrap/js/dist/modal', () => ({ default: { getOrCreateInstance } }));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));
vi.mock('bootstrap/js/dist/offcanvas', () => ({}));

describe('overview select filters', () => {
    let course;
    let instructor;
    let sessions;
    let document;

    beforeEach(() => {
        vi.resetModules();
        course = Object.assign(new EventTarget(), { value: '' });
        instructor = Object.assign(new EventTarget(), { value: '' });
        sessions = [
            { hidden: false, dataset: { course: '1', instructor: '1' } },
            { hidden: false, dataset: { course: '10', instructor: '2' } },
            { hidden: false, dataset: { course: '3', instructor: '1' } },
        ];
        document = {
            getElementById: (id) => ({ 'course-filter': course, 'instructor-filter': instructor })[id] ?? null,
            querySelectorAll: () => sessions,
        };
        vi.stubGlobal('document', document);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('matches exact IDs, combines selections, and resets to all', async () => {
        await import('../../resources/js/app.js');
        expect(sessions.map((session) => session.hidden)).toEqual([false, false, false]);
        course.value = '1';
        course.dispatchEvent(new Event('change'));
        expect(sessions.map((session) => session.hidden)).toEqual([false, true, true]);
        instructor.value = '2';
        instructor.dispatchEvent(new Event('change'));
        expect(sessions.every((session) => session.hidden)).toBe(true);
        course.value = '';
        course.dispatchEvent(new Event('change'));
        expect(sessions.map((session) => session.hidden)).toEqual([true, false, true]);
        instructor.value = '';
        instructor.dispatchEvent(new Event('change'));
        expect(sessions.every((session) => !session.hidden)).toBe(true);
    });

    it('applies an existing selection with only one filter present', async () => {
        course.value = '3';
        document.getElementById = (id) => id === 'course-filter' ? course : null;
        await import('../../resources/js/app.js');
        expect(sessions.map((session) => session.hidden)).toEqual([true, true, false]);
    });
});

describe('overview detail links', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
    });

    afterEach(() => vi.unstubAllGlobals());

    it.each(['course', 'session'])('opens the selected %s details', async (type) => {
        const modal = { classList: { contains: (name) => name === 'modal' } };
        vi.stubGlobal('window', { location: { search: `?${type}=12` } });
        vi.stubGlobal('document', {
            getElementById: (id) => id === `${type}-overview` ? new EventTarget() : id === `${type}-12` ? modal : null,
        });

        await import('../../resources/js/app.js');

        expect(getOrCreateInstance).toHaveBeenCalledWith(modal);
        expect(show).toHaveBeenCalledOnce();
    });

    it.each(['?course=invalid', '?course=999', '?session=12'])('ignores unavailable or invalid course targets: %s', async (search) => {
        vi.stubGlobal('window', { location: { search } });
        vi.stubGlobal('document', { getElementById: (id) => id === 'course-overview' ? {} : null });

        await import('../../resources/js/app.js');

        expect(show).not.toHaveBeenCalled();
    });

    it('preserves the list position and filters across an evaluation status update', async () => {
        const overview = new EventTarget();
        const modal = { classList: { contains: (name) => name === 'modal' } };
        const storage = new Map();
        const course = Object.assign(new EventTarget(), { value: '3' });
        const instructor = Object.assign(new EventTarget(), { value: '7' });
        const rows = [{ dataset: { course: '3', instructor: '7' } }, { dataset: { course: '4', instructor: '7' } }];
        const scrollTo = vi.fn(() => expect(show).not.toHaveBeenCalled());
        vi.stubGlobal('sessionStorage', {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
            removeItem: (key) => storage.delete(key),
        });
        const frames = [];
        vi.stubGlobal('requestAnimationFrame', (callback) => frames.push(callback));
        vi.stubGlobal('window', { location: { search: '' }, scrollY: 850, scrollTo });
        vi.stubGlobal('document', {
            getElementById: (id) => ({ 'session-overview': overview, 'session-12': modal, 'course-filter': course, 'instructor-filter': instructor })[id] ?? null,
            querySelectorAll: () => rows,
        });
        await import('../../resources/js/app.js');
        const submission = new Event('submit');
        Object.defineProperty(submission, 'target', { value: { matches: () => true, dataset: { sessionId: '12' } } });
        overview.dispatchEvent(submission);

        vi.resetModules();
        window.location.search = '?session=12';
        course.value = '';
        instructor.value = '';
        await import('../../resources/js/app.js');
        expect(rows.map(row => row.hidden)).toEqual([false, true]);
        frames.forEach(callback => callback());
        expect(scrollTo).toHaveBeenCalledWith({ top: 850, behavior: 'instant' });
        expect(show).toHaveBeenCalledOnce();
        expect(storage.size).toBe(0);
    });
});
