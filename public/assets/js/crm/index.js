'use strict';

const normalizeSearch = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const viewSwitcher = document.querySelector('[data-crm-view-switcher]');

const crmReturnForms = [...document.querySelectorAll('[data-crm-return-context]')];

const syncCrmReturnContext = (view, section) => {
    crmReturnForms.forEach((form) => {
        const values = {
            return_section: view === 'tabs' ? section : '',
        };

        Object.entries(values).forEach(([name, value]) => {
            let field = form.elements.namedItem(name);

            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = name;
                form.append(field);
            }

            field.value = value;
        });
    });
};

const persistCrmLocation = (view, section) => {
    const url = new URL(window.location.href);

    url.searchParams.delete('view');

    if (view === 'tabs') {
        url.searchParams.set('section', section);
    } else {
        url.searchParams.delete('section');
    }

    window.history.replaceState(
        window.history.state,
        '',
        `${url.pathname}${url.search}${url.hash}`,
    );
    syncCrmReturnContext(view, section);
};

if (viewSwitcher) {
    const sectionTabs = [...viewSwitcher.querySelectorAll('[data-crm-section-tab]')];
    const sectionPanels = [...viewSwitcher.querySelectorAll('[data-crm-section-panel]')];
    const sectionTabList = viewSwitcher.querySelector('[data-crm-section-tabs]');
    const requestedLocation = new URL(window.location.href);
    const preferredView = viewSwitcher.dataset.crmView === 'tabs'
        ? 'tabs'
        : 'combined';
    const requestedSection = requestedLocation.searchParams.get('section');
    let activeSection = ['contacts', 'opportunities'].includes(requestedSection)
        ? requestedSection
        : 'contacts';

    const selectSection = (section, moveFocus = false) => {
        if (!sectionTabs.some((tab) => tab.dataset.crmSectionTab === section)) {
            return;
        }

        activeSection = section;
        sectionTabs.forEach((tab) => {
            const isActive = tab.dataset.crmSectionTab === activeSection;

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;

            if (isActive && moveFocus) {
                tab.focus();
            }
        });
        sectionPanels.forEach((panel) => {
            panel.hidden = panel.dataset.crmSectionPanel !== activeSection;
        });

        if (viewSwitcher.dataset.crmView === 'tabs') {
            persistCrmLocation('tabs', activeSection);
        }
    };

    const selectView = (view) => {
        const usesTabs = view === 'tabs';

        viewSwitcher.dataset.crmView = usesTabs ? 'tabs' : 'combined';

        if (sectionTabList) {
            sectionTabList.hidden = !usesTabs;
        }

        if (usesTabs) {
            selectSection(activeSection);
        } else {
            sectionPanels.forEach((panel) => {
                panel.hidden = false;
            });
            persistCrmLocation('combined', activeSection);
        }
    };

    sectionTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            selectSection(tab.dataset.crmSectionTab || 'contacts');
        });
        tab.addEventListener('keydown', (event) => {
            let nextIndex = null;

            if (event.key === 'ArrowRight') {
                nextIndex = (index + 1) % sectionTabs.length;
            } else if (event.key === 'ArrowLeft') {
                nextIndex = (index - 1 + sectionTabs.length) % sectionTabs.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = sectionTabs.length - 1;
            }

            if (nextIndex === null) {
                return;
            }

            event.preventDefault();
            selectSection(
                sectionTabs[nextIndex].dataset.crmSectionTab || 'contacts',
                true,
            );
        });
    });

    selectView(preferredView);
} else {
    syncCrmReturnContext('combined', 'contacts');
}

const applyOpportunityFilters = (root, filters) => {
    const rows = [...root.querySelectorAll('[data-crm-opportunity]')];
    let visibleRows = 0;
    const search = normalizeSearch(filters.search);

    rows.forEach((row) => {
        const matches = (!search || normalizeSearch(row.dataset.search).includes(search))
            && (!filters.stage || row.dataset.stage === filters.stage)
            && (!filters.status || row.dataset.status === filters.status)
            && (!filters.channel || row.dataset.channel === filters.channel)
            && (!filters.followUp || row.dataset.followUp === filters.followUp);

        row.hidden = !matches;
        visibleRows += matches ? 1 : 0;
    });

    const empty = root.querySelector('[data-crm-filter-empty]');

    if (empty) {
        empty.hidden = rows.length === 0 || visibleRows > 0;
    }
};

