# PyMe ERP-LITE — Prototipo interactivo y fundaciones del alfa

Prototipo interactivo de alta fidelidad utilizado como demo para validar el recorrido inicial de una plataforma de gestión sencilla para pequeños negocios.

El proyecto utiliza CodeIgniter 4 y presenta un caso ficticio llamado `Dulce Barrio`. `/demolite` es la única línea activa de producto y publica el recorrido persistente `Onboarding inicial → Dashboard → Objetivos → Actividades → Prioridades → Finanzas → Clientes y ventas`, construido sobre CodeIgniter Shield, aislamiento por negocio y auditoría. Después del onboarding, la información inicial queda disponible como `Perfil del negocio` desde accesos secundarios. `/demo` conserva la presentación simulada que ya fue validada por el cliente, pero queda congelada como referencia histórica: no recibirá nuevas funcionalidades, correcciones visuales ni sincronización con el MVP salvo una autorización expresa. Finanzas registra cierres diarios agregados y calcula utilidad bruta y EBITDA mediante la fórmula compartida por el cliente. El CRM básico registra contactos, oportunidades y próximos seguimientos. No incluye API, inteligencia artificial ni integración técnica con WhatsApp.

## Requisitos

- PHP 8.2 o posterior.
- Dependencias de Composer instaladas.

En el entorno actual debe utilizarse:

```bash
/opt/lampp/bin/php
```

## Iniciar la demostración

### URL pública

La versión desplegada está disponible en:

```text
Presentación: http://rewo.lat:8080/proyectos/proy-alx/code/public/demo
Alfa funcional: http://rewo.lat:8080/proyectos/proy-alx/code/public/demolite
```

Credenciales públicas de validación:

```text
Recorrido con datos — dulce@demo.com / demo12345
Recorrido desde cero — piloto@demo.com / piloto12345
```

La segunda cuenta posee un negocio estructural asociado, pero no tiene perfil,
objetivos ni movimientos. Al ingresar comienza por la configuración inicial de
`Mi negocio` y luego accede a los estados vacíos normales del producto.

La publicación utiliza el directorio `public/` de CodeIgniter servido por Apache. La
URL base se define dentro del proyecto y los recursos se generan con `base_url()`,
por lo que no requiere cambios en la configuración global del servidor.

### Entorno local

Desde `code/`:

```bash
/opt/lampp/bin/php spark serve
```

Abrir:

```text
http://localhost:8080/
```

También está disponible en:

```text
http://localhost:8080/demo
```

Si el puerto `8080` está ocupado:

```bash
/opt/lampp/bin/php spark serve --host 127.0.0.1 --port 8098
```

La interfaz del prototipo todavía no utiliza persistencia ni necesita caché de archivos. El entorno
publicado mantiene el modo de producción y utiliza el manejador de caché `dummy`
para evitar escrituras innecesarias fuera de los recursos propios de la demo.

La publicación mantiene dos recorridos independientes:

```text
/demo      → presentación conceptual anónima
/demolite  → entrada a la alfa funcional autenticada
```

La alfa utiliza sesiones persistentes en la tabla `ci_sessions`. Esta decisión
evita depender de permisos del sistema de archivos y no modifica Apache ni
`.env`. HTTPS y las cookies `Secure` quedan reservados para la etapa formal.

## Persistencia de la Fase 1

La base configurada es `proy_alx`. Las primeras migraciones crean:

```text
businesses
├── business_profiles
└── objectives
        └── activities
```

El seeder `DemoAlphaSeeder` contiene un recorrido demostrativo transaccional,
repetible y reparable de `Dulce Barrio`: perfil completo, cuatro objetivos,
dieciséis actividades y seis cierres financieros diarios coherentes. No crea
usuarios, credenciales, membresías ni auditoría histórica; tampoco se ejecuta
automáticamente. El conjunto fue cargado explícitamente en `proy_alx` para la
validación local controlada.

## Identidad, roles y administración

