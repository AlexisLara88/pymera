<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mockup navegable de PyMe ERP-LITE para validación de producto">
    <title>PyMe ERP-LITE — Demo</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/erp-lite-demo.css?v=' . filemtime(FCPATH . 'assets/css/erp-lite-demo.css')) ?>">
</head>
<body>
<div class="demo-shell" id="demoApp">
    <aside class="sidebar" aria-label="Navegación principal">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">P</span>
            <div>
                <strong>PyMe</strong>
                <span>ERP-LITE</span>
            </div>
        </div>

        <div class="demo-label">
            <span class="status-dot"></span>
            Prototipo navegable
        </div>

        <nav class="main-nav">
            <button class="nav-item is-active" type="button" data-view-target="business">
                <span class="nav-icon">01</span>
                <span>Mi negocio</span>
                <small>Diagnóstico</small>
            </button>
            <button class="nav-item" type="button" data-view-target="objectives">
                <span class="nav-icon">02</span>
                <span>Objetivos</span>
                <small>Plan de acción</small>
            </button>
            <button class="nav-item" type="button" data-view-target="priorities">
                <span class="nav-icon">03</span>
                <span>Prioridades</span>
                <small>Matriz Eisenhower</small>
            </button>
            <button class="nav-item" type="button" data-view-target="finances">
                <span class="nav-icon">04</span>
                <span>Finanzas</span>
                <small>Salud del negocio</small>
            </button>
            <button class="nav-item" type="button" data-view-target="crm">
                <span class="nav-icon">05</span>
                <span>Clientes y ventas</span>
                <small>CRM básico</small>
            </button>
            <button class="nav-item" type="button" data-view-target="summary">
                <span class="nav-icon">06</span>
                <span>Resumen</span>
                <small>Vista integral</small>
            </button>
        </nav>

        <div class="sidebar-bottom">
            <div class="business-switcher">
                <span class="business-avatar">DB</span>
                <div>
                    <strong>Dulce Barrio</strong>
                    <small>Pastelería artesanal</small>
                </div>
                <span class="demo-chip">Demo</span>
            </div>

            <form class="sidebar-session" action="<?= site_url('logout') ?>" method="post">
                <?= csrf_field() ?>
                <span>Sesión protegida</span>
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu" id="mobileMenu" type="button" aria-label="Abrir menú">☰</button>
            <div>
                <p class="eyebrow" id="sectionEyebrow">Diagnóstico del negocio</p>
                <h1 id="sectionTitle">Mi negocio</h1>
            </div>
            <div class="topbar-actions">
                <button class="tour-launch-button" id="startTour" type="button">
                    <span aria-hidden="true">▶</span>
                    <span>Ver recorrido</span>
                </button>
                <span class="plan-badge">Versión básica</span>
                <button class="notification-button" type="button" data-toast="No hay notificaciones nuevas en esta demostración" aria-label="Notificaciones">
                    <svg class="notification-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                        <path d="M10 21h4"></path>
                    </svg>
                    <span class="notification-dot" aria-hidden="true"></span>
                </button>
                <div class="user-profile">
                    <span class="user-avatar">AM</span>
                    <div>
                        <strong>Ana Morales</strong>
                        <small>Propietaria</small>
                    </div>
                </div>
            </div>
        </header>

        <div class="prototype-notice">
            <strong>Datos ficticios.</strong>
            Esta experiencia simula el recorrido del producto; no guarda información ni realiza cálculos definitivos.
            <button type="button" data-modal-open="aboutModal">Qué estamos validando</button>
        </div>

        <section class="app-view is-visible" data-view="business">
            <div class="page-intro">
                <div>
                    <span class="step-pill">Paso 1 de 6</span>
                    <h2>Primero, entendamos tu negocio</h2>
                    <p>Estas respuestas aportan contexto a tus objetivos, prioridades, finanzas y seguimiento comercial.</p>
                </div>
                <div class="completion-card">
                    <div class="completion-ring"><span>100%</span></div>
                    <div>
                        <strong>Diagnóstico completo</strong>
                        <small>Actualizado hoy</small>
                    </div>
                </div>
            </div>

            <div class="content-grid business-grid">
                <article class="panel diagnosis-panel">
                    <div class="panel-heading">
                        <div>
                            <span class="section-kicker">Tu punto de partida</span>
                            <h3>Diagnóstico inicial</h3>
                        </div>
                        <button class="button button-ghost" id="editDiagnosis" type="button">Editar respuestas</button>
                    </div>

                    <div class="question-list">
                        <label class="question-card">
                            <span class="question-number">01</span>
                            <span class="question-content">
                                <strong>¿Qué haces diferente frente a otros negocios?</strong>
                                <textarea disabled>Diseñamos pasteles personalizados y acompañamos al cliente desde la elección del sabor hasta la entrega.</textarea>
                            </span>
                        </label>
                        <label class="question-card">
                            <span class="question-number">02</span>
                            <span class="question-content">
                                <strong>¿Cómo produces o entregas esa diferencia?</strong>
                                <textarea disabled>Realizamos una entrevista breve, confirmamos el diseño y ofrecemos una muestra de sabor para pedidos especiales.</textarea>
                            </span>
                        </label>
                        <label class="question-card">
                            <span class="question-number">03</span>
                            <span class="question-content">
                                <strong>¿Qué resultado obtiene tu cliente?</strong>
                                <textarea disabled>Recibe un pastel alineado con la celebración y reduce la incertidumbre sobre diseño, tamaño y sabor.</textarea>
                            </span>
                        </label>
                        <label class="question-card">
                            <span class="question-number">04</span>
                            <span class="question-content">
                                <strong>¿Por qué consideras que te compran a ti?</strong>
                                <textarea disabled>Por la personalización, la cercanía y las recomendaciones de clientes anteriores.</textarea>
                            </span>
                        </label>
                        <label class="question-card">
                            <span class="question-number">05</span>
                            <span class="question-content">
                                <strong>¿Cómo llegan actualmente tus clientes?</strong>
                                <textarea disabled>Principalmente por Instagram, recomendaciones y búsquedas locales.</textarea>
                            </span>
                        </label>
                    </div>

                    <div class="panel-actions">
                        <button class="button button-secondary is-hidden" id="cancelDiagnosis" type="button">Cancelar</button>
                        <button class="button button-primary is-hidden" id="saveDiagnosis" type="button">Guardar diagnóstico</button>
                        <button class="button button-primary" type="button" data-view-target="objectives">
                            Continuar con mis objetivos
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </article>

                <aside class="side-stack">
                    <article class="panel business-profile-card">
                        <div class="profile-cover">
                            <span class="profile-logo">DB</span>
                            <span class="verified-badge">Perfil verificado</span>
                        </div>
                        <h3>Dulce Barrio</h3>
                        <p>Pastelería artesanal especializada en celebraciones y pedidos personalizados.</p>
                        <dl class="profile-facts">
                            <div><dt>Ubicación</dt><dd>Quito, Ecuador</dd></div>
                            <div><dt>Equipo</dt><dd>3 personas</dd></div>
                            <div><dt>Moneda</dt><dd>USD</dd></div>
                            <div><dt>Canal principal</dt><dd>Instagram</dd></div>
                        </dl>
                    </article>

                    <button class="premium-card" type="button" data-modal-open="premiumModal">
                        <span class="premium-icon">✦</span>
                        <span>
                            <small>Visión futura</small>
                            <strong>Análisis guiado con IA</strong>
                            <em>Profundiza el diagnóstico y detecta oportunidades.</em>
                        </span>
                        <span aria-hidden="true">→</span>
                    </button>
                </aside>
            </div>
        </section>

        <section class="app-view" data-view="objectives">
            <div class="page-intro compact">
                <div>
                    <span class="step-pill">Paso 2 de 6</span>
                    <h2>Convierte problemas en objetivos concretos</h2>
                    <p>Organiza acciones de mejora y revisa su avance sin perder el contexto del negocio.</p>
                </div>
                <button class="button button-primary" type="button" data-modal-open="objectiveModal">+ Nuevo objetivo</button>
            </div>

            <div class="metric-row">
                <article class="metric-card"><span>Objetivos activos</span><strong>1</strong><small>Prioridad alta</small></article>
                <article class="metric-card"><span>Actividades</span><strong id="activityCount">6</strong><small>Ligadas al objetivo</small></article>
                <article class="metric-card"><span>En curso</span><strong id="activeTaskCount">2</strong><small>Esta semana</small></article>
                <article class="metric-card"><span>Vencidas</span><strong>0</strong><small class="positive">Todo al día</small></article>
            </div>

            <article class="panel objective-hero">
                <div class="objective-main">
                    <div class="objective-meta">
                        <span class="category-tag">Mejora del negocio</span>
                        <span class="status-tag status-progress">En marcha</span>
                    </div>
                    <h3>Reducir en 30 % los reclamos relacionados con entregas de pasteles</h3>
                    <p>Crear un proceso claro de confirmación, prueba y conformidad para mejorar la experiencia del cliente durante los próximos 30 días.</p>
                    <div class="objective-details">
                        <span><small>Responsable</small><strong>Ana Morales</strong></span>
                        <span><small>Fecha objetivo</small><strong>28 ago 2026</strong></span>
                        <span><small>Origen</small><strong>Diagnóstico inicial</strong></span>
                    </div>
                </div>
                <div class="progress-block">
                    <div class="progress-value"><strong id="objectiveProgress">35%</strong><span>completado</span></div>
                    <div class="progress-track"><span id="objectiveProgressBar" style="width: 35%"></span></div>
                    <small id="progressCaption">2 de 6 actividades en curso</small>
                </div>
            </article>

            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Plan de acción</span>
                        <h3>Actividades relacionadas</h3>
                    </div>
                    <button class="button button-ghost" type="button" data-view-target="priorities">Ver en prioridades →</button>
                </div>
                <div class="task-list" id="objectiveTaskList">
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Crear un formulario de confirmación del pedido</strong><small>Impacto alto · Urgencia alta</small></span>
                        <span class="status-tag status-progress">En curso</span>
                        <time>31 jul</time>
                    </label>
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Preparar una muestra de sabor para pedidos especiales</strong><small>Impacto alto · Urgencia media</small></span>
                        <span class="status-tag status-pending">Pendiente</span>
                        <time>04 ago</time>
                    </label>
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Incorporar condiciones de entrega y conformidad</strong><small>Impacto alto · Urgencia alta</small></span>
                        <span class="status-tag status-pending">Pendiente</span>
                        <time>02 ago</time>
                    </label>
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Registrar la causa de cada reclamo</strong><small>Impacto alto · Urgencia media</small></span>
                        <span class="status-tag status-progress">En curso</span>
                        <time>30 jul</time>
                    </label>
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Delegar la confirmación de pedidos del viernes</strong><small>Impacto medio · Urgencia alta</small></span>
                        <span class="status-tag status-pending">Pendiente</span>
                        <time>31 jul</time>
                    </label>
                    <label class="task-row">
                        <input type="checkbox" data-progress-task>
                        <span class="custom-check"></span>
                        <span class="task-copy"><strong>Eliminar el registro duplicado en papel</strong><small>Impacto bajo · Urgencia baja</small></span>
                        <span class="status-tag status-pending">Pendiente</span>
                        <time>07 ago</time>
                    </label>
                </div>
            </article>
        </section>

        <section class="app-view" data-view="priorities">
            <div class="page-intro compact">
                <div>
                    <span class="step-pill">Paso 3 de 6</span>
                    <h2>Decide dónde enfocar tu tiempo</h2>
                    <p>Las mismas actividades del objetivo se organizan según su impacto y urgencia.</p>
                </div>
                <button class="button button-primary" type="button" data-view-target="finances">Ver finanzas →</button>
            </div>

            <div class="eisenhower-legend">
                <span><i class="dot do"></i> Hacer ahora</span>
                <span><i class="dot plan"></i> Planificar</span>
                <span><i class="dot delegate"></i> Delegar</span>
                <span><i class="dot remove"></i> Eliminar</span>
                <button type="button" data-modal-open="priorityHelpModal">¿Cómo se clasifica?</button>
            </div>

            <div class="eisenhower-grid" id="eisenhowerGrid">
                <article class="quadrant quadrant-do" data-quadrant="do">
                    <header><span>Urgente + importante</span><strong>Hacer ahora</strong><em>2 tareas</em></header>
                    <div class="priority-task" data-priority-task data-title="Crear formulario de confirmación" data-quadrant-value="do">
                        <span class="task-type">Proceso comercial</span>
                        <strong>Crear formulario de confirmación del pedido</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>AM</span><time>31 jul</time></footer>
                    </div>
                    <div class="priority-task" data-priority-task data-title="Incorporar condiciones de entrega" data-quadrant-value="do">
                        <span class="task-type">Experiencia del cliente</span>
                        <strong>Incorporar condiciones de entrega y conformidad</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>AM</span><time>02 ago</time></footer>
                    </div>
                </article>

                <article class="quadrant quadrant-plan" data-quadrant="plan">
                    <header><span>Importante + no urgente</span><strong>Planificar</strong><em>2 tareas</em></header>
                    <div class="priority-task" data-priority-task data-title="Preparar muestra de sabor" data-quadrant-value="plan">
                        <span class="task-type">Calidad</span>
                        <strong>Preparar una muestra de sabor para pedidos especiales</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>AM</span><time>04 ago</time></footer>
                    </div>
                    <div class="priority-task" data-priority-task data-title="Registrar causas de reclamos" data-quadrant-value="plan">
                        <span class="task-type">Medición</span>
                        <strong>Registrar la causa de cada reclamo</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>AM</span><time>30 jul</time></footer>
                    </div>
                </article>

                <article class="quadrant quadrant-delegate" data-quadrant="delegate">
                    <header><span>Urgente + menos importante</span><strong>Delegar</strong><em>1 tarea</em></header>
                    <div class="priority-task" data-priority-task data-title="Delegar confirmación de pedidos" data-quadrant-value="delegate">
                        <span class="task-type">Operación</span>
                        <strong>Delegar la confirmación de pedidos del viernes</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>LC</span><time>31 jul</time></footer>
                    </div>
                </article>

                <article class="quadrant quadrant-remove" data-quadrant="remove">
                    <header><span>No urgente + bajo impacto</span><strong>Eliminar</strong><em>1 tarea</em></header>
                    <div class="priority-task" data-priority-task data-title="Eliminar registro duplicado" data-quadrant-value="remove">
                        <span class="task-type">Simplificación</span>
                        <strong>Eliminar el registro duplicado en papel</strong>
                        <small>Objetivo: reducir reclamos</small>
                        <footer><span>AM</span><time>07 ago</time></footer>
                    </div>
                </article>
            </div>

            <button class="premium-strip" type="button" data-modal-open="premiumModal">
                <span class="premium-icon">✦</span>
                <span><strong>Optimización inteligente</strong><small>Detecta tareas repetitivas y sugiere qué delegar o simplificar.</small></span>
                <span class="premium-label">Premium</span>
                <span aria-hidden="true">→</span>
            </button>
        </section>

        <section class="app-view" data-view="finances">
            <div class="page-intro compact">
                <div>
                    <span class="step-pill">Paso 4 de 6</span>
                    <h2>Comprende la salud básica del negocio</h2>
                    <p>Un cierre simple permite observar ventas, costo de ventas, utilidad bruta y EBITDA provisional sin exigir contabilidad detallada.</p>
                </div>
                <button class="button button-primary" type="button" data-view-target="crm">Revisar clientes →</button>
            </div>

            <div class="metric-row finance-metrics">
                <article class="metric-card accent-green"><span>Ventas del día</span><strong id="dailySales">$480</strong><small>Jueves 6 ago</small></article>
                <article class="metric-card"><span>Costo de ventas</span><strong id="dailyCostOfSales">$190</strong><small>Costos variables asociados</small></article>
                <article class="metric-card accent-blue"><span>Utilidad bruta</span><strong id="dailyGrossProfit">$290</strong><small class="positive">Ventas menos costo de ventas</small></article>
                <article class="metric-card accent-warm"><span>EBITDA provisional</span><strong id="dailyEbitda">$170</strong><small>Según fórmula del cliente</small></article>
            </div>

            <div class="content-grid finance-grid">
                <article class="panel">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Registro simplificado</span><h3>Cierre del día</h3></div>
                        <span class="status-tag status-progress">Borrador</span>
                    </div>
                    <form class="finance-form" id="financeForm">
                        <label><span>Fecha operativa</span><input type="date" value="2026-08-06"></label>
                        <label><span>Ventas totales</span><div class="money-input"><b>$</b><input id="salesInput" type="number" min="0" value="480"></div></label>
                        <label><span>Costo de ventas</span><div class="money-input"><b>$</b><input id="costOfSalesInput" type="number" min="0" value="190"></div></label>
                        <label><span>Gastos operativos o fijos</span><div class="money-input"><b>$</b><input id="operatingExpenseInput" type="number" min="0" value="90"></div></label>
                        <label><span>Gastos administrativos</span><div class="money-input"><b>$</b><input id="administrativeExpenseInput" type="number" min="0" value="30"></div></label>
                        <label class="full-field"><span>Observación</span><textarea>Jornada estable. Se entregaron cuatro pedidos personalizados.</textarea></label>
                        <div class="calculation-preview full-field">
                            <span>EBITDA provisional del día</span>
                            <strong id="formResult">$170</strong>
                        </div>
                        <button class="button button-primary full-field" type="submit">Registrar cierre en la demostración</button>
                    </form>
                </article>

                <article class="panel chart-panel">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Últimos 7 días</span><h3>Ventas frente a costos y gastos</h3></div>
                        <span class="chart-legend"><i></i> Ventas <i></i> Costos y gastos</span>
                    </div>
                    <div class="bar-chart" aria-label="Gráfico demostrativo de ventas, costos y gastos">
                        <div class="chart-column"><div><i style="height:68%"></i><b style="height:43%"></b></div><span>Jue</span></div>
                        <div class="chart-column"><div><i style="height:52%"></i><b style="height:39%"></b></div><span>Vie</span></div>
                        <div class="chart-column"><div><i style="height:82%"></i><b style="height:54%"></b></div><span>Sáb</span></div>
                        <div class="chart-column"><div><i style="height:34%"></i><b style="height:26%"></b></div><span>Dom</span></div>
                        <div class="chart-column"><div><i style="height:61%"></i><b style="height:42%"></b></div><span>Lun</span></div>
                        <div class="chart-column"><div><i style="height:73%"></i><b style="height:46%"></b></div><span>Mar</span></div>
                        <div class="chart-column is-current"><div><i style="height:76%"></i><b style="height:49%"></b></div><span>Hoy</span></div>
                    </div>
                    <div class="monthly-breakdown">
                        <div><span>Ventas acumuladas</span><strong>$8.650</strong></div>
                        <div><span>Costo de ventas</span><strong>$3.480</strong></div>
                        <div><span>Utilidad bruta</span><strong>$5.170</strong></div>
                        <div><span>Gastos operativos o fijos</span><strong>$2.090</strong></div>
                        <div><span>Gastos administrativos</span><strong>$610</strong></div>
                        <div class="highlight"><span>EBITDA provisional</span><strong>$2.470</strong></div>
                    </div>
                </article>
            </div>

            <div class="indicator-row">
                <button class="indicator-card pending" type="button" data-modal-open="financialInfoModal">
                    <span class="indicator-icon">◫</span>
                    <span><small>Punto de equilibrio</small><strong>Pendiente de validar</strong><em>Fórmula no confirmada</em></span>
                </button>
                <button class="indicator-card" type="button" data-modal-open="financialInfoModal">
                    <span class="indicator-icon">↗</span>
                    <span><small>Utilidad bruta</small><strong>$5.170</strong><em>Ventas menos costo de ventas</em></span>
                </button>
                <button class="indicator-card" type="button" data-modal-open="financialInfoModal">
                    <span class="indicator-icon">◎</span>
                    <span><small>EBITDA provisional</small><strong>$2.470</strong><em>Fórmula compartida por el cliente</em></span>
                </button>
            </div>

            <button class="premium-strip" type="button" data-modal-open="financePremiumModal">
                <span class="premium-icon">✦</span>
                <span><strong>Finanzas detalladas y captura por WhatsApp</strong><small>Categorías, recurrencia, proveedores, costos, alertas y movimientos diarios.</small></span>
                <span class="premium-label">Premium</span>
                <span aria-hidden="true">→</span>
            </button>
        </section>

        <section class="app-view" data-view="crm">
            <div class="page-intro compact">
                <div>
                    <span class="step-pill">Paso 5 de 6</span>
                    <h2>Da seguimiento a clientes y oportunidades</h2>
                    <p>Observa quién está interesado, cómo llegó y cuál es la próxima acción comercial.</p>
                </div>
                <button class="button button-primary" type="button" data-modal-open="opportunityModal">+ Nueva oportunidad</button>
            </div>

            <div class="metric-row">
                <article class="metric-card"><span>Prospectos</span><strong>18</strong><small>5 nuevos este mes</small></article>
                <article class="metric-card"><span>Clientes</span><strong>42</strong><small>8 compras recurrentes</small></article>
                <article class="metric-card"><span>Oportunidades abiertas</span><strong id="opportunityCount">6</strong><small>En seguimiento</small></article>
                <article class="metric-card accent-green"><span>Valor estimado</span><strong id="opportunityValue">$2.450</strong><small>Pipeline demostrativo</small></article>
            </div>

            <div class="content-grid crm-grid">
                <article class="panel crm-table-panel">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Pipeline comercial</span><h3>Oportunidades activas</h3></div>
                        <div class="table-filters">
                            <button class="filter-button is-active" type="button">Todas</button>
                            <button class="filter-button" type="button">Esta semana</button>
                            <button class="filter-button" type="button">Instagram</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Contacto</th><th>Necesidad</th><th>Canal</th><th>Estado</th><th>Valor</th><th>Seguimiento</th></tr></thead>
                            <tbody id="opportunityTable">
                            <tr data-opportunity data-name="María P." data-need="Pastel de boda" data-channel="Recomendación" data-status="Propuesta enviada" data-value="$650">
                                <td><span class="contact-cell"><i>MP</i><strong>María P.</strong></span></td><td>Pastel de boda</td><td><span class="channel-tag">Recomendación</span></td><td><span class="status-tag status-proposal">Propuesta enviada</span></td><td><strong>$650</strong></td><td>31 jul</td>
                            </tr>
                            <tr data-opportunity data-name="Empresa Andina" data-need="Cumpleaños corporativo" data-channel="Instagram" data-status="Contactado" data-value="$420">
                                <td><span class="contact-cell"><i>EA</i><strong>Empresa Andina</strong></span></td><td>Cumpleaños corporativo</td><td><span class="channel-tag">Instagram</span></td><td><span class="status-tag status-contacted">Contactado</span></td><td><strong>$420</strong></td><td>01 ago</td>
                            </tr>
                            <tr data-opportunity data-name="Carlos R." data-need="Mesa dulce" data-channel="Búsqueda local" data-status="Nuevo" data-value="$280">
                                <td><span class="contact-cell"><i>CR</i><strong>Carlos R.</strong></span></td><td>Mesa dulce</td><td><span class="channel-tag">Búsqueda local</span></td><td><span class="status-tag status-new">Nuevo</span></td><td><strong>$280</strong></td><td>30 jul</td>
                            </tr>
                            <tr data-opportunity data-name="Valentina S." data-need="Pastel infantil" data-channel="Instagram" data-status="Negociación" data-value="$190">
                                <td><span class="contact-cell"><i>VS</i><strong>Valentina S.</strong></span></td><td>Pastel infantil</td><td><span class="channel-tag">Instagram</span></td><td><span class="status-tag status-negotiation">Negociación</span></td><td><strong>$190</strong></td><td>30 jul</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="table-note">Los campos y estados son ilustrativos y deberán validarse antes del alfa funcional.</p>
                </article>

                <aside class="panel acquisition-panel">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Origen</span><h3>¿Cómo llegan?</h3></div>
                    </div>
                    <div class="donut-wrap">
                        <div class="donut"><span><strong>18</strong><small>prospectos</small></span></div>
                    </div>
                    <div class="channel-breakdown">
                        <div><span><i class="channel-instagram"></i>Instagram</span><strong>44%</strong></div>
                        <div><span><i class="channel-referral"></i>Recomendaciones</span><strong>33%</strong></div>
                        <div><span><i class="channel-local"></i>Búsqueda local</span><strong>23%</strong></div>
                    </div>
                    <button class="button button-secondary button-block" type="button" data-view-target="business">Ver diagnóstico del negocio</button>
                </aside>
            </div>

            <div class="panel-actions right">
                <button class="button button-primary" type="button" data-view-target="summary">Ir al resumen general →</button>
            </div>
        </section>

        <section class="app-view" data-view="summary">
            <div class="summary-hero">
                <div>
                    <span class="step-pill light">Paso 6 de 6</span>
                    <h2>Tu negocio, en una sola vista</h2>
                    <p>Dulce Barrio ya tiene contexto, un objetivo activo, prioridades claras, información financiera y oportunidades en seguimiento.</p>
                </div>
                <div class="summary-score">
                    <span>Estado general</span>
                    <strong>En marcha</strong>
                    <small>4 áreas con información</small>
                </div>
            </div>

            <div class="summary-metrics">
                <button type="button" data-view-target="business"><span>Perfil del negocio</span><strong>Completo</strong><small>5 respuestas clave</small></button>
                <button type="button" data-view-target="objectives"><span>Objetivo principal</span><strong id="summaryProgress">35%</strong><small>6 actividades</small></button>
                <button type="button" data-view-target="finances"><span>EBITDA provisional</span><strong id="summaryMonthly">$2.470</strong><small>Según fórmula del cliente</small></button>
                <button type="button" data-view-target="crm"><span>Oportunidades</span><strong id="summaryOpportunities">6</strong><small>$2.450 estimados</small></button>
            </div>

            <div class="content-grid summary-grid">
                <article class="panel next-actions">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Foco inmediato</span><h3>Próximas acciones</h3></div>
                        <button class="button button-ghost" type="button" data-view-target="priorities">Ver matriz →</button>
                    </div>
                    <ol>
                        <li><span class="action-index">01</span><span><strong>Confirmar condiciones de entrega</strong><small>Objetivo de mejora · Hoy</small></span><em class="priority-high">Alta</em></li>
                        <li><span class="action-index">02</span><span><strong>Dar seguimiento a Carlos R.</strong><small>Oportunidad comercial · Hoy</small></span><em class="priority-medium">Media</em></li>
                        <li><span class="action-index">03</span><span><strong>Registrar reclamos de la semana</strong><small>Medición · 31 jul</small></span><em class="priority-medium">Media</em></li>
                        <li><span class="action-index">04</span><span><strong>Registrar cierre del día</strong><small>Finanzas · 18:00</small></span><em class="priority-routine">Rutina</em></li>
                    </ol>
                </article>

                <article class="panel health-card">
                    <div class="panel-heading">
                        <div><span class="section-kicker">Lectura rápida</span><h3>Señales del negocio</h3></div>
                    </div>
                    <div class="health-list">
                        <div><span class="health-icon good">✓</span><span><strong>Finanzas registradas</strong><small>Resultado positivo en el período demostrado</small></span></div>
                        <div><span class="health-icon focus">!</span><span><strong>Reclamos bajo seguimiento</strong><small>Objetivo y acciones definidos</small></span></div>
                        <div><span class="health-icon good">✓</span><span><strong>Canal principal identificado</strong><small>Instagram aporta 44 % de los prospectos</small></span></div>
                        <div><span class="health-icon neutral">◎</span><span><strong>Indicador por validar</strong><small>Punto de equilibrio</small></span></div>
                    </div>
                </article>
            </div>

            <article class="future-panel">
                <div>
                    <span class="section-kicker light-text">Evolución propuesta</span>
                    <h3>Más acompañamiento cuando el negocio lo necesite</h3>
                    <p>La versión básica organiza lo esencial. Las siguientes etapas pueden profundizar el análisis y reducir la carga manual.</p>
                </div>
                <div class="future-features">
                    <button type="button" data-modal-open="financePremiumModal"><span>◫</span><strong>Finanzas detalladas</strong><small>Costos, proveedores y categorías</small></button>
                    <button type="button" data-modal-open="financePremiumModal"><span>◉</span><strong>Captura por WhatsApp</strong><small>Cierre diario conversacional</small></button>
                    <button type="button" data-modal-open="premiumModal"><span>✦</span><strong>Asistente de IA</strong><small>Seguimiento y recomendaciones</small></button>
                    <button type="button" data-modal-open="premiumModal"><span>↗</span><strong>Reportes avanzados</strong><small>Alertas, patrones y desempeño</small></button>
                </div>
            </article>

            <div class="closing-actions">
                <button class="button button-secondary" type="button" data-view-target="business">← Ver recorrido nuevamente</button>
                <button class="button button-primary" type="button" data-modal-open="validationModal">Preguntas para validar</button>
            </div>
        </section>
    </main>