const filterRoot = document.querySelector('[data-crm-filter-app]');

if (filterRoot) {
    const filterState = {
        search: '',
        stage: '',
        status: '',
        channel: '',
        followUp: '',
    };

    const enhanceFiltersWithoutVue = () => {
        const controls = [...filterRoot.querySelectorAll('.crm-filters input, .crm-filters select')];
        const update = () => {
            filterState.search = controls.find((field) => field.type === 'search')?.value || '';
            const selects = controls.filter((field) => field.tagName === 'SELECT');
            [filterState.stage, filterState.status, filterState.channel, filterState.followUp] = selects
                .map((field) => field.value);
            applyOpportunityFilters(filterRoot, filterState);
        };

        controls.forEach((field) => field.addEventListener('input', update));
        controls.forEach((field) => field.addEventListener('change', update));
        filterRoot.querySelector('[data-crm-filter-empty] button')?.addEventListener('click', () => {
            controls.forEach((field) => {
                field.value = '';
            });
            update();
        });
        update();
    };

    if (window.Vue) {
        try {
            window.Vue.createApp({
                data() {
                    return { ...filterState };
                },
                mounted() {
                    this.applyFilters();
                },
                methods: {
                    applyFilters() {
                        applyOpportunityFilters(filterRoot, this);
                    },
                    clearFilters() {
                        this.search = '';
                        this.stage = '';
                        this.status = '';
                        this.channel = '';
                        this.followUp = '';
                        this.$nextTick(() => this.applyFilters());
                    },
                },
            }).mount(filterRoot);
        } catch (error) {
            enhanceFiltersWithoutVue();
        }
    } else {
        enhanceFiltersWithoutVue();
    }
}

const applyContactSearch = (root, value) => {
    const cards = [...root.querySelectorAll('[data-crm-contact-card]')];
    const search = normalizeSearch(value);
    let visibleCards = 0;

    cards.forEach((card) => {
        const matches = !search
            || normalizeSearch(card.dataset.contactName).includes(search);

        card.hidden = !matches;
        visibleCards += matches ? 1 : 0;
    });

    const empty = root.querySelector('[data-crm-contact-search-empty]');

    if (empty) {
        empty.hidden = search === '' || visibleCards > 0;
    }
};

const contactSearchRoot = document.querySelector('[data-crm-contact-search-app]');

if (contactSearchRoot) {
    const enhanceContactSearchWithoutVue = () => {
        const input = contactSearchRoot.querySelector('input[type="search"]');

        if (!input) {
            return;
        }

        const update = () => applyContactSearch(contactSearchRoot, input.value);

        input.addEventListener('input', update);
        contactSearchRoot
            .querySelector('[data-crm-contact-search-empty] button')
            ?.addEventListener('click', () => {
                input.value = '';
                update();
                input.focus();
            });
        update();
    };

    if (window.Vue) {
        try {
            window.Vue.createApp({
                data() {
                    return { contactSearch: '' };
                },
                mounted() {
                    this.applyContactSearch();
                },
                methods: {
                    applyContactSearch() {
                        applyContactSearch(contactSearchRoot, this.contactSearch);
                    },
                    clearContactSearch() {
                        this.contactSearch = '';
                        this.$nextTick(() => {
                            this.applyContactSearch();
                            contactSearchRoot.querySelector('input[type="search"]')?.focus();
                        });
                    },
                },
            }).mount(contactSearchRoot);
        } catch (error) {
            enhanceContactSearchWithoutVue();
        }
    } else {
        enhanceContactSearchWithoutVue();
    }
}

const editorRoot = document.getElementById('crmEditorApp');
let editorApp = null;

const emptyContact = () => ({
    id: 0,
    display_name: '',
    contact_kind: 'person',
    lifecycle_stage: 'prospect',
    acquisition_channel: '',
    email: '',
    phone: '',
    identity_document: '',
    notes: '',
});

const emptyOpportunity = () => ({
    id: 0,
    contact_id: '',
    need: '',
    status: 'new',
    estimated_value: '',
    next_follow_up_date: '',
    notes: '',
});

const parsePayload = (value) => {
    if (!value) {
        return {};
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return {};
    }
};

