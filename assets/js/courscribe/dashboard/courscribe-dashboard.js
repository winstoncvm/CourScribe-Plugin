document.addEventListener('DOMContentLoaded', function() {
    // Hide loader after 10 seconds
    setTimeout(function() {
        const loader = document.getElementById('courscribe-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }, 10000);

    // Initialize Studios Dashboard
    if (document.querySelector('#courscribe-studios-stats')) {
        fetchStudiosStats();
    }

    // Initialize Courses Dashboard
    if (document.querySelector('#courscribe-courses-stats')) {
        fetchCoursesStats();
    }

    // Initialize Modules Dashboard
    if (document.querySelector('#courscribe-modules-stats')) {
        fetchModulesStats();
    }

    // Initialize Lessons Dashboard
    if (document.querySelector('#courscribe-lessons-stats')) {
        fetchLessonsStats();
    }

    // Initialize Settings Form
    const settingsForm = document.querySelector('.courscribe-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            const loader = document.getElementById('courscribe-loader');
            if (loader) {
                loader.style.display = 'flex';
            }
        });
    }

    // Initialize Studio Page Invite Form
    const inviteForm = document.querySelector('.courscribe-invite-form');
    if (inviteForm) {
        inviteForm.addEventListener('submit', function(e) {
            const loader = document.getElementById('courscribe-loader');
            if (loader) {
                loader.style.display = 'flex';
            }
        });
    }

    function fetchStudiosStats() {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', courscribeAjax.ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateStudiosStats(response.data);
                }
            }
        };
        xhr.send('action=courscribe_get_studios_stats&nonce=' + courscribeAjax.nonce);
    }

    function updateStudiosStats(data) {
        document.getElementById('stats-total-studios').textContent = data.stats.total_studios;
        document.getElementById('stats-total-curriculums').textContent = data.stats.total_curriculums;
        document.getElementById('stats-total-collaborators').textContent = data.stats.total_collaborators;
        document.getElementById('stats-active-users').textContent = data.stats.active_users;

        const ctx = document.getElementById('activity-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: data.activity_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: true, title: { display: true, text: 'Date', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } },
                    y: { display: true, title: { display: true, text: 'Actions', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } }
                },
                plugins: {
                    legend: { labels: { color: '#FFFFFF' } }
                }
            }
        });

        document.querySelectorAll('.studio-logs').forEach(function(logTable) {
            const studioId = logTable.getAttribute('data-studio-id');
            const logs = data.logs[studioId] || [];
            logTable.innerHTML = logs.length ? logs.map(log => `
                <tr>
                    <td>${log.timestamp}</td>
                    <td>${log.user}</td>
                    <td>${log.action}</td>
                    <td>${log.studio_id}</td>
                    <td>${log.details}</td>
                </tr>
            `).join('') : '<tr><td colspan="5">No logs available.</td></tr>';
        });
    }

    function fetchCoursesStats() {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', courscribeAjax.ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateCoursesStats(response.data);
                }
            }
        };
        xhr.send('action=courscribe_get_courses_stats&nonce=' + courscribeAjax.nonce);
    }

    function updateCoursesStats(data) {
        document.getElementById('stats-total-courses').textContent = data.stats.total_courses;
        document.getElementById('stats-total-modules').textContent = data.stats.total_modules;
        document.getElementById('stats-total-lessons').textContent = data.stats.total_lessons;
        document.getElementById('stats-active-users').textContent = data.stats.active_users;

        const ctx = document.getElementById('activity-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: data.activity_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: true, title: { display: true, text: 'Date', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } },
                    y: { display: true, title: { display: true, text: 'Actions', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } }
                },
                plugins: {
                    legend: { labels: { color: '#FFFFFF' } }
                }
            }
        });

        document.querySelectorAll('.course-logs').forEach(function(logTable) {
            const curriculumId = logTable.getAttribute('data-curriculum-id');
            const logs = data.logs[curriculumId] || [];
            
            logTable.innerHTML = logs.length ? logs.map(log => `
                <tr>
                    <td>${log.timestamp}</td>
                    <td>${log.user}</td>
                    <td>${log.action}</td>
                    <td>${log.course_id}</td>
                    <td>${log.details}</td>
                </tr>
            `).join('') : '<tr><td colspan="5">No logs available.</td></tr>';
        });
    }

    function fetchModulesStats() {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', courscribeAjax.ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateModulesStats(response.data);
                }
            }
        };
        xhr.send('action=courscribe_get_modules_stats&nonce=' + courscribeAjax.nonce);
    }

    function updateModulesStats(data) {
        document.getElementById('stats-total-modules').textContent = data.stats.total_modules;
        document.getElementById('stats-total-lessons').textContent = data.stats.total_lessons;
        document.getElementById('stats-active-users').textContent = data.stats.active_users;

        const ctx = document.getElementById('activity-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: data.activity_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: true, title: { display: true, text: 'Date', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } },
                    y: { display: true, title: { display: true, text: 'Actions', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } }
                },
                plugins: {
                    legend: { labels: { color: '#FFFFFF' } }
                }
            }
        });

        document.querySelectorAll('.module-logs').forEach(function(logTable) {
            const courseId = logTable.getAttribute('data-course-id');
            const logs = data.logs[courseId] || [];
            logTable.innerHTML = logs.length ? logs.map(log => `
                <tr>
                    <td>${log.timestamp}</td>
                    <td>${log.user}</td>
                    <td>${log.action}</td>
                    <td>${log.module_id}</td>
                    <td>${log.details}</td>
                </tr>
            `).join('') : '<tr><td colspan="5">No logs available.</td></tr>';
        });
    }

    function fetchLessonsStats() {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', courscribeAjax.ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateLessonsStats(response.data);
                }
            }
        };
        xhr.send('action=courscribe_get_lessons_stats&nonce=' + courscribeAjax.nonce);
    }

    function updateLessonsStats(data) {
        document.getElementById('stats-total-lessons').textContent = data.stats.total_lessons;
        document.getElementById('stats-active-users').textContent = data.stats.active_users;

        const ctx = document.getElementById('activity-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: data.activity_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: true, title: { display: true, text: 'Date', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } },
                    y: { display: true, title: { display: true, text: 'Actions', color: '#FFFFFF' }, ticks: { color: '#FFFFFF' } }
                },
                plugins: {
                    legend: { labels: { color: '#FFFFFF' } }
                }
            }
        });

        document.querySelectorAll('.lesson-logs').forEach(function(logTable) {
            const moduleId = logTable.getAttribute('data-module-id');
            const logs = data.logs[moduleId] || [];
            logTable.innerHTML = logs.length ? logs.map(log => `
                <tr>
                    <td>${log.timestamp}</td>
                    <td>${log.user}</td>
                    <td>${log.action}</td>
                    <td>${log.lesson_id}</td>
                    <td>${log.details}</td>
                </tr>
            `).join('') : '<tr><td colspan="5">No logs available.</td></tr>';
        });
    }
});