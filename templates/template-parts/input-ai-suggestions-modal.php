<div class="modal fade ai-suggestion-modal" id="inputAiSuggestionsModal" tabindex="-1" aria-labelledby="aiSuggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content courscribe-dark-gradient-one text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="aiSuggestionModalLabel">AI Suggestions for <span id="modal-title-target"></span></h5>
                <button class="courscribe-close-button btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="X"></span>
                    <span class="Y"></span>
                    <div class="courscribe-close-close">Close</div>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>AI Generation Options</h6>
                    <p>Refine the AI's output:</p>
                </div>
                <div id="suggestionsContainer"></div>
                <div id="ai-suggestions-results"></div>
                <div class="ai-input-container">
                    <textarea class="ai-input-field" id="chat_bot" name="ai-suggestions-additional-field" placeholder="Type additional instructions here (e.g., 'Focus on practical examples')..." aria-label="Additional AI instructions"></textarea>
                    <button class="ai-send-button" id="courscribe-ai-suggest-send">
                        <div class="ai-send-icon"></div>
                    </button>
                    <div class="ai-input-info">
                        <span class="ai-input-hint">Type additional instructions and press Generate button</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-gray-600 hover:bg-gray-700 text-white" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>