const contactPayload = (payload = {}) => ({
    ...emptyContact(),
    id: Number(payload.id) || 0,
    display_name: String(payload.display_name || ''),
    contact_kind: String(payload.contact_kind || 'person'),
    lifecycle_stage: String(payload.lifecycle_stage || 'prospect'),
    acquisition_channel: String(payload.acquisition_channel || ''),
    email: String(payload.email || ''),
    phone: String(payload.phone || ''),
    identity_document: String(payload.identity_document || ''),
    notes: String(payload.notes || ''),
});

const opportunityPayload = (payload = {}) => ({
    ...emptyOpportunity(),
    id: Number(payload.id) || 0,
    contact_id: String(payload.contact_id || ''),
    need: String(payload.need || ''),
    status: String(payload.status || 'new'),
    estimated_value: payload.estimated_value === null
        ? ''
        : String(payload.estimated_value || ''),
    next_follow_up_date: String(payload.next_follow_up_date || ''),
    notes: String(payload.notes || ''),
});

const replaceHash = () => {
    if (window.location.hash === '#crmContactEditor'
        || window.location.hash === '#crmOpportunityEditor') {
        window.history.replaceState(
            null,
            '',
            window.location.pathname + window.location.search,
        );
    }
};

const focusableElements = (container) => [...container.querySelectorAll([
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', '))];

if (editorRoot && window.Vue) {
    try {
        const initialEditor = parsePayload(editorRoot.dataset.initialEditor);
        const contactCreateUrl = editorRoot.dataset.contactCreateUrl || '';
        const opportunityCreateUrl = editorRoot.dataset.opportunityCreateUrl || '';

        editorApp = window.Vue.createApp({
            data() {
                return {
                    contactOpen: false,
                    opportunityOpen: false,
                    returnFocusTo: null,
                    contact: emptyContact(),
                    opportunity: emptyOpportunity(),
                };
            },
            computed: {
                contactAction() {
                    return this.contact.id
                        ? `${contactCreateUrl}/${this.contact.id}`
                        : contactCreateUrl;
                },
                opportunityAction() {
                    return this.opportunity.id
                        ? `${opportunityCreateUrl}/${this.opportunity.id}`
                        : opportunityCreateUrl;
                },
            },
            mounted() {
                if (initialEditor.type === 'contact') {
                    this.openContact({ id: initialEditor.id, ...initialEditor.payload });
                } else if (initialEditor.type === 'opportunity') {
                    this.openOpportunity({ id: initialEditor.id, ...initialEditor.payload });
                }
            },
            beforeUnmount() {
                this.releaseBackground();
            },
            methods: {
                openContact(payload = {}, trigger = null) {
                    this.contact = contactPayload(payload);
                    this.returnFocusTo = trigger;
                    this.opportunityOpen = false;
                    this.contactOpen = true;
                    this.lockBackground();
                    this.$nextTick(() => {
                        this.$refs.contactForm.action = this.contactAction;
                        this.$refs.contactTitle.textContent = this.contact.id
                            ? 'Editar contacto'
                            : 'Nuevo contacto';
                        this.$refs.contactSubmit.textContent = this.contact.id
                            ? 'Guardar cambios'
                            : 'Crear contacto';
                        this.$refs.contactForm
                            ?.querySelector('input, select, textarea')
                            ?.focus();
                    });
                },
                openOpportunity(payload = {}, trigger = null) {
                    this.opportunity = opportunityPayload(payload);
                    this.returnFocusTo = trigger;
                    this.contactOpen = false;
                    this.opportunityOpen = true;
                    this.lockBackground();
                    this.$nextTick(() => {
                        this.$refs.opportunityForm.action = this.opportunityAction;
                        this.$refs.opportunityTitle.textContent = this.opportunity.id
                            ? 'Editar oportunidad'
                            : 'Nueva oportunidad';
                        this.$refs.opportunitySubmit.textContent = this.opportunity.id
                            ? 'Guardar cambios'
                            : 'Crear oportunidad';
                        this.$refs.opportunityForm
                            ?.querySelector('select, input, textarea')
                            ?.focus();
                    });
                },
                closeContact() {
                    this.contactOpen = false;
                    this.finishClose();
                },
                closeOpportunity() {
                    this.opportunityOpen = false;
                    this.finishClose();
                },
                finishClose() {
                    replaceHash();
                    this.releaseBackground();
                    this.$nextTick(() => {
                        this.returnFocusTo?.focus();
                        this.returnFocusTo = null;
                    });
                },
                lockBackground() {
                    document.body.classList.add('crm-modal-is-open');
                    document.querySelector('.business-shell')?.setAttribute('inert', '');
                },
                releaseBackground() {
                    document.body.classList.remove('crm-modal-is-open');
                    document.querySelector('.business-shell')?.removeAttribute('inert');
                },
                handleKeydown(event, type) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        type === 'contact' ? this.closeContact() : this.closeOpportunity();

                        return;
                    }

                    if (event.key !== 'Tab') {
                        return;
                    }

                    const dialog = type === 'contact'
                        ? this.$refs.contactDialog
                        : this.$refs.opportunityDialog;
                    const focusable = focusableElements(dialog);

                    if (focusable.length === 0) {
                        event.preventDefault();
                        dialog.focus();

                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            },
        }).mount(editorRoot);
    } catch (error) {
        editorApp = null;
    }
}

