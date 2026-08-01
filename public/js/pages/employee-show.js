/* Employee profile page */
(function ($) {
    'use strict';

    let empId = 0;

    window.NHS.editEmployee = function (id) {
        empId = id;
        $.get('/employees/' + id + '/edit')
            .done((html) => {
                $('<div class="modal fade" id="dynEmpModal" tabindex="-1">'
                    + '<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">'
                    + '<form id="dynEmpForm" enctype="multipart/form-data">'
                    + '<input type="hidden" name="id" value="' + id + '">'
                    + '<div class="modal-header"><h5 class="modal-title">Edit Employee</h5>'
                    + '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
                    + '<div class="modal-body"><div class="row g-3">' + html + '</div></div>'
                    + '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>'
                    + '<button type="submit" class="btn btn-primary">Save Employee</button></div>'
                    + '</form></div></div></div>').appendTo('body').modal('show');
            });
    };

    window.NHS.init = function () {
        const series = window.EMP_EARNINGS_SERIES || { labels: [], values: [] };
        if (series.labels.length && document.getElementById('empEarningsChart')) {
            new Chart(document.getElementById('empEarningsChart'), {
                type: 'line',
                data: {
                    labels: series.labels,
                    datasets: [{
                        label: 'Earnings (₹)',
                        data: series.values,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.12)',
                        fill: true,
                        tension: .4,
                        pointRadius: 3,
                        pointBackgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(100,116,139,.12)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        $(document).on('submit', '#dynEmpForm', function (e) {
            e.preventDefault();
            const form = this;
            $.ajax({
                url: '/employees/' + empId + '/update',
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#dynEmpModal').modal('hide');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    const flat = Object.values(res.errors || {}).map(v => Array.isArray(v) ? v.join(' ') : v);
                    Swal.fire({ icon: 'error', title: 'Validation failed', html: flat.join('<br>') });
                }
            }).fail(() => Swal.fire({ icon: 'error', title: 'Update failed' }));
        });
    };
})(jQuery);
