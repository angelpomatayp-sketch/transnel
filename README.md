# Logistica DYM

Sistema web de logistica e inventario para una sola empresa, orientado a operaciones mineras.

## Decision tecnica

- Backend: PHP 8.2+ y Laravel 12.
- Frontend: React 18 con JavaScript/JSX, Inertia.js v2, Vite y Tailwind CSS.
- Autenticacion: Laravel Breeze con React/Inertia.
- Autorizacion: Spatie Laravel Permission.
- Iconos: Lucide React.
- Graficos: Recharts.
- PDFs: Blade + DomPDF.
- Excel: PhpSpreadsheet.
- Base local: MySQL en XAMPP.
- Base produccion: PostgreSQL.
- Tabla de usuarios: `users`.
- Empresa: una sola empresa mediante `empresa_configuracion`.
- Valorizacion: promedio ponderado por producto y almacen.
- Stock negativo: bloqueado.

## Empresa de ejemplo

Estos datos son referenciales para desarrollo y deben reemplazarse antes de produccion.

- Razon social: Contratistas Asociados Pacifico S.R.L.
- RUC demo: 20601234567.
- Pais: Peru.
- Ciudad: Lima.
- Rubro: servicios logisticos y contratistas para mineria.

## Roles iniciales

- `administrador`: acceso total al sistema.
- `jefe_logistica`: aprobaciones, supervision logistica, anulaciones operativas, compras y reportes.
- `almacenero`: gestion operativa de productos, stock, movimientos, vales, recepciones, EPPs y prestamos en almacenes asignados.

## Estilo visual

La interfaz seguira un estilo administrativo similar a la referencia CAP/Pacifico:

- Sidebar azul corporativo.
- Opcion activa en verde.
- Topbar blanca con buscador, notificaciones y usuario.
- Fondo gris claro.
- Tarjetas KPI con iconos y acentos por estado.
- Graficos operativos en paneles blancos.
- Tablas densas con filtros, paginacion y acciones por fila.
- Los CRUD y formularios de mantenimiento/operacion deben abrirse en modales; las paginas principales deben priorizar listados, filtros y acciones.

## Plan por fases

### Fase 0 - Preparacion del proyecto

Estado: completada.

Avance:

- Laravel 12 creado en la raiz del proyecto.
- Breeze React/Inertia instalado.
- Spatie Permission, DomPDF y PhpSpreadsheet instalados.
- Lucide React y Recharts instalados.
- `.env` local configurado para MySQL/XAMPP.
- `.env.production.example` creado para PostgreSQL.
- `DEPLOYMENT.md` creado.
- Registro publico desactivado; los usuarios se gestionaran desde administracion.
- Layout administrativo base creado con sidebar, topbar, tarjetas KPI y graficos.
- Seeder inicial de empresa, roles, permisos y administrador creado.
- Tests aislados con SQLite en memoria para no borrar la base MySQL local.

Fecha de cierre: 2026-08-18.

Comandos ejecutados:

- `composer create-project laravel/laravel`.
- `composer require laravel/breeze --dev spatie/laravel-permission barryvdh/laravel-dompdf phpoffice/phpspreadsheet`.
- `php artisan breeze:install react`.
- `npm install lucide-react recharts`.
- `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`.
- `php artisan migrate:fresh --seed`.
- `npm run build`.
- `php artisan test`.

Credenciales locales iniciales:

- Email: `admin@logistica.test`.
- Password: `password`.

Entregables:

- Crear proyecto Laravel 12.
- Instalar Breeze React/Inertia.
- Configurar Vite, Tailwind, React e Inertia.
- Configurar `.env` local con MySQL/XAMPP.
- Instalar Spatie Permission, DomPDF, PhpSpreadsheet, Lucide React y Recharts.
- Crear layout base autenticado.
- Crear seed inicial de roles y permisos.
- Crear registro inicial de `empresa_configuracion`.

Criterio de cierre:

- Login funcional.
- Dashboard protegido visible.
- Roles creados.
- Usuario administrador inicial operativo.

### Fase 1 - Administracion base

Estado: completada.

Avance:

- Modelos y migraciones creados para `centros_costos`, `almacenes` y `trabajadores`.
- Usuarios ampliados con DNI, telefono, estado, centro de costo, almacen y soft deletes.
- Controladores Inertia creados para usuarios, roles, almacenes, centros de costo, trabajadores y empresa.
- Rutas administrativas protegidas con middleware `auth` y permisos Spatie.
- Sidebar administrativo enlazado y filtrado por permisos.
- Pantallas CRUD iniciales creadas para usuarios, almacenes, centros de costo y trabajadores.
- Pantalla de roles creada para asignar permisos.
- Pantalla de configuracion de empresa creada.
- Seeders de datos administrativos base creados.
- Pruebas de acceso administrativo y creacion de registros base agregadas.

Fecha de cierre: 2026-08-18.

Comandos ejecutados:

- `php artisan make:model CentroCosto -m`.
- `php artisan make:model Almacen -m`.
- `php artisan make:model Trabajador -m`.
- `php artisan make:controller Administracion/*`.
- `php artisan migrate:fresh --seed`.
- `php artisan test`.
- `npm run build`.

Pruebas realizadas:

- `php artisan test`: 27 tests pasaron.
- `npm run build`: compilacion correcta.

Entregables:

- Usuarios.
- Roles y permisos.
- Almacenes.
- Centros de costo.
- Trabajadores.
- Configuracion de empresa.

Criterio de cierre:

- El administrador puede mantener usuarios, roles, almacenes, centros de costo y trabajadores desde la interfaz.

### Fase 2 - Catalogo de inventario

Estado: completada.

Avance:

- Modelos y migraciones creados para `familias`, `unidades_medida`, `productos` y `producto_imagenes`.
- CRUD Inertia creado para familias.
- CRUD Inertia creado para unidades de medida.
- CRUD Inertia creado para productos.
- Productos vinculados a familia y unidad de medida.
- Productos preparados para datos tecnicos, stock minimo/maximo, costo referencial y configuracion EPP.
- Carga de imagen principal de producto implementada con storage publico.
- Exportacion Excel de productos implementada con PhpSpreadsheet.
- Importacion Excel basica de productos implementada con PhpSpreadsheet.
- Sidebar de inventario enlazado a productos, familias y unidades.
- Permisos especificos agregados para familias y unidades.
- Seeder de catalogo demo creado con datos mineros iniciales.
- Pruebas de acceso, creacion de catalogo y exportacion agregadas.

Fecha de cierre: 2026-08-18.

Comandos ejecutados:

- `php artisan make:model Familia -m`.
- `php artisan make:model UnidadMedida -m`.
- `php artisan make:model Producto -m`.
- `php artisan make:model ProductoImagen -m`.
- `php artisan make:controller Inventario/*`.
- `php artisan storage:link`.
- `php artisan migrate:fresh --seed`.
- `php artisan test`.
- `npm run build`.

Pruebas realizadas:

- `php artisan test`: 30 tests pasaron.
- `npm run build`: compilacion correcta.

Datos demo creados:

- 3 familias.
- 3 unidades de medida.
- 4 productos.

Entregables:

- Familias.
- Unidades de medida.
- Productos.
- Imagenes de productos.
- Stock minimo y maximo.
- Importacion/exportacion basica.

Criterio de cierre:

- El sistema permite crear productos completos y asociarlos a familias, unidades e imagenes.

### Fase 3 - Stock, movimientos y kardex

Estado: completada.

Avance:

- Migraciones y modelos creados para `stock_almacen`, `movimientos`, `movimientos_detalle` y `kardex`.
- Servicio transaccional `InventarioService` creado para centralizar entradas, salidas, transferencias y ajustes.
- Promedio ponderado implementado por producto y almacen.
- Bloqueo de stock negativo implementado con validacion de negocio.
- Kardex valorizado generado automaticamente por cada impacto de inventario.
- Transferencias implementadas como salida del almacen origen y entrada al almacen destino.
- Pantalla de movimientos creada para registrar entrada, salida, transferencia y ajustes.
- Pantalla de stock por almacen creada.
- Pantalla de kardex con filtros por producto y almacen creada.
- Sidebar actualizado con Stock, Movimientos y Kardex.
- Seeder de inventario inicial creado para generar stock demo.
- Pruebas de stock inicial, promedio ponderado, salida, bloqueo de negativo y acceso a pantallas agregadas.