</div>

<div class="tour-scrim" id="tourScrim" aria-hidden="true" hidden>
    <span class="tour-spotlight" id="tourSpotlight"></span>
</div>
<div class="tour-chapter" id="tourChapter" role="status" aria-live="polite" hidden>
    <div class="tour-chapter-title">
        <span>Etapa del recorrido</span>
        <strong id="tourChapterTitle">Mi negocio</strong>
    </div>
</div>
<article class="tour-card" id="tourCard" role="status" aria-live="polite" hidden>
    <header class="tour-card-meta">
        <span class="tour-kicker">Recorrido del negocio</span>
        <strong id="tourFocusLabel">Sector 1 de 4</strong>
    </header>
    <h2 id="tourTitle">Conocer el negocio</h2>
    <p class="tour-screen-description" id="tourDescription">Primero entendemos qué hace el negocio, a quién atiende, qué ofrece y qué quiere conseguir.</p>
    <p class="tour-focus-description" id="tourFocusDescription">La presentación general de esta etapa.</p>
</article>

<section class="tour-panel" id="tourPanel" role="region" aria-label="Controles del recorrido" hidden>
    <div class="tour-control-status">
        <strong id="tourStepLabel">Paso 1 de 6</strong>
        <small id="tourPlaybackHint">6 segundos de lectura por sector</small>
    </div>
    <div class="tour-progress" id="tourProgress" aria-hidden="true"></div>
    <footer class="tour-actions">
        <button class="button button-ghost" id="previousTourStep" type="button">← Anterior</button>
        <button class="tour-play-button" id="toggleTourPlayback" type="button" aria-pressed="false">
            <span aria-hidden="true">▶</span>
            <span>Reproducir automáticamente</span>
        </button>
        <button class="button button-primary" id="nextTourStep" type="button">Siguiente →</button>
        <button class="tour-close" id="closeTour" type="button" aria-label="Cerrar recorrido">×</button>
    </footer>