const updateFallbackForm = (type, payload = {}) => {
    if (!editorRoot) {
        return;
    }

    const isContact = type === 'contact';
    const layer = document.getElementById(
        isContact ? 'crmContactEditor' : 'crmOpportunityEditor',
    );
    const form = layer?.querySelector('form');
    const normalized = isContact ? contactPayload(payload) : opportunityPayload(payload);
    const baseUrl = isContact
        ? editorRoot.dataset.contactCreateUrl
        : editorRoot.dataset.opportunityCreateUrl;

    if (!layer || !form) {
        return;
    }

    form.reset();
    form.action = normalized.id ? `${baseUrl}/${normalized.id}` : baseUrl;
    Object.entries(normalized).forEach(([name, value]) => {
        const field = form.elements.namedItem(name);

        if (field) {
            field.value = value;
        }
    });
    const opportunityStatus = form.querySelector('[data-opportunity-status-field]');

    if (opportunityStatus) {
        opportunityStatus.disabled = !isContact && normalized.id > 0;
    }
    layer.querySelector('h2').textContent = normalized.id
        ? `Editar ${isContact ? 'contacto' : 'oportunidad'}`
        : `${isContact ? 'Nuevo contacto' : 'Nueva oportunidad'}`;
    layer.querySelector('button[type="submit"]').textContent = normalized.id
        ? 'Guardar cambios'
        : `Crear ${isContact ? 'contacto' : 'oportunidad'}`;
    layer.classList.add('is-open');
    document.body.classList.add('crm-modal-is-open');
    document.querySelector('.business-shell')?.setAttribute('inert', '');
    form.querySelector('input, select, textarea')?.focus();
};

const openEditor = (type, payload, trigger) => {
    if (editorApp) {
        type === 'contact'
            ? editorApp.openContact(payload, trigger)
            : editorApp.openOpportunity(payload, trigger);

        return;
    }

    updateFallbackForm(type, payload);
};

document.querySelectorAll('[data-open-contact-editor]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openEditor('contact', {}, trigger);
    });
});

document.querySelectorAll('[data-open-opportunity-editor]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openEditor('opportunity', {}, trigger);
    });
});

document.querySelectorAll('[data-edit-contact]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openEditor('contact', parsePayload(trigger.dataset.editContact), trigger);
    });
});

document.querySelectorAll('[data-edit-opportunity]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openEditor('opportunity', parsePayload(trigger.dataset.editOpportunity), trigger);
    });
});

editorRoot?.querySelectorAll('.crm-editor-form').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');

        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Guardando…';
        }
    });
});

if (!editorApp && editorRoot) {
    editorRoot.querySelectorAll('.crm-modal-close, .crm-modal-actions .button-ghost')
        .forEach((trigger) => {
            trigger.addEventListener('click', () => {
                editorRoot.querySelectorAll('.crm-modal-layer').forEach((layer) => {
                    layer.classList.remove('is-open');
                });
                document.body.classList.remove('crm-modal-is-open');
                document.querySelector('.business-shell')?.removeAttribute('inert');
                replaceHash();
            });
        });

    const initialEditor = parsePayload(editorRoot.dataset.initialEditor);

    if (initialEditor.type === 'contact' || initialEditor.type === 'opportunity') {
        updateFallbackForm(initialEditor.type, {
            id: initialEditor.id,
            ...initialEditor.payload,
        });
    }
}

