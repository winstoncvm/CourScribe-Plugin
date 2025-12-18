<!-- Teaching Point Modal -->
<div class="modal fade" id="addTeachingPointModal" tabindex="-1" aria-labelledby="addTeachingPointLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeachingPointLabel">Create New Teaching Point for </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="teachingPointForm">


                    <!-- Method Field Group (only one method allowed) -->
                    <div id="methods-container">
                        <h5>Method</h5>
                        <div class="method-group mb-4">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="method-thinking-skill" class="form-label">Select Thinking Skill</label>
                                    <select class="form-control bg-dark text-light thinking-skill-method">
                                        <option value="Know">Know</option>
                                        <option value="Comprehend">Comprehend</option>
                                        <option value="Apply">Apply</option>
                                        <option value="Analyze">Analyze</option>
                                        <option value="Evaluate">Evaluate</option>
                                        <option value="Create">Create</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="action-verb" class="form-label text-light">Action Verb</label>
                                    <select class="form-control bg-dark text-light action-verb w-100">
                                        <option value="" disabled selected>Select Action Verb</option>
                                        <!-- Options dynamically populated based on Thinking Skill -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="teaching-strategy" class="form-label">Teaching Strategy</label>
                                    <select class="form-control bg-dark text-light teaching-strategy">
                                        <option value="" disabled selected>Select Teaching Strategy</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="method-add-link" class="form-label">Add Link</label>
                                <input type="url" class="form-control bg-dark text-light method-add-link" placeholder="Add link">
                            </div>

                        </div>
                    </div>

                    <!-- Materials Field Group (allowing multiple materials) -->
                    <div id="materials-container">
                        <h5>Materials</h5>
                        <div class="materials-group mb-4">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="materials-thinking-skill" class="form-label">Select Thinking Skill</label>
                                    <select class="form-control bg-dark text-light thinking-skill-materials">
                                        <option value="Know">Know</option>
                                        <option value="Comprehend">Comprehend</option>
                                        <option value="Apply">Apply</option>
                                        <option value="Analyze">Analyze</option>
                                        <option value="Evaluate">Evaluate</option>
                                        <option value="Create">Create</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="learner-activities" class="form-label">Learner Activities</label>
                                    <select class="form-control bg-dark text-light learner-activities">
                                        <option value="" disabled selected>Select Learner Activities</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="materials-add-link" class="form-label">Add Link</label>
                                <input type="url" class="form-control bg-dark text-light materials-add-link" placeholder="Add link">
                            </div>


                        </div>
                    </div>



                    <button type="button" class="add-objective mb-3" id="addMaterialBtn"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Another Material</button>


                    <!-- Media Upload -->
                    <div class="mb-4">
                        <h5>Media Upload</h5>
                        <div class="media-upload-wrapper" id="media-dropzone">
                            <p>Drag & drop media here, or click to select</p>
                            <input type="file" id="teaching-point-media" name="media[]" class="form-control-file" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.ppt,.pptx" multiple hidden>
                        </div>
                        <div id="media-preview"></div> <!-- Preview section -->
                    </div>

                    <script>
                        jQuery(document).ready(function($) {
                            const mediaInput = $('#teaching-point-media');
                            const mediaDropzone = $('#media-dropzone');
                            const mediaPreview = $('#media-preview');

                            // Click to select files
                            mediaDropzone.on('click', function() {
                                mediaInput.click();
                            });

                            // Drag and Drop file handling
                            mediaDropzone.on('dragover', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                $(this).addClass('dragging');
                            });

                            mediaDropzone.on('dragleave', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                $(this).removeClass('dragging');
                            });

                            mediaDropzone.on('drop', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                $(this).removeClass('dragging');

                                const files = e.originalEvent.dataTransfer.files;
                                handleFileUpload(files);
                            });

                            // Handle file input change (for click select)
                            mediaInput.on('change', function() {
                                const files = mediaInput[0].files;
                                handleFileUpload(files);
                            });

                            function handleFileUpload(files) {
                                mediaPreview.empty(); // Clear previous previews
                                $.each(files, function(index, file) {
                                    const fileReader = new FileReader();
                                    fileReader.onload = function(e) {
                                        let mediaElement = '';
                                        if (file.type.startsWith('image')) {
                                            mediaElement = `<img src="${e.target.result}" class="img-thumbnail" width="100">`;
                                        } else {
                                            mediaElement = `<p>${file.name}</p>`;
                                        }
                                        mediaPreview.append(mediaElement);
                                    };
                                    fileReader.readAsDataURL(file);
                                });
                            }
                        });
                    </script>
                </form>
            </div>

            <div class="modal-footer">
                <button id="saveTeachingPointBtnCourScribe" class="Documents-btn" >
                    <span class="folderContainer">
                        <svg
                                class="fileBack"
                                width="146"
                                height="113"
                                viewBox="0 0 146 113"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                                  d="M0 4C0 1.79086 1.79086 0 4 0H50.3802C51.8285 0 53.2056 0.627965 54.1553 1.72142L64.3303 13.4371C65.2799 14.5306 66.657 15.1585 68.1053 15.1585H141.509C143.718 15.1585 145.509 16.9494 145.509 19.1585V109C145.509 111.209 143.718 113 141.509 113H3.99999C1.79085 113 0 111.209 0 109V4Z"
                                  fill="url(#paint0_linear_117_4)"
                          ></path>
                          <defs>
                            <linearGradient
                                    id="paint0_linear_117_4"
                                    x1="0"
                                    y1="0"
                                    x2="72.93"
                                    y2="95.4804"
                                    gradientUnits="userSpaceOnUse"
                            >
                              <stop stop-color="#8F88C2"></stop>
                              <stop offset="1" stop-color="#5C52A2"></stop>
                            </linearGradient>
                          </defs>
                        </svg>
                        <svg
                                class="filePage"
                                width="88"
                                height="99"
                                viewBox="0 0 88 99"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                        >
                          <rect width="88" height="99" fill="url(#paint0_linear_117_6)"></rect>
                          <defs>
                            <linearGradient
                                    id="paint0_linear_117_6"
                                    x1="0"
                                    y1="0"
                                    x2="81"
                                    y2="160.5"
                                    gradientUnits="userSpaceOnUse"
                            >
                              <stop stop-color="white"></stop>
                              <stop offset="1" stop-color="#686868"></stop>
                            </linearGradient>
                          </defs>
                        </svg>

                        <svg
                                class="fileFront"
                                width="160"
                                height="79"
                                viewBox="0 0 160 79"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                                  d="M0.29306 12.2478C0.133905 9.38186 2.41499 6.97059 5.28537 6.97059H30.419H58.1902C59.5751 6.97059 60.9288 6.55982 62.0802 5.79025L68.977 1.18034C70.1283 0.410771 71.482 0 72.8669 0H77H155.462C157.87 0 159.733 2.1129 159.43 4.50232L150.443 75.5023C150.19 77.5013 148.489 79 146.474 79H7.78403C5.66106 79 3.9079 77.3415 3.79019 75.2218L0.29306 12.2478Z"
                                  fill="url(#paint0_linear_117_5)"
                          ></path>
                          <defs>
                            <linearGradient
                                    id="paint0_linear_117_5"
                                    x1="38.7619"
                                    y1="8.71323"
                                    x2="66.9106"
                                    y2="82.8317"
                                    gradientUnits="userSpaceOnUse"
                            >
                              <stop stop-color="#C3BBFF"></stop>
                              <stop offset="1" stop-color="#51469A"></stop>
                            </linearGradient>
                          </defs>
                        </svg>
                     </span>

                    <p class="text-for-save">Save Teaching Point</p>
                </button>

                <button class="courscribe-delete-button" data-bs-dismiss="modal">
                    <span class="text">Close</span>
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                        </svg>
                    </span>
                </button>


            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let currentLessonId = null;
            let currentLessonName = '';

            // When the "Add Teaching Point" button is clicked
            $('.add-teachingPoint').on('click', function() {
                currentLessonId = $(this).data('lesson-id');
                currentLessonName = $(this).data('lesson-name'); // Assuming you have data-lesson-name attribute in your button

                $('#addTeachingPointModal').data('lesson-id', currentLessonId);

                // Update the modal title with the lesson name
                $('#addTeachingPointLabel').text(`Create New Teaching Point for ${currentLessonName}`);
            });
            // Function to dynamically populate Teaching Strategy and Learner Activities based on Thinking Skill
            function populateOptions(thinkingSkill, targetField, options) {
                targetField.empty();
                targetField.append('<option value="" disabled selected>Select an option</option>');
                if (options[thinkingSkill]) {
                    options[thinkingSkill].forEach(function(option) {
                        targetField.append('<option value="' + option + '">' + option + '</option>');
                    });
                }
            }

            // Dynamic population for Methods (only one method group allowed)
            $('#methods-container').on('change', '.thinking-skill-method', function() {
                const teachingStrategies = {
                    'Know': ['Lecture', 'Audio', 'Video'],
                    'Comprehend': ['Discussion', 'Q & A', 'Examples'],
                    'Apply': ['Practice', 'Exercises'],
                    'Analyze': ['Case Studies', 'Problem Solving'],
                    'Evaluate': ['Critique', 'Assess'],
                    'Create': ['Design', 'Develop']
                };

                const thinkingSkill = $(this).val();
                const teachingStrategyField = $(this).closest('.method-group').find('.teaching-strategy');
                populateOptions(thinkingSkill, teachingStrategyField, teachingStrategies);
            });

            // Dynamic population for Materials (multiple materials allowed)
            $(document).on('change', '#materials-container .thinking-skill-materials', function() {
                const learnerActivities = {
                    'Know': ['Define', 'Label', 'List'],
                    'Comprehend': ['Discuss', 'Explain', 'Summarize'],
                    'Apply': ['Demonstrate', 'Practice'],
                    'Analyze': ['Analyze', 'Evaluate'],
                    'Evaluate': ['Assess', 'Judge'],
                    'Create': ['Develop', 'Design']
                };

                const thinkingSkill = $(this).val();
                const learnerActivitiesField = $(this).closest('.materials-group').find('.learner-activities');
                populateOptions(thinkingSkill, learnerActivitiesField, learnerActivities);
            });

