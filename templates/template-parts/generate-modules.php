<div id="generateModulesOffcanvas" class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header mt-4">
        <h5 id="offcanvasRightLabel">Generate Modules</h5>
        <button class="courscribe-close-button btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <span class="X"></span>
            <span class="Y"></span>
            <div class="courscribe-close-close">Close</div>
        </button>
    </div>
    <div class="offcanvas-body">
        <div id="courscribe-generate-modules-form">
            <div class="options">
                <div class="btns-add">
                    <div>
                        <label for="module-tone">Tone</label>
                        <select id="module-tone" class="form-control bg-dark text-light">
                            <option value="Formal">Formal</option>
                            <option value="Informal">Informal</option>
                            <option value="Humorous">Humorous</option>
                            <option value="Friendly">Friendly</option>
                            <option value="Professional">Professional</option>
                            <option value="Concise">Concise</option>
                            <option value="Detailed">Detailed</option>
                        </select>
                    </div>
                    <div>
                        <label for="module-audience">Audience</label>
                        <select id="module-audience" class="form-control bg-dark text-light">
                            <option value="Beginners">Beginners</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Experts">Experts</option>
                            <option value="Children">Children</option>
                            <option value="Adults">Adults</option>
                        </select>
                    </div>
                    <div>
                        <label for="module-count">Number of Modules</label>
                        <select id="module-count" class="form-control bg-dark text-light">
                            <option value="1">One</option>
                            <option value="2">Two</option>
                            <option value="3">Three</option>
                            <option value="4">Four</option>
                            <option value="5">Five</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ai-input-container">
                <textarea class="ai-input-field" id="module-instructions" placeholder="Type additional instructions here (e.g., 'Focus on practical examples')..."></textarea>
                <button class="ai-send-button" id="courscribe-generate-modules">
                    <div class="ai-send-icon"></div>
                </button>
                <div class="ai-input-info">
                    <span class="ai-input-hint">Type additional instructions and press Generate</span>
                </div> 
            </div>
        </div>

        <!-- Generated Modules Section -->
        <div id="courscribe-generated-modules" class="mt-4" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Generated Modules</h6>
                <button class="btn courscribe-stepper-nextBtn" id="courscribe-select-all-modules">Select All</button>
            </div>
            <div id="courscribe-modules-list">
                <!-- Generated modules will be dynamically added here -->
            </div>
            <div class="mt-3">
                <button class="btn btn-success" id="courscribe-add-selected-modules">Add Selected Modules</button>
                <button class="btn btn-secondary ms-2" id="courscribe-regenerate-modules">Regenerate</button>
            </div>
        </div>
    </div>
</div>