- CodeIgniter Shield `1.4.x` y CodeIgniter Settings están instalados.
- La autenticación admite únicamente sesiones y correo electrónico.
- Registro público, enlaces mágicos, recuperación automática y “recordarme” están desactivados.
- Toda cuenta autenticada puede cambiar su propia contraseña desde `Mi cuenta → Seguridad`; la operación exige la credencial actual, validación de Shield, CSRF y limitación de intentos.
- `alpha` habilita el producto mediante el permiso global `app.access`.
- `platform_admin` administra cuentas y negocios mediante permisos globales literales, sin entrar automáticamente a los datos internos de una PyME.
- `/demolite` es la entrada estable a la alfa funcional publicada.
- Las rutas `login`, `logout` y `/app` se registran también en producción por una decisión explícita y reversible.
- La pantalla de acceso es propia, está en español y no ofrece registro ni recuperación pública.
- Los módulos funcionales incluyen cierre de sesión mediante `POST` protegido por CSRF.
- Producción no expone `/register`, recuperación ni enlaces mágicos.
- Existe una cuenta demostrativa fija asociada con `Dulce Barrio` y una cuenta piloto estable asociada con un negocio estructural vacío.
- Ambas asociaciones utilizan el rol contextual `owner`; `coach` y `collaborator` están reconocidos pero permanecen sin permisos operativos hasta confirmar su alcance.
- La cuenta piloto inicia por el onboarding; no queda sin negocio ni recibe el rechazo reservado para asociaciones inválidas.
- No existe vencimiento o rotación automática de estas contraseñas de validación.
- El cambio personal no crea archivos ni tablas adicionales: Shield actualiza la identidad de contraseña existente y la sesión actual se regenera.
- `/entry` dirige una cuenta del producto a `/app` y un administrador a `/admin`.
- `/admin` permite crear cuentas propietarias con negocio, crear administradores, activar o desactivar cuentas y accesos y consultar auditoría administrativa.
- La primera cuenta personal de administración debe crearse fuera del código con el comando interactivo correspondiente.

Comandos disponibles:

```bash
/opt/lampp/bin/php spark erp:user:create-admin
/opt/lampp/bin/php spark erp:user:create-owner
```

Las contraseñas se solicitan interactivamente y no se almacenan en código ni documentación.

## Autorización por negocio y auditoría

- `business_users` vincula las cuentas de Shield con los negocios autorizados.
- `business_users.role_code` separa `owner`, `coach` y `collaborator` de los permisos globales de Shield.
- El esquema permite una evolución futura a multiempresa, pero durante el alfa el contexto exige exactamente una asociación activa; una cuenta sin negocio o con más de uno se rechaza de forma segura.
- El provisionamiento piloto crea usuario, negocio y asociación dentro de una misma transacción para que una cuenta válida nunca nazca en ese estado incompleto.
- `AlphaBusinessContext` obtiene el actor desde la sesión y nunca acepta un `business_id` del navegador.
- `AuthorizedBusinessReader` limita negocio, perfil, objetivos y actividades al negocio resuelto.
- `audit_events` conserva eventos mínimos atribuidos a negocio, usuario, entidad, acción y fecha técnica en UTC.
- `platform_audit_events` registra por separado altas y cambios de estado administrativos.
- `AuditEventRecorder` valida la pertenencia de la entidad antes de registrar el evento.
- Las tablas están migradas; la cuenta demostrativa, la cuenta piloto, sus asociaciones y la auditoría de validación están cargadas.

## Dashboard general

- `GET /app` es la entrada habitual después del ingreso.
- Resume perfil, objetivos, actividades, prioridades y finanzas desde los servicios existentes.
- Es una vista de solo lectura: no tiene formularios, tablas propias ni reglas duplicadas.
- Presenta inmediatamente después de las KPI una evolución financiera protagonista de hasta siete cierres confirmados, reutilizando la serie de `FinanceService` sin duplicar cálculos.
- Debajo del bloque financiero, los paneles operativos utilizan dos columnas independientes para evitar estiramientos y espacios vacíos; el gráfico se renderiza con PHP/CSS y no incorpora Vue porque no tiene estado interactivo.
- Exige completar las cuatro respuestas mínimas de `Mi negocio`; las cinco preguntas ampliadas siguen siendo opcionales.
- Si el perfil mínimo está incompleto, la guarda central abre la configuración inicial antes de cualquier módulo operativo.
- El Dashboard general todavía no muestra métricas CRM, punto de equilibrio, ROI ni recuperación de inversión.

