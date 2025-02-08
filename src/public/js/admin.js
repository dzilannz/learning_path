function confirmAction(event, message, form) {
    event.preventDefault();

    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

// Ambil elemen yang diperlukan
// Ambil elemen yang diperlukan
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');
const container = document.querySelector('.container');
const footer = document.querySelector('.footer');
const progressSections = document.querySelectorAll('.progress-section'); // Ensure this is a NodeList

// Tambahkan event listener untuk tombol toggle
sidebarToggle.addEventListener('click', () => {
    // Toggle kelas 'hidden' pada sidebar
    sidebar.classList.toggle('hidden');

    // Toggle kelas 'expanded' pada container dan footer
    container.classList.toggle('expanded');
    footer.classList.toggle('expanded');

    // Expand all progress sections by adding 'expanded' class to each
    progressSections.forEach(function(section) {
        section.classList.toggle('expanded');
    });
});


function toggleDetails(button) {
    let detailsRow = button.closest("tr").nextElementSibling;

    if (!detailsRow || !detailsRow.classList.contains("details-row")) {
        console.error("Detail row tidak ditemukan!");
        return;
    }

    detailsRow.classList.toggle("show");

    if (detailsRow.classList.contains("show")) {
        button.textContent = "Tutup";
    } else {
        button.textContent = "Detail";
    }
}



document.addEventListener("DOMContentLoaded", function () {
    // Sidebar toggle untuk mobile
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".sidebar");

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }

    // Toggle detail tabel di mobile
    const toggleButtons = document.querySelectorAll(".toggle-details");

    toggleButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const row = this.closest("tr").nextElementSibling;
            if (row && row.classList.contains("details-row")) {
                row.style.display = row.style.display === "table-row" ? "none" : "table-row";
                button.innerHTML =
                    row.style.display === "table-row"
                        ? '<i class="fas fa-minus"></i>'
                        : '<i class="fas fa-plus"></i>';
            }
        });
    });
});
