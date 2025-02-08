// When the page is ready
document.addEventListener("DOMContentLoaded", function () {
    const filterButton = document.getElementById("filter-button");
    filterButton.innerText = "All";  // Default to "All" on page load

    const dropdown = document.getElementById("filter-dropdown");
    dropdown.classList.add("hidden");

    // Initially load data for all years
    filterData("all");

    // Listen for dropdown menu and year selection
    document.getElementById('angkatanFilter').addEventListener('change', function () {
        const selectedAngkatan = this.value;
        filterData(selectedAngkatan);  // Update stats and charts based on the selected year
    });
});

// Function to fetch and update data based on the selected "Angkatan"
function filterData(angkatan) {
    const filterButton = document.getElementById("filter-button");
    filterButton.innerText = angkatan === "all" ? "All" : angkatan;  // Update the button label

    // Fetch data based on selected year (AJAX request)
    fetch(`/landing?angkatan=${angkatan}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data) {
            console.log(data);  // Debugging: Check what data is being returned

            // Update stats section with fetched data
            updateStats([
                { title: "Mahasiswa Aktif", value: data.mahasiswaAktifCount || 0 },
                { title: "Ibtitah", value: data.keseluruhanIbtitah || 0 },
                { title: "Kerja Praktik", value: data.jumlahKerjaPraktikAktif || 0 },
                { title: "Sidang", value: data.totalSidang || 0 },
            ]);

            // Update charts with new data
            updateCharts({
                statistik: [
                    data.mahasiswaAktifCount || 0,
                    data.keseluruhanIbtitah || 0,
                    data.jumlahKerjaPraktikAktif || 0,
                    data.totalSidang || 0,
                ],
                ibtitah: [
                    data.ibtitahPerKategoriPerAngkatan?.tilawah || 0,
                    data.ibtitahPerKategoriPerAngkatan?.ibadah || 0,
                    data.ibtitahPerKategoriPerAngkatan?.tahfidz || 0,
                ],
                taSidang: [
                    data.sidangPerKategori?.sempro || 0,
                    data.sidangPerKategori?.kompre || 0,
                    data.sidangPerKategori?.kolokium || 0,
                    data.sidangPerKategori?.munaqasyah || 0,
                ],
            });
        } else {
            console.error("Data is empty or invalid");
        }
    })
    .catch(error => {
        console.error("Error fetching data:", error);
    });
}

// Function to update stats display
function updateStats(stats) {
    const statsSection = document.querySelector(".stats-section");
    statsSection.innerHTML = stats.map(stat => `
        <div class="stats-card">
            <h3 class="stats-title">${stat.title}</h3>
            <p class="stats-value">${stat.value}</p>
        </div>
    `).join("");
}

// Function to update charts (Chart.js)
function updateCharts(data) {
    // Ensure that each chart is updated correctly
    if (statistikChart && ibtitahChart && taSidangChart) {
        statistikChart.data.datasets[0].data = data.statistik;
        ibtitahChart.data.datasets[0].data = data.ibtitah;
        taSidangChart.data.datasets[0].data = data.taSidang;

        // Update the charts with new data
        statistikChart.update();
        ibtitahChart.update();
        taSidangChart.update();
    } else {
        console.error("Charts not initialized properly.");
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // Sidebar toggle functionality
    const sidebar = document.querySelector(".sidebar");
    const dashboard = document.querySelector(".dashboard");
    const footer = document.querySelector(".footer");
    const progresSection = document.querySelector(".progress-section");
    const toggleButton = document.getElementById("sidebarToggle");

    toggleButton.addEventListener("click", function () {
        sidebar.classList.toggle("hidden");
        dashboard.classList.toggle("expanded");
        footer.classList.toggle("expanded");
        progresSection.classList.toggle("expanded");
    });

    // Search form toggle functionality
    const searchButton = document.querySelector('.search-form button');
    if (searchButton) {
        searchButton.addEventListener('click', function (event) {
            event.preventDefault();  // Prevent form submission
            const searchForm = document.querySelector('.search-form');
            searchForm.classList.toggle('expanded');  // Toggle the 'expanded' class to show/hide the input
        });
    }
});

function toggleDetails(button) {
    let row = button.closest("tr");
    let detailsRow = row.nextElementSibling;

    if (detailsRow.style.display === "table-row" || detailsRow.style.display === "block") {
        detailsRow.style.display = "none";
        button.innerHTML = '<i class="fas fa-plus"></i>';
    } else {
        detailsRow.style.display = "block";
        button.innerHTML = '<i class="fas fa-minus"></i>';
    }
}
