{{-- Librerías de DataTables (se incluyen en los dos listados) --}}
@push('estilos')
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Configuración común: idioma español y 10 filas por página
        function crearTabla(selector, opciones = {}) {
            return new DataTable(selector, Object.assign({
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json' },
                pageLength: 10,
            }, opciones));
        }
    </script>
@endpush
