
function handleSave(post_id) {
    var title = jQuery(`#title-${post_id}`).val();
    var topic = jQuery(`#topic-${post_id}`).val();
    var goal = jQuery(`#goal-${post_id}`).val();
    var notes = tinyMCE.get(`notes-${post_id}`).getContent();   
    const studio = parseInt(ajax_object.group_id) || 0;
    // Check if the user is a group admin or in 'can_edit'
    // $is_group_admin = groups_is_user_admin( $user_id, $studio );
    // $is_group_mod = groups_is_user_mod( $user_id, $studio );
    // $can_edit_users = get_post_meta( $post_id, 'can_edit', true );
    // $can_edit_users = is_array( $can_edit_users ) ? $can_edit_users : array();

    // if ( ! ( $is_group_admin || $is_group_mod || in_array( $user_id, $can_edit_users ) ) ) {
    //     wp_send_json_error( 'You do not have permission to edit this curriculum.' );
    //     exit;
    // }

    jQuery.ajax({
        url: ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'save_curriculum',
            security: ajax_object.security,
            post_id: post_id,
            topic: topic,
            goal: goal,
            notes: notes,
            studio: studio,  // Access the group ID here
            title: title
        },
        success: function(response) {
            if (response.success) {
                alert('Curriculum saved successfully!');
            } else {
                alert('An error occurred while saving.');
            }
        },
        error: function() {
            alert('An error occurred while saving.');
        }
    });
}

function handleDelete(post_id) {
    if (!confirm('Are you sure you want to delete this curriculum?')) {
        return;
    }

    jQuery.ajax({
        url: ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'delete_curriculum',
            security: ajax_object.security,
            post_id: post_id
        },
        success: function(response) {
            if (response.success) {
                alert('Curriculum deleted successfully!');
                location.reload();  // Reload the page after deletion
            } else {
                alert('Failed to delete curriculum.');
            }
        },
        error: function() {
            alert('An error occurred while deleting.');
        }
    });
}




document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission 
    document.getElementById('saveNewCurriculumBtn').addEventListener('click', function() {
        var title = jQuery('#new-title').val();
        var topic = jQuery('#new-topic').val();
        var goal = jQuery('#new-goal').val();
        var notes = jQuery('#new-notes').val();
        const studio = parseInt(ajax_object.group_id) || 0;

        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'create_curriculum',
                security: ajax_object.security,
                topic: topic,
                goal: goal,
                notes: notes,
                title: title,
                studio: studio  // Access the group ID here
            },
            success: function(response) {
                console.log(JSON.stringify(response))
                if (response.success) {
                    alert('Curriculum created successfully!');
                    location.reload(); // Reload the page to show the new curriculum
                } else {
                alert('Curriculum created successfully!');
                location.reload(); 
                }
            },
            error: function() {
                alert('An error occurred while creating.');
            }
        });
    });
    document.getElementById('addNewCurr').addEventListener('click', function() {
        var title = jQuery('#topic-new').val();
        var topic = jQuery('#topic-new').val();
        var goal = jQuery('#goal-new').val();
        var notes = jQuery('#notes-new').val();
        const studio = parseInt(ajax_object.group_id) || 0;

        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'create_curriculum',
                security: ajax_object.security,
                topic: topic,
                goal: goal,
                notes: notes,
                title: title,
                studio: studio  // Access the group ID here
            },
            success: function(response) {
                if (response.success) {
                    alert('Curriculum created successfully!');
                    location.reload(); // Reload the page to show the new curriculum
                } else {
                    alert('Failed to create curriculum.');
                }
            },
            error: function() {
                alert('An error occurred while creating.');
            }
        });
    });
});

jQuery(document).ready(function($) {
    $('#saveCourseBtn').on('click', function() {
        var courseTitle = $('#course-title').val();
        var courseContent = tinymce.get('course-content').getContent(); // Get the content from TinyMCE

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_course_to_curriculum',
                security: ajax_object.security,
                curriculum_id: ajax_object.curriculum_id, // Use the localized curriculum_id
                course_title: courseTitle,
                course_content: courseContent
            },
            success: function(response) {
                if (response.success) {
                    alert('Course added successfully!');
                    location.reload(); // Reload the page to show the new course
                } else {
                    alert('Failed to add course.');
                }
            },
            error: function() {
                alert('An error occurred while adding the course.');
            }
        });
    });
});

