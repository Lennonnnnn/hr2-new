<style>
                .nav-link .fa-bell {
            font-size: 1.25rem;
            color: #fff; /* White color for the bell icon */
        }

        /* Notification Count Badge */
        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            background-color: #dc3545; /* Red color for the badge */
        }

        /* Dropdown Menu */
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            width: 300px;
            border: 1px solid #ddd; /* Light border */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        /* Notification Item */
        .dropdown-item {
            white-space: normal;
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #eee; /* Separator between notifications */
        }

        .dropdown-item:last-child {
            border-bottom: none; /* Remove separator for the last item */
        }

        /* Hover Effect for Notification Items */
        .dropdown-item:hover {
            background-color: #f8f9fa; /* Light gray background on hover */
        }

        /* Unread Notification Style */
        .unread-notification {
            background-color: #f8f9fa; /* Light gray background for unread notifications */
        }

        /* Delete Button Style */
        .delete-notification {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }


        .collapse {
            transition: width 3s ease;
        }

        #searchInput.collapsing {
            width: 0;
        }

        #searchInput.collapse.show {
            width: 250px; /* Adjust the width as needed */
        }

        .search-bar {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        #search-results {
            position: absolute;
            width: 100%;
            z-index: 1000;
            display: none; /* Hidden by default */
        }

        #search-results a {
            text-decoration: none;
        }

        .form-control:focus + #search-results {
            display: block; /* Show the results when typing */
        }
    </style>


    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark border-bottom border-1 border-secondary">
        <a class="navbar-brand d-none d-md-inline-block form-inline ps-3 text-muted" href="../../employee/staff/dashboard.php">Employee Portal</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
        <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
            <div class="d-none d-md-inline-block form-inline text-light me-3 p-2 rounded shadow-sm bg-light" id="currentTimeContainer">
                <span class="d-flex align-items-center fw-bold">
                    <span class="pe-2 text-dark">
                        <i class="fas fa-clock"></i> 
                        <span id="currentTime">00:00:00</span>
                    </span>
                    <button class="btn btn-outline-dark btn-sm ms-2" title="Calendar" type="button" onclick="toggleCalendar()">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDate">00/00/0000</span>
                    </button>
                </span>
            </div>

            <div class="dropdown search-container" style="position: relative;">
                <form class="d-none d-md-inline-block form-inline">
                    <div class="input-group">
                        <!-- Search Input -->
                        <input class="form-control collapse" id="searchInput" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" data-bs-toggle="dropdown" aria-expanded="false" />
                        <button class="btn btn-light text-dark" id="btnNavbarSearch" type="button" title="Search" data-bs-toggle="collapse" data-bs-target="#searchInput" aria-expanded="false" aria-controls="searchInput">
                            <i id="searchIcon" class="fas fa-search"></i> <!-- Initial Icon -->
                        </button>
                    </div>
                    <ul id="searchResults" class="dropdown-menu list-group bg-transparent border-none border-0"></ul>
                </form>
            </div>

            <div class="dropdown ms-3 d-none d-md-inline-block form-inline">
                <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell text-light fs-2"></i>
                    <span class="badge bg-danger" id="notificationCount">0</span> <!-- Notification Count -->
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-3" aria-labelledby="notificationDropdown" id="notificationList" style="width: 400px;">
                    <?php if ($notifications->num_rows > 0) : ?>
                        <?php while ($notification = $notifications->fetch_assoc()) : ?>
                            <li class="dropdown-item <?= $notification['status'] === 'unread' ? 'unread-notification' : '' ?>">
                                <div class="d-flex justify-content-between">
                                    <span><?= htmlspecialchars($notification['message']) ?></span>
                                    <small class="text-muted"><?= $notification['created_at'] ?></small>
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button class="btn btn-sm btn-danger delete-notification" data-id="<?= $notification['notification_id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <li><a class="dropdown-item" href="#">No new notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        let calendar;
        function toggleCalendar() {
            const calendarContainer = document.getElementById('calendarContainer');
            if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                calendarContainer.style.display = 'block';

                // Initialize the calendar if it hasn't been initialized yet
                if (!calendar) {
                    initializeCalendar();
                }
            } else {
                calendarContainer.style.display = 'none';
            }
        }

        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 440,  // Set the height of the calendar to make it small
                events: {
                    url: '../../db/holiday.php',  // Endpoint for fetching events
                    method: 'GET',
                    failure: function() {
                        alert('There was an error fetching events!');
                    }
                }
            });

            calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentDateElement = document.getElementById('currentDate');
            const currentDate = new Date().toLocaleDateString(); // Get the current date
            currentDateElement.textContent = currentDate; // Set the date text
        });

        document.addEventListener('click', function(event) {
            const calendarContainer = document.getElementById('calendarContainer');
            const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

            if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                calendarContainer.style.display = 'none';
            }
        });
        // for calendar only end

        function setCurrentTime() {
            const currentTimeElement = document.getElementById('currentTime');
            const currentDateElement = document.getElementById('currentDate');

            const currentDate = new Date();

            // Convert to 12-hour format with AM/PM
            let hours = currentDate.getHours();
            const minutes = currentDate.getMinutes();
            const seconds = currentDate.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12; // If hour is 0, set to 12

            const formattedHours = hours < 10 ? '0' + hours : hours;
            const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
            const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

            currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds} ${ampm}`;

            // Format the date in text form (e.g., "January 12, 2025")
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            currentDateElement.textContent = currentDate.toLocaleDateString('en-US', options);
        }

        setCurrentTime();
        setInterval(setCurrentTime, 1000);


        // GENERAL SEARCH
        const features = [
            { name: "Dashboard", link: "../../employee/staff/dashboard.php", path: "Employee Dashboard" },
            { name: "Attendance Scanner", link: "../../employee/staff/attendance.php", path: "Time and Attendance/Attendance Scanner" },
            { name: "Leave Request", link: "../../employee/staff/leave_request.php", path: "Leave Management/Leave Request" },
            { name: "Evaluation Ratings", link: "../../employee/staff/evaluation.php", path: "Performance Management/Evaluation Ratings" },
            { name: "File Leave", link: "../../employee/staff/leave_file.php", path: "Leave Management/File Leave" },
            { name: "Awardee", link: "../../employee/staff/awardee.php", path: "Social Recognition/Awardee" },
            { name: "Profile", link: "../../employee/staff/profile.php", path: "Profile" },
            { name: "Report Issue", link: "../../employee/staff/report_issue.php", path: "Feedback/Report Issue" }
        ];

        document.getElementById('searchInput').addEventListener('input', function () {
            let input = this.value.toLowerCase();
            let results = '';

            if (input) {
                // Filter the features based on the search input
                const filteredFeatures = features.filter(feature => 
                    feature.name.toLowerCase().includes(input)
                );

                if (filteredFeatures.length > 0) {
                    // Generate the HTML for the filtered results
                    filteredFeatures.forEach(feature => {
                        results += `                   
                            <a href="${feature.link}" class="list-group-item list-group-item-action">
                                ${feature.name}
                                <br>
                                <small class="text-muted">${feature.path}</small>
                            </a>`;
                    });
                } else {
                    // If no matches found, show "No result found"
                    results = `<li class="list-group-item list-group-item-action">No result found</li>`;
                }
            }

            // Update the search results with the filtered features
            document.getElementById('searchResults').innerHTML = results;
            
            if (!input) {
                document.getElementById('searchResults').innerHTML = ''; // Clears the dropdown if input is empty
            }
        });

        const searchInputElement = document.getElementById('searchInput');
        searchInputElement.addEventListener('hidden.bs.collapse', function () {
            searchInputElement.value = '';
            document.getElementById('searchResults').innerHTML = ''; 
        });


        document.addEventListener('DOMContentLoaded', function () {
            const notificationList = document.getElementById('notificationList');

            // Handle notification clicks (Mark as Read)
            notificationList.addEventListener('click', function (event) {
                const notificationItem = event.target.closest('.dropdown-item');
                if (notificationItem && notificationItem.classList.contains('unread-notification')) {
                    const notificationId = notificationItem.querySelector('.delete-notification').dataset.id;

                    // Mark the notification as read
                    fetch('../../employee_db/db/markNotif.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Remove the "unread" class
                                notificationItem.classList.remove('unread-notification');
                            } else {
                                console.error('Failed to mark notification as read:', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error marking notification as read:', error);
                        });
                }
            });

            // Handle delete button clicks
            notificationList.addEventListener('click', function (event) {
                if (event.target.closest('.delete-notification')) {
                    const notificationId = event.target.closest('.delete-notification').dataset.id;

                    // Delete the notification
                    fetch('../../employee_db/deleteNotif.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Remove the notification from the DOM
                                event.target.closest('.dropdown-item').remove();
                            } else {
                                console.error('Failed to delete notification:', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting notification:', error);
                        });
                }
            });
        });
    </script>