</section>

<div class="modal-backdrop" id="aboutModal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="aboutTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon neutral">◎</span>
        <h2 id="aboutTitle">Qué estamos validando</h2>
        <p>Este prototipo permite revisar el recorrido, el lenguaje y la correspondencia con la metodología del cliente antes de construir la aplicación funcional.</p>
        <ul class="modal-list">
            <li>Orden y utilidad de las pantallas.</li>
            <li>Información mínima para un pequeño negocio.</li>
            <li>Relación entre diagnóstico, acciones, finanzas y ventas.</li>
            <li>Prioridades para la siguiente etapa.</li>
        </ul>
        <button class="button button-primary button-block" type="button" data-modal-close>Entendido</button>
    </section>
</div>

<div class="modal-backdrop" id="premiumModal" hidden>
    <section class="modal-card premium-modal" role="dialog" aria-modal="true" aria-labelledby="premiumTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon premium">✦</span>
        <span class="premium-label">Visión futura</span>
        <h2 id="premiumTitle">Acompañamiento inteligente</h2>
        <p>En una etapa posterior, la plataforma podrá utilizar el contexto del negocio para realizar seguimiento, detectar retrasos y proponer oportunidades de mejora.</p>
        <div class="modal-feature-grid">
            <span><strong>Recordatorios</strong><small>Objetivos y actividades</small></span>
            <span><strong>Seguimiento</strong><small>Preguntas periódicas</small></span>
            <span><strong>Alertas</strong><small>Riesgos y desviaciones</small></span>
            <span><strong>Recomendaciones</strong><small>Con validación humana</small></span>
        </div>
        <p class="modal-disclaimer">No se está ejecutando inteligencia artificial en esta demostración.</p>
        <button class="button button-primary button-block" type="button" data-modal-close>Volver a la demo</button>
    </section>
