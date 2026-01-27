jQuery(document).ready(function($) {
    const notificationList = $('#realtime-notifications');
    if (!notificationList.length) return;

    let lastId = 0;

    function fetchNotifications() {
        if (!schoolData.browser_alerts) return;
        $.ajax({
            url: schoolData.ajaxurl,
            type: 'POST',
            data: {
                action: 'school_get_realtime_notifications',
                nonce: schoolData.nonce,
                last_id: lastId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    if (lastId === 0) notificationList.empty();
                    
                    response.data.forEach(function(item) {
                        const li = $('<li class="notification-item-enhanced">')
                            .html(`
                                <div class="notif-content">
                                    <div class="notif-title">${item.title}</div>
                                    <div class="notif-meta">
                                        <span class="notif-time-diff">${item.diff}</span>
                                        <span class="notif-timestamp">${item.timestamp}</span>
                                    </div>
                                </div>
                                <div class="notif-actions">
                                    <button class="button button-small notif-action-btn" data-id="${item.id}">تحديث</button>
                                </div>
                            `)
                            .hide();
                        notificationList.prepend(li);
                        li.fadeIn();
                        lastId = Math.max(lastId, item.id);
                    });
                    
                    // Keep only last 10
                    while (notificationList.children().length > 10) {
                        notificationList.children().last().remove();
                    }
                }
            }
        });
    }

    // Poll every 10 seconds
    setInterval(fetchNotifications, 10000);
    fetchNotifications();

    // Handle notification action button
    $(document).on('click', '.notif-action-btn', function() {
        // For now, simple refresh to show updated data
        window.location.reload();
    });

    // Auto-refresh the entire dashboard every 5 minutes (300,000 ms)
    // Only if on the dashboard page
    if ($('.school-advanced-dashboard').length > 0) {
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    }

    // Media Uploader
    $('.school-upload-logo-btn').click(function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var frame = wp.media({
            title: 'رفع شعار المؤسسة',
            multiple: false,
            button: { text: 'استخدام كشعار' }
        }).open().on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $(target).val(attachment.url);
        });
    });
});