const saleNoteRoot = document.getElementById('crmSaleNoteApp');
let saleNoteApp = null;

const saleNotePayload = (payload = {}) => ({
    opportunity_id: Number(payload.opportunity_id) || 0,
    contact_name: String(payload.contact_name || 'Contacto'),
    identity_document: String(payload.identity_document || ''),
});

const rememberSaleNoteIdentity = (opportunityId, identityDocument) => {
    document.querySelectorAll('[data-crm-sale-note]').forEach((trigger) => {
        const payload = saleNotePayload(parsePayload(trigger.dataset.saleNote));

        if (payload.opportunity_id !== Number(opportunityId)) {
            return;
        }

        payload.identity_document = String(identityDocument || '').trim();
        trigger.dataset.saleNote = JSON.stringify(payload);
    });
};

if (saleNoteRoot && window.Vue) {
    try {
        const saleNoteBaseUrl = saleNoteRoot.dataset.saleNoteBaseUrl || '';

        saleNoteApp = window.Vue.createApp({
            data() {
                return {
                    open: false,
                    opportunityId: 0,
                    contactName: 'Contacto',
                    identityDocument: '',
                    returnFocusTo: null,
                };
            },
            computed: {
                action() {
                    return this.opportunityId
                        ? `${saleNoteBaseUrl}/${this.opportunityId}/nota-venta`
                        : saleNoteBaseUrl;
                },
            },
            methods: {
                show(payload, trigger = null) {
                    const normalized = saleNotePayload(payload);

                    this.opportunityId = normalized.opportunity_id;
                    this.contactName = normalized.contact_name;
                    this.identityDocument = normalized.identity_document;
                    this.returnFocusTo = trigger;
                    this.open = true;
                    document.body.classList.add('crm-modal-is-open');
                    document.querySelector('.business-shell')?.setAttribute('inert', '');
                    this.$nextTick(() => {
                        this.$refs.form.action = this.action;
                        this.$refs.identityDocument?.focus();
                    });
                },
                close() {
                    this.open = false;
                    document.body.classList.remove('crm-modal-is-open');
                    document.querySelector('.business-shell')?.removeAttribute('inert');
                    this.$nextTick(() => {
                        this.returnFocusTo?.focus();
                        this.returnFocusTo = null;
                    });
                },
                handleSubmit() {
                    rememberSaleNoteIdentity(this.opportunityId, this.identityDocument);
                    window.setTimeout(() => this.close(), 300);
                },
                handleKeydown(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        this.close();

                        return;
                    }

                    if (event.key !== 'Tab') {
                        return;
                    }

                    const focusable = focusableElements(this.$refs.dialog);

                    if (focusable.length === 0) {
                        event.preventDefault();
                        this.$refs.dialog?.focus();

                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            },
        }).mount(saleNoteRoot);
    } catch (error) {
        saleNoteApp = null;
    }
}

const showSaleNoteFallback = (payload) => {
    const normalized = saleNotePayload(payload);
    const layer = saleNoteRoot?.querySelector('.crm-sale-note-modal-layer');
    const form = saleNoteRoot?.querySelector('form');
    const contactName = saleNoteRoot?.querySelector('.crm-sale-note-customer strong');
    const identityDocument = form?.elements.namedItem('identity_document');

    if (!layer || !form || !identityDocument || normalized.opportunity_id < 1) {
        return;
    }

    form.action = `${saleNoteRoot.dataset.saleNoteBaseUrl}/${normalized.opportunity_id}/nota-venta`;
    form.dataset.opportunityId = String(normalized.opportunity_id);
    contactName.textContent = normalized.contact_name;
    identityDocument.value = normalized.identity_document;
    layer.classList.add('is-open');
    layer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('crm-modal-is-open');
    document.querySelector('.business-shell')?.setAttribute('inert', '');
    identityDocument.focus();
};

document.querySelectorAll('[data-crm-sale-note]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const payload = saleNotePayload(parsePayload(trigger.dataset.saleNote));

        if (payload.identity_document.trim() !== '') {
            return;
        }

        event.preventDefault();

        if (saleNoteApp) {
            saleNoteApp.show(payload, trigger);

            return;
        }

        showSaleNoteFallback(payload);
    });
});

