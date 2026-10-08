export function initializeLanguageSwitcher(document, window) {
    const switchForm = document.getElementById('language-switch');
    if (!switchForm) return;

    const participantForm = document.getElementById('questionnaire-form');
    const editorRefresh = document.getElementById('refresh-answer-types');
    const draftKey = `evalio-language-draft:${window.location.pathname}${window.location.search}`;
    const answerFields = () => [...participantForm.elements].filter((field) =>
        field.name?.startsWith('answers[') || field.name?.startsWith('answers_comment['));

    if (participantForm) {
        try {
            const saved = window.sessionStorage.getItem(draftKey);
            window.sessionStorage.removeItem(draftKey);
            if (saved) {
                const fields = answerFields();
                for (const answer of JSON.parse(saved)) {
                    const field = fields.find((item) => item.name === answer.name &&
                        (item.type !== 'radio' || item.value === answer.value));
                    if (!field) continue;
                    if (field.type === 'radio') field.checked = answer.checked;
                    else field.value = answer.value;
                }
            }
        } catch {
            // Language selection still works if browser storage is unavailable.
        }
    }

    switchForm.addEventListener('submit', async (event) => {
        if (participantForm) {
            try {
                window.sessionStorage.setItem(draftKey, JSON.stringify(answerFields().map((field) => ({
                    name: field.name, value: field.value, checked: field.checked,
                }))));
            } catch {
                // Draft restoration is optional when browser storage is unavailable.
            }
        }

        if (!editorRefresh) return;
        event.preventDefault();

        try {
            const data = new FormData(switchForm);
            data.set('locale', event.submitter.value);
            const response = await window.fetch(switchForm.action, {
                method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Language update failed');
            editorRefresh.form.requestSubmit(editorRefresh);
        } catch {
            window.alert(switchForm.dataset.error);
        }
    });
}
