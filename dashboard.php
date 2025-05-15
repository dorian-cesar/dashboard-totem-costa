<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Totem Logs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Datepicker CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .dataTables_wrapper .dataTables_info {
            padding-top: 0.85em !important;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Registros de Totem</h1>
        
        <div class="filter-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="dateRange" class="form-label">Filtrar por rango de fechas:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="dateRange" placeholder="Seleccione rango de fechas">
                            <button class="btn btn-outline-secondary" type="button" id="btnToday">Hoy</button>
                            <button class="btn btn-primary" type="button" id="btnFilter">Filtrar</button>
                        </div>
                    </div>
                </div>                
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Registros</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="logsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>RUT</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Fecha Viaje</th>
                                <th>Hora Viaje</th>
                                <th>Asiento</th>
                                <th>Código Reserva</th>
                                <th>Estado de Transacción</th>
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Moment JS (necesario para daterangepicker) -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <!-- Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <!-- Excel Export -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Configuración inicial del datepicker
            $('#dateRange').daterangepicker({
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Aplicar',
                    cancelLabel: 'Cancelar',
                    fromLabel: 'Desde',
                    toLabel: 'Hasta',
                    customRangeLabel: 'Personalizado',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    firstDay: 1
                },
                opens: 'right',
                autoUpdateInput: false
            });
            

            // Botón para establecer fecha actual
            $('#btnToday').click(function() {
                setTodayDate();
                loadData();
            });

            // Botón para aplicar filtro
            $('#btnFilter').click(function() {
                loadData();
            });

            var table = $('#logsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Exportar a Excel',
                        className: 'btn-success'
                    }
                ],
                pageLength: 200, // Mostrar 200 registros por página
                lengthMenu: [[200, 400, 600], [200, 400, 600]], // Opciones de paginación
                processing: true,
                serverSide: true, // IMPORTANTE: Mantener serverSide para no cargar todos los datos
                ajax: {
                    url: 'api.php',
                    type: 'GET',
                    data: function(d) {
                        // Configuración básica para la primera carga
                        var params = {
                            draw: d.draw,
                            start: 0, // Siempre comenzar desde el primer registro
                            length: 200, // Limitar a 200 registros inicialmente
                            order: [{column: 0, dir: 'desc'}] // Ordenar por ID descendente
                        };
                        
                        // Solo agregar filtros de fecha si existen
                        var dateRange = $('#dateRange').val();
                        if (dateRange && dateRange.includes(' - ')) {
                            var dates = dateRange.split(' - ');
                            if (dates[0].trim() && dates[1].trim()) {
                                params.start_date = moment(dates[0], 'DD/MM/YYYY').format('YYYY-MM-DD');
                                params.end_date = moment(dates[1], 'DD/MM/YYYY').format('YYYY-MM-DD');
                            }
                        }
                        
                        return params;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error AJAX:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'id', orderable: true },
                    { data: 'rut', orderable: true },
                    { data: 'origen', orderable: true },
                    { data: 'destino', orderable: true },
                    { 
                        data: 'fecha_viaje',
                        render: function(data) {
                            return data ? moment(data).format('DD/MM/YYYY') : '';
                        },
                        orderable: true
                    },
                    { 
                        data: 'hora_viaje',
                        orderable: true
                    },
                    { 
                        data: 'asiento',
                        orderable: true
                    },
                    { 
                        data: 'codigo_reserva',
                        orderable: true
                    },
                    { 
                        data: 'estado_transaccion',
                        orderable: true
                    },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : '';
                        },
                        orderable: true
                    }
                ],
                order: [[0, 'desc']], // Ordenar por la primera columna (id) descendente
                language: {
                    url: 'es-ES.json'
                },
                initComplete: function(settings, json) {
                    console.log('DataTable inicializado correctamente', json);
                }
            });        
            
            // Función para cargar datos
            function loadData() {
            // Resetear a la primera página y 200 registros
            table.page.len(200).draw();
            }

            // Función para establecer fecha actual en el datepicker
            function setTodayDate() {
                var today = moment();
                $('#dateRange').val(today.format('DD/MM/YYYY') + ' - ' + today.format('DD/MM/YYYY'));
                $('#dateRange').data('daterangepicker').setStartDate(today);
                $('#dateRange').data('daterangepicker').setEndDate(today);
            }

            // Evento para aplicar el filtro al cambiar fechas
            $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                loadData();
            });

            // Botón de exportar a Excel
            $('#btnExportExcel').click(function() {
                table.button('.buttons-excel').trigger();
            });
        });
    </script>
</body>
</html>