</div>

<div class="modal-backdrop" id="objectiveModal" hidden>
    <section class="modal-card modal-form-card" role="dialog" aria-modal="true" aria-labelledby="objectiveTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon warm">◎</span>
        <h2 id="objectiveTitle">Nuevo objetivo</h2>
        <p>Simula cómo el emprendedor convertiría una necesidad en un objetivo concreto.</p>
        <form data-demo-form data-success="Objetivo agregado a la demostración">
            <label><span>Nombre del objetivo</span><input type="text" value="Aumentar los pedidos corporativos"></label>
            <div class="form-row">
                <label><span>Categoría</span><select><option>Comercial</option><option>Financiero</option><option>Mejora</option></select></label>
                <label><span>Fecha objetivo</span><input type="date" value="2026-09-30"></label>
            </div>
            <label><span>Resultado esperado</span><textarea>Conseguir cinco clientes corporativos recurrentes.</textarea></label>
            <button class="button button-primary button-block" type="submit">Guardar objetivo simulado</button>
        </form>
    </section>
</div>

<div class="modal-backdrop" id="priorityTaskModal" hidden>
    <section class="modal-card modal-form-card" role="dialog" aria-modal="true" aria-labelledby="priorityTaskTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon warm">↗</span>
        <span class="section-kicker">Actividad</span>
        <h2 id="priorityTaskTitle">Detalle de la tarea</h2>
        <p>La clasificación puede corregirse visualmente durante la demostración.</p>
        <form id="priorityTaskForm">
            <label><span>Acción sugerida</span>
                <select id="quadrantSelect">
                    <option value="do">Hacer ahora</option>
                    <option value="plan">Planificar</option>
                    <option value="delegate">Delegar</option>
                    <option value="remove">Eliminar</option>
                </select>
            </label>
            <div class="form-row">
                <label><span>Impacto</span><select><option>Alto</option><option>Medio</option><option>Bajo</option></select></label>
                <label><span>Urgencia</span><select><option>Alta</option><option>Media</option><option>Baja</option></select></label>
            </div>
            <button class="button button-primary button-block" type="submit">Aplicar cambio visual</button>
        </form>
    </section>
