<div class="edit-post-modal" id="editPostModal">
    <div class="edit-post-modal-content">
        <div class="edit-post-modal-header">
            <h3>Edit Post</h3>
            <button type="button" class="close-edit-modal" data-bs-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="edit-post-input-container">
            <textarea id="modalContentTextarea" class="form-control"></textarea>
        </div>
        <div class="edit-post-footer">
            <button type="button" class="cancel-edit-btn" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="save-edit-btn" id="saveModalEditButton">Save Changes</button>
        </div>
    </div>
</div>

<div class="report-modal" id="reportModal">
    <div class="report-modal-content">
        <div class="report-modal-header">
            <h3>Why are you reporting this post?</h3>
            <button type="button" class="close-report-modal" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="report-form-container">
            <form id="reportReasonsForm">
                <div class="form-group">
                    <label for="reportReasonSelect" class="form-label">Select a reason:</label>
                    <select class="form-select" id="reportReasonSelect">
                        <option value="" selected disabled>Choose a reason</option>
                        <option value="Nudity or sexual activity">Nudity or sexual activity</option>
                        <option value="Bullying or harassment">Bullying or harassment</option>
                        <option value="Suicide, self-injury or eating disorders">Suicide, self-injury or eating disorders</option>
                        <option value="Violence, hate or exploitation">Violence, hate or exploitation</option>
                        <option value="Selling or promoting restricted items">Selling or promoting restricted items</option>
                        <option value="Scam, fraud or impersonation">Scam, fraud or impersonation</option>
                        <option value="I just don't like it">I just don't like it</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group other-reason-container" id="otherReasonContainer">
                    <label for="otherReasonTextarea" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="otherReasonTextarea" placeholder="Describe your reason here..."></textarea>
                </div>
                <input type="hidden" id="reportStreamId" value="">
            </form>
        </div>
        <div class="report-modal-footer">
            <button type="button" class="cancel-report-btn" onclick="closeReportModal()">Cancel</button>
            <button type="button" id="submitReportButton" class="submit-report-btn" onclick="submitReport()">Submit Report</button>
        </div>
    </div>
</div>

<div class="bookmarkNotification"></div>