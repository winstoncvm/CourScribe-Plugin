(function ($) {
    $(document).ready(function () {
        console.log('Courscribe Dashboard JS loaded');

        // Fetch dashboard stats
        function fetchDashboardStats() {
            if (!courscribeAjax || !courscribeAjax.ajaxurl || !courscribeAjax.nonce) {
                console.error('courscribeAjax not properly localized');
                $('#courscribe-stats').html('<p class="text-danger">Error: AJAX configuration missing.</p>');
                return;
            }

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_dashboard_stats',
                    nonce: courscribeAjax.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $('#stats-studios').text(response.data.studios);
                        $('#stats-curriculums').text(response.data.curriculums);
                        $('#stats-courses').text(response.data.courses);
                        $('#stats-modules').text(response.data.modules);
                        $('#stats-lessons').text(response.data.lessons);
                        $('#stats-active-users').text(response.data.active_users);

                        // Populate recent logs
                        let logsHtml = '';
                        if (response.data.recent_logs.length > 0) {
                            response.data.recent_logs.forEach(function (log) {
                                logsHtml += `
                                    <tr>
                                        <td>${log.timestamp}</td>
                                        <td>${log.user_login || 'Unknown'}</td>
                                        <td>${log.action.replace(/_/g, ' ').toUpperCase()}</td>
                                        <td>${log.course_id}</td>
                                        <td>${JSON.parse(log.changes).message || 'No details'}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            logsHtml = '<tr><td colspan="5">No recent activity.</td></tr>';
                        }
                        $('#courscribe-recent-logs').html(logsHtml);
                    } else {
                        console.error('Error fetching stats:', response.data?.message);
                        $('#courscribe-stats').html('<p class="text-danger">Error: ' + (response.data?.message || 'Unknown error') + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error fetching stats:', status, error);
                    $('#courscribe-stats').html('<p class="text-danger">Error loading stats: ' + error + '</p>');
                },
            });
        }

        // Initialize
        fetchDashboardStats();
    });
})(jQuery);