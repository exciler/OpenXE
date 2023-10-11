export function reloadDataTables() {
    window.$('#main .dataTable').DataTable().ajax.reload();
}