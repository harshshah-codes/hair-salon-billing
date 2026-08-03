/* ============================================================
   Billing (POS) — primary page logic
   ============================================================ */
(function ($) {
    'use strict';

    const money = (v) => '₹' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const state = {
        customer: null,
        items: [],
        notes: '',
        lastPayload: null
    };

    let uidCounter = 0;
    const nextUid = () => 'r' + (++uidCounter);

    /* ---------- Customer search ---------- */
    let searchTimer;
    const $input = $('#customerSearchInput');
    const $results = $('#customerResults');

    function customerSearch() {
        const q = $input.val().trim();
        if (q.length < 2) {
            $results.addClass('d-none').html('');
            return;
        }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            $.getJSON('/customers/ajax/search', { q: q })
                .done((res) => {
                    if (!res.success) return;
                    renderCustomerResults(res.customers);
                });
        }, 300);
    }

    function renderCustomerResults(list) {
        let html = '';
        if (!list.length) {
            html = '<div class="list-group-item text-muted small py-3 text-center border-0">No customers found.</div>';
        }
        list.forEach((c) => {
            const bal = parseFloat(c.available_balance) || 0;
            const out = parseFloat(c.outstanding) || 0;
            html += '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0" data-customer=\'' + JSON.stringify({ id: c.id, name: c.name, mobile: c.mobile, email: c.email, photo: c.photo || null, outstanding: out, credits: bal, last_visit: c.last_visit_at }) + '\'>'
                + '<span class="avatar-initials" style="width:36px;height:36px;background:hsl(' + (c.name.length * 40 % 360) + ',55%,40%)">' + c.name.charAt(0).toUpperCase() + '</span>'
                + '<span class="flex-grow-1 text-start"><span class="d-block fw-semibold">' + c.name + '</span>'
                + '<span class="small text-muted">' + (c.mobile || '') + '</span></span>'
                + '<span class="text-end small"><span class="d-block text-success fw-semibold">' + money(bal) + '</span>'
                + '<span class="' + (out > 0 ? 'text-danger' : 'text-muted') + '">Outstanding ' + money(out) + '</span></span>'
                + '</button>';
        });
        html += '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0 border-top text-primary fw-semibold" id="btnCreateCustomerFromSearch">'
            + '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-soft text-primary" style="width:36px;height:36px"><i class="fa-solid fa-plus"></i></span>'
            + '<span>Create new customer</span>'
            + '</button>';
        $results.html('<div class="list-group list-group-flush">' + html + '</div>').removeClass('d-none');
    }

    $(document).on('click', '#btnCreateCustomerFromSearch', function () {
        $results.addClass('d-none').html('');
        openCreateCustomerModal();
    });

    $(document).on('click', '#customerResults .list-group-item', function () {
        const c = $(this).data('customer');
        selectCustomer(c);
    });

    function selectCustomer(c) {
        state.customer = c;
        $input.val(c.name + ' (' + c.mobile + ')');
        $results.addClass('d-none').html('');
        $('#customerSearchClear').show();

        const html = '<div class="d-flex align-items-center gap-3 mt-3 border rounded-3 p-3">'
            + '<span class="avatar-initials" style="width:46px;height:46px;background:hsl(' + (c.name.length * 40 % 360) + ',55%,40%)">' + c.name.charAt(0).toUpperCase() + '</span>'
            + '<div class="flex-grow-1">'
            + '<div class="fw-bold">' + c.name + '</div>'
            + '<div class="small text-muted"><i class="fa-solid fa-phone me-1"></i>' + (c.mobile || '—') + '</div>'
            + '</div>'
            + '<div class="text-end small d-none d-md-block">'
            + '<div><span class="text-muted">Balance: </span><span class="fw-semibold text-success">' + money(c.credits) + '</span></div>'
            + '<div><span class="text-muted">Outstanding: </span><span class="fw-semibold ' + (c.outstanding > 0 ? 'text-danger' : 'text-muted') + '">' + money(c.outstanding) + '</span></div>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-light" id="btnChangeCustomer"><i class="fa-solid fa-rotate-left"></i></button>'
            + '</div>';
        $('#customerCard').html(html).removeClass('d-none');

        if (c.credits > 0) {
            $('#pkgAvailable').text(money(c.credits)).removeClass('text-danger').addClass('text-success');
        } else {
            $('#pkgAvailable').text(money(c.credits)).removeClass('text-success').addClass('text-danger');
        }
        recalc();
    }

    $(document).on('click', '#btnChangeCustomer', function () {
        state.customer = null;
        $input.val('').focus();
        $('#customerCard').addClass('d-none').html('');
        $('#pkgAvailable').text('₹0.00').removeClass('text-success').addClass('text-danger');
        recalc();
    });

    $('#customerSearchInput').on('input', customerSearch);
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.customer-search-wrap').length) {
            $results.addClass('d-none');
        }
    });

    /* ---------- Service rows (custom name + price) ---------- */
    function renderEmpOptions(selected) {
        let opts = '';
        BILLING.employees.forEach((e) => {
            opts += '<option value="' + e.id + '"' + ((selected || []).includes(e.id) ? ' selected' : '') + '>' + e.name + '</option>';
        });
        return opts;
    }

    function addItemRow() {
        if (!$('#billingItemsBody tr:first').hasClass('bill-item-row')) {
            $('#billingItemsBody').html('');
        }
        const uid = nextUid();
        const tr = $('<tr>', { class: 'bill-item-row', 'data-uid': uid })
            .append($('<td>').append($('<input>', { class: 'form-control form-control-sm name-input', type: 'text', placeholder: 'Service name (e.g. Haircut)' })))
            .append($('<td>').append($('<div>', { class: 'input-group input-group-sm' }).append($('<span>', { class: 'input-group-text' }).text('₹')).append($('<input>', { class: 'form-control text-end price-input', type: 'number', step: '0.01', min: '0', value: '' }))))
            .append($('<td>').append($('<input>', { class: 'form-control form-control-sm text-center qty-input', type: 'number', min: '1', value: '1' })))
            .append($('<td>').append(
                $('<select>', { class: 'form-select form-select-sm emp-select', multiple: true }).html(renderEmpOptions()),
                $('<button>', { type: 'button', class: 'btn btn-sm btn-soft alloc-btn mt-2 w-100', disabled: true }).text('Allocate')
            ))
            .append($('<td>').append($('<button>', { type: 'button', class: 'btn btn-sm btn-icon text-danger remove-row' }).append($('<i>', { class: 'fa-solid fa-trash' }))));

        $('#billingItemsBody').append(tr);

        tr.find('.emp-select').select2({ dropdownParent: tr, width: '100%', placeholder: 'Assign employees…', closeOnSelect: false });

        state.items.push({ uid: uid, name: '', price: 0, qty: 1, employees: [] });
        recalc();
    }

    $(document).on('input', '.name-input', function () {
        const tr = $(this).closest('tr');
        const item = state.items.find((i) => i.uid === tr.data('uid'));
        if (item) { item.name = $(this).val().trim(); }
    });

    $(document).on('input change', '.price-input', function () {
        const tr = $(this).closest('tr');
        const item = state.items.find((i) => i.uid === tr.data('uid'));
        if (item) { item.price = parseFloat($(this).val()) || 0; recalc(); }
    });

    $(document).on('input change', '.qty-input', function () {
        const tr = $(this).closest('tr');
        const item = state.items.find((i) => i.uid === tr.data('uid'));
        if (item) { item.qty = Math.max(1, parseInt($(this).val(), 10) || 1); recalc(); }
    });

    $(document).on('change', '.emp-select', function () {
        const tr = $(this).closest('tr');
        const item = state.items.find((i) => i.uid === tr.data('uid'));
        if (!item) return;
        const ids = $(this).val() || [];
        item.employees = ids.map((id) => ({ id: parseInt(id, 10), amount: 0 }));
        tr.find('.alloc-btn').prop('disabled', ids.length === 0);
    });

    $(document).on('click', '.remove-row', function () {
        const tr = $(this).closest('tr');
        const uid = tr.data('uid');
        tr.find('.select2-container').remove();
        tr.remove();
        state.items = state.items.filter((i) => i.uid !== uid);
        recalc();
    });

    $('#btnAddServiceRow').on('click', () => addItemRow());

    /* ---------- Allocation ---------- */
    let allocUid = null;

    $(document).on('click', '.alloc-btn', function () {
        const tr = $(this).closest('tr');
        allocUid = tr.data('uid');
        const item = state.items.find((i) => i.uid === allocUid);
        if (!item || !item.employees.length) return;

        const total = item.price * item.qty;
        $('#allocServiceTotal').text(money(total));
        const even = total / item.employees.length;

        let html = '';
        item.employees.forEach((emp, idx) => {
            const name = BILLING.employees.find((e) => e.id === emp.id);
            html += '<div class="d-flex align-items-center gap-2 mb-2">'
                + '<span class="flex-grow-1 small fw-semibold">' + (name ? name.name : emp.id) + '</span>'
                + '<div class="input-group input-group-sm" style="width:140px"><span class="input-group-text">₹</span>'
                + '<input type="number" class="form-control text-end alloc-amount" step="0.01" min="0" value="' + (emp.amount > 0 ? emp.amount : even.toFixed(2)) + '"></div>'
                + '</div>';
        });
        $('#allocRows').html(html);
        $('#allocSum').text(money(even * item.employees.length));
        $('#allocError').addClass('d-none');
        $('#allocModal').modal('show');
        updateAllocSum();
    });

    function updateAllocSum() {
        const item = state.items.find((i) => i.uid === allocUid);
        if (!item) return;
        let sum = 0;
        $('.alloc-amount').each(function () {
            sum += parseFloat($(this).val()) || 0;
        });
        $('#allocSum').text(money(sum));
        const total = item.price * item.qty;
        $('#allocError').toggleClass('d-none', Math.abs(sum - total) < 0.009);
        $('#allocSave').prop('disabled', Math.abs(sum - total) >= 0.009);
    }

    $(document).on('input', '.alloc-amount', updateAllocSum);

    $('#allocSave').on('click', function () {
        const item = state.items.find((i) => i.uid === allocUid);
        if (!item) return;
        item.employees.forEach((emp, idx) => {
            emp.amount = parseFloat($('.alloc-amount').eq(idx).val()) || 0;
        });
        $('#allocModal').modal('hide');
        recalc();
    });

    /* ---------- Totals ---------- */
    function walletBalance() {
        return state.customer ? parseFloat(state.customer.credits) || 0 : 0;
    }

    function calc() {
        const total = state.items.reduce((s, i) => s + i.price * i.qty, 0);
        const balance = walletBalance();
        $('#tTotal').text(money(total));
        $('#tPayable').text(money(total));
        $('#tBalanceAfter').text(money(balance - total))
            .toggleClass('text-danger', (balance - total) < 0)
            .toggleClass('text-success', (balance - total) >= 0);
        return total;
    }

    function recalc() {
        calc();
    }

    $('#billNotes').on('input', function () {
        state.notes = $(this).val() || '';
    });

    /* ---------- Submit ---------- */
    function buildPayload(flags) {
        flags = flags || {};
        const data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            customer_id: state.customer ? state.customer.id : '',
            package_used: state.customer ? calc() : 0,
            notes: state.notes,
            draft: flags.draft ? 1 : 0
        };
        if (flags.top_up) { data.top_up = flags.top_up; }
        state.items.forEach((item, idx) => {
            data['items_name[' + idx + ']'] = item.name;
            data['items_price[' + idx + ']'] = item.price;
            data['items_qty[' + idx + ']'] = item.qty;
            item.employees.forEach((emp, j) => {
                data['alloc_employee[' + idx + '][' + j + ']'] = emp.id;
                data['alloc_amount[' + idx + '][' + j + ']'] = emp.amount;
            });
        });
        return data;
    }

    function submitBill(draft) {
        if (!state.customer) {
            Swal.fire({ icon: 'warning', title: 'No customer selected', text: 'Search and select a customer first.' });
            return;
        }
        const validItems = state.items.filter((i) => i.name && i.price > 0);
        if (!validItems.length) {
            Swal.fire({ icon: 'warning', title: 'No services', text: 'Add at least one service with a name and price.' });
            return;
        }
        for (const item of state.items) {
            if (item.name && item.price > 0 && item.employees.length && Math.abs(item.employees.reduce((s, e) => s + e.amount, 0) - item.price * item.qty) > 0.009) {
                Swal.fire({ icon: 'warning', title: 'Allocation incomplete', text: 'Complete employee allocation for "' + item.name + '".' });
                return;
            }
        }

        const total = calc();
        const balance = walletBalance();

        if (!draft && total > balance) {
            showTopUpModal(total, balance);
            return;
        }

        createTransaction(draft);
    }

    function showTopUpModal(total, balance) {
        const shortfall = total - balance;
        $('#exceedTotal').text(money(total));
        $('#exceedBalance').text(money(balance));
        $('#exceedShortfall').text(money(shortfall));
        $('#topUpAmount').val(shortfall.toFixed(2));
        $('#exceedModal').modal('show');
    }

    $('#btnTopUpAndCreate').on('click', function () {
        const val = parseFloat($('#topUpAmount').val()) || 0;
        const shortfall = (calc() - walletBalance());
        if (val < 0.01 || val < shortfall - 0.01) {
            Swal.fire({ icon: 'warning', title: 'Top-up too small', text: 'The top-up must cover the shortfall of ' + money(shortfall) + '.' });
            return;
        }
        $('#exceedModal').modal('hide');
        createTransaction(false, val);
    });

    function createTransaction(draft, topUp) {
        Swal.fire({
            title: draft ? 'Save as draft?' : 'Create transaction?',
            text: topUp ? 'A lifetime wallet top-up of ' + money(topUp) + ' will be added and applied to this transaction.' : 'The wallet will be charged for this transaction.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: draft ? 'Save Draft' : 'Create Transaction',
            confirmButtonColor: '#10b981',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;
            const btn = draft ? '#btnSaveDraft' : '#btnGenerateInvoice';
            $(btn).prop('disabled', true);

            $.ajax({
                url: '/billing/store',
                method: 'POST',
                data: buildPayload(overrunFlag(draft, topUp)),
                dataType: 'json'
            }).done((res) => {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false })
                        .then(() => {
                            window.location.href = '/billing/invoice/' + res.invoice_id;
                        });
                } else {
                    Swal.fire({ icon: 'error', title: res.message });
                    $(btn).prop('disabled', false);
                }
            }).fail((xhr) => {
                const res = xhr.responseJSON || {};
                Swal.fire({ icon: 'error', title: res.message || 'Billing failed. Please try again.' });
                $(btn).prop('disabled', false);
            });
        });
    }

    function overrunFlag(draft, topUp) {
        const data = { draft: draft };
        if (topUp) { data.top_up = topUp; }
        return data;
    }

    $('#btnGenerateInvoice').on('click', () => submitBill(false));
    $('#btnSaveDraft').on('click', () => submitBill(true));
    $('#btnCancelBill').on('click', () => {
        Swal.fire({
            title: 'Cancel transaction?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Clear', confirmButtonColor: '#dc2626', cancelButtonText: 'Keep Editing'
        }).then((r) => {
            if (r.isConfirmed) window.location.reload();
        });
    });

    /* ---------- Create customer from search ---------- */
    function openCreateCustomerModal() {
        $('#createCustomerForm input:not([type=hidden])').removeClass('is-invalid').val('');
        $('#createCustomerModal .invalid-feedback').addClass('d-none').html('');
        $('#createCustomerModal').modal('show');
        setTimeout(() => $('#ccName').focus(), 300);
    }

    $('#btnCreateCustomer').on('click', function () {
        const form = $('#createCustomerForm')[0];
        if (!form.name.value.trim()) {
            $('#ccName').addClass('is-invalid');
            return;
        }
        const btn = $(this).prop('disabled', true);
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
                Swal.fire({ icon: 'success', title: 'Customer created', timer: 1200, showConfirmButton: false });
                selectCustomer(res.customer);
            } else {
                showCcErrors(res.errors);
                btn.prop('disabled', false);
            }
        }).fail((xhr) => {
            const res = xhr.responseJSON || {};
            if (res.errors) showCcErrors(res.errors);
            else Swal.fire({ icon: 'error', title: res.message || 'Could not create customer.' });
            btn.prop('disabled', false);
        });
    });

    function showCcErrors(errors) {
        $('#createCustomerModal input').removeClass('is-invalid');
        $('#createCustomerModal .invalid-feedback').addClass('d-none').html('');
        if (!errors) return;
        for (const key in errors) {
            const input = $('#createCustomerModal [name="' + key + '"]');
            if (input.length) {
                input.addClass('is-invalid');
                const fb = input.closest('.mb-3').find('.invalid-feedback').length ? input.closest('.mb-3').find('.invalid-feedback') : input.siblings('.invalid-feedback');
                fb.removeClass('d-none').html(Array.isArray(errors[key]) ? errors[key].join(' ') : errors[key]);
            }
        }
    }

    $('input', $('#createCustomerForm')).on('input', function () {
        $(this).removeClass('is-invalid');
    });
    $('#createCustomerModal').on('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            $('#btnCreateCustomer').trigger('click');
        }
    });

    /* ---------- Init ---------- */
    window.NHS.init = function () {
        addItemRow();
        if (BILLING.preselectCustomerId) {
            $.getJSON('/billing/customer/' + BILLING.preselectCustomerId)
                .done((res) => {
                    if (res.success) selectCustomer(res.customer);
                });
        }
    };
})(jQuery);
