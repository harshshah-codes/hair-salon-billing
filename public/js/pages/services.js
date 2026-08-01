/* Services page */
(function ($) {
    'use strict';

    window.NHS.init = function () {
        function openModal(url, title) {
            $.get(url)
                .done((html) => {
                    $('#serviceFormFields').html(html);
                    $('#serviceModalTitle').text(title);
                    $('#serviceModal').modal('show');
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Could not load form' }));
        }

        $('#btnAddService').on('click', () => openModal('/services/create', 'Create Service'));
        $(document).on('click', '.btn-edit-service', function () {
            openModal('/services/' + $(this).data('id') + '/edit', 'Edit Service');
        });

        $('#serviceForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#serviceId').val();
            const url = id ? '/services/' + id + '/update' : '/services';
            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#serviceModal').modal('hide');
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

        $(document).on('click', '.btn-delete-service', function () {
            window.NHS.confirm(
                'Delete ' + $(this).data('name') + '?',
                'Historical invoices will keep their records.',
                $(this).data('url')
            );
        });
    };
})(jQuery);
