<!-- Global confirmation modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h5 class="fw-bold mb-1" id="confirmTitle">Are you sure?</h5>
                <p class="text-muted small mb-3" id="confirmMessage">This action cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmOk">Yes, proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>
