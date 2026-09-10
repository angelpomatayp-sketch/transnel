# Especificaciones del sistema objetivo

## 1. Alcance del analisis

Este documento describe la version objetivo del sistema de logistica e inventario que se va a desarrollar. La aplicacion sera para una sola empresa, sin arquitectura multiempresa ni aislamiento por `empresa_id`.

El sistema corresponde a una aplicacion web para gestion de inventario, requerimientos, vales de salida, EPPs, prestamos de equipos, compras, proveedores, reportes y administracion de usuarios, almacenes y centros de costo.

No se deben incluir en este documento credenciales, tokens, usuarios reales, registros productivos ni datos sensibles.

## 2. Arquitectura general

El sistema se desarrollara como una aplicacion Laravel monolitica moderna:

- Backend: Laravel 12 sobre PHP 8.2+.
- Frontend: React 18 con JavaScript/JSX, servido mediante Inertia.js v2.
- Build frontend: Vite.
- Estilos: Tailwind CSS.
- Plantilla base: Blade de Laravel en `resources/views/app.blade.php`.
- PDFs: vistas Blade dedicadas bajo `resources/views/pdf`.
- Persistencia local: MySQL segun `.env` actual.
- Persistencia prevista en produccion: PostgreSQL segun `DEPLOYMENT.md` y `.env.production.example`.

Patron arquitectonico objetivo:

- Laravel actua como aplicacion principal y punto unico de entrada.
- Inertia conecta controladores Laravel con paginas React sin construir una API REST separada para todo el frontend.
- Los controladores devuelven paginas Inertia para vistas interactivas y respuestas JSON solo cuando una interaccion puntual lo justifique.
- La logica de negocio critica debe vivir en servicios de dominio, especialmente inventario, kardex, vales, EPPs, prestamos y compras.
- Eloquent ORM sera la capa principal de acceso a datos.
- La aplicacion sera de una sola empresa; no se usara `stancl/tenancy`, scopes globales por empresa ni traits de multiempresa.

## 3. Stack tecnologico

Backend:

- PHP `8.2+`.
- Laravel `12`.
- Inertia Laravel.
- Laravel Breeze con React/Inertia para autenticacion inicial.
- Spatie Laravel Permission para roles y permisos.
- DomPDF para generacion de documentos PDF.
- PhpSpreadsheet para importacion/exportacion Excel.
- Eloquent ORM.
- Migraciones Laravel bajo `database/migrations`.
- Jobs, cache, sesiones y colas de Laravel cuando el flujo lo requiera.

Frontend:

- React `18`.
- JavaScript / JSX.
- Inertia.js `v2`.
- Vite.
- Tailwind CSS.
- Lucide React para iconografia.
- Recharts para graficos de dashboard y reportes.
- Componentes React bajo `resources/js/Components`.
- Paginas Inertia bajo `resources/js/Pages`.
- Estilos globales bajo `resources/css/app.css`.

Vistas Blade:

- `resources/views/app.blade.php`: plantilla base usada por Inertia.
- `resources/views/pdf`: plantillas Blade para documentos PDF como requerimientos, vales, kardex, ordenes de compra u otros reportes imprimibles.

Base de datos:

- Local: MySQL, configurado en `.env`.
- Produccion: PostgreSQL, previsto por `DEPLOYMENT.md` y `.env.production.example`.
- Las migraciones deben escribirse evitando detalles innecesariamente dependientes de un solo motor.

## 3.1. Decisiones base cerradas

- Tabla de usuarios: se usara `users`, la convencion nativa de Laravel.
- Autenticacion: Laravel Breeze con React/Inertia.
- Empresa: una sola empresa, configurada en `empresa_configuracion`.
- Empresa de ejemplo inicial: `Contratistas Asociados Pacifico S.R.L.`, RUC demo `20601234567`, Lima, Peru. Estos datos son referenciales y se reemplazaran por los reales antes de produccion.
- Valorizacion de inventario: promedio ponderado por producto y almacen.
- Stock negativo: bloqueado. Ninguna salida, vale, EPP, prestamo consumible o transferencia podra dejar stock menor a cero.
- Numeracion: automatica por tipo de documento, serie y periodo anual.
- Roles iniciales: `administrador`, `almacenero`, `jefe_logistica`.
- Base de datos local: MySQL bajo XAMPP.
- Base de datos produccion: PostgreSQL, validando migraciones y consultas antes del despliegue.
- Estilo visual: layout administrativo inspirado en la referencia CAP/Pacifico, con sidebar azul, estado activo verde, topbar blanca, fondo gris claro, tarjetas de metricas y graficos operativos.