## Onboarding inicial y `Perfil del negocio`

- `GET /app/mi-negocio` muestra el perfil autorizado.
- `POST /app/mi-negocio` actualiza identidad, contexto mínimo y diagnóstico.
- Las rutas utilizan el filtro de sesión en todos los entornos.
- El navegador no envía ni selecciona un `business_id`.
- `BusinessService` valida moneda, zona horaria IANA, longitudes y las cuatro respuestas mínimas.
- `BusinessOnboardingFilter` protege Dashboard, Objetivos, Actividades, Prioridades y Finanzas mientras ese mínimo esté incompleto.
- Negocio, perfil y dos eventos de auditoría se guardan dentro de una transacción.
- El diagnóstico ampliado es opcional y puede completarse gradualmente.
- La primera configuración continúa al Dashboard; una edición posterior permanece en el perfil con confirmación.
- Después del onboarding, el menú principal omite `Mi negocio`. El perfil se abre desde la identidad del negocio o desde la tarjeta del Dashboard.
- La salida se escapa y los errores se presentan sin detalles técnicos.
- Los datos y comportamientos permanecen separados del prototipo público, mientras la identidad visual se comparte mediante la estructura del alfa.

## Módulos funcionales `Objetivos` y `Prioridades`

- `GET /app/objetivos` consulta objetivos y actividades del negocio autorizado.
- Los formularios protegidos permiten crear, editar y archivar lógicamente objetivos y actividades.
- Toda actividad pertenece obligatoriamente a un objetivo.
- Los catálogos provisionales de categoría y estado están centralizados en `WorkflowCatalog`.
- `ObjectiveService` y `ActivityService` validan, aíslan, guardan mediante transacciones y registran auditoría.
- `GET /app/prioridades` muestra una Matriz de Eisenhower derivada de `is_urgent` e `is_important`; no duplica la prioridad ni admite escrituras.
- Archivar un objetivo lo oculta junto con sus actividades, pero conserva el historial subyacente.
- Las rutas están disponibles dentro de la alfa autenticada y conservan el aislamiento por negocio.

## CRM básico funcional

- CRM-1 incorpora las tablas `contacts` y `opportunities`.
- Prospectos y clientes son etapas de un mismo contacto.
- Una clave foránea compuesta impide relacionar una oportunidad con un contacto de otro negocio.
- `ContactModel` y `OpportunityModel` aplican validaciones, borrado lógico y consultas acotadas por negocio.
- Los estados, canales y etapas provisionales viven en `CrmCatalog`.
- CRM-2 incorpora `ContactService`, `OpportunityService` y `CrmOverviewService`.
- Los servicios permiten crear, editar, convertir y archivar con transacciones, aislamiento y auditoría.
- Los valores estimados se normalizan sin utilizar coma flotante para los indicadores.
- Los controladores de lectura y escritura delegan a servicios y están cubiertos por pruebas de producción.
- `GET /app/clientes` y siete rutas mutantes están publicadas detrás de sesión, permiso, onboarding y CSRF.
- La interfaz ofrece métricas, filtros, contactos, oportunidades, conversión y archivado con estados vacíos reales.
- Vue se limita a las islas de filtros y editores; existe alternativa nativa y no se incorporó Tabulator porque no se justifica todavía.
- WhatsApp aparece como canal de adquisición manual; no existe mensajería, webhook, proveedor ni automatización.
- `Dulce Barrio` conserva dos contactos cargados manualmente para validación; no existen oportunidades ni un seeder CRM ejecutado.
- CRM-4 requiere una autorización independiente si se desea cargar un conjunto ficticio para `Dulce Barrio`.

## Seguridad de la demo