</div>

<div class="modal-backdrop" id="priorityHelpModal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="priorityHelpTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon neutral">◫</span>
        <h2 id="priorityHelpTitle">Matriz de Eisenhower</h2>
        <p>Organiza las actividades combinando impacto y urgencia. En el producto funcional todavía deberá validarse si la clasificación será automática, editable o ambas.</p>
        <button class="button button-primary button-block" type="button" data-modal-close>Entendido</button>
    </section>
</div>

<div class="modal-backdrop" id="financialInfoModal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="financialInfoTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon neutral">◎</span>
        <h2 id="financialInfoTitle">Indicadores financieros</h2>
        <p>La utilidad bruta se calcula como ventas menos costo de ventas. El EBITDA provisional resta además los gastos operativos o fijos y los gastos administrativos, conforme a la fórmula compartida por el cliente.</p>
        <p>El punto de equilibrio continúa pendiente de validación y no se calcula en esta versión.</p>
        <p class="modal-disclaimer">El EBITDA mostrado es una referencia operativa de la metodología del cliente y no sustituye un estado financiero o criterio contable formal.</p>
        <button class="button button-primary button-block" type="button" data-modal-close>Volver a finanzas</button>
    </section>
</div>

<div class="modal-backdrop" id="financePremiumModal" hidden>
    <section class="modal-card premium-modal" role="dialog" aria-modal="true" aria-labelledby="financePremiumTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon premium">✦</span>
        <span class="premium-label">Premium · Visión futura</span>
        <h2 id="financePremiumTitle">Mayor detalle con menos carga manual</h2>
        <div class="modal-feature-grid">
            <span><strong>Movimientos</strong><small>Ingresos y egresos detallados</small></span>
            <span><strong>Categorías</strong><small>Conceptos y recurrencia</small></span>
            <span><strong>Proveedores</strong><small>Compras, insumos y costos</small></span>
            <span><strong>WhatsApp</strong><small>Captura diaria futura</small></span>
        </div>
        <p class="modal-disclaimer">Estas capacidades se muestran como visión. No están implementadas ni tienen precio definido.</p>
        <button class="button button-primary button-block" type="button" data-modal-close>Volver a la demo</button>
    </section>