## 4. Organizacion del backend

Estructura principal:

- `app/Models`: modelos Eloquent.
- `app/Http/Controllers`: controladores web, Inertia y endpoints auxiliares.
- `app/Services`: servicios de negocio para operaciones transaccionales.
- `app/Actions` o `app/UseCases`: opcional para casos de uso puntuales si el codigo empieza a crecer.
- `app/Policies`: autorizacion por recurso cuando aplique.
- `app/Http/Requests`: validacion de formularios.
- `routes/web.php`: rutas principales de Inertia y acciones web.
- `routes/auth.php`: rutas de autenticacion si se usa Breeze/Jetstream u organizacion equivalente.
- `routes/api.php`: solo para integraciones externas o endpoints realmente API.
- `config`: configuracion de Laravel y paquetes.
- `database/migrations`: definicion evolutiva del esquema.

Modulos funcionales sugeridos:

- `Administracion`: usuarios, roles, permisos, almacenes y centros de costo.
- `Inventario`: productos, familias, unidades, stock por almacen, movimientos, kardex e imagenes.
- `Requerimientos`: requerimientos y vales de salida.
- `Proveedores`: registro, validacion y calificacion de proveedores.
- `Compras`: cotizaciones y ordenes de compra.
- `EPPs`: tipos/configuracion de EPP, asignaciones, renovaciones y alertas.
- `Prestamos`: equipos prestables, prestamos, renovaciones y devoluciones.
- `Reportes`: dashboard, kardex, inventario, movimientos, consumo, stock bajo y exportaciones.
- `Notificaciones`: alertas internas por usuario.

Patrones recomendados:

- Controladores delgados: validan, autorizan y delegan operaciones complejas a servicios.
- Servicios transaccionales para cualquier flujo que afecte stock o kardex.
- Form Requests para validacion consistente.
- Policies o permisos Spatie para control de acceso.
- Soft Deletes en entidades maestras y transaccionales donde convenga conservar historial.
- Auditoria o bitacora en operaciones sensibles, aunque no necesariamente en todos los modelos.

## 5. Seguridad y control de acceso

Autenticacion:

- Login por email y password contra la tabla `users`.
- Sesion web tradicional de Laravel para Inertia.
- Proteccion CSRF propia de Laravel.
- No se requiere token Bearer para el frontend principal, porque React se sirve dentro de la misma aplicacion mediante Inertia.

Autorizacion:

- Roles y permisos mediante Spatie Laravel Permission.
- Las rutas protegidas deben usar middleware de autenticacion.
- Las acciones sensibles deben validar permisos en backend, no solo ocultarse en frontend.
- Los menus y botones en React deben renderizarse segun permisos enviados por Inertia.
- Para usuarios almaceneros, se puede restringir el acceso por almacen asignado cuando el recurso contenga `almacen_id`, `almacen_origen_id` o `almacen_destino_id`.

Roles iniciales:

- `administrador`: acceso total a configuracion, usuarios, roles, inventario, requerimientos, compras, EPPs, prestamos, reportes y mantenimiento.
- `jefe_logistica`: aprueba requerimientos, valida compras, supervisa stock, revisa reportes, autoriza anulaciones operativas y gestiona flujos logisticos.
- `almacenero`: gestiona productos, stock, movimientos, vales, entregas, recepciones, EPPs y prestamos dentro de los almacenes asignados.

Permisos base sugeridos:

- `dashboard.ver`
- `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.desactivar`
- `roles.ver`, `roles.editar`
- `almacenes.ver`, `almacenes.crear`, `almacenes.editar`, `almacenes.desactivar`
- `centros-costos.ver`, `centros-costos.crear`, `centros-costos.editar`, `centros-costos.desactivar`
- `trabajadores.ver`, `trabajadores.crear`, `trabajadores.editar`, `trabajadores.desactivar`
- `productos.ver`, `productos.crear`, `productos.editar`, `productos.desactivar`, `productos.importar`
- `inventario.ver-stock`, `inventario.movimientos`, `inventario.entrada`, `inventario.salida`, `inventario.transferencia`, `inventario.ajuste`, `inventario.anular`, `inventario.kardex`
- `requerimientos.ver`, `requerimientos.crear`, `requerimientos.aprobar`, `requerimientos.rechazar`, `requerimientos.anular`
- `vales.ver`, `vales.crear`, `vales.entregar`, `vales.anular`, `vales.pdf`
- `compras.ver`, `compras.crear`, `compras.aprobar`, `compras.recibir`, `compras.anular`
- `proveedores.ver`, `proveedores.crear`, `proveedores.editar`, `proveedores.desactivar`
- `epps.ver`, `epps.asignar`, `epps.devolver`, `epps.renovar`, `epps.anular`
- `prestamos.ver`, `prestamos.crear`, `prestamos.devolver`, `prestamos.renovar`, `prestamos.anular`
- `reportes.ver`, `reportes.exportar`

Modelo de empresa:

- El sistema sera para una sola empresa.
- No se usara columna `empresa_id` como tenant obligatorio.
- Si se necesita almacenar datos de la empresa para encabezados, PDFs o configuracion, se recomienda una tabla simple `empresa_configuracion` o `configuraciones`, con un unico registro editable.
- No se deben implementar traits como `PerteneceAEmpresa`, `EmpresaScope` ni middleware de contexto tenant.

## 6. Patron de diseno del frontend

El frontend usara Inertia con React:

- Paginas bajo `resources/js/Pages`.
- Componentes reutilizables bajo `resources/js/Components`.
- Layout administrativo compartido para las paginas autenticadas.
- Navegacion mediante links Inertia, no Vue Router ni React Router salvo una necesidad puntual.
- Formularios con helpers de Inertia o manejo React controlado.
- Estado local por pagina cuando sea suficiente.
- Estado global solo para necesidades transversales reales, como usuario autenticado, permisos, tema o notificaciones.

Layout objetivo:

- Sidebar fijo o colapsable con modulos del sistema.
- Topbar con titulo, usuario, notificaciones y acciones rapidas.
- Contenido principal optimizado para trabajo operativo.
- Tablas con filtros, paginacion, busqueda y acciones por fila.
- Dialogos o paginas dedicadas para crear/editar segun complejidad del formulario.
- Estados visuales claros para pendiente, aprobado, rechazado, completado, anulado, vencido y bajo stock.

Sistema visual:

- Tailwind CSS como base.
- Componentes propios React o una libreria UI ligera si se decide incorporarla.
- Evitar dependencia de PrimeVue, Pinia, Vue Router o Axios como base del frontend.
- Mantener una interfaz administrativa sobria, densa y facil de escanear.
- Usar Lucide React para iconos de menu, botones y estados.
- Usar Recharts para graficos de consumo, stock, productos mas consumidos y distribucion por centro de costo.
- Paleta base: sidebar azul corporativo `#1565C0`, activo verde `#1A9A3B`, fondo `#f1f5f9`, texto principal `#0f172a`, bordes `#e2e8f0`, alertas en naranja/rojo y acentos secundarios cyan/violeta solo para metricas.
- El dashboard inicial seguira la estructura visual de la referencia: sidebar izquierdo, topbar blanca, buscador, usuario, tarjetas KPI en grilla, graficos en paneles y tablas operativas.

## 7. Modulos funcionales

Dashboard:

- Indicadores de inventario, movimientos, requerimientos, compras y alertas.
- Graficos de stock, consumo, movimientos y productos criticos.
- Accesos rapidos a tareas frecuentes.

Administracion:

- Usuarios.
- Roles y permisos.
- Almacenes.
- Centros de costo.
- Trabajadores sin login para EPP, prestamos y documentos.
- Configuracion de datos de la empresa para encabezados, documentos y parametros generales.

Inventario:

- Familias/categorias.
- Unidades de medida.
- Productos con imagenes, datos tecnicos, stock minimo/maximo y configuracion EPP cuando aplique.
- Stock por almacen.
- Movimientos de entrada, salida, transferencia y ajuste.
- Kardex valorizado por producto y almacen.
- Importacion y exportacion con PhpSpreadsheet.

Requerimientos y vales:

- Creacion y seguimiento de requerimientos.
- Estados de borrador, pendiente, aprobado, rechazado, parcial, completado y anulado.
- Generacion de vale desde requerimiento aprobado.
- Entrega de vale con descuento de stock mediante movimiento asociado.
- PDF de requerimientos y vales con Blade y DomPDF.

EPPs:

- Gestion de asignaciones de EPP a trabajadores o usuarios.
- Producto EPP vinculado directamente desde inventario.
- Registro de almacen y movimiento asociado para impacto en stock.
- Control de vida util, vencimiento, devolucion, renovacion y alertas.

Prestamos:

- Registro o importacion de equipos prestables desde productos del inventario.
- Control individual o por cantidad.
- Prestamo, devolucion, renovacion y vencimiento.
- Receptor trabajador o usuario.
- Campos de guias y requerimiento para trazabilidad documental.

Compras:

- Proveedores.
- Cotizaciones con detalle, proveedor, fechas, estado, moneda, subtotal, IGV y total.
- Ordenes de compra con proveedor, almacen destino, aprobacion, recepcion y movimiento asociado.
- Recepcion de orden con impacto en inventario.
- PDF de ordenes de compra cuando aplique.

Reportes:

- Kardex.
- Inventario.
- Movimientos.
- Consumo por centro de costo.
- Stock bajo.
- Requerimientos.
- Top productos.
- Dashboard y graficos.
- Exportaciones Excel/PDF.

Notificaciones:

- Listado, conteo y resumen.
- Marcado como leidas.
- Alertas por stock bajo, vencimientos, requerimientos, EPPs y prestamos.

## 8. Base de datos

Entorno local actual:

- `DB_CONNECTION=mysql`.
- `DB_HOST=127.0.0.1`.
- `DB_PORT=3306`.
- La base local se define en `.env`.

Produccion prevista:

- `DB_CONNECTION=pgsql`.
- La configuracion se documenta en `DEPLOYMENT.md` y `.env.production.example`.

Criterios de compatibilidad:

- Usar migraciones Laravel para todos los cambios de esquema.
- Evitar SQL crudo especifico de MySQL si existe una alternativa portable con Schema Builder o Query Builder.
- Evitar tipos, indices o expresiones que no funcionen igual en PostgreSQL sin una razon justificada.
- Validar nombres de tablas y columnas en minusculas y `snake_case`.
- Recordar que PostgreSQL es mas estricto con tipos, agrupaciones, defaults y comparaciones.

## 9. Esquema principal de base de datos

Tablas de plataforma Laravel:

- `cache`, `cache_locks`: cache por base de datos.
- `jobs`, `job_batches`, `failed_jobs`: colas Laravel.
- `sessions`: sesiones web.
- `password_reset_tokens`: recuperacion de contrasena.
- `migrations`: control de migraciones ejecutadas.

Tablas de seguridad y permisos:

- `users`: usuarios con login. Campos clave: name, email, password, DNI, telefono, centro de costo, almacen asignado, estado activo y soft delete.
- `roles`, `permissions`: catalogos de roles y permisos Spatie.
- `model_has_roles`, `model_has_permissions`, `role_has_permissions`: pivotes polimorficos de autorizacion.

Tablas maestras:

- `empresa_configuracion`: datos de la empresa unica para documentos, encabezados y parametros generales.
- `trabajadores`: personal sin login para EPP, prestamos y documentos.
- `centros_costos`: unidades, obras o areas.
- `almacenes`: almacenes fisicos o logicos.
- `proveedores`: proveedores con RUC, razon social, datos comerciales/contacto, tipo, calificacion y estado.
- `familias`: clasificacion de productos.
- `unidades_medida`: unidades con codigo, nombre, abreviatura y estado.