- CSRF está activo globalmente para solicitudes mutantes.
- La suite comprueba el rechazo sin token y la aceptación con token.
- `/` y `/demo` permanecen públicos y de solo lectura.
- `/demolite` conduce a la alfa; los módulos sólo permiten escritura mediante sesión, CSRF y negocio autorizado.
- La demo no puede cargar el seeder automáticamente.
- La interfaz del negocio no muestra rótulos técnicos o experimentales; las limitaciones del MVP se conservan en la documentación.
- La publicación temporal opera por HTTP; HTTPS, cookies `Secure`, recuperación y respaldos formales quedan para la etapa de producto.
- Mientras continúe HTTP, el cambio de contraseña queda limitado operativamente a cuentas controladas de validación; las credenciales personales requieren la etapa HTTPS.
- La asociación usuario-negocio, el aislamiento de lectura y la atribución mínima ya están implementados y probados.
- La excepción HTTP y su retorno están documentados en `info/arquitectura/decision-publicacion-alfa-http-demolite.md`.

## Identidad visual validada y evolución del alfa

La identidad aprobada originalmente en la demo ya fue incorporada al alfa. Desde
este punto, `/demolite` es la referencia visual y funcional vigente. Sus
pantallas funcionales utilizan una estructura compartida para evitar que cada
pantalla derive hacia un diseño diferente:

- menú lateral y cabecera consistentes;
- paleta verde petróleo, acento coral y superficies claras;
- tipografía `Inter`, escalas y densidad equivalentes a la demo;
- tarjetas, campos, botones, métricas y estados con un mismo lenguaje;
- navegación activa, contexto de `Dulce Barrio` y advertencia del entorno;
- adaptación a escritorio, tableta, móvil y preferencia de movimiento reducido.
- contenido centrado con el mismo límite máximo de `1500px` de la demo;
- menú móvil lateral, campana informativa y módulos futuros identificados sin rutas ficticias;
- formularios de negocio y objetivos disponibles bajo demanda, sin dominar la primera vista;
- navegación lateral mediante nombres funcionales, sin prefijos numéricos;
- creación de objetivos concentrada en la acción principal `+ Nuevo objetivo`, sin un segundo control redundante.

Esta convergencia es exclusivamente de presentación. Los formularios siguen
enviando operaciones reales al servidor; la autenticación, CSRF, validación,
aislamiento por negocio, transacciones y auditoría no fueron reemplazados por
JavaScript ni por la simulación del prototipo.

La ruta `/demo` y sus archivos permanecen disponibles únicamente como evidencia
del prototipo validado. No forman parte del backlog activo ni deben modificarse
para acompañar cambios posteriores de `/demolite`.

Objetivos, Perfil y Finanzas cargan una base compartida y versionada de Vue 3 y
Bootstrap 5. Los estilos propios del alfa se aplican después y continúan siendo
la autoridad visual. Vue administra únicamente el estado interactivo necesario;
los formularios, cálculos definitivos, CSRF y persistencia siguen a cargo de
CodeIgniter.

Los montajes siguen un patrón de islas: Objetivos controla únicamente su modal,
Perfil únicamente el editor y Finanzas crea una instancia independiente por
formulario. Menú, cabecera, métricas, gráficos y contenido de lectura permanecen
fuera de Vue. Perfil y Finanzas conservan una alternativa nativa si el CDN no
carga o si el montaje falla; las pruebas impiden volver a montar sobre
`.business-shell` y rechazan directivas `v-text` con contenido hijo conflictivo.

La composición funcional utiliza datos reales:

- `Dashboard` reúne el perfil, el foco actual, próximas acciones, prioridades abiertas y la síntesis financiera sin duplicar datos;
- `Perfil del negocio` calcula el mínimo de onboarding y la completitud de los nueve campos de perfil;
- `Objetivos` resume objetivos activos, actividades, trabajo en curso, vencimientos y progreso;
- `Prioridades` conserva la clasificación derivada y recupera la semántica visual de los cuadrantes;
- `Finanzas` genera métricas y un gráfico de hasta siete cierres confirmados, excluyendo borradores; separa ventas, costo de ventas, gastos operativos y gastos administrativos para derivar utilidad bruta y EBITDA.