Fecha de cierre: 2026-08-18.

Comandos ejecutados:

- `php artisan make:model StockAlmacen -m`.
- `php artisan make:model Movimiento -m`.
- `php artisan make:model MovimientoDetalle -m`.
- `php artisan make:model Kardex -m`.
- `php artisan make:controller Inventario/StockController`.
- `php artisan make:controller Inventario/MovimientoController`.
- `php artisan migrate:fresh --seed`.
- `php artisan test`.
- `npm run build`.

Pruebas realizadas:

- `php artisan test`: 34 tests pasaron.
- `npm run build`: compilacion correcta.

Datos demo creados:

- 4 filas de stock.
- 1 movimiento inicial de entrada.
- 4 detalles de movimiento.
- 4 asientos de kardex.

Entregables:

- Stock por almacen.
- Movimientos de entrada.
- Movimientos de salida.
- Transferencias.
- Ajustes.
- Kardex valorizado.
- Promedio ponderado.
- Bloqueo de stock negativo.

Criterio de cierre:

- Una entrada incrementa stock y kardex.
- Una salida descuenta stock y kardex.
- Una transferencia descuenta origen e incrementa destino.
- Ninguna operacion deja stock negativo.

### Fase 4 - Requerimientos y vales de salida

Estado: completada.

Avance:

- Migraciones y modelos creados para `requerimientos`, `requerimientos_detalle`, `vales_salida` y `vale_salida_detalles`.
- Servicio `RequerimientoService` creado para centralizar creacion, aprobacion, rechazo, generacion de vale y entrega.
- Flujo implementado: requerimiento pendiente, aprobacion por jefe de logistica, rechazo, generacion de vale y entrega.
- La entrega de vale usa `InventarioService`, genera movimiento de salida, descuenta stock y registra kardex valorizado.
- Controladores Inertia creados para requerimientos y vales de salida.
- Pantalla de requerimientos creada con formulario de solicitud, detalle de productos y acciones por estado.
- Pantalla de vales creada con entrega operativa y trazabilidad hacia requerimiento y movimiento.
- PDFs de requerimiento y vale implementados con Blade + DomPDF.
- Sidebar actualizado para Requerimientos y Vales de Salida.
- Pruebas de flujo completo y acceso a pantallas agregadas.

Fecha de cierre: 2026-08-18.

Comandos ejecutados:

- `php artisan make:model Requerimiento -m`.
- `php artisan make:model RequerimientoDetalle -m`.
- `php artisan make:model ValeSalida -m`.
- `php artisan make:model ValeSalidaDetalle -m`.
- `php artisan make:controller Operaciones/RequerimientoController`.
- `php artisan make:controller Operaciones/ValeSalidaController`.
- `php artisan migrate:fresh --seed`.
- `php artisan test`.
- `npm run build`.

Pruebas realizadas:

- `php artisan test`: 36 tests pasaron.
- `npm run build`: compilacion correcta.

Migraciones creadas:

- `2026_08_18_172007_create_requerimientos_table.php`.
- `2026_08_18_172008_create_requerimientos_detalle_table.php`.
- `2026_08_18_172009_create_vales_salida_table.php`.
- `2026_08_18_172010_create_vales_salida_detalle_table.php`.

Entregables:

- Requerimientos.
- Aprobacion por jefe de logistica.
- Rechazo.
- Generacion de vale.
- Entrega de vale con impacto en inventario.
- PDF de requerimiento y vale.

Criterio de cierre:

- Un requerimiento aprobado puede convertirse en vale y el vale entregado descuenta stock correctamente.

Riesgos o pendientes:

- La anulacion posterior a aprobacion/entrega queda pendiente para una fase de control operativo, porque requiere reglas de reversa de inventario y auditoria.
- El flujo actual aprueba el total solicitado; queda pendiente aprobacion parcial editable si la operacion lo requiere.

