/* ============================================================
   Create customer modal — shared by dashboard & billing
   ============================================================ */
(function ($) {
    'use strict';

    let onCreated = null;

    function resetForm() {
        $('#createCustomerForm input:not([type=hidden])').removeClass('is-invalid').val('');
        $('#createCustomerModal .invalid-feedback').addClass('d-none').html('');
        $('#ccPackageId').val('');
        $('#ccPackageName, #ccPackagePrice, #ccPackageCredits, #ccPackageValidity, #ccPackageNotes').val('');
        $('#ccSoldBy').val('');
        $('#ccPackageDate').val(new Date().toISOString().slice(0, 10));
    }

    function open(prefill) {
        resetForm();
        if (prefill && prefill.phone) $('#ccPhone').val(prefill.phone);
        if (prefill && prefill.name) $('#ccName').val(prefill.name);
        $('#createCustomerModal').modal('show');
        setTimeout(() => $('#ccName').focus(), 300);
    }

    function showErrors(errors) {
        $('#createCustomerModal input, #createCustomerModal select').removeClass('is-invalid');
        $('#createCustomerModal .invalid-feedback').addClass('d-none').html('');
        if (!errors) return;
        for (const key in errors) {
            const input = $('#createCustomerModal [name="' + key + '"]');
            if (input.length) {
                input.addClass('is-invalid');
                const fb = input.siblings('.invalid-feedback');
                fb.removeClass('d-none').html(Array.isArray(errors[key]) ? errors[key].join(' ') : errors[key]);
            }
        }
    }

    function submit() {
        const form = $('#createCustomerForm')[0];
        if (!form.name.value.trim()) {
            $('#ccName').addClass('is-invalid');
            return;
        }
        const btn = $('#btnCreateCustomer').prop('disabled', true);
        $.ajax({
            url: '/customers',
            method: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done((res) => {
            if (res.success) {
                $('#createCustomerModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Customer created', timer: 900, showConfirmButton: false });
                if (typeof onCreated === 'function') onCreated(res.customer);
            } else {
                showErrors(res.errors);
                btn.prop('disabled', false);
            }
        }).fail((xhr) => {
            const res = xhr.responseJSON || {};
            if (res.errors) showErrors(res.errors);
            else Swal.fire({ icon: 'error', title: res.message || 'Could not create customer.' });
            btn.prop('disabled', false);
        });
    }

    /* ---------- Package template auto-fill (values stay editable) ---------- */
    $(document).on('change', '#ccPackageId', function () {
        const opt = $(this).find(':selected');
        if (opt.val()) {
            const name = opt.text().split(' — ')[0].trim();
            $('#ccPackageName').val(name);
            $('#ccPackagePrice').val(opt.data('price'));
            $('#ccPackageCredits').val(opt.data('credits'));
            $('#ccPackageValidity').val(opt.data('validity') || '');
        } else {
            $('#ccPackageName, #ccPackagePrice, #ccPackageCredits, #ccPackageValidity').val('');
        }
    });

    /* ---------- Wire up ---------- */
    $('#btnCreateCustomer').on('click', submit);

    $('#createCustomerForm input, #createCustomerForm select').on('input change', function () {
        $(this).removeClass('is-invalid');
    });
    $('#createCustomerModal').on('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            $('#btnCreateCustomer').trigger('click');
        }
    });

    window.CreateCustomerModal = {
        open: open,
        onCreated: (fn) => { onCreated = fn; }
    };
})(jQuery);