</div>

<div class="modal-backdrop" id="opportunityModal" hidden>
    <section class="modal-card modal-form-card" role="dialog" aria-modal="true" aria-labelledby="opportunityTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon warm">+</span>
        <h2 id="opportunityTitle">Nueva oportunidad</h2>
        <form id="opportunityForm">
            <label><span>Contacto</span><input id="newContactName" type="text" value="Lucía G."></label>
            <label><span>Necesidad</span><input id="newContactNeed" type="text" value="Torta para aniversario"></label>
            <div class="form-row">
                <label><span>Canal</span><select id="newContactChannel"><option>Instagram</option><option>Recomendación</option><option>Búsqueda local</option></select></label>
                <label><span>Valor estimado</span><input id="newContactValue" type="number" value="320"></label>
            </div>
            <button class="button button-primary button-block" type="submit">Agregar a la demostración</button>
        </form>
    </section>
</div>

<div class="modal-backdrop" id="opportunityDetailModal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="opportunityDetailTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon warm">◎</span>
        <span class="section-kicker">Oportunidad comercial</span>
        <h2 id="opportunityDetailTitle">Detalle</h2>
        <dl class="modal-detail-list">
            <div><dt>Necesidad</dt><dd id="detailNeed">—</dd></div>
            <div><dt>Canal</dt><dd id="detailChannel">—</dd></div>
            <div><dt>Estado</dt><dd id="detailStatus">—</dd></div>
            <div><dt>Valor estimado</dt><dd id="detailValue">—</dd></div>
        </dl>
        <button class="button button-primary button-block" id="advanceOpportunity" type="button">Avanzar estado visualmente</button>
    </section>
