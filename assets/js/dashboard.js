import $ from 'jquery';

$(function() {
    console.log('Dashboard JS loaded');
    // Sidebar Toggle Logic
    const $sidebar = $('#sidebar');
    const $mainContent = $('#main-content');
    const $sidebarToggle = $('#sidebar-toggle');
    const $mobileOverlay = $('#mobile-overlay');
    const $body = $('body');
    
    // Check local storage for collapsed state
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        $sidebar.addClass('w-20').removeClass('w-64');
        $mainContent.addClass('lg:ms-20').removeClass('lg:ms-64');
        $sidebarToggle.find('svg').addClass('rotate-180');
    }



    const $logoWrapper = $('#user-profile-logo-wrapper');
    const $logoInner = $('#user-profile-logo-inner');
    
    function updateSidebarState(isCollapsed) {
        if (isCollapsed) {
            // Collapse
            $sidebar.addClass('w-20').removeClass('w-64');
            $mainContent.addClass('lg:ms-20').removeClass('lg:ms-64');
            $sidebarToggle.find('svg').addClass('rotate-180');
            
            // Hide text elements
            $sidebar.find('.sidebar-text').addClass('hidden');
            
            // Logo specific updates
            if ($logoWrapper.length) {
                $logoWrapper.removeClass('justify-start p-4').addClass('justify-center p-2');
                $logoInner.removeClass('gap-3').addClass('gap-0');
            }
        } else {
            // Expand
            $sidebar.removeClass('w-20').addClass('w-64');
            $mainContent.removeClass('lg:ms-20').addClass('lg:ms-64');
            $sidebarToggle.find('svg').removeClass('rotate-180');
            
            // Show text elements that were hidden
            $sidebar.find('.sidebar-text').removeClass('hidden');
             
            // Logo specific updates
            if ($logoWrapper.length) {
                $logoWrapper.addClass('justify-start p-4').removeClass('justify-center p-2');
                $logoInner.addClass('gap-3').removeClass('gap-0');
            }
        }
    }

    // Initial check
    updateSidebarState(isCollapsed);
    console.log('Sidebar state initialized. Collapsed:', isCollapsed);

    $(document).on('click', '#sidebar-toggle', function() {
        console.log('Sidebar toggle clicked');
        const $sidebar = $('#sidebar'); // Re-select in case of DOM update
        const currentlyCollapsed = $sidebar.hasClass('w-20');
        const newState = !currentlyCollapsed;
        
        updateSidebarState(newState);
        localStorage.setItem('sidebarCollapsed', newState.toString());
    });

    // Mobile Menu Logic
    const $mobileToggle = $('#mobile-menu-toggle'); 
    
    $mobileToggle.on('click', function() {
        window.openSidebar();
    });

    // Function to open mobile sidebar
    window.openSidebar = function() {
        $sidebar.removeClass('-translate-x-full').addClass('translate-x-0');
        $mobileOverlay.removeClass('hidden');
    }

    // Function to close mobile sidebar
    window.closeSidebar = function() {
        $sidebar.addClass('-translate-x-full').removeClass('translate-x-0');
        $mobileOverlay.addClass('hidden');
    }

    $mobileOverlay.on('click', function() {
        window.closeSidebar();
    });

    // Dropdown Logic (for sidebar and other components)
    $(document).on('click', '[data-toggle="dropdown"]', function(e) {
        e.stopPropagation();
        const $toggle = $(this);
        const $dropdown = $toggle.next('.dropdown-menu');
        const $icon = $toggle.find('.dropdown-icon');

        // Close other dropdowns if needed (optional, depending on UX)
        // $('.dropdown-menu').not($dropdown).addClass('hidden');

        $dropdown.toggleClass('hidden');
        $icon.toggleClass('rotate-180');
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown-menu').length && !$(e.target).closest('[data-toggle="dropdown"]').length) {
             $('.dropdown-menu').addClass('hidden');
             $('.dropdown-icon').removeClass('rotate-180');
        }
    });

    // Notifications Logic
    const $notificationDropdown = $('#notifications-dropdown');
    const $notificationList = $('#notification-list');
    const $notificationCount = $('#notification-count');
    const $markAllReadBtn = $('#mark-all-read');
    
    if ($notificationDropdown.length) {
        const urlList = $notificationDropdown.data('url-list');
        const urlMarkAllRead = $notificationDropdown.data('url-mark-all-read');
        
        // Fetch notifications when dropdown opens
        // Note: DaisyUI dropdown doesn't have a specific "open" event we can easily hook into generally without checking class changes or click
        // But we can load on click of the toggle
        $notificationDropdown.find('[role="button"]').on('click', function() {
            loadNotifications();
        });

        function loadNotifications() {
            if (!urlList) return;
            
            // Show loading state if empty
            if ($notificationList.children().length === 0) {
                 $notificationList.html('<div class="p-4 text-center text-slate-500 text-sm">Loading...</div>');
            }

            $.ajax({
                url: urlList,
                method: 'GET',
                success: function(data) {
                    renderNotifications(data);
                },
                error: function() {
                    $notificationList.html('<div class="p-4 text-center text-slate-500 text-sm">Error loading notifications</div>');
                }
            });
        }

        function renderNotifications(notifications) {
            $notificationList.empty();
            let unreadCount = 0;

            if (notifications.length === 0) {
                $notificationList.html('<div class="p-4 text-center text-slate-500 text-sm">No new notifications</div>');
                $notificationCount.hide();
                $markAllReadBtn.hide();
                return;
            }

            notifications.forEach(function(notification) {
                if (!notification.isRead) unreadCount++;
                
                const $item = $(`
                    <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-750 border-b border-slate-50 dark:border-slate-700/50 last:border-0 transition-colors group cursor-pointer ${!notification.isRead ? 'bg-slate-50 dark:bg-slate-800/50' : ''}">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 mt-1">
                                    <div class="w-2 h-2 rounded-full ${notification.isRead ? 'bg-slate-300 dark:bg-slate-600' : 'bg-blue-500'}"></div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-slate-800 dark:text-slate-200 ${!notification.isRead ? 'font-semibold' : ''}">${notification.data.message}</p>
                                <div class="flex justify-between items-center mt-1">
                                    <p class="text-xs text-slate-500">${new Date(notification.createdAt).toLocaleString()}</p>
                                    ${!notification.isRead ? `<button class="text-xs text-blue-600 hover:text-blue-800 opacity-0 group-hover:opacity-100 transition-opacity mark-read-btn" data-id="${notification.id}">Mark read</button>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                // Handle click on notification (e.g., to open announcement or navigate)
                $item.on('click', function(e) {
                     if (!$(e.target).hasClass('mark-read-btn')) {
                         // Logic to show announcement modal or navigate
                         if (notification.type === 'announcement') { // Assuming type check
                             showAnnouncement(notification);
                         }
                     }
                });

                $notificationList.append($item);
            });

            if (unreadCount > 0) {
                $notificationCount.show();
                $markAllReadBtn.show();
            } else {
                $notificationCount.hide();
                $markAllReadBtn.hide();
            }
        }

        $notificationList.on('click', '.mark-read-btn', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            // Optimistic update
            $(this).closest('.group').removeClass('bg-slate-50 dark:bg-slate-800/50');
            $(this).remove();
            
            // Call API (placeholder) - existing implementation implies specific endpoint or method
            // For now, assuming standard mark read endpoint if available, or just reload
            // But the previous implementation had a specific markAsRead method. 
            // We would need the URL for marking a specific one fetchable from data or constructed.
             $.ajax({
                 url: `/admin/notifications/${id}/read`, // Verify this path
                 method: 'POST'
             });
        });

        $markAllReadBtn.on('click', function() {
             $.ajax({
                url: urlMarkAllRead,
                method: 'POST',
                success: function() {
                    loadNotifications(); // Reload to update UI
                }
            });
        });

        // Announcement Modal Logic
        const $announcementModal = $('#announcement-modal');
        const $announcementContent = $('#announcement-content');
        const $announcementTitle = $('#announcement-title');
        const $announcementClose = $('#announcement-close');

        function showAnnouncement(notification) {
            if (!$announcementModal.length) return;
            
            $announcementTitle.text(notification.data.message);
            $announcementContent.html(notification.data.description);
            // Set colors based on type
             const type = notification.type;
             const header = $announcementModal.find('.modal-header');
             header.removeClass('bg-blue-50 bg-amber-50 bg-red-50 bg-emerald-50 dark:bg-blue-900/20 dark:bg-amber-900/20 dark:bg-red-900/20 dark:bg-emerald-900/20');
             
             if(type === 'info') header.addClass('bg-blue-50 dark:bg-blue-900/20');
             else if(type === 'warning') header.addClass('bg-amber-50 dark:bg-amber-900/20');
             else if(type === 'danger') header.addClass('bg-red-50 dark:bg-red-900/20');
             else if(type === 'success') header.addClass('bg-emerald-50 dark:bg-emerald-900/20');

            $announcementModal.removeClass('hidden'); // Or use <dialog>.showModal() if it is a dialog
            // If using standard DaisyUI modal with checkbox or dialog
            if ($announcementModal[0].showModal) {
                $announcementModal[0].showModal();
            } else {
                 $announcementModal.addClass('modal-open');
            }
        }
        
        $announcementClose.on('click', function() {
             if ($announcementModal[0].close) {
                $announcementModal[0].close();
            } else {
                 $announcementModal.removeClass('modal-open');
            }
        });
    }
});
