import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/dropdown";

document.querySelectorAll("[data-searchable-dropdown]").forEach((dropdown) => {
    const input = dropdown.querySelector("input");
    const clearButton = dropdown.querySelector("[data-clear-search]");
    const menu = dropdown.querySelector('[role="listbox"]');
    const options = [...menu.querySelectorAll('[role="option"]')];
    let active = -1;

    const visibleOptions = () => options.filter((option) => !option.hidden);
    const highlight = (index) => {
        active = index;
        const selected = visibleOptions()[index];
        options.forEach((option) => {
            option.classList.toggle("active", option === selected);
            option.setAttribute("aria-selected", String(option === selected));
        });
        if (selected) {
            input.setAttribute("aria-activedescendant", selected.id);
            selected.scrollIntoView({ block: "nearest" });
        } else {
            input.removeAttribute("aria-activedescendant");
        }
    };
    const close = () => {
        menu.classList.remove("show");
        input.setAttribute("aria-expanded", "false");
        highlight(-1);
    };
    const open = () => {
        if (clearButton) clearButton.hidden = input.value.length === 0;
        const query = input.value.trim().toLocaleLowerCase();
        options.forEach((option) => {
            option.hidden = !option.textContent
                .trim()
                .toLocaleLowerCase()
                .includes(query);
        });
        menu.querySelector("[data-no-results]").hidden =
            visibleOptions().length > 0;
        menu.classList.add("show");
        input.setAttribute("aria-expanded", "true");
        highlight(-1);
    };
    const choose = (option) => {
        input.value = option.textContent.trim();
        input.dispatchEvent(new Event("input", { bubbles: true }));
        if (clearButton) clearButton.hidden = false;
        close();
    };

    clearButton?.addEventListener("click", () => {
        input.value = "";
        input.focus();
        input.dispatchEvent(new Event("input", { bubbles: true }));
    });

    input.addEventListener("focus", open);
    input.addEventListener("click", open);
    input.addEventListener("input", open);
    input.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            event.preventDefault();
            close();
        } else if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            if (!menu.classList.contains("show")) open();
            const count = visibleOptions().length;
            if (count)
                highlight(
                    (active + (event.key === "ArrowDown" ? 1 : -1) + count) %
                        count,
                );
        } else if (
            event.key === "Enter" &&
            menu.classList.contains("show") &&
            active >= 0
        ) {
            event.preventDefault();
            choose(visibleOptions()[active]);
        } else if (event.key === "Tab") {
            close();
        }
    });
    menu.addEventListener("mousedown", (event) => event.preventDefault());
    options.forEach((option) =>
        option.addEventListener("click", () => choose(option)),
    );
    dropdown.addEventListener("focusout", (event) => {
        if (!dropdown.contains(event.relatedTarget)) close();
    });
    document.addEventListener("click", (event) => {
        if (!dropdown.contains(event.target)) close();
    });
});

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
        const instructor = instructorFilter?.value.trim().toLocaleLowerCase() ?? "";
        const course = courseFilter?.value.trim().toLocaleLowerCase() ?? "";

        sessions.forEach((session) => {
            const matchesInstructor = session.dataset.instructor.toLocaleLowerCase().includes(instructor);
            const matchesCourse = session.dataset.course.toLocaleLowerCase().includes(course);
            session.hidden = !(matchesInstructor && matchesCourse);
        });
    };

    instructorFilter?.addEventListener("input", filterSessions);
    courseFilter?.addEventListener("input", filterSessions);
    filterSessions();
}
