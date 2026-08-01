/* ============================================================
   Billing (POS) — primary page logic
   ============================================================ */
(function ($) {
    'use strict';

    const money = (v) => '₹' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const state = {
        customer: null,
        items: [],
        payments: [],
        discount: 0,
        gstPercent: parseFloat($('#tGstPercent').val() || 18),
        usePackage: false,
        packageUsed: 0
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
        if (!list.length) {
            $results.html('<div class="list-group-item text-muted small py-3 text-center">No customers found. Create one from the Customers page.</div>').removeClass('d-none');
            return;
        }
        let html = '';
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
        $results.html('<div class="list-group list-group-flush">' + html + '</div>').removeClass('d-none');
    }

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

        // package availability
        if (c.credits > 0) {
            $('#pkgAvailable').text(money(c.credits));
            $('#pkgName').text('Credits available');
            $('#tUsePackage').prop('disabled', false);
            $('#tUsePackage').prop('checked', true);
            $('#pkgRow').show();
            state.usePackage = true;
        } else {
            $('#pkgAvailable').text('₹0.00');
            $('#pkgName').text('—');
            $('#tUsePackage').prop('disabled', true).prop('checked', false);
            $('#pkgRow').hide();
            state.usePackage = false;
        }
        $('#tOutstanding').text(money(c.outstanding));
        recalc();
    }

    $(document).on('click', '#btnChangeCustomer', function () {
        state.customer = null;
        $input.val('').focus();
        $('#customerCard').addClass('d-none').html('');
        $('#pkgAvailable').text('₹0.00');
        $('#pkgName').text('—');
        $('#tUsePackage').prop('disabled', true).prop('checked', false);
        $('#pkgRow').hide();
        state.usePackage = false;
        recalc();
    });

    $('#customerSearchInput').on('input', customerSearch);
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.customer-search-wrap').length) {
            $results.addClass('d-none');
        }
    });

    /* ---------- Service rows ---------- */
    function renderServiceOptions(selected) {
        let opts = '<option value="">Choose service…</option>';
        BILLING.services.forEach((s) => {
            opts += '<option value="' + s.id + '" data-name="' + s.name + '" data-price="' + s.price + '"' + (selected == s.id ? ' selected' : '') + '>' + s.name + ' — ₹' + s.price.toFixed(2) + '</option>';
        });
        return opts;
    }

    function renderEmpOptions(selected) {
        let opts = '';
        BILLING.employees.forEach((e) => {
            opts += '<option value="' + e.id + '"' + ((selected || []).includes(e.id) ? ' selected' : '') + '>' + e.name + '</option>';
        });
        return opts;
    }

    function addItemRow(serviceId) {
        if (!$('#billingItemsBody tr:first').hasClass('bill-item-row')) {
            $('#billingItemsBody').html('');
        }
        const uid = nextUid();
        const tr = $('<tr>', { class: 'bill-item-row', 'data-uid': uid })
            .append($('<td>').append($('<select>', { class: 'form-select form-select-sm service-select' }).html(renderServiceOptions(serviceId))))
            .append($('<td>').append($('<div>', { class: 'input-group input-group-sm' }).append($('<span>', { class: 'input-group-text' }).text('₹')).append($('<input>', { class: 'form-control text-end price-input', type: 'number', step: '0.01', min: '0', value: '0' }))))
            .append($('<td>').append($('<input>', { class: 'form-control form-control-sm text-center qty-input', type: 'number', min: '1', value: '1' })))
            .append($('<td>').append(
                $('<select>', { class: 'form-select form-select-sm emp-select', multiple: true }).html(renderEmpOptions()),
                $('<button>', { type: 'button', class: 'btn btn-sm btn-soft alloc-btn mt-2 w-100', disabled: true }).text('Allocate')
            ))
            .append($('<td>').append($('<button>', { type: 'button', class: 'btn btn-sm btn-icon text-danger remove-row' }).append($('<i>', { class: 'fa-solid fa-trash' }))));

        $('#billingItemsBody').append(tr);

        tr.find('.service-select').select2({ dropdownParent: tr, width: '100%', placeholder: 'Choose service…' });
        tr.find('.emp-select').select2({ dropdownParent: tr, width: '100%', placeholder: 'Assign employees…', closeOnSelect: false });

        state.items.push({ uid: uid, service_id: serviceId || 0, name: '', price: 0, qty: 1, employees: [] });

        if (serviceId) {
            const svc = BILLING.services.find((s) => s.id === serviceId);
            if (svc) {
                setRowService(uid, svc);
            }
        }
        recalc();
    }

    function setRowService(uid, svc) {
        const item = state.items.find((i) => i.uid === uid);
        if (!item) return;
        item.service_id = svc.id;
        item.name = svc.name;
        item.price = svc.price;
        const tr = $('tr[data-uid="' + uid + '"]');
        tr.find('.price-input').val(svc.price.toFixed(2));
    }

    $(document).on('change', '.service-select', function () {
        const tr = $(this).closest('tr');
        const uid = tr.data('uid');
        const id = parseInt($(this).val(), 10);
        const svc = BILLING.services.find((s) => s.id === id);
        if (svc) setRowService(uid, svc);
        else {
            const item = state.items.find((i) => i.uid === uid);
            if (item) { item.service_id = 0; item.name = ''; item.price = 0; }
        }
        recalc();
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

    $('#btnAddServiceRow').on('click', () => addItemRow(0));

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

    /* ---------- Payments ---------- */
    function addPaymentRow(method, amount, reference) {
        const row = $('<div>', { class: 'payment-row d-flex gap-1 align-items-center mb-2' })
            .append($('<select>', { class: 'form-select form-select-sm pay-method', style: 'width:100px' })
                .append('<option value="cash">Cash</option><option value="card">Card</option><option value="upi">UPI</option><option value="bank">Bank</option><option value="other">Other</option>').val(method || 'cash'))
            .append($('<input>', { class: 'form-control form-control-sm pay-amount text-end', type: 'number', step: '0.01', min: '0', value: (amount || ''), placeholder: 'Amount' }))
            .append($('<input>', { class: 'form-control form-control-sm pay-reference d-none', type: 'text', value: (reference || ''), placeholder: 'Ref / UTR' }))
            .append($('<button>', { type: 'button', class: 'btn btn-sm btn-icon text-danger remove-payment' }).append($('<i>', { class: 'fa-solid fa-xmark' })));

        $('#paymentRows').append(row);
        row.find('.pay-method').on('change', function () {
            $(this).closest('.payment-row').find('.pay-reference').toggleClass('d-none', ['cash'].includes($(this).val()));
        });
        updateReceived();
    }

    $(document).on('input', '.pay-amount', updateReceived);
    $(document).on('click', '.remove-payment', function () {
        $(this).closest('.payment-row').remove();
        updateReceived();
    });
    $('#btnAddPayment').on('click', () => addPaymentRow('cash', '', ''));

    function collectPayments() {
        const list = [];
        $('.payment-row').each(function () {
            const method = $(this).find('.pay-method').val();
            const amount = parseFloat($(this).find('.pay-amount').val()) || 0;
            const reference = $(this).find('.pay-reference').val() || '';
            if (amount > 0) list.push({ method: method, amount: amount, reference: reference });
        });
        return list;
    }

    function updateReceived() {
        const payable = calc();
        let received = 0;
        $('.pay-amount').each(function () {
            received += parseFloat($(this).val()) || 0;
        });
        const overpaid = received > payable && payable > 0;
        $('#tReceived').text(money(overpaid ? payable : received));
        $('#tDue').text(money(Math.max(0, payable - received)));
        $('#payError').toggleClass('d-none', !overpaid);
        if (overpaid) {
            $('#payErrorMsg').text('Payments exceed the balance of ' + money(payable) + '. Amount is limited to the payable balance.');
        }
        return { payable, received, overpaid };
    }

    /* ---------- Totals ---------- */
    function calc() {
        const subtotal = state.items.reduce((s, i) => s + i.price * i.qty, 0);
        state.discount = Math.min(parseFloat($('#tDiscount').val()) || 0, subtotal);
        state.gstPercent = parseFloat($('#tGstPercent').val()) || 0;
        const gstAmount = (subtotal - state.discount) * state.gstPercent / 100;
        const total = subtotal - state.discount + gstAmount;

        let available = state.customer ? parseFloat(state.customer.credits) || 0 : 0;
        if (state.usePackage && available > 0) {
            let used = Math.min(total, available);
            if (used !== state.packageUsed) {
                state.packageUsed = used;
                $('#tPackageUsed').val(used.toFixed(2));
            }
            $('#tBalanceAfter').text(money(available - used));
        } else {
            state.packageUsed = 0;
            $('#tPackageUsed').val('0');
            $('#tBalanceAfter').text(money(available));
        }

        $('#tSubtotal').text(money(subtotal));
        $('#tGstAmount').text(money(gstAmount));
        $('#tTotal').text(money(total));
        $('#tPayable').text(money(total - state.packageUsed));

        if (available > 0) $('#pkgAvailable').text(money(available));
        return total - state.packageUsed;
    }

    function recalc() {
        calc();
        updateReceived();
    }

    $('#tDiscount').on('input', recalc);
    $('#tGstPercent').on('input', recalc);
    $('#tPackageUsed').on('input', recalc);
    $('#tUsePackage').on('change', function () {
        state.usePackage = $(this).is(':checked');
        $('#pkgRow').toggle(state.usePackage);
        if (state.usePackage && state.customer) {
            const available = parseFloat(state.customer.credits) || 0;
            const total = parseFloat($('#tTotal').text().replace('₹', '').replace(/,/g, '')) || 0;
            $('#tPackageUsed').val(Math.min(total, available).toFixed(2));
        }
        recalc();
    });

    /* ---------- Submit ---------- */
    function buildPayload(draft) {
        const data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            customer_id: state.customer ? state.customer.id : '',
            discount: state.discount,
            gst_percent: state.gstPercent,
            use_package: state.usePackage ? 1 : 0,
            package_used: state.usePackage ? state.packageUsed : 0,
            notes: $('#billNotes').val() || '',
            draft: draft ? 1 : 0
        };
        state.items.forEach((item, idx) => {
            data['items_service[' + idx + ']'] = item.service_id;
            data['items_name[' + idx + ']'] = item.name;
            data['items_price[' + idx + ']'] = item.price;
            data['items_qty[' + idx + ']'] = item.qty;
            item.employees.forEach((emp, j) => {
                data['alloc_employee[' + idx + '][' + j + ']'] = emp.id;
                data['alloc_amount[' + idx + '][' + j + ']'] = emp.amount;
            });
        });
        const payments = collectPayments();
        payments.forEach((p, idx) => {
            data['pay_method[' + idx + ']'] = p.method;
            data['pay_amount[' + idx + ']'] = p.amount;
            data['pay_reference[' + idx + ']'] = p.reference;
        });
        return data;
    }

    function submitBill(draft) {
        if (!state.customer) {
            Swal.fire({ icon: 'warning', title: 'No customer selected', text: 'Search and select a customer first.' });
            return;
        }
        const validItems = state.items.filter((i) => i.service_id && i.price > 0);
        if (!validItems.length) {
            Swal.fire({ icon: 'warning', title: 'No services', text: 'Add at least one service to the bill.' });
            return;
        }
        for (const item of state.items) {
            const allocSum = item.employees.reduce((s, e) => s + e.amount, 0);
            if (item.employees.length && Math.abs(allocSum - item.price * item.qty) > 0.009) {
                Swal.fire({ icon: 'warning', title: 'Allocation incomplete', text: 'Complete employee allocation for "' + item.name + '".' });
                return;
            }
        }

        const payable = calc();
        const received = collectPayments().reduce((s, p) => s + p.amount, 0);
        if (received > payable) {
            Swal.fire({ icon: 'error', title: 'Payments exceed balance', text: 'Payments of ' + money(received) + ' exceed the balance of ' + money(payable) + '.' });
            return;
        }

        Swal.fire({
            title: draft ? 'Save as draft?' : 'Generate invoice?',
            text: draft ? 'This bill will be saved as a draft.' : 'The invoice will be generated and payments recorded.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: draft ? 'Save Draft' : 'Generate Invoice',
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
                data: buildPayload(draft),
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

    $('#btnGenerateInvoice').on('click', () => submitBill(false));
    $('#btnSaveDraft').on('click', () => submitBill(true));
    $('#btnCancelBill').on('click', () => {
        Swal.fire({
            title: 'Cancel bill?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Clear Bill', confirmButtonColor: '#dc2626', cancelButtonText: 'Keep Editing'
        }).then((r) => {
            if (r.isConfirmed) window.location.reload();
        });
    });

    /* ---------- Init ---------- */
    window.NHS.init = function () {
        addPaymentRow('cash', '', '');
        if (BILLING.preselectCustomerId) {
            $.getJSON('/billing/customer/' + BILLING.preselectCustomerId)
                .done((res) => {
                    if (res.success) selectCustomer(res.customer);
                });
        }
    };
})(jQuery);
