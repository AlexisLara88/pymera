'use strict';

const root = document.getElementById('businessProfileApp');
let businessProfile = null;

const enhanceProfileWithoutVue = () => {
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-character-count]').forEach((field) => {
        const output = field.parentElement?.querySelector('[data-character-output]');
        const updateCount = () => {
            if (output) {
                output.textContent = `${field.value.length} / 5000`;
            }
        };

        field.addEventListener('input', updateCount);
        updateCount();
    });

    root.querySelector('.business-form')?.addEventListener('submit', (event) => {
        const submit = event.currentTarget.querySelector('button[type="submit"]');

        if (submit) {
            submit.disabled = true;
            submit.querySelector('[data-submit-default]')?.setAttribute('hidden', '');
            submit.querySelector('[data-submit-progress]')?.removeAttribute('hidden');
        }
    });
};

if (root && window.Vue) {
    try {
        businessProfile = window.Vue.createApp({
        data() {
            return {
                characterCounts: {},
                editorOpen: root.dataset.initialEditorOpen === 'true',
                submitting: false,
            };
        },
        mounted() {
            root.querySelectorAll('[data-character-count]').forEach((field) => {
                this.characterCounts[field.name] = field.value.length;
            });
        },
        methods: {
            characterLabel(field, fallback) {
                return `${this.characterCounts[field] ?? fallback} / 5000`;
            },
            openEditor() {
                this.editorOpen = true;

                this.$nextTick(() => {
                    document.getElementById('businessEditor')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                });
            },
            startSubmitting() {
                this.submitting = true;
            },
            syncEditorState(event) {
                this.editorOpen = event.target.open;
            },
            updateCharacterCount(event) {
                this.characterCounts[event.target.name] = event.target.value.length;
            },
        },
        }).mount(root);
    } catch (error) {
        businessProfile = null;
        enhanceProfileWithoutVue();
    }
} else if (root) {
    enhanceProfileWithoutVue();
}

document.querySelectorAll('[data-open-business-editor]').forEach((button) => {
    button.addEventListener('click', () => {
        if (businessProfile) {
            businessProfile.openEditor();

            return;
        }

        const editor = document.getElementById('businessEditor');

        if (editor) {
            editor.open = true;
            editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
