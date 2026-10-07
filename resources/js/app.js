import Modal from "bootstrap/js/dist/modal";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/dropdown";

for (const [overviewId, parameter] of [['session-overview', 'session'], ['course-overview', 'course']]) {
    if (!document.getElementById(overviewId)) continue;
    const selectedId = new URLSearchParams(window.location.search).get(parameter);

    if (selectedId && /^\d+$/.test(selectedId)) {
        const modal = document.getElementById(`${parameter}-${selectedId}`);

        if (modal?.classList.contains('modal')) {
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