// Remove Material
            $(document).on('click', '#materials-container .remove-material', function() {
                if ($('#materials-container .materials-group').length > 1) {
                    $(this).closest('.materials-group').remove();
                }
            });
            // Add another Material
            $('#addMaterialBtn').on('click', function() {
                const newMaterial = $('#materials-container .materials-group:first').clone();
                newMaterial.find('input, select, textarea').val(''); // Reset form fields
                $('#materials-container').append(newMaterial);
            });

            // Handle Save Teaching Point
            $('#saveTeachingPointBtnCourScribe').on('click', function() {

                const teachingPointData = {
                    action: 'finalize_teachingPoint',
                    lesson_id: $('#addTeachingPointModal').data('lesson-id'),
                    method: {
                        thinkingSkill: $('#methods-container .thinking-skill-method').val(),
                        teachingStrategy: $('#methods-container .teaching-strategy').val(),
                        addLink: $('#methods-container .method-add-link').val(),
                    },
                    materials: {
                        thinkingSkill: $('#materials-container .thinking-skill-materials').val(),
                        learnerActivities: $('#materials-container .learner-activities').val(),
                        addLink: $('#materials-container .materials-add-link').val()
                    }
                };



                // AJAX request to save the teaching point
                console.log('teaching point data', teachingPointData)
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: teachingPointData,
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload on success
                        } else {
                            alert('Failed to save teaching point: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error occurred while saving the teaching point.');
                    }
                });
            });
        });
    </script>
</div>