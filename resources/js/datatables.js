import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-responsive-bs5';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.css';

const indonesianLanguage = {
    emptyTable: 'Tidak ada data',
    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
    infoFiltered: '(disaring dari _MAX_ total data)',
    lengthMenu: 'Tampilkan _MENU_ data',
    loadingRecords: 'Memuat...',
    processing: 'Memproses...',
    search: 'Cari:',
    zeroRecords: 'Data tidak ditemukan',
    paginate: {
        first: 'Awal',
        last: 'Akhir',
        next: '›',
        previous: '‹',
    },
};

export function initServerDataTable(selector, options = {}) {
    const defaults = {
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
        order: [[0, 'desc']],
        language: indonesianLanguage,
        layout: {
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
    };

    return new DataTable(selector, {
        ...defaults,
        ...options,
    });
}

/** Render HTML dari server-side tanpa escape (DT2 tidak punya render.raw). */
export function dtHtmlRender(data, type) {
    if (type === 'display') {
        return data ?? '';
    }

    return typeof data === 'string' ? data.replace(/<[^>]*>/g, '') : (data ?? '');
}

window.DataTable = DataTable;
window.initServerDataTable = initServerDataTable;
window.dtHtmlRender = dtHtmlRender;
