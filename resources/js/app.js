import 'bootstrap/js/dist/modal';
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';

const questionnaire = document.getElementById('questionnaire-form');
const incompleteAnswers = document.getElementById('incomplete-answers');

if (questionnaire && incompleteAnswers) {
    questionnaire.addEventListener('invalid', () => {
        incompleteAnswers.hidden = false;
    }, true);

    questionnaire.addEventListener('input', () => {
        if (!questionnaire.querySelector(':invalid')) {
            incompleteAnswers.hidden = true;
        }
    });
}
