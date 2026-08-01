/* Packages (Package Manager) page */
(function ($) {
    'use strict';

    window.NHS.init = function () {
        function openModal(url, title) {
            $.get(url)
                .done((html) => {
                    $('#packageFormFields').html(html);
                    $('#packageModalTitle').text(title);
                    $('#packageModal').modal('show');
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Could not load form' }));
        }

        $('#btnAddPackage').on('click', () => openModal('/packages/create', 'Create Package'));

        $(document).on('click', '.btn-edit-package', function () {
            openModal('/packages/' + $(this).data('id') + '/edit', 'Edit Package');
        });

        $('#packageForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#packageId').val();
            const url = id ? '/packages/' + id + '/update' : '/packages';

            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#packageModal').modal('hide');
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

        $(document).on('click', '.btn-delete-package', function () {
            window.NHS.confirm(
                'Delete ' + $(this).data('name') + '?',
                'Existing customer packages will not be affected.',
                $(this).data('url')
            );
        });
    };
})(jQuery);
