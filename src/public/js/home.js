function toggleMenu() {
    const navLinks = document.getElementById('nav-links');
    navLinks.classList.toggle('show'); // Toggle class 'show'
}


document.addEventListener("DOMContentLoaded", () => {
    const filterButton = document.getElementById("filter-button");
    filterButton.innerText = "All";

    const dropdown = document.getElementById("filter-dropdown");
    dropdown.classList.add("hidden");

    // Muat data awal
    filterData("all");
    scrollToSection();
});

function filterByAngkatan() {
    const selectedAngkatan = document.getElementById('angkatanFilter').value;

    // AJAX untuk mengambil data
    fetch(`/landing?angkatan=${selectedAngkatan}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            // Perbarui data di tampilan
            updateStats([
                { title: "Mahasiswa Aktif", value: data.mahasiswaAktifCount },
                { title: "Ibtitah", value: data.keseluruhanIbtitah },
                { title: "Kerja Praktek", value: data.jumlahKerjaPraktikAktif },
                { title: "Sidang", value: data.totalSidang },
            ]);

            updateCharts({
                statistik: [
                    data.mahasiswaAktifCount,
                    data.keseluruhanIbtitah,
                    data.jumlahKerjaPraktikAktif,
                    data.totalSidang,
                ],
                ibtitah: [
                    data.ibtitahPerKategoriPerAngkatan.tilawah,
                    data.ibtitahPerKategoriPerAngkatan.ibadah,
                    data.ibtitahPerKategoriPerAngkatan.tahfidz,
                ], // Tambahkan data kategori ibtitah jika ada
                taSidang: [
                    data.sidangPerKategori.sempro,
                    data.sidangPerKategori.kompre,
                    data.sidangPerKategori.kolokium,
                    data.sidangPerKategori.munaqasyah,
                ],
            });
        })
        .catch((error) => console.error("Error fetching data:", error));
}


function scrollToSection() {
    const section = document.getElementById("target-section");
    if (section) {
        section.scrollIntoView({ behavior: "smooth" });
    }
}

const sections = document.querySelectorAll(".stats-section, .chart-section, .about-section");
const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.1 }
);

sections.forEach((section) => {
    observer.observe(section);
});

function filterData(year) {
    const filterButton = document.getElementById("filter-button");
    filterButton.innerText = year === "all" ? "All" : year;

    const data = dataSets[year];
    updateStats(data.stats);
    updateCharts(data);
}

function updateStats(stats) {
    const statsSection = document.querySelector(".stats-section");
    statsSection.innerHTML = stats
        .map(
            (stat) => `
            <div class="stats-card">
                <h3 class="stats-title">${stat.title}</h3>
                <p class="stats-value">${stat.value}</p>
            </div>
        `
        )
        .join("");
}

function updateCharts(data) {
    statistikChart.data.datasets[0].data = data.statistik;
    ibtitahChart.data.datasets[0].data = data.ibtitah;
    taSidangChart.data.datasets[0].data = data.taSidang;

    statistikChart.update();
    ibtitahChart.update();
    taSidangChart.update();
}

const statistikCtx = document.getElementById("statistikChart").getContext("2d");
const ibtitahCtx = document.getElementById("ibtitahChart").getContext("2d");
const taSidangCtx = document.getElementById("sidangChart").getContext("2d");

const statistikChart = new Chart(statistikCtx, {
    type: "bar",
    data: {
        labels: ["Mahasiswa Aktif", "Ibtitah", "Kerja Praktek", "Sidang"],
        datasets: [
            {
                label: "Jumlah",
                data: dataSets.all.statistik,
                backgroundColor: ["#FFD523", "#FFC107", "#FFB300", "#FFA000"],
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
    },
});

const ibtitahChart = new Chart(ibtitahCtx, {
    type: "doughnut",
    data: {
        labels: ["Ibadah", "Tilawah", "Tahfidz"],
        datasets: [
            {
                data: dataSets.all.ibtitah,
                backgroundColor: ["#FFE893", "#FFD65E", "#FFBF40"],
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
    },
});

const taSidangChart = new Chart(taSidangCtx, {
    type: "doughnut",
    data: {
        labels: ["Sempro", "Kompre", "Kolokium", "Munaqasyah"],
        datasets: [
            {
                data: dataSets.all.taSidang,
                backgroundColor: ["#FFE893", "#FFD65E", "#FFC244", "#FFAE2A"],
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
    },
});

function toggleDropdown() {
    const dropdown = document.getElementById("filter-dropdown");
    dropdown.classList.toggle("hidden");
    dropdown.classList.toggle("visible");
}

document.addEventListener("click", function (event) {
    const navMenu = document.getElementById("nav-links");
    const hamburger = document.querySelector(".hamburger-menu");

    if (!navMenu.contains(event.target) && !hamburger.contains(event.target)) {
        console.log("Klik di luar menu, tapi tidak menghapus class 'show'");
    }
});


document.addEventListener("DOMContentLoaded", function () {
    // Sidebar toggle functionality
    const sidebar = document.querySelector(".sidebar");
    const dashboard = document.querySelector(".dashboard");
    const footer = document.querySelector(".footer");
    const progresSection = document.querySelector(".progress-section");
    const toggleButton = document.getElementById("sidebarToggle");
    const hamburger = document.querySelector(".hamburger-menu");
    const navbarRight = document.querySelector(".navbar-right");

    toggleButton.addEventListener("click", function () {
        sidebar.classList.toggle("hidden");
        dashboard.classList.toggle("expanded");
        footer.classList.toggle("expanded");
        progresSection.classList.toggle("expanded");
    });

    hamburger.addEventListener("click", function () {
        // Toggle the 'show' class on the navbar-right when hamburger is clicked
        navbarRight.classList.toggle("show");
    });

});