### Fase 5 - EPPs

Estado: pendiente.

Entregables:

- Configuracion de productos EPP.
- Asignacion a trabajador o usuario.
- Descuento de stock.
- Vencimientos.
- Devoluciones.
- Renovaciones.
- Alertas.

Criterio de cierre:

- Una asignacion EPP descuenta stock, registra trazabilidad y genera alertas segun vida util.

### Fase 6 - Prestamos

Estado: pendiente.

Entregables:

- Equipos prestables.
- Prestamos.
- Devoluciones.
- Renovaciones.
- Estados y vencimientos.
- Trazabilidad por equipo y receptor.

Criterio de cierre:

- Un equipo puede prestarse, renovarse y devolverse manteniendo uy anchohistorial.

### Fase 7 - Proveedores y compras

Estado: pendiente.

Entregables:

- Proveedores.
- Cotizaciones.
- Ordenes de compra.
- Aprobacion por jefe de logistica.
- Recepcion de orden.
- Movimiento de entrada por recepcion.
- PDF de orden de compra.

Criterio de cierre:

- Una orden recibida genera entrada de inventario, actualiza costo promedio y registra kardex.

### Fase 8 - Reportes y dashboard

Estado: pendiente.

Entregables:

- Dashboard con KPIs.
- Consumo mensual.
- Top productos consumidos.
- Valor de inventario por familia.
- Consumo por centro de costo.
- Stock bajo.
- Kardex exportable.
- Exportaciones Excel/PDF.

Criterio de cierre:

- Los indicadores y reportes reflejan movimientos reales del sistema.

### Fase 9 - Preparacion para produccion

Estado: pendiente.

Entregables:

- Validar migraciones en PostgreSQL.
- Crear `.env.production.example`.
- Crear `DEPLOYMENT.md`.
- Revisar permisos de archivos.
- Configurar colas si aplican.
- Optimizar cache/config/rutas/vistas.
- Pruebas funcionales completas.

Criterio de cierre:

- El sistema queda listo para despliegue sobre PostgreSQL sin depender de configuracion local XAMPP.

## Regla de documentacion por fase

Al avanzar una fase se debe actualizar este README con:

- Estado de la fase.
- Fecha de avance.
- Entregables completados.
- Comandos relevantes.
- Migraciones creadas.
- Riesgos o pendientes.
- Pruebas realizadas.

## Primer hito funcional

El primer hito real del proyecto sera:

1. Login funcional.
2. Roles base creados.
3. Almacenes, centros de costo, familias, unidades y productos operativos.
4. Entrada de inventario funcional.
5. Stock actualizado.
6. Kardex correcto.