Tablas de inventario:

- `productos`: catalogo de productos, familia, codigo, nombre, descripcion, unidad, marca, modelo, stock minimo/maximo, ubicacion, lote y campos EPP cuando aplique.
- `producto_imagenes`: imagenes de productos.
- `stock_almacen`: stock agregado por producto y almacen.
- `movimientos`: cabecera de operaciones de inventario.
- `movimientos_detalle`: detalle de movimiento.
- `kardex`: historial valorizado por producto y almacen.

Tablas de requerimientos y despacho:

- `requerimientos`: cabecera de requerimientos.
- `requerimientos_detalle`: detalle de productos solicitados, aprobados y entregados.
- `vales_salida`: despacho de productos asociado o no a un requerimiento.
- `vales_salida_detalle`: detalle de vale.

Tablas de compras:

- `cotizaciones`: cabecera de cotizacion.
- `cotizaciones_detalle`: detalle por producto, cantidad, precio, descuento y subtotal.
- `ordenes_compra`: cabecera de orden.
- `ordenes_compra_detalle`: detalle de orden, recepcion, lote y vencimiento.

Tablas de EPP:

- `tipos_epp`: configuracion de tipos de EPP, vida util, alertas y tallas.
- `asignaciones_epp`: entregas de EPP a trabajador o usuario.
- `renovaciones_epp`: vincula asignacion anterior y nueva.

Tablas de prestamos:

- `equipos_prestables`: equipos o herramientas controlables.
- `prestamos_equipos`: prestamos, renovaciones y devoluciones.

Tablas de notificacion:

- `notificaciones`: alertas por usuario, tipo, titulo, mensaje, severidad, entidad relacionada, URL y fecha de lectura.

## 10. Relaciones principales

- `usuario` puede pertenecer a un centro de costo y tener un almacen asignado.
- `usuario` recibe roles y permisos mediante Spatie.
- `centro_costo` puede tener un responsable usuario.
- `almacen` puede pertenecer a un centro de costo y tener un responsable usuario.
- `producto` pertenece a una familia y puede tener imagenes, stock por almacen, movimientos detalle y kardex.
- `stock_almacen` une producto y almacen con stock actual y costo promedio.
- `movimiento` pertenece a un usuario y puede vincular almacenes, proveedor, centro de costo y una referencia funcional.
- `kardex` referencia producto, almacen y movimiento.
- `requerimiento` tiene detalle de productos y puede originar vales.
- `vale_salida` tiene detalle y puede generar o vincular un movimiento de salida.
- `cotizacion` pertenece a proveedor y usuario solicitante.
- `orden_compra` puede derivar de cotizacion y generar movimiento de entrada al recibir.
- `asignacion_epp` puede vincular producto, almacen y movimiento para descontar inventario.
- `equipo_prestable` puede originarse desde un producto y tener muchos prestamos.
- `prestamo_equipo` vincula equipo, receptor, centro de costo y usuarios responsables.

## 11. Restricciones e indices relevantes

- `users.email` unico.
- `centros_costos.codigo` unico.
- `almacenes.codigo` unico.
- `familias.codigo` unico.
- `productos.codigo` unico.
- `proveedores.ruc` unico.
- `stock_almacen`: unico por `producto_id + almacen_id`.
- `movimientos.numero` unico.
- `requerimientos.numero` unico.
- `vales_salida.numero` unico.
- `cotizaciones.numero` unico.
- `ordenes_compra.numero` unico.
- `prestamos_equipos.numero` unico.
- Indices por estado y fecha en tablas transaccionales para reportes y filtros operativos.
- Indices para consultas frecuentes sobre productos activos, familias, proveedores activos, movimientos por tipo/estado, stock y kardex.

## 12. Flujos de negocio principales

Entrada de inventario:

1. Se registra un movimiento tipo `ENTRADA`.
2. Se agregan productos, cantidades, costos, lote/vencimiento cuando aplica.
3. Al confirmarse, se actualiza `stock_almacen`.
4. Se recalcula costo promedio ponderado del producto en el almacen.
5. Se registra asiento en `kardex`.

Salida directa o vale:

1. Se crea un vale o movimiento tipo `SALIDA`.
2. Se valida stock disponible.
3. Se descuenta del almacen correspondiente.
4. Se valoriza la salida con el costo promedio vigente.
5. Se registra kardex con saldo resultante.

Transferencia:

1. Movimiento tipo `TRANSFERENCIA` con almacen origen y destino.
2. Descuenta origen, incrementa destino.
3. Registra trazabilidad en movimiento/kardex.

Requerimiento:

1. Se crea requerimiento con productos y cantidades.
2. Pasa por aprobacion, rechazo o anulacion.
3. Si se aprueba, puede generar vale de salida.
4. El vale entregado impacta inventario mediante movimiento.

EPP:

1. Se selecciona producto EPP y receptor.
2. Se registra entrega, vencimiento y cantidad.
3. Si el flujo esta vinculado a inventario, registra almacen/movimiento y descuenta stock.
4. Puede confirmar recepcion, devolver, renovar o cambiar estado.

Prestamo:

1. Se registra o importa equipo prestable.
2. Se presta a trabajador o usuario con fecha esperada de devolucion.
3. Se controla estado, renovaciones y devolucion.
4. Se mantiene historial por equipo y receptor.

Compra:

1. Se crea cotizacion y se registra respuesta/aprobacion.
2. Se crea orden de compra.
3. Al recibir, puede generar movimiento de entrada y actualizar inventario.

## 13. Observaciones de desarrollo y mantenimiento

- El sistema sera monolitico Laravel + Inertia + React; no se debe construir un frontend Vue separado.
- No se debe implementar multiempresa ni columnas `empresa_id` como aislamiento tenant.
- Si el proyecto parte de codigo anterior multiempresa, retirar o adaptar cuidadosamente traits, scopes, middleware y validaciones por empresa.
- Los datos de la empresa unica deben manejarse como configuracion, no como tenant.
- Cualquier cambio de esquema debe hacerse mediante migraciones nuevas.
- Las migraciones deben probarse en MySQL local y considerar compatibilidad con PostgreSQL para produccion.
- No se deben ejecutar seeders demo en produccion.
- No se deben incluir datos reales en documentacion ni commits.
- Antes de cambios funcionales en stock, vales, EPPs, prestamos o compras, probar que `stock_almacen` y `kardex` queden consistentes.
- La consistencia de stock y kardex debe manejarse en servicios transaccionales con `DB::transaction`.
- Los PDFs deben generarse desde vistas Blade bajo `resources/views/pdf`.
- El frontend principal usa sesion web Laravel/Inertia; no debe depender de `localStorage` para persistir tokens de autenticacion.
- Cada fase avanzada debe quedar documentada en el `README.md` de la raiz con alcance, entregables, comandos y estado.
- Antes de pasar a produccion se debe ejecutar una validacion completa sobre PostgreSQL, aunque el desarrollo local se mantenga en XAMPP/MySQL.

## 14. Archivos clave

- Aplicacion Laravel: `app/`.
- Controladores: `app/Http/Controllers`.
- Modelos: `app/Models`.
- Servicios de negocio: `app/Services`.
- Validaciones: `app/Http/Requests`.
- Politicas: `app/Policies`.
- Rutas web/Inertia: `routes/web.php`.
- Rutas API auxiliares: `routes/api.php`.
- Configuracion: `config/`.
- Migraciones: `database/migrations/`.
- Paginas React/Inertia: `resources/js/Pages/`.
- Componentes React: `resources/js/Components/`.
- Entrada JS: `resources/js/app.jsx`.
- Estilos: `resources/css/app.css`.
- Plantilla base Inertia: `resources/views/app.blade.php`.
- Vistas PDF: `resources/views/pdf/`.
- Variables locales: `.env` (no documentar credenciales).
- Variables de produccion de ejemplo: `.env.production.example`.
- Guia de despliegue: `DEPLOYMENT.md`.
