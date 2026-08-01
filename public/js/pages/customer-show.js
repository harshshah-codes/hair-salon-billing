/* Customer detail page */
(function ($) {
    'use strict';

    const customerId = window.location.pathname.split('/')[2];

    window.NHS.assignPackage = function () {
        $('#assignPackageModal').modal('show');
    };

    window.NHS.editCustomer = function (id) {
        $.get('/customers/' + id + '/edit')
            .done((html) => {
                $('<div class="modal fade" id="dynCustomerModal" tabindex="-1">'
                    + '<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">'
                    + '<form id="dynCustomerForm" enctype="multipart/form-data">'
                    + '<input type="hidden" name="id" value="' + id + '">'
                    + '<div class="modal-header"><h5 class="modal-title">Edit Customer</h5>'
                    + '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
                    + '<div class="modal-body"><div class="row g-3">' + html + '</div></div>'
                    + '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>'
                    + '<button type="submit" class="btn btn-primary">Save Customer</button></div>'
                    + '</form></div></div></div>').appendTo('body').modal('show');
                $('#dynCustomerModal').modal('show');
            });
    };

    function init() {
        const modal = $('#assignPackageModal');

        $('input[name="source"]', modal).on('change', function () {
            const custom = $(this).val() === 'custom';
            $('#customFields').toggle(custom);
            $('#predefinedFields').toggle(!custom);
        });

        $('#packageSelect').on('change', function () {
            const opt = $(this).find(':selected');
            if (!opt.val()) { $('#packagePreview').html(''); return; }
            $('#packagePreview').html(
                '<div class="col-md-4"><label class="form-label">Price</label><input class="form-control" value="₹' + Number(opt.data('price')).toFixed(2) + '" disabled></div>'
                + '<div class="col-md-4"><label class="form-label">Credits</label><input class="form-control" value="' + opt.data('credits') + '" disabled></div>'
                + '<div class="col-md-4"><label class="form-label">Validity</label><input class="form-control" value="' + opt.data('validity') + ' days" disabled></div>'
            );
        });

        $('#assignPackageForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('[type=submit]').prop('disabled', true);
            $.post('/customers/' + customerId + '/packages', $(this).serialize())
                .done((res) => {
                    if (res.success) {
                        modal.modal('hide');
                        Swal.fire({ icon: 'success', title: res.message, timer: 1600, showConfirmButton: false })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: res.message });
                    }
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Could not assign package' }))
                .always(() => btn.prop('disabled', false));
        });

        /* Notes */
        $('#noteForm').on('submit', function (e) {
            e.preventDefault();
            const note = $('#noteText').val().trim();
            if (!note) return;
            $.post('/customers/' + customerId + '/notes', { note: note })
                .done((res) => {
                    if (res.success) {
                        $('#noteText').val('');
                        Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false })
                            .then(() => window.location.reload());
                    }
                });
        });

        /* Edit via dynamic modal */
        $(document).on('submit', '#dynCustomerForm', function (e) {
            e.preventDefault();
            const form = this;
            $.ajax({
                url: '/customers/' + customerId + '/update',
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    $('#dynCustomerModal').modal('hide');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    const flat = Object.values(res.errors || {}).map(v => Array.isArray(v) ? v.join(' ') : v);
                    Swal.fire({ icon: 'error', title: 'Validation failed', html: flat.join('<br>') });
                }
            }).fail(() => Swal.fire({ icon: 'error', title: 'Update failed' }));
        });
    }

    window.NHS.init = init;
})(jQuery);
