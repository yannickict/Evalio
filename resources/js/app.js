import Modal from "bootstrap/js/dist/modal";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/dropdown";
import "bootstrap/js/dist/offcanvas";
import { initializeLanguageSwitcher } from './language-switch';

const sidebar = document.getElementById('main-navigation');
if (sidebar) {
    for (const [event, expanded] of [['shown.bs.offcanvas', 'true'], ['hidden.bs.offcanvas', 'false']]) {
        sidebar.addEventListener(event, () => {
            document.querySelector('[data-bs-toggle="offcanvas"][aria-controls="main-navigation"]')?.setAttribute('aria-expanded', expanded);
        });
    }
}

if (document.getElementById('language-switch')) {
    initializeLanguageSwitcher(document, window);
}

const profileForm = document.getElementById('profile-form');

if (profileForm) {
    const fields = [...profileForm.querySelectorAll('input[name="name"], input[name="email"]')];
    const initialValues = fields.map((field) => field.dataset.originalValue ?? field.value);
    const details = document.getElementById('profile-details');
    const editButton = document.getElementById('profile-edit');
    const cancelButton = document.getElementById('profile-cancel');

    editButton.addEventListener('click', () => {
        details.hidden = true;
        profileForm.hidden = false;
        editButton.hidden = true;
        editButton.setAttribute('aria-expanded', 'true');
        fields[0]?.focus();
    });

    cancelButton.addEventListener('click', () => {
        fields.forEach((field, index) => { field.value = initialValues[index]; });
        profileForm.hidden = true;
        details.hidden = false;
        editButton.hidden = false;
        editButton.setAttribute('aria-expanded', 'false');
        editButton.focus();
    });
}

const sessionOverview = document.getElementById('session-overview');
const sessionReturnKey = 'evaluation-status-return';
if (sessionOverview) {
    sessionOverview.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.matches('[data-evaluation-status-form]')) return;
        try {
            sessionStorage.setItem(sessionReturnKey, JSON.stringify({
                sessionId: form.dataset.sessionId,
                top: window.scrollY,
                course: document.getElementById('course-filter')?.value ?? '',
                instructor: document.getElementById('instructor-filter')?.value ?? '',
            }));
        } catch {
            // Status updates still work without browser storage.
        }
    });
}

for (const [overviewId, parameter] of [['session-overview', 'session'], ['course-overview', 'course']]) {
    if (!document.getElementById(overviewId)) continue;
    const selectedId = new URLSearchParams(window.location.search).get(parameter);

    if (selectedId && /^\d+$/.test(selectedId)) {
        const modal = document.getElementById(`${parameter}-${selectedId}`);

        if (modal?.classList.contains('modal')) {
            if (parameter === 'session') {
                try {
                    const saved = sessionStorage.getItem(sessionReturnKey);
                    sessionStorage.removeItem(sessionReturnKey);
                    const position = saved ? JSON.parse(saved) : null;
                    if (position?.sessionId === selectedId && Number.isFinite(position.top) && position.top >= 0) {
                        for (const field of ['course', 'instructor']) {
                            const filter = document.getElementById(`${field}-filter`);
                            if (filter) filter.value = position[field] ?? '';
                        }
                        requestAnimationFrame(() => {
                            window.scrollTo({ top: position.top, behavior: 'instant' });
                            Modal.getOrCreateInstance(modal).show();
                        });
                        continue;
                    }
                } catch {
                    // Direct links still open when stored state is unavailable or invalid.
                }
            }
            Modal.getOrCreateInstance(modal).show();
        }
    }
}

const refreshAnswerTypes = document.getElementById("refresh-answer-types");

if (refreshAnswerTypes) {
    refreshAnswerTypes.hidden = true;
    const scrollKey = "questionnaire-editor-scroll";

    try {
        const savedPosition = sessionStorage.getItem(scrollKey);
        sessionStorage.removeItem(scrollKey);

        if (savedPosition !== null) {
            const position = JSON.parse(savedPosition);
            requestAnimationFrame(() => {
                document.getElementById(position.focusId)?.focus({ preventScroll: true });
                window.scrollTo({ top: position.top, behavior: "instant" });
            });
        }
    } catch {
        // The editor still works when browser storage is unavailable.
    }

    refreshAnswerTypes.form.addEventListener("submit", (event) => {
        try {
            if (event.submitter?.hasAttribute("formaction")) {
                sessionStorage.removeItem(scrollKey);
                return;
            }
            sessionStorage.setItem(scrollKey, JSON.stringify({
                top: window.scrollY,
                focusId: document.activeElement?.id ?? "",
            }));
        } catch {
            // Saving scroll position is optional.
        }
    });

    document.querySelectorAll("[data-answer-type]").forEach((select) => {
        select.addEventListener("change", () => {
            select.form.requestSubmit(refreshAnswerTypes);
        });
    });
}

const instructorFilter = document.getElementById("instructor-filter");
const courseFilter = document.getElementById("course-filter");

if (instructorFilter || courseFilter) {
    const sessions = document.querySelectorAll("[data-session]");

    const filterSessions = () => {
        const instructor = instructorFilter?.value ?? "";
        const course = courseFilter?.value ?? "";

        sessions.forEach((session) => {
            const matchesInstructor = !instructor || session.dataset.instructor === instructor;
            const matchesCourse = !course || session.dataset.course === course;
            session.hidden = !(matchesInstructor && matchesCourse);
        });
    };

    instructorFilter?.addEventListener("change", filterSessions);
    courseFilter?.addEventListener("change", filterSessions);
    filterSessions();
}
