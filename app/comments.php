    <!-- Modal Structure -->
    <div class="modal fade" id="commentModal" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="commentModalLabel">Comments</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <!-- Comments Section -->
                    <div id="commentsSection" class="mb-3">
                        <!-- Existing comments will be loaded here -->
                    </div>

                </div>
                <div class="modal-footer border-top-0" style="justify-content:space-around !important">
                    <!-- Input Field -->
                    <div class="d-flex align-items-center">
                        <textarea id="myComment" class="form-control" rows="2" placeholder="Add a comment..." aria-label="Add a comment"></textarea>
                        <button id="sendMyComment" class="btn btn-primary ml-2" onclick="sendComment()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> -->
                </div>
            </div>
        </div>
    </div>