## Recorrido

```text
Mi negocio
    ↓
Objetivos
    ↓
Matriz de prioridades
    ↓
Finanzas
    ↓
Clientes y ventas
    ↓
Resumen general
```

Los cambios visuales se reinician al recargar la página.

## Recorrido guiado

El botón `Ver recorrido` inicia automáticamente una explicación contextual de las
seis etapas. Cada sector permanece seis segundos completos para lectura y reserva
otros `450ms` para la transición. Las pantallas recorren ordenadamente entre cuatro
y cinco sectores propios antes de avanzar a la siguiente. El indicador `Sector X
de Y` muestra la posición dentro de la pantalla actual. El área activa permanece
nítida mientras el resto de la interfaz recibe un oscurecimiento uniforme.

Antes de comenzar cada pantalla aparece durante `2,2s` un título central pulsante:
`Mi negocio`, `Objetivos`, `Prioridades`, `Finanzas`, `Clientes y ventas` o
`Resumen`. Durante el anuncio y los focos, el menú lateral permanece por encima del
oscurecimiento y muestra la pantalla activa.

La explicación aparece en una tarjeta pequeña situada alrededor del foco. La
tarjeta busca espacio a la derecha, izquierda, debajo o encima del elemento para no
cubrirlo, y se desvanece antes de trasladarse al siguiente sector. Los controles
generales permanecen en una barra compacta inferior.

Controles disponibles:

- `Anterior` y `Siguiente`.
- `Reproducir automáticamente` y `Pausar recorrido`.
- Flechas izquierda y derecha del teclado.
- `Escape` para salir.
- Botón de cierre para regresar a la demo.

El usuario puede pausar la reproducción y continuar manualmente. El recorrido
conserva el foco del teclado, recalcula la selección al desplazar o redimensionar
la ventana y respeta la preferencia del sistema para reducir movimiento. La
secuencia utiliza un único temporizador para impedir saltos entre páginas o focos
fuera de contexto. El marco tarda aproximadamente un segundo en completar cada
desplazamiento y la tarjeta utiliza una transición de `450ms`. El recorrido
completo dura aproximadamente tres minutos.

Al terminar el último sector, la reproducción se cierra definitivamente y abre el
modal `Preguntas para cerrar la presentación`. El recorrido no vuelve al inicio ni
continúa en loop; solo puede reiniciarse mediante una nueva acción sobre `Ver
recorrido`.

El oscurecimiento se genera desde la misma capa del marco. No utiliza cortinas
desenfocadas independientes, evitando costuras o barridos durante el movimiento.

## Archivos principales