if (!saleNoteApp && saleNoteRoot) {
    const closeFallback = () => {
        saleNoteRoot.querySelector('.crm-sale-note-modal-layer')?.classList.remove('is-open');
        document.body.classList.remove('crm-modal-is-open');
        document.querySelector('.business-shell')?.removeAttribute('inert');
    };

    saleNoteRoot.querySelectorAll('.crm-modal-close, .crm-modal-actions .button-ghost')
        .forEach((trigger) => trigger.addEventListener('click', closeFallback));
    saleNoteRoot.querySelector('form')?.addEventListener('submit', () => {
        const form = saleNoteRoot.querySelector('form');
        const identityDocument = form?.elements.namedItem('identity_document');

        rememberSaleNoteIdentity(
            Number(form?.dataset.opportunityId) || 0,
            identityDocument?.value || '',
        );
        window.setTimeout(closeFallback, 300);
    });
}

const statusRoot = document.getElementById('crmStatusApp');
let statusApp = null;

if (statusRoot && window.Vue) {
    try {
        const statusBaseUrl = statusRoot.dataset.statusBaseUrl || '';
        const defaultSaleDate = statusRoot.dataset.today || '';

        statusApp = window.Vue.createApp({
            data() {
                return {
                    open: false,
                    submitting: false,
                    opportunity: {},
                    sourceForm: null,
                    sourceSelect: null,
                    targetStatus: '',
                    targetStatusLabel: '',
                    recordInFinance: true,
                    saleAmount: '',
                    saleDate: defaultSaleDate,
                    returnFocusTo: null,
                };
            },
            computed: {
                activePosting() {
                    return this.opportunity.finance_posting?.status === 'recorded';
                },
                canOfferFinance() {
                    return this.targetStatus === 'won' && !this.activePosting;
                },
                requiresReversal() {
                    return this.activePosting && this.targetStatus !== 'won';
                },
                financeAction() {
                    if (this.requiresReversal) {
                        return 'reverse';
                    }

                    return this.canOfferFinance && this.recordInFinance ? 'record' : 'none';
                },
                statusAction() {
                    const id = Number(this.opportunity.id) || 0;

                    return id ? `${statusBaseUrl}/${id}/estado` : statusBaseUrl;
                },
                contactName() {
                    return String(this.opportunity.contact?.display_name || 'Contacto');
                },
                opportunityNeed() {
                    return String(this.opportunity.need || 'Oportunidad');
                },
                currentStatusLabel() {
                    return String(this.opportunity.status_label || 'Estado actual');
                },
                dialogTitle() {
                    if (this.requiresReversal) {
                        return 'Cambiar estado y revertir venta';
                    }

                    return this.targetStatus === 'won'
                        ? 'Confirmar venta ganada'
                        : 'Actualizar estado';
                },
                dialogDescription() {
                    if (this.requiresReversal) {
                        return 'La oportunidad dejará de estar ganada y su venta vinculada se ajustará en Finanzas.';
                    }

                    if (this.targetStatus === 'won') {
                        return 'Confirmá el cierre comercial y decidí si querés incluir la venta en Finanzas.';
                    }

                    return 'Revisá el cambio antes de actualizar el seguimiento comercial.';
                },
                confirmLabel() {
                    if (this.requiresReversal) {
                        return 'Cambiar y revertir';
                    }

                    if (this.financeAction === 'record') {
                        return 'Guardar venta';
                    }

                    return 'Actualizar estado';
                },
            },
            methods: {
                show(payload, form, select, targetStatus, targetLabel, trigger = null) {
                    this.opportunity = payload;
                    this.sourceForm = form;
                    this.sourceSelect = select;
                    this.targetStatus = targetStatus;
                    this.targetStatusLabel = targetLabel;
                    this.recordInFinance = true;
                    this.saleAmount = payload.estimated_value === null
                        ? ''
                        : String(payload.estimated_value || '');
                    this.saleDate = defaultSaleDate;
                    this.returnFocusTo = trigger || select;
                    this.submitting = false;
                    this.open = true;
                    document.body.classList.add('crm-modal-is-open');
                    document.querySelector('.business-shell')?.setAttribute('inert', '');
                    this.$nextTick(() => {
                        this.$refs.form.action = this.statusAction;
                        this.$refs.dialog?.focus();
                    });
                },
                close() {
                    this.open = false;

                    if (this.sourceSelect) {
                        this.sourceSelect.value = this.sourceSelect.dataset.currentStatus || '';
                    }

                    document.body.classList.remove('crm-modal-is-open');
                    document.querySelector('.business-shell')?.removeAttribute('inert');
                    this.$nextTick(() => this.returnFocusTo?.focus());
                },
                handleKeydown(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        this.close();

                        return;
                    }

                    if (event.key !== 'Tab') {
                        return;
                    }

                    const focusable = focusableElements(this.$refs.dialog);

                    if (focusable.length === 0) {
                        event.preventDefault();
                        this.$refs.dialog?.focus();

                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            },
        }).mount(statusRoot);
    } catch (error) {
        statusApp = null;
    }
}

document.querySelectorAll('[data-crm-status-form]').forEach((form) => {
    const select = form.querySelector('.crm-status-select');
    const payload = parsePayload(form.dataset.opportunity);

    select?.addEventListener('change', () => {
        const current = select.dataset.currentStatus || '';

        if (select.value === current) {
            return;
        }

        const targetLabel = select.options[select.selectedIndex]?.textContent?.trim()
            || select.value;

        if (statusApp) {
            statusApp.show(payload, form, select, select.value, targetLabel);

            return;
        }

        if (window.confirm(`¿Querés cambiar el estado a ${targetLabel}?`)) {
            form.requestSubmit();
        } else {
            select.value = current;
        }
    });

});

document.querySelectorAll('[data-crm-register-sale]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const row = trigger.closest('[data-crm-opportunity]');
        const form = row?.querySelector('[data-crm-status-form]');
        const select = form?.querySelector('.crm-status-select');

        if (!form || !select) {
            return;
        }

        if (!statusApp) {
            return;
        }

        const payload = parsePayload(form.dataset.opportunity);

        statusApp.show(
            payload,
            form,
            select,
            'won',
            payload.status_label || 'Ganada',
            event.currentTarget,
        );
    });
});

