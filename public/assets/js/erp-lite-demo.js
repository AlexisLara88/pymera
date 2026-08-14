(function () {
    'use strict';

    const app = document.getElementById('demoApp');

    if (!app) {
        return;
    }

    const viewMeta = {
        business: ['Diagnóstico del negocio', 'Mi negocio'],
        objectives: ['Objetivos y seguimiento', 'Objetivos'],
        priorities: ['Organización del trabajo', 'Matriz de prioridades'],
        finances: ['Salud financiera', 'Finanzas'],
        crm: ['Gestión comercial básica', 'Clientes y ventas'],
        summary: ['Vista integral', 'Resumen general']
    };

    const sidebar = document.querySelector('.sidebar');
    const sectionEyebrow = document.getElementById('sectionEyebrow');
    const sectionTitle = document.getElementById('sectionTitle');
    const toast = document.getElementById('demoToast');
    const toastMessage = document.getElementById('toastMessage');
    const tourPanel = document.getElementById('tourPanel');
    const tourCard = document.getElementById('tourCard');
    const tourScrim = document.getElementById('tourScrim');
    const tourChapter = document.getElementById('tourChapter');
    const tourChapterTitle = document.getElementById('tourChapterTitle');
    const tourStepLabel = document.getElementById('tourStepLabel');
    const tourTitle = document.getElementById('tourTitle');
    const tourDescription = document.getElementById('tourDescription');
    const tourFocusLabel = document.getElementById('tourFocusLabel');
    const tourFocusDescription = document.getElementById('tourFocusDescription');
    const tourProgress = document.getElementById('tourProgress');
    const tourSpotlight = document.getElementById('tourSpotlight');
    const tourPlaybackButton = document.getElementById('toggleTourPlayback');
    const tourPlaybackLabel = tourPlaybackButton.querySelector('span:last-child');
    const tourPlaybackIcon = tourPlaybackButton.querySelector('span:first-child');
    const previousTourStep = document.getElementById('previousTourStep');
    const nextTourStep = document.getElementById('nextTourStep');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const tourChapterDuration = 2200;
    const tourHighlightReadingDuration = 6000;
    const tourCardTransitionDuration = 450;
    const tourChapterTitles = {
        business: 'Mi negocio',
        objectives: 'Objetivos',
        priorities: 'Prioridades',
        finances: 'Finanzas',
        crm: 'Clientes y ventas',
        summary: 'Resumen'
    };
    const tourSteps = [
        {
            view: 'business',
            title: 'Conocer el negocio',
            description: 'Primero entendemos qué hace el negocio, a quién atiende, qué ofrece y qué quiere conseguir. Este diagnóstico aporta el contexto para todas las decisiones posteriores.',
            highlights: [
                { target: '[data-view="business"] .page-intro', description: 'La pantalla comienza explicando por qué conocer el negocio aporta contexto a todo el recorrido.' },
                { target: '[data-view="business"] .business-profile-card', description: 'El perfil resume actividad, ubicación, equipo, moneda y canal comercial principal.' },
                { target: '[data-view="business"] .diagnosis-panel .panel-heading', description: 'El diagnóstico reúne las preguntas mínimas necesarias antes de definir acciones.' },
                { target: '[data-view="business"] .question-list', description: 'Las respuestas describen diferenciación, forma de entrega, resultado esperado y origen de los clientes.' }
            ]
        },
        {
            view: 'objectives',
            title: 'Convertir el diagnóstico en objetivos',
            description: 'Las necesidades del negocio se transforman en objetivos comerciales, financieros, operativos o de mejora, con actividades y fechas para poder darles seguimiento.',
            highlights: [
                { target: '[data-view="objectives"] .page-intro', description: 'La segunda pantalla transforma problemas detectados en objetivos concretos.' },
                { target: '[data-view="objectives"] .metric-row', description: 'Los indicadores muestran cuántos objetivos y actividades requieren atención.' },
                { target: '[data-view="objectives"] .objective-hero', description: 'El objetivo conecta un problema con una meta, una fecha y una persona responsable.' },
                { target: '[data-view="objectives"] #objectiveTaskList', description: 'El plan de acción convierte ese objetivo en actividades observables y medibles.' }
            ]
        },
        {
            view: 'priorities',
            title: 'Decidir qué atender primero',
            description: 'Las actividades se organizan entre hacer ahora, planificar, delegar o eliminar. Así el emprendedor concentra su tiempo en lo que realmente mueve el negocio.',
            highlights: [
                { target: '[data-view="priorities"] .page-intro', description: 'La tercera pantalla ordena las mismas actividades según impacto y urgencia.' },
                { target: '[data-view="priorities"] [data-quadrant="do"]', description: 'Primero aparecen las tareas urgentes e importantes: Hacer ahora.' },
                { target: '[data-view="priorities"] [data-quadrant="plan"]', description: 'Después se muestran las actividades importantes que conviene Planificar.' },
                { target: '[data-view="priorities"] [data-quadrant="delegate"]', description: 'Las tareas urgentes que no requieren al propietario pueden Delegarse.' },
                { target: '[data-view="priorities"] [data-quadrant="remove"]', description: 'Finalmente se identifican actividades de bajo valor que conviene Eliminar.' }
            ]
        },
        {
            view: 'finances',
            title: 'Entender el desempeño financiero',
            description: 'El registro simple de ventas, costo de ventas y gastos permite calcular la utilidad bruta y el EBITDA provisional con la fórmula compartida por el cliente.',
            highlights: [
                { target: '[data-view="finances"] .page-intro', description: 'La cuarta pantalla presenta una lectura financiera básica y comprensible.' },
                { target: '[data-view="finances"] .finance-metrics', description: 'Los indicadores separan ventas, costo de ventas, utilidad bruta y EBITDA provisional.' },
                { target: '[data-view="finances"] #financeForm', description: 'El cierre diario solicita ventas, costo de ventas, gastos operativos y gastos administrativos.' },
                { target: '[data-view="finances"] .chart-panel', description: 'El gráfico permite comparar la evolución reciente de las ventas frente a los costos y gastos.' },
                { target: '[data-view="finances"] .indicator-row', description: 'La utilidad bruta y el EBITDA ya tienen cálculo provisional; el punto de equilibrio continúa pendiente.' }
            ]
        },
        {
            view: 'crm',
            title: 'Dar seguimiento a clientes y ventas',
            description: 'Los prospectos, clientes y oportunidades se reúnen en una vista comercial básica para saber de dónde llegan y cuál debería ser el próximo seguimiento.',
            highlights: [
                { target: '[data-view="crm"] .page-intro', description: 'La quinta pantalla conecta la operación con prospectos, clientes y ventas.' },
                { target: '[data-view="crm"] > .metric-row', description: 'El resumen comercial muestra prospectos, clientes y valor potencial de las oportunidades.' },
                { target: '[data-view="crm"] .crm-table-panel', description: 'El pipeline reúne contacto, necesidad, canal, estado, valor y próximo seguimiento.' },
                { target: '[data-view="crm"] .acquisition-panel', description: 'El origen de los prospectos muestra cuáles canales acercan nuevos clientes.' }
            ]
        },
        {
            view: 'summary',
            title: 'Reunir el negocio en una sola vista',
            description: 'El resumen conecta diagnóstico, objetivos, prioridades, finanzas y ventas para mostrar avances, alertas y próximos pasos sin perder el hilo del negocio.',
            highlights: [
                { target: '[data-view="summary"] .summary-hero', description: 'La última pantalla presenta el avance general y el principal foco de atención.' },
                { target: '[data-view="summary"] .summary-metrics', description: 'Diagnóstico, objetivos, finanzas y oportunidades quedan conectados en una sola lectura.' },
                { target: '[data-view="summary"] .next-actions', description: 'Las próximas acciones indican qué debería atender el negocio primero.' },
                { target: '[data-view="summary"] .health-card', description: 'Las señales del negocio separan avances, alertas y aspectos pendientes de validar.' },
                { target: '[data-view="summary"] .future-panel', description: 'El cierre diferencia claramente la versión básica de las capacidades futuras.' }
            ]
        }
    ];
    let toastTimer = null;
    let activePriorityTask = null;
    let activeOpportunity = null;
    let activeTourTarget = null;
    let tourIndex = -1;
    let tourTimer = null;
    let tourHighlightIndex = 0;
    let tourIsPlaying = false;
    let focusBeforeTour = null;
    let spotlightFrame = null;

    function showToast(message) {
        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.classList.add('is-visible');
        toastTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2600);
    }

    function showView(viewName, options) {
        if (!viewMeta[viewName]) {
            return;
        }

        const settings = options || {};

        document.querySelectorAll('[data-view]').forEach(function (view) {
            view.classList.toggle('is-visible', view.dataset.view === viewName);
        });

        document.querySelectorAll('.nav-item').forEach(function (item) {
            item.classList.toggle('is-active', item.dataset.viewTarget === viewName);
        });

        sectionEyebrow.textContent = viewMeta[viewName][0];
        sectionTitle.textContent = viewMeta[viewName][1];
        sidebar.classList.remove('is-open');

        if (settings.scroll !== false) {
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion.matches ? 'auto' : 'smooth'
            });
        }
    }

    document.addEventListener('click', function (event) {
        const viewTrigger = event.target.closest('[data-view-target]');

        if (viewTrigger) {
            showView(viewTrigger.dataset.viewTarget);
            return;
        }

        const modalTrigger = event.target.closest('[data-modal-open]');

        if (modalTrigger) {
            openModal(modalTrigger.dataset.modalOpen);
            return;
        }

        if (event.target.closest('[data-modal-close]')) {
            closeModal(event.target.closest('.modal-backdrop'));
            return;
        }

        const toastTrigger = event.target.closest('[data-toast]');

        if (toastTrigger) {
            showToast(toastTrigger.dataset.toast);
        }
    });

    document.getElementById('mobileMenu').addEventListener('click', function () {
        sidebar.classList.toggle('is-open');
    });

    function openModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        const firstInteractive = modal.querySelector('button, input, select, textarea');

        if (firstInteractive) {
            window.setTimeout(function () {
                firstInteractive.focus();
            }, 40);
        }
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = true;

        if (!document.querySelector('.modal-backdrop:not([hidden])')) {
            document.body.style.overflow = '';
        }
    }

    document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (isTourActive()) {
            if (event.key === 'Escape') {
                event.preventDefault();
                stopTour();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                moveTour(1);
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                moveTour(-1);
            } else if (event.key === 'Tab') {
                trapTourFocus(event);
            }

            return;
        }

        if (event.key !== 'Escape') {
            return;
        }

        const openModalElement = document.querySelector('.modal-backdrop:not([hidden])');

        if (openModalElement) {
            closeModal(openModalElement);
        } else {
            sidebar.classList.remove('is-open');
        }
    });

    function isTourActive() {
        return !tourPanel.hidden;
    }

    function clearTourTimer() {
        window.clearTimeout(tourTimer);
        tourTimer = null;
    }

    function setTourPlayback(isPlaying, shouldSchedule) {
        tourIsPlaying = isPlaying;
        tourPlaybackButton.setAttribute('aria-pressed', String(isPlaying));
        tourPlaybackIcon.textContent = isPlaying ? 'Ⅱ' : '▶';
        tourPlaybackLabel.textContent = isPlaying ? 'Pausar recorrido' : 'Reproducir automáticamente';

        clearTourTimer();

        if (isPlaying && shouldSchedule !== false) {
            scheduleTourAdvance();
        }
    }

    function scheduleTourAdvance() {
        clearTourTimer();

        if (!tourIsPlaying) {
            return;
        }

        const highlights = tourSteps[tourIndex].highlights;

        tourTimer = window.setTimeout(function () {
            tourCard.classList.add('is-leaving');

            tourTimer = window.setTimeout(function () {
                if (tourHighlightIndex < highlights.length - 1) {
                    renderTourHighlight(tourHighlightIndex + 1, true);
                    scheduleTourAdvance();
                    return;
                }

                if (tourIndex >= tourSteps.length - 1) {
                    tourCard.classList.remove('is-leaving');
                    completeTour();
                    return;
                }

                renderTourStep(tourIndex + 1);
            }, tourCardTransitionDuration);
        }, tourHighlightReadingDuration);
    }

    function renderTourProgress() {
        tourProgress.replaceChildren();

        tourSteps.forEach(function (_, index) {
            const segment = document.createElement('span');
            segment.classList.toggle('is-complete', index < tourIndex);
            segment.classList.toggle('is-current', index === tourIndex);
            tourProgress.appendChild(segment);
        });
    }

    function setTourBox(element, top, left, width, height) {
        element.style.top = Math.round(top) + 'px';
        element.style.left = Math.round(left) + 'px';
        element.style.width = Math.max(0, Math.round(width)) + 'px';
        element.style.height = Math.max(0, Math.round(height)) + 'px';
    }

    function setSpotlightTransition(isImmediate) {
        const duration = isImmediate || prefersReducedMotion.matches ? '0ms' : '';

        tourSpotlight.style.transitionDuration = duration;
    }

    function positionTourCard(focus) {
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight;
        const cardRect = tourCard.getBoundingClientRect();
        const controlsRect = tourPanel.getBoundingClientRect();
        const safeMargin = 12;
        const gap = 18;
        const cardWidth = cardRect.width;
        const cardHeight = cardRect.height;
        const availableBottom = Math.min(viewportHeight - safeMargin, controlsRect.top - gap);
        const clampLeft = function (value) {
            return Math.max(safeMargin, Math.min(value, viewportWidth - cardWidth - safeMargin));
        };
        const clampTop = function (value) {
            return Math.max(safeMargin, Math.min(value, availableBottom - cardHeight));
        };
        const candidates = [
            { left: focus.right + gap, top: clampTop(focus.top) },
            { left: focus.left - cardWidth - gap, top: clampTop(focus.top) },
            { left: clampLeft(focus.left), top: focus.bottom + gap },
            { left: clampLeft(focus.left), top: focus.top - cardHeight - gap }
        ];
        const position = candidates.find(function (candidate) {
            return candidate.left >= safeMargin
                && candidate.top >= safeMargin
                && candidate.left + cardWidth <= viewportWidth - safeMargin
                && candidate.top + cardHeight <= availableBottom;
        }) || {
            left: viewportWidth <= 580 ? safeMargin : viewportWidth - cardWidth - safeMargin,
            top: Math.max(safeMargin, availableBottom - cardHeight)
        };

        tourCard.style.left = Math.round(position.left) + 'px';
        tourCard.style.top = Math.round(position.top) + 'px';
    }

    function positionTourSpotlight(isImmediate) {
        if (!isTourActive() || !activeTourTarget) {
            return;
        }

        const rect = activeTourTarget.getBoundingClientRect();
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight;
        const padding = 10;
        const safeMargin = 12;
        const top = Math.max(
            safeMargin,
            Math.min(rect.top - padding, viewportHeight - safeMargin - 40)
        );
        const left = Math.max(
            safeMargin,
            Math.min(rect.left - padding, viewportWidth - safeMargin - 40)
        );
        const right = Math.max(
            left + 40,
            Math.min(viewportWidth - safeMargin, rect.right + padding)
        );
        const bottom = Math.max(
            top + 40,
            Math.min(viewportHeight - safeMargin, rect.bottom + padding)
        );
        const width = Math.max(40, right - left);
        const height = Math.max(40, bottom - top);

        setSpotlightTransition(isImmediate);
        setTourBox(tourSpotlight, top, left, width, height);
        positionTourCard({ top: top, right: right, bottom: bottom, left: left });
    }

    function queueSpotlightPosition() {
        if (spotlightFrame || !isTourActive()) {
            return;
        }

        spotlightFrame = window.requestAnimationFrame(function () {
            spotlightFrame = null;
            positionTourSpotlight(true);
        });
    }

    function renderTourHighlight(nextHighlightIndex, shouldScroll) {
        const step = tourSteps[tourIndex];
        const boundedIndex = Math.max(0, Math.min(step.highlights.length - 1, nextHighlightIndex));
        const highlight = step.highlights[boundedIndex];

        tourHighlightIndex = boundedIndex;
        activeTourTarget = document.querySelector(highlight.target);
        tourFocusLabel.textContent = 'Sector ' + (tourHighlightIndex + 1) + ' de ' + step.highlights.length;
        tourFocusDescription.textContent = highlight.description;
        tourDescription.hidden = tourHighlightIndex !== 0;

        if (!activeTourTarget) {
            return;
        }

        positionTourSpotlight(false);
        tourCard.classList.remove('is-leaving');

        if (shouldScroll) {
            activeTourTarget.scrollIntoView({
                behavior: prefersReducedMotion.matches ? 'auto' : 'smooth',
                block: 'center'
            });
        }
    }

    function showTourChapter(viewName) {
        const duration = prefersReducedMotion.matches ? 900 : tourChapterDuration;

        activeTourTarget = null;
        tourScrim.hidden = true;
        tourCard.hidden = true;
        tourChapterTitle.textContent = tourChapterTitles[viewName];
        tourChapter.hidden = false;
        tourChapter.classList.remove('is-visible');
        void tourChapter.offsetWidth;
        tourChapter.classList.add('is-visible');
        previousTourStep.disabled = true;
        nextTourStep.disabled = true;
        tourPlaybackButton.disabled = true;

        tourTimer = window.setTimeout(function () {
            tourChapter.classList.remove('is-visible');
            tourChapter.hidden = true;
            tourScrim.hidden = false;
            tourCard.hidden = false;
            previousTourStep.disabled = tourIndex === 0;
            nextTourStep.disabled = false;
            tourPlaybackButton.disabled = false;
            renderTourHighlight(0, true);

            if (tourIsPlaying) {
                scheduleTourAdvance();
            }
        }, duration);
    }

    function renderTourStep(nextIndex) {
        const boundedIndex = Math.max(0, Math.min(tourSteps.length - 1, nextIndex));
        const step = tourSteps[boundedIndex];

        clearTourTimer();
        tourIndex = boundedIndex;
        tourHighlightIndex = 0;

        showView(step.view, { scroll: false });
        window.scrollTo({ top: 0, behavior: 'auto' });
        tourStepLabel.textContent = 'Paso ' + (tourIndex + 1) + ' de ' + tourSteps.length;
        tourTitle.textContent = step.title;
        tourDescription.textContent = step.description;
        previousTourStep.disabled = tourIndex === 0;
        nextTourStep.textContent = tourIndex === tourSteps.length - 1 ? 'Finalizar recorrido' : 'Siguiente →';
        renderTourProgress();
        showTourChapter(step.view);
    }

    function startTour() {
        document.querySelectorAll('.modal-backdrop:not([hidden])').forEach(function (modal) {
            closeModal(modal);
        });

        focusBeforeTour = document.activeElement;
        tourScrim.hidden = false;
        tourCard.hidden = false;
        tourPanel.hidden = false;
        document.body.classList.add('tour-is-active');
        setTourPlayback(true, false);
        renderTourStep(0);
        document.getElementById('closeTour').focus();
    }

    function stopTour() {
        if (!isTourActive()) {
            return;
        }

        clearTourTimer();
        tourIsPlaying = false;
        tourIndex = -1;
        tourScrim.hidden = true;
        tourChapter.hidden = true;
        tourChapter.classList.remove('is-visible');
        tourCard.hidden = true;
        tourCard.classList.remove('is-leaving');
        tourPanel.hidden = true;
        document.body.classList.remove('tour-is-active');
        activeTourTarget = null;

        if (focusBeforeTour && typeof focusBeforeTour.focus === 'function') {
            focusBeforeTour.focus();
        }

    }

    function completeTour() {
        stopTour();
        openModal('validationModal');
    }

    function moveTour(direction) {
        if (direction > 0 && tourIndex === tourSteps.length - 1) {
            completeTour();
            return;
        }

        renderTourStep(tourIndex + direction);
    }

    function trapTourFocus(event) {
        const controls = Array.from(tourPanel.querySelectorAll('button:not([disabled])'));

        if (!controls.length) {
            return;
        }

        const firstControl = controls[0];
        const lastControl = controls[controls.length - 1];

        if (event.shiftKey && document.activeElement === firstControl) {
            event.preventDefault();
            lastControl.focus();
        } else if (!event.shiftKey && document.activeElement === lastControl) {
            event.preventDefault();
            firstControl.focus();
        }
    }

    document.getElementById('startTour').addEventListener('click', startTour);
    document.getElementById('closeTour').addEventListener('click', function () {
        stopTour();
    });
    previousTourStep.addEventListener('click', function () {
        moveTour(-1);
    });
    nextTourStep.addEventListener('click', function () {
        moveTour(1);
    });
    tourPlaybackButton.addEventListener('click', function () {
        setTourPlayback(!tourIsPlaying);
    });
    window.addEventListener('scroll', queueSpotlightPosition, { passive: true });
    window.addEventListener('resize', queueSpotlightPosition);

    const editDiagnosis = document.getElementById('editDiagnosis');
    const saveDiagnosis = document.getElementById('saveDiagnosis');
    const cancelDiagnosis = document.getElementById('cancelDiagnosis');
    const diagnosisFields = Array.from(document.querySelectorAll('.question-content textarea'));
    const originalDiagnosis = diagnosisFields.map(function (field) {
        return field.value;
    });

    function setDiagnosisEditMode(isEditing) {
        diagnosisFields.forEach(function (field) {
            field.disabled = !isEditing;
        });

        editDiagnosis.classList.toggle('is-hidden', isEditing);
        saveDiagnosis.classList.toggle('is-hidden', !isEditing);
        cancelDiagnosis.classList.toggle('is-hidden', !isEditing);
    }

    editDiagnosis.addEventListener('click', function () {
        setDiagnosisEditMode(true);
        diagnosisFields[0].focus();
    });

    cancelDiagnosis.addEventListener('click', function () {
        diagnosisFields.forEach(function (field, index) {
            field.value = originalDiagnosis[index];
        });
        setDiagnosisEditMode(false);
    });

    saveDiagnosis.addEventListener('click', function () {
        setDiagnosisEditMode(false);
        showToast('Diagnóstico actualizado en la demostración');
    });

    document.querySelectorAll('[data-demo-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            closeModal(form.closest('.modal-backdrop'));
            showToast(form.dataset.success || 'Cambio aplicado en la demostración');
        });
    });

    const progressTasks = Array.from(document.querySelectorAll('[data-progress-task]'));
    const objectiveProgress = document.getElementById('objectiveProgress');
    const objectiveProgressBar = document.getElementById('objectiveProgressBar');
    const summaryProgress = document.getElementById('summaryProgress');
    const progressCaption = document.getElementById('progressCaption');

    function refreshTaskProgress() {
        const completed = progressTasks.filter(function (task) {
            return task.checked;
        }).length;
        const percentage = Math.min(100, 35 + completed * 10);

        objectiveProgress.textContent = percentage + '%';
        objectiveProgressBar.style.width = percentage + '%';
        summaryProgress.textContent = percentage + '%';
        progressCaption.textContent = completed
            ? completed + ' actividad' + (completed === 1 ? '' : 'es') + ' completada' + (completed === 1 ? '' : 's') + ' en esta demostración'
            : '2 de 6 actividades en curso';
    }

    progressTasks.forEach(function (task) {
        task.addEventListener('change', function () {
            refreshTaskProgress();
            showToast(task.checked ? 'Actividad marcada como completada' : 'Actividad devuelta a pendiente');
        });
    });

    document.querySelectorAll('[data-priority-task]').forEach(function (task) {
        task.addEventListener('click', function () {
            activePriorityTask = task;
            document.getElementById('priorityTaskTitle').textContent = task.dataset.title;
            document.getElementById('quadrantSelect').value = task.dataset.quadrantValue;
            openModal('priorityTaskModal');
        });
    });

    function updateQuadrantCounts() {
        document.querySelectorAll('[data-quadrant]').forEach(function (quadrant) {
            const count = quadrant.querySelectorAll('[data-priority-task]').length;
            quadrant.querySelector('header em').textContent = count + ' tarea' + (count === 1 ? '' : 's');
        });
    }

    document.getElementById('priorityTaskForm').addEventListener('submit', function (event) {
        event.preventDefault();

        if (!activePriorityTask) {
            return;
        }

        const newQuadrant = document.getElementById('quadrantSelect').value;
        const target = document.querySelector('[data-quadrant="' + newQuadrant + '"]');

        if (target && activePriorityTask.dataset.quadrantValue !== newQuadrant) {
            activePriorityTask.dataset.quadrantValue = newQuadrant;
            target.appendChild(activePriorityTask);
            updateQuadrantCounts();
        }

        closeModal(document.getElementById('priorityTaskModal'));
        showToast('Clasificación actualizada visualmente');
    });

    const salesInput = document.getElementById('salesInput');
    const costOfSalesInput = document.getElementById('costOfSalesInput');
    const operatingExpenseInput = document.getElementById('operatingExpenseInput');
    const administrativeExpenseInput = document.getElementById('administrativeExpenseInput');
    const formResult = document.getElementById('formResult');

    function getFinanceValues() {
        const sales = Number(salesInput.value) || 0;
        const costOfSales = Number(costOfSalesInput.value) || 0;
        const operatingExpenses = Number(operatingExpenseInput.value) || 0;
        const administrativeExpenses = Number(administrativeExpenseInput.value) || 0;
        const grossProfit = sales - costOfSales;

        return {
            sales: sales,
            costOfSales: costOfSales,
            grossProfit: grossProfit,
            ebitda: grossProfit - operatingExpenses - administrativeExpenses
        };
    }

    function formatMoney(value) {
        const sign = value < 0 ? '-' : '';
        return sign + '$' + Math.abs(value).toLocaleString('es-EC', { maximumFractionDigits: 0 });
    }

    function refreshFinancePreview() {
        formResult.textContent = formatMoney(getFinanceValues().ebitda);
    }

    [salesInput, costOfSalesInput, operatingExpenseInput, administrativeExpenseInput].forEach(function (input) {
        input.addEventListener('input', refreshFinancePreview);
    });

    document.getElementById('financeForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const values = getFinanceValues();

        document.getElementById('dailySales').textContent = formatMoney(values.sales);
        document.getElementById('dailyCostOfSales').textContent = formatMoney(values.costOfSales);
        document.getElementById('dailyGrossProfit').textContent = formatMoney(values.grossProfit);
        document.getElementById('dailyEbitda').textContent = formatMoney(values.ebitda);
        showToast('Cierre registrado únicamente en la demostración');
    });

    const opportunityTable = document.getElementById('opportunityTable');
    const opportunityCount = document.getElementById('opportunityCount');
    const summaryOpportunities = document.getElementById('summaryOpportunities');
    const opportunityValue = document.getElementById('opportunityValue');
    let visibleOpportunityCount = 6;
    let visibleOpportunityValue = 2450;

    function initials(name) {
        return name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (part) { return part.charAt(0).toUpperCase(); })
            .join('');
    }

    function createOpportunityRow(name, need, channel, value) {
        const row = document.createElement('tr');
        row.dataset.opportunity = '';
        row.dataset.name = name;
        row.dataset.need = need;
        row.dataset.channel = channel;
        row.dataset.status = 'Nuevo';
        row.dataset.value = formatMoney(value);

        const contactCell = document.createElement('td');
        const contactWrap = document.createElement('span');
        const avatar = document.createElement('i');
        const contactName = document.createElement('strong');
        contactWrap.className = 'contact-cell';
        avatar.textContent = initials(name);
        contactName.textContent = name;
        contactWrap.append(avatar, contactName);
        contactCell.appendChild(contactWrap);

        const needCell = document.createElement('td');
        needCell.textContent = need;

        const channelCell = document.createElement('td');
        const channelTag = document.createElement('span');
        channelTag.className = 'channel-tag';
        channelTag.textContent = channel;
        channelCell.appendChild(channelTag);

        const statusCell = document.createElement('td');
        const statusTag = document.createElement('span');
        statusTag.className = 'status-tag status-new';
        statusTag.textContent = 'Nuevo';
        statusCell.appendChild(statusTag);

        const valueCell = document.createElement('td');
        const valueStrong = document.createElement('strong');
        valueStrong.textContent = formatMoney(value);
        valueCell.appendChild(valueStrong);

        const followUpCell = document.createElement('td');
        followUpCell.textContent = '31 jul';

        row.append(contactCell, needCell, channelCell, statusCell, valueCell, followUpCell);
        return row;
    }

    document.getElementById('opportunityForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const name = document.getElementById('newContactName').value.trim() || 'Nuevo contacto';
        const need = document.getElementById('newContactNeed').value.trim() || 'Pedido por definir';
        const channel = document.getElementById('newContactChannel').value;
        const value = Number(document.getElementById('newContactValue').value) || 0;
        const row = createOpportunityRow(name, need, channel, value);

        opportunityTable.prepend(row);
        visibleOpportunityCount += 1;
        visibleOpportunityValue += value;
        opportunityCount.textContent = visibleOpportunityCount;
        summaryOpportunities.textContent = visibleOpportunityCount;
        opportunityValue.textContent = formatMoney(visibleOpportunityValue);
        closeModal(document.getElementById('opportunityModal'));
        showToast('Oportunidad agregada a la demostración');
    });

    opportunityTable.addEventListener('click', function (event) {
        const row = event.target.closest('[data-opportunity]');

        if (!row) {
            return;
        }

        activeOpportunity = row;
        document.getElementById('opportunityDetailTitle').textContent = row.dataset.name;
        document.getElementById('detailNeed').textContent = row.dataset.need;
        document.getElementById('detailChannel').textContent = row.dataset.channel;
        document.getElementById('detailStatus').textContent = row.dataset.status;
        document.getElementById('detailValue').textContent = row.dataset.value;
        openModal('opportunityDetailModal');
    });

    document.getElementById('advanceOpportunity').addEventListener('click', function () {
        if (!activeOpportunity) {
            return;
        }

        const statusOrder = ['Nuevo', 'Contactado', 'Propuesta enviada', 'Negociación'];
        const currentIndex = statusOrder.indexOf(activeOpportunity.dataset.status);
        const nextStatus = statusOrder[Math.min(statusOrder.length - 1, currentIndex + 1)];
        const statusClass = {
            'Nuevo': 'status-new',
            'Contactado': 'status-contacted',
            'Propuesta enviada': 'status-proposal',
            'Negociación': 'status-negotiation'
        };
        const statusElement = activeOpportunity.querySelector('.status-tag');

        activeOpportunity.dataset.status = nextStatus;
        statusElement.className = 'status-tag ' + statusClass[nextStatus];
        statusElement.textContent = nextStatus;
        document.getElementById('detailStatus').textContent = nextStatus;
        showToast('Estado avanzado visualmente a “' + nextStatus + '”');
    });

    document.querySelectorAll('.filter-button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.filter-button').forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');
            showToast('Filtro “' + button.textContent.trim() + '” aplicado visualmente');
        });
    });
})();