</div>

<div class="modal-backdrop" id="validationModal" hidden>
    <section class="modal-card validation-modal" role="dialog" aria-modal="true" aria-labelledby="validationTitle">
        <button class="modal-close" type="button" data-modal-close aria-label="Cerrar">×</button>
        <span class="modal-icon neutral">?</span>
        <h2 id="validationTitle">Preguntas para cerrar la presentación</h2>
        <ol class="validation-questions">
            <li>¿Este recorrido representa la forma en que hoy acompaña a sus clientes?</li>
            <li>¿Las cinco preguntas son suficientes para iniciar el diagnóstico?</li>
            <li>¿Qué pantalla debería recibir primero al emprendedor?</li>
            <li>¿Qué información financiera mínima necesita ver?</li>
            <li>¿Qué seguimiento comercial resulta indispensable?</li>
            <li>¿Qué capacidad premium aportaría más valor después?</li>
        </ol>
        <button class="button button-primary button-block" type="button" data-modal-close>Finalizar recorrido</button>
    </section>
</div>

<div class="toast" id="demoToast" role="status" aria-live="polite">
    <span>✓</span>
    <p id="toastMessage">Cambio aplicado en la demostración</p>
</div>

<script src="<?= base_url('assets/js/erp-lite-demo.js?v=' . filemtime(FCPATH . 'assets/js/erp-lite-demo.js')) ?>"></script>
</body>
</html>
