/* Customers listing page */
(function ($) {
    'use strict';

    function currentUrl() {
        return '/customers';
    }

    function applyFilters() {
        const params = new URLSearchParams();
        const search = $('#customerSearch').val();
        const status = $('#filterStatus').val();
        const hasPackage = $('#filterPackage').val();
        const outstanding = $('#filterOutstanding').val();
        const lastVisit = $('#filterLastVisit').val();

        if (search) params.set('search', search);
        if (status) params.set('status', status);
        if (hasPackage !== '') params.set('has_package', hasPackage);
        if (outstanding) params.set('outstanding', outstanding);
        if (lastVisit) params.set('last_visit', lastVisit);

        window.location.href = currentUrl() + (params.toString() ? '?' + params.toString() : '');
    }

    function showErrors(errors) {
        if (!errors) return;
        const flat = Object.values(errors).map(v => Array.isArray(v) ? v.join(' ') : v);
        Swal.fire({ icon: 'error', title: 'Validation failed', html: flat.join('<br>') });
    }

    function openCustomerModal(url, title) {
        $.get(url)
            .done((html) => {
                $('#customerFormFields').html(html);
                $('#customerModalTitle').text(title);
                $('#customerModal').modal('show');
            })
            .fail(() => Swal.fire({ icon: 'error', title: 'Could not load form' }));
    }

    window.NHS.init = function () {
        /* Debounced live search */
        let timer;
        $('#customerSearch').on('input', function () {
            clearTimeout(timer);
            timer = setTimeout(applyFilters, 500);
        });
        $('#filterStatus, #filterPackage, #filterOutstanding, #filterLastVisit').on('change', applyFilters);
        $('#filterReset').on('click', () => {
            window.location.href = currentUrl();
        });

        /* Add / Edit */
        $('#btnAddCustomer').on('click', () => openCustomerModal('/customers/create', 'Add Customer'));

        $(document).on('click', '.btn-edit-customer', function () {
            openCustomerModal('/customers/' + $(this).data('id') + '/edit', 'Edit Customer');
        });

        $('#customerForm').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            const url = form.id.value
                ? '/customers/' + form.id.value + '/update'
                : '/customers';

            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#customerModal').modal('hide');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => { window.location.href = currentUrl(); });
                } else {
                    showErrors(res.errors);
                }
            }).fail((xhr) => {
                const res = xhr.responseJSON;
                if (res && res.errors) showErrors(res.errors);
                else Swal.fire({ icon: 'error', title: res && res.message || 'Something went wrong' });
            });
        });

        /* Delete */
        $(document).on('click', '.btn-delete-customer', function () {
            const url = $(this).data('url');
            const name = $(this).data('name');
            Swal.fire({
                title: 'Delete ' + name + '?',
                text: 'All associated records will remain, but the customer will be hidden.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((r) => {
                if (r.isConfirmed) {
                    const token = $('meta[name="csrf-token"]').attr('content');
                    $('<form method="post" action="' + url + '"><input type="hidden" name="_token" value="' + token + '"></form>')
                        .appendTo('body').submit();
                }
            });
        });
    };
})(jQuery);