- `app/Views/demo/erp_lite.php`: estructura de las seis pantallas.
- `app/Database/Migrations/`: estructura persistente de las Fases 1 y 2.
- `app/Database/Migrations/2026-08-06-150000_CreateCiSessions.php`: sesiones persistentes del alfa.
- `app/Database/Migrations/2026-08-06-180500_CreateContacts.php`: estructura aplicada de contactos CRM.
- `app/Database/Migrations/2026-08-06-180501_CreateOpportunities.php`: oportunidades con pertenencia compuesta al negocio y contacto.
- `app/Models/`: modelos y validaciones mínimas.
- `app/Domain/CrmCatalog.php`: etapas, canales y estados provisionales del CRM.
- `app/Database/Seeds/DemoAlphaSeeder.php`: recorrido demostrativo completo y opcional, sin credenciales.
- `app/Database/Seeds/DemoAlphaAccountSeeder.php`: cuenta pública de validación, repetible y reparable.
- `app/Database/Seeds/DemoAlphaMembershipSeeder.php`: asociación de la cuenta con `Dulce Barrio`.
- `app/Database/Seeds/PilotAlphaAccountSeeder.php`: cuenta piloto estable con negocio inicial vacío.
- `app/Services/PilotAccountProvisioner.php`: alta atómica de usuario, negocio y asociación para el recorrido piloto.
- `app/Services/AlphaBusinessContext.php`: resolución del actor y negocio autorizados desde la sesión.
- `app/Services/AuthorizedBusinessReader.php`: lecturas aisladas por negocio.
- `app/Services/AuditEventRecorder.php`: trazabilidad mínima validada por entidad.
- `app/Services/BusinessService.php`: lectura y actualización transaccional de `Mi negocio`.
- `app/Services/ObjectiveService.php`: objetivos, lectura compuesta y matriz derivada.
- `app/Services/ActivityService.php`: actividades obligatoriamente vinculadas con un objetivo.
- `app/Services/EisenhowerClassifier.php`: derivación de la prioridad.
- `app/Services/FinanceService.php`: cierres diarios agregados, cálculos exactos en centavos y totales mensuales.
- `app/Services/DashboardService.php`: composición de lectura sobre negocio, flujo de trabajo y finanzas.
- `app/Controllers/DashboardController.php`: entrada `/app`, control de onboarding y estados seguros.
- `app/Controllers/BusinessController.php`: adaptación HTTP del primer caso de uso.
- `app/Controllers/ObjectiveController.php`, `ActivityController.php` y `PriorityController.php`: adaptación HTTP del recorrido de trabajo.
- `app/Controllers/FinanceController.php`: adaptación HTTP del módulo financiero básico.
- `app/Controllers/AlphaController.php`: entrada estable `/demolite`.
- `app/Filters/BusinessOnboardingFilter.php`: guarda central del perfil mínimo para las rutas operativas.
- `app/Views/business/`: formulario funcional y estados de error controlados.
- `app/Views/auth/login.php`: acceso cerrado en español, sin registro público.
- `app/Views/objectives/` y `app/Views/priorities/`: vistas funcionales separadas.
- `app/Views/finances/`: registro y edición de cierres diarios y resumen del período.
- `app/Views/dashboard/`: vista general de solo lectura con información real.
- `app/Views/layouts/alpha_sidebar.php`: navegación y contexto compartidos del alfa.
- `app/Views/layouts/alpha_topbar.php`: cabecera compartida de los módulos funcionales.
- `app/Views/layouts/alpha_frontend_head.php` y `alpha_frontend_scripts.php`: carga compartida, fijada e íntegra de Bootstrap 5 y Vue 3 para módulos con estado.
- `app/Views/layouts/alpha_project_head.php`: estilos propios versionados y tema global, con orden uniforme respecto de Bootstrap.
- `app/Config/Filters.php`: filtros globales, incluido CSRF.
- `app/Config/Auth.php`: autenticación cerrada por sesión para el alfa.
- `app/Config/AuthGroups.php`: único nivel técnico `alpha`.
- `app/Config/AlphaAccess.php`: compuerta explícita y reversible de publicación del alfa.
- `app/Config/Session.php`: sesiones en base de datos para producción y archivos para desarrollo, con cookie exclusiva `pyme_erp_lite_session`.
- `public/assets/css/erp-lite-demo.css`: diseño visual adaptable.
- `public/assets/js/erp-lite-demo.js`: navegación, interacciones simuladas y recorrido guiado.
- `public/assets/css/business/profile.css`: presentación aislada del módulo funcional.
- `public/assets/css/auth/login.css`: presentación adaptable de la pantalla de acceso.
- `public/assets/js/business/profile.js`: montaje Vue acotado para apertura del editor, conteos y estado de envío, con fallback ante ausencia o fallo de Vue.
- `public/assets/css/workflow/index.css` y `public/assets/js/workflow/index.js`: presentación y modal Vue accesible para crear objetivos.
- `public/assets/css/finances/index.css` y `public/assets/js/finances/index.js`: presentación y una isla Vue por formulario para previsualizar el EBITDA diario; la regla real y la validación permanecen en `FinanceService` y existe fallback nativo.
- `public/assets/css/dashboard/index.css`: composición adaptable del Dashboard sin lógica de negocio.
- `public/assets/css/alpha-shell.css`: capa visual canónica que aproxima los módulos funcionales a la demo aprobada.
- `public/assets/js/alpha-shell.js`: apertura accesible del menú lateral móvil, sin persistencia ni solicitudes propias.
- `public/assets/css/theme.css` y `public/assets/js/theme.js`: alternancia global Claro/Oscuro mediante un botón circular, resuelta antes del primer pintado y persistida localmente sin involucrar al backend.
- `tests/database/PhaseOneDatabaseTest.php`: migraciones, modelos y seeder.
- `tests/database/BusinessAccessIsolationTest.php`: membresía, aislamiento, rechazo seguro y auditoría.
- `tests/database/PilotAlphaAccountSeederTest.php`: alta piloto atómica, recorrido vacío e idempotencia.
- `tests/database/CrmFoundationDatabaseTest.php`: esquema CRM, validaciones, borrado lógico, aislamiento e integridad referencial.
- `tests/feature/CsrfProtectionTest.php`: rechazo y aceptación de solicitudes mutantes.
- `tests/feature/AuthenticationFoundationTest.php`: configuración, rutas, ingreso, cierre, CSRF y limitación de intentos.
- `tests/feature/BusinessModuleTest.php`: rutas, persistencia, validación, CSRF, aislamiento, auditoría y escape.
- `tests/feature/WorkflowModuleTest.php`: objetivos, actividades, estados, aislamiento, auditoría, borrado lógico y cuatro cuadrantes.
- `tests/feature/FinanceModuleTest.php`: fechas, montos, totales exactos, punto de equilibrio estimado, casos sin margen, borradores, aislamiento, auditoría, CSRF y escape.
- `tests/feature/DashboardModuleTest.php`: entrada protegida, onboarding mínimo, métricas integradas, aislamiento y solo lectura.
- `tests/unit/CrmCatalogTest.php`: partición y vocabulario de los catálogos provisionales.
- `tests/unit/ArchitectureRulesTest.php`: límites automáticos entre HTTP, servicios, persistencia y Vue.
- `tests/js/business-profile.test.js`: límites del JavaScript y estructura segura del formulario.
- `tests/js/auth-login.test.js`: acceso cerrado, CSRF, adaptación móvil y movimiento reducido.
- `tests/js/workflow.test.js`: límites del JavaScript, formularios seguros y prioridades de solo lectura.
- `tests/js/finances.test.js`: alcance agregado, CSRF y mejora progresiva sin persistencia cliente.
- `tests/js/dashboard.test.js`: estructura, escape, navegación y límites del Dashboard.
- `tests/js/erp-lite-demo.test.js`: comprobaciones estructurales del prototipo.
- `tests/js/alpha-shell.test.js`: adopción de la estructura compartida, rutas, salida segura y tokens visuales.
- `tests/js/theme.test.js`: precedencia de la elección manual, seguimiento del sistema, persistencia local y límites del controlador visual.

