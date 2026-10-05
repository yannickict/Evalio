import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/dropdown";

const questionnaire = document.getElementById("questionnaire-form");
const incompleteAnswers = document.getElementById("incomplete-answers");

if (questionnaire && incompleteAnswers) {
    questionnaire.addEventListener(
        "invalid",
        () => {
            incompleteAnswers.hidden = false;
        },
        true,
    );

    questionnaire.addEventListener("input", () => {
        if (!questionnaire.querySelector(":invalid")) {
            incompleteAnswers.hidden = true;
        }
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
