/* Employees page */
(function ($) {
    'use strict';

    window.NHS.init = function () {
        function openModal(url, title) {
            $.get(url)
                .done((html) => {
                    $('#employeeFormFields').html(html);
                    $('#employeeModalTitle').text(title);
                    $('#employeeModal').modal('show');
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Could not load form' }));
        }

        $('#btnAddEmployee').on('click', () => openModal('/employees/create', 'Add Employee'));
        $(document).on('click', '.btn-edit-employee', function () {
            openModal('/employees/' + $(this).data('id') + '/edit', 'Edit Employee');
        });

        $('#employeeForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#employeeId').val();
            const url = id ? '/employees/' + id + '/update' : '/employees';
            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#employeeModal').modal('hide');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    const flat = Object.values(res.errors || {}).map(v => Array.isArray(v) ? v.join(' ') : v);
                    Swal.fire({ icon: 'error', title: 'Validation failed', html: flat.join('<br>') });
                }
            }).fail((xhr) => {
                const res = xhr.responseJSON;
                if (res && res.errors) {
                    const flat = Object.values(res.errors).map(v => Array.isArray(v) ? v.join(' ') : v);
                    Swal.fire({ icon: 'error', title: 'Validation failed', html: flat.join('<br>') });
                } else {
                    Swal.fire({ icon: 'error', title: 'Something went wrong' });
                }
            });
        });

        $(document).on('click', '.btn-delete-employee', function () {
            window.NHS.confirm(
                'Delete ' + $(this).data('name') + '?',
                'Historical billing allocations will be kept.',
                $(this).data('url')
            );
        });
    };
})(jQuery);