## Validación

```bash
/opt/lampp/bin/php -l app/Views/demo/erp_lite.php
node --check public/assets/js/erp-lite-demo.js
composer verify
```

Estado verificado: `117/117` pruebas PHP, `1003` aserciones y ocho suites JavaScript aprobadas.

## Límites

- La carga inicial de `Dulce Barrio` y su cuenta son demostrativas.
- `/demo` no lee ni escribe la base; `/demolite` sí persiste los cambios realizados por una sesión autorizada.
- La utilidad bruta se calcula como `ventas − costo de ventas`.
- El EBITDA se calcula como `utilidad bruta − gastos operativos o fijos − gastos administrativos`, conforme a la metodología compartida por el cliente. El alcance agregado de la fórmula continúa documentado internamente y no sustituye un estado financiero formal.
- El punto de equilibrio agregado se estima por período como `gastos operativos o fijos + gastos administrativos`, dividido por la proporción `utilidad bruta / ventas`. El margen porcentual se calcula internamente y queda disponible en `FinanceService`, pero no se presenta como KPI. Cuando no hay ventas o la contribución es nula o negativa, el indicador se muestra como no disponible. El cálculo no representa todavía un punto de equilibrio por producto o por unidades. ROI y recuperación de inversión fueron retirados de la dirección vigente.
- Los campos y estados del CRM deben validarse con el cliente.
- Las capacidades premium se muestran únicamente como visión futura.