const confirmLayer = document.getElementById('crmConfirmLayer');
const confirmDialog = confirmLayer?.querySelector('.crm-confirm-dialog');
const confirmTitle = confirmLayer?.querySelector('#crmConfirmTitle');
const confirmMessage = confirmLayer?.querySelector('#crmConfirmMessage');
const confirmAccept = confirmLayer?.querySelector('[data-confirm-accept]');
const confirmCancel = confirmLayer?.querySelector('[data-confirm-cancel]');
let pendingConfirmationForm = null;
let confirmationTrigger = null;

const closeConfirmation = () => {
    if (!confirmLayer) {
        return;
    }

    confirmLayer.classList.remove('is-open');
    confirmLayer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('crm-modal-is-open');
    document.querySelector('.business-shell')?.removeAttribute('inert');
    pendingConfirmationForm = null;
    confirmationTrigger?.focus();
    confirmationTrigger = null;
};

document.querySelectorAll('[data-confirm-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;

            return;
        }

        event.preventDefault();

        if (!confirmLayer || !confirmDialog || !confirmAccept) {
            if (window.confirm(form.dataset.confirmMessage || '¿Querés continuar?')) {
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            }

            return;
        }

        pendingConfirmationForm = form;
        confirmationTrigger = document.activeElement;
        confirmTitle.textContent = form.dataset.confirmTitle || 'Confirmar acción';
        confirmMessage.textContent = form.dataset.confirmMessage || '¿Querés continuar?';
        confirmAccept.textContent = form.dataset.confirmLabel || 'Confirmar';
        confirmLayer.classList.add('is-open');
        confirmLayer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('crm-modal-is-open');
        document.querySelector('.business-shell')?.setAttribute('inert', '');
        confirmDialog.focus();
    });
});

confirmAccept?.addEventListener('click', () => {
    const form = pendingConfirmationForm;

    if (!form) {
        return;
    }

    form.dataset.confirmed = 'true';
    closeConfirmation();
    form.requestSubmit();
});

confirmCancel?.addEventListener('click', closeConfirmation);
confirmLayer?.addEventListener('click', (event) => {
    if (event.target === confirmLayer) {
        closeConfirmation();
    }
});
confirmLayer?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeConfirmation();
    }
});
