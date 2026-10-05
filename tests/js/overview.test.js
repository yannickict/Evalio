import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';

vi.mock('bootstrap/js/dist/modal', () => ({}));
vi.mock('bootstrap/js/dist/collapse', () => ({}));
vi.mock('bootstrap/js/dist/dropdown', () => ({}));

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