Con ese hito terminado, el nucleo del sistema queda validado y se puede avanzar hacia requerimientos, vales, EPPs, prestamos y compras.
***
analisi familias
El módulo de familias es una tabla maestra que ordena el catálogo y, en este sistema, también activa comportamientos especiales. No solo sirve para agrupar productos visualmente; condiciona inventario, EPP, reportes, búsquedas, préstamos y validaciones.
Funcionalidades Principales
1. Clasificación de productos: cada producto puede pertenecer a una familia. Esto permite filtrar, buscar, ordenar y agrupar el catálogo por tipo de insumo.
2. Código automático de productos: al crear un producto, el backend usa el código de la familia como prefijo. Por ejemplo, una familia EPP-MAN o MAN puede generar códigos tipo MAN-001, MAN-002. Si no hay familia, usa PRD.
3. Control de familias activas/inactivas: las familias tienen estado activo. Esto permite ocultarlas o dejarlas fuera de ciertos selects sin borrarlas.
4. Protección contra eliminación: una familia no se puede eliminar si tiene productos asociados. Esto evita romper relaciones históricas de inventario.
5. Marcado EPP: una familia puede tener es_epp = true. Este es el punto más importante: todos los productos dentro de esa familia pasan a ser considerados EPP.
6. Categoría EPP: cuando una familia es EPP, se le asigna una categoría como CABEZA, OJOS, MANOS, PIES, CUERPO, etc. Esa categoría luego se hereda funcionalmente por los productos.
Impacto en Productos
Al crear o editar productos, la familia determina parte del comportamiento del producto.
Si la familia no es EPP, el producto funciona como producto común: repuesto, herramienta, insumo, químico, lubricante, etc.
Si la familia sí es EPP, el producto activa campos adicionales:
vida útil en días
días para alerta de vencimiento
requiere talla
tallas disponibles
Esto impacta directamente en la asignación de EPP. Por ejemplo, unas botas en una familia EPP de PIES pueden tener vida útil de 180 días y tallas disponibles. Un producto normal no necesita esos datos.
Impacto en el Módulo EPP
Este es el impacto más fuerte.
Para que un producto pueda asignarse como EPP, debe pertenecer a una familia marcada como EPP. El sistema valida eso al momento de asignar. Si el producto no pertenece a una familia EPP, aunque se llame “casco”, “guante” o “arnés”, no se podrá asignar como EPP.
Cuando se asigna un EPP:
se valida que el producto pertenezca a familia EPP
se verifica stock en almacén
se descuenta stock
se crea movimiento de salida
se registra kardex
se crea la asignación al trabajador o usuario
se calcula fecha de vencimiento
La fecha de vencimiento sale de vida_util_dias del producto. Si no está configurada, el sistema usa 365 días por defecto.
Impacto en Stock e Inventario
Familias no mueven stock por sí solas, pero determinan qué productos entran a ciertos flujos.
En productos EPP, al asignar uno a un trabajador, el sistema genera una salida de inventario. Eso afecta:
stock por almacén
kardex
costo promedio usado en salida
valor de inventario
alertas de stock bajo
historial de movimientos
Entonces, clasificar mal una familia puede causar impactos operativos. Si una familia EPP no está marcada como EPP, sus productos no aparecerán para entrega. Si una familia normal se marca por error como EPP, productos que no deberían entregarse como protección personal podrían aparecer en el módulo EPP.
Impacto en Reportes
Las familias permiten análisis por agrupación. En reportes de inventario o consumo, sirven para responder preguntas como:
qué familias tienen más consumo
qué familias tienen más stock
qué productos EPP están por vencer
qué categoría EPP tiene más asignaciones
qué productos tienen stock bajo por familia
En EPP, la categoría de la familia ayuda a separar protección de cabeza, manos, pies, cuerpo, altura, etc.
Impacto en Préstamos
El sistema de préstamos también usa familias como filtro conceptual. En el código hay lógica para productos prestables según familias como herramientas, equipos, maquinaria o electromecánicos. Es decir, familias también ayudan a decidir qué productos pueden aparecer en préstamos, no solo en EPP.
Entonces, familias funcionan como una puerta de entrada a módulos especializados.
Impacto en Seguridad y Control Operativo
Para EPP, familias tienen impacto administrativo y de seguridad laboral:
evitan asignar productos que no son EPP
permiten calcular renovaciones
permiten generar alertas de vencimiento
permiten registrar historial por trabajador
permiten controlar devolución, daño o extravío
Esto vuelve a la familia un dato crítico. No es solo una etiqueta.
Riesgos si se Configura Mal
Si una familia EPP se crea sin marcar es_epp, sus productos no aparecerán en asignación de EPP.
Si una familia EPP no tiene categoria_epp, los productos pueden quedar mal clasificados para filtros/reportes.
Si se cambia una familia normal a EPP, todos sus productos pasan a comportarse como posibles EPP.
Si se desmarca una familia EPP, sus productos dejan de ser considerados EPP para nuevas asignaciones.
Si se asignan productos a una familia incorrecta, el código automático, reportes, filtros y módulos especializados se ven afectados.
Conclusión
El módulo de familias tiene impacto transversal. Sirve para ordenar productos, generar códigos, filtrar reportes y proteger integridad de datos, pero además actúa como regla de negocio: define si un producto participa en EPP o en otros flujos especiales.
Para productos EPP, la familia es la condición principal. El sistema no pregunta “este producto es EPP”; pregunta “la familia de este producto es EPP”. Por eso, antes de cargar productos como cascos, guantes, botas, lentes o arneses, lo más importante es tener bien creadas y marcadas las familias EPP.

