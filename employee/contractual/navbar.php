
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark border-bottom border-1 border-secondary">
    <a class="navbar-brand ps-3 text-muted" href="../../employee/contractual/dashboard.php">Employee Portal</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
    <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
        <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer" 
        style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
            <span class="d-flex align-items-center">
                <span class="pe-2">
                    <i class="fas fa-clock"></i> 
                    <span id="currentTime">00:00:00</span>
                </span>
                <button class="btn btn-outline-secondary btn-sm ms-2 text-light" title="Calendar" type="button" onclick="toggleCalendar()">
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
                    <button class="btn btn-outline-secondary rounded" id="btnNavbarSearch" type="button" data-bs-toggle="collapse" data-bs-target="#searchInput" aria-expanded="false" aria-controls="searchInput">
                        <i id="searchIcon" class="fas fa-search"></i> <!-- Initial Icon -->
                    </button>
                </div>
                <ul id="searchResults" class="dropdown-menu list-group mt-2 bg-transparent" style="width: 100%;"></ul>
            </form>
        </div>
    </div>
</nav>

<script>
    // GENERAL SEARCH
    const features = [
        { name: "Dashboard", link: "../../employee/contractual/dashboard.php", path: "Employee Dashboard" },
        { name: "Attendance Scanner", link: "../../employee/contractual/attendance.php", path: "Time and Attendance/Attendance Scanner" },
        { name: "Evaluation Ratings", link: "../../employee/contractual/evaluation.php", path: "Performance Management/Evaluation Ratings" },
        { name: "Awardee", link: "../../employee/contractual/Awardee", path: "Social Recognition/Awardee" },
        { name: "Profile", link: "../../employee/contractual/Profile", path: "Profile" },
        { name: "Report Issue", link: "../../employee/contractual/report_issue.php", path: "Feedback/Report Issue" }
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

    </script>
