<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLessons" aria-labelledby="offcanvasLessonsLabel">
    <div class="offcanvas-header mt-4">
        <h5 id="offcanvasLessonsLabel">Generate Lessons</h5>
        <button class="courscribe-close-button btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <span class="X"></span>
            <span class="Y"></span>
            <div class="courscribe-close-close">Close</div>
        </button>
    </div>
    <div class="offcanvas-body">
        <form id="courscribe-generate-lessons-form">
            <div class="options">
                <div class="btns-add">
                    <div>
                        <label for="lesson-tone">Tone</label>
                        <select id="lesson-tone" class="form-control bg-dark text-light">
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
                        <label for="lesson-audience">Audience</label>
                        <select id="lesson-audience" class="form-control bg-dark text-light">
                            <option value="Beginners">Beginners</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Experts">Experts</option>
                            <option value="Children">Children</option>
                            <option value="Adults">Adults</option>
                        </select>
                    </div>
                    <div>
                        <label for="lesson-count">Number of Lessons</label>
                        <select id="lesson-count" class="form-control bg-dark text-light">
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
                <textarea class="ai-input-field" id="lesson-instructions" placeholder="Type additional instructions here (e.g., 'Include hands-on activities')..."></textarea>
                <button type="submit" class="ai-send-button" id="courscribe-generate-lessons">
                    <div class="ai-send-icon"></div>
                </button>
                <div class="ai-input-info">
                    <span class="ai-input-hint">Type additional instructions and press Generate</span>
                </div>
            </div>
        </form>

        <!-- Generated Lessons Section -->
        <div id="courscribe-generated-lessons" class="mt-4" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Generated Lessons</h6>
                <button class="btn courscribe-stepper-nextBtn" id="courscribe-select-all-lessons">Select All</button>
            </div>
            <div id="courscribe-lessons-list">
                <!-- Generated lessons will be dynamically added here -->
            </div>
            <div class="mt-3">
                <button class="add-objective" id="courscribe-add-selected-lessons"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i> Add Selected Lessons</button>
                
                <button id="courscribe-regenerate-lessons" class="get-ai-button min-w-150" >
                    <span class="get-ai-inner">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                            <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                        </svg>
                        Regenerate
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>