jQuery(document).ready(function($) {
    // Check if the localized data exists
    if (typeof teachingPointsData !== 'undefined') {
        console.log('Teaching Points:', teachingPointsData.teachingPoints); // Log teaching points to the console

        // You can loop through the data and log each teaching point if needed
        teachingPointsData.teachingPoints.forEach(function(teachingPoint) {
            console.log('Teaching Point ID:', teachingPoint.ID);
            console.log('Teaching Point Title:', teachingPoint.title);
            console.log('Method:', teachingPoint.method);
            console.log('Materials:', teachingPoint.materials);
        });
    } else {
        console.log('No teaching points data available');
    }
});

// drag-and-drop interaction

document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('media-dropzone');
    const mediaInput = document.getElementById('media');
    const mediaPreview = document.getElementById('media-preview');

    // Drag over event
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
    });

    // Drag leave event
    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
    });

    // Drop event
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');

        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // Click to open file dialog
    dropzone.addEventListener('click', function() {
        mediaInput.click();
    });

    // Handle file selection
    mediaInput.addEventListener('change', function(e) {
        const files = e.target.files;
        handleFiles(files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            const fileType = file.type;

            mediaPreview.innerHTML = ''; // Clear any previous preview

            if (fileType.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                mediaPreview.appendChild(img);
            } else if (fileType.startsWith('video/')) {
                const video = document.createElement('video');
                video.controls = true;
                video.src = URL.createObjectURL(file);
                mediaPreview.appendChild(video);
            } else if (fileType.startsWith('audio/')) {
                const audio = document.createElement('audio');
                audio.controls = true;
                audio.src = URL.createObjectURL(file);
                mediaPreview.appendChild(audio);
            } else {
                mediaPreview.innerHTML = '<p>File type not supported for preview.</p>';
            }
        }
    }
});

//update course script start
jQuery(document).ready(function($) {
    $('#save-can-edit').on('click', function() {
        var curriculumId = $(this).data('curriculum-id');
        var selectedEditors = $('#can-edit-select').val(); // Array of user IDs

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'save_can_edit_users',
                security: ajax_object.security,
                curriculum_id: curriculumId,
                can_edit_users: selectedEditors
            },
            success: function(response) {
                if (response.success) {
                    alert('Editors updated successfully.');
                } else {
                    alert('Error updating editors: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });
});

jQuery(document).ready(function($) {
    $('#can-edit-select').select2();
});

document.addEventListener('DOMContentLoaded', function() {
    const addCurriculumBtn = document.getElementById('addCurriculumBtn');
    const curriculumFormContainer = document.getElementById('curriculumFormContainer');
    const curriculumListContainer = document.getElementById('curriculumListContainer');
    const cancelCurriculumBtn = document.getElementById('cancelCurriculumBtn');

    // Show form and hide curriculum list when "Add Curriculum" is clicked
    addCurriculumBtn.addEventListener('click', function() {
    
        curriculumFormContainer.style.display = 'block';
        curriculumListContainer.style.display = 'none';
    });

    // Hide form and show curriculum list when "Cancel" is clicked
    cancelCurriculumBtn.addEventListener('click', function() {
        curriculumFormContainer.style.display = 'none';
        curriculumListContainer.style.display = 'block';
    });
     
});
document.addEventListener('DOMContentLoaded', function() {
    // Open Course Aside
    const openCourseBtn = document.getElementById('open-course-aside');
    const courseAside = document.getElementById('course-aside');
    const closeCourseAsideFooterBtn = document.getElementById('closeCourseAsideFooter');
    const closeCourseBtn = document.getElementById('closeCourseAside');
  
    openCourseBtn.addEventListener('click', function() {
      courseAside.classList.add('active');
    });
  
    closeCourseBtn.addEventListener('click', function() {
      courseAside.classList.remove('active');
    });
    closeCourseAsideFooterBtn.addEventListener('click', function() {
        courseAside.classList.remove('active');
    });
     
    // Repeat similar scripts for modules, lessons, and teaching points
  });
 