## Avance - Compras y Proveedores

Se inicio el bloque de compras dentro del flujo logistico:

- Maestro de proveedores con RUC, razon social, contacto, telefono, correo, direccion y estado.
- Ordenes de compra con proveedor, almacen destino, centro de costo, fechas, documento de referencia y observaciones.
- Detalle de productos con cantidad, precio unitario, subtotal y cantidad recibida.
- Recepcion parcial o total de compras.
- Al recepcionar una compra se genera movimiento de entrada para actualizar inventario/kardex.
- Estados considerados: pendiente, recibido_parcial, recibido y anulado.
- Anulacion de orden con motivo obligatorio.
- Pantallas creadas en modales para mantener el patron visual del sistema.

Rutas principales:

- `/administracion/proveedores`
- `/operaciones/compras`

## Avance - Aprobaciones de Requerimientos

Se definio el flujo de aprobacion para requerimientos:

- Los almaceneros registran requerimientos como borrador o pendiente.
- Jefe de logistica o administrador puede aprobar, observar, rechazar o anular.
- Observar, rechazar y anular requieren comentario/motivo.
- Se agrego historial de decisiones del requerimiento con usuario, accion, estado anterior, estado nuevo y comentario.
- Un requerimiento aprobado no genera compra ni vale automaticamente.
- En Compras se agrego selector de requerimiento aprobado.
- Al seleccionar un requerimiento aprobado, la compra sugiere sus productos y cantidades.
- El usuario puede editar cantidades, precios, quitar o agregar productos antes de guardar.
- Al guardar una compra basada en requerimiento, el requerimiento pasa a estado `en_compra`.
## Avance - Validaciones de Stock

- Se centralizo la validacion de inventario en `app/Services/MovimientoService.php`.
- Las salidas validan stock suficiente antes de descontar.
- Las entradas validan cantidades positivas y costo unitario cuando corresponde.
- El costo promedio se recalcula al ingresar mercaderia.
- Los reajustes exigen motivo y diferencian ajuste de entrada y salida.
- Las anulaciones de movimientos confirmados revierten stock y guardan motivo cuando la tabla lo permite.
- La fuente confiable para existencias queda centralizada en la tabla de stock detectada por el servicio.
- Prestamos descuentan stock como salida temporal y las devoluciones registran entrada para reponer el almacen.

## Avance - Kardex y Auditoria

- Kardex calcula `saldo_valor` usando el costo promedio vigente del producto en el almacen.
- Se agrego migracion para campos de anulacion en movimientos: motivo, usuario, fecha y movimiento reversa.
- Se agrego tabla `inventario_auditorias` para guardar stock antes/despues, usuario, accion, documento y motivo.
- Cada movimiento centralizado registra auditoria si la tabla existe.
- Las anulaciones quedan auditadas como reversa de stock, sin eliminar historial operativo.

## Avance - Roles, Permisos y Alcance Operativo

- Roles definidos: `administrador`, `jefe logistica`, `almacenero`.
- `administrador`: acceso total.
- `jefe logistica`: operacion, compras, aprobaciones, auditoria y reportes; sin gestion de usuarios/roles.
- `almacenero`: operacion diaria sin aprobaciones ni anulaciones administrativas.
- Usuarios ahora pueden tener `almacen_id` y `centro_costo_id` asignados.
- El almacenero queda limitado a registros de su almacen y centro de costo asignado.
- El backend fuerza el almacen/centro asignado en movimientos centralizados para evitar manipulacion desde el frontend.
- Seeder agregado: `Database\\Seeders\\RolesPermisosSeeder`.

## Avance - Separacion Catalogo e Inventario

- `Productos` queda como catalogo maestro general de la empresa.
- El stock ya no se muestra ni se calcula desde el catalogo de productos.
- Se agrego el modulo `Inventario > Inventario` para consultar existencias reales por almacen.
- El almacenero ve solo existencias de su almacen asignado.
- Administrador y jefe logistica ven el inventario general y pueden filtrar por almacen.


yefri@transnel.pe password