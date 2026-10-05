import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/dropdown";

const answerType = document.getElementById("question-one-type");

if (answerType) {
    const answerOptions = document.getElementById("question-one-options");
    const freeTextPreview = document.getElementById("question-one-preview");
    const commentOptions = document.getElementById("question-one-comment-options");

    const updateAnswerType = () => {
        const isSingleChoice = answerType.value === "single_choice";
        answerOptions.hidden = !isSingleChoice;
        commentOptions.hidden = !isSingleChoice;
        freeTextPreview.hidden = isSingleChoice;
    };

    answerType.addEventListener("change", updateAnswerType);
    updateAnswerType();
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
