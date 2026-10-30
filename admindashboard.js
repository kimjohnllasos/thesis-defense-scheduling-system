const navLinks = document.querySelectorAll(".nav-link");
const sections = document.querySelectorAll(".content-section");
const pageTitle = document.getElementById("pageTitle");

const pageNames = {
    dashboard: "Dashboard",
    users: "User Management",
    projects: "Project Management",
    requests: "Defense Requests",
    schedule: "Defense Schedule",
    panels: "Panel Assignment",
    rooms: "Room Management",
    conflicts: "Conflict Detection",
    reports: "Reports"
};

function openSection(sectionName) {
    if (!pageNames[sectionName]) return;

    navLinks.forEach(function (link) {
        link.classList.toggle(
            "active",
            link.dataset.section === sectionName
        );
    });

    sections.forEach(function (section) {
        section.classList.toggle(
            "active-section",
            section.id === sectionName
        );
    });

    pageTitle.textContent = pageNames[sectionName];
}

navLinks.forEach(function (link) {
    link.addEventListener("click", function (event) {
        event.preventDefault();

        const sectionName = link.getAttribute("data-section");

        console.log("Clicked section:", sectionName);

        openSection(sectionName);
    });
});

document.querySelectorAll("[data-open]").forEach(function (button) {
    button.addEventListener("click", function () {
        openSection(button.dataset.open);
    });
});

document.querySelectorAll("[data-message]").forEach(function (button) {
    button.addEventListener("click", function () {
        alert(button.dataset.message);
    });
});

document.querySelectorAll("[data-search]").forEach(function (input) {
    input.addEventListener("input", function () {
        const table = document.getElementById(input.dataset.search);
        if (!table) return;

        const searchText = input.value.toLowerCase();

        table.querySelectorAll("tbody tr").forEach(function (row) {
            row.hidden = !row.textContent
                .toLowerCase()
                .includes(searchText);
        });
    });
});

document.querySelectorAll("[data-decision]").forEach(function (button) {
    button.addEventListener("click", function () {
        const decision = button.dataset.decision;
        const row = button.closest("tr");
        const statusCell = row.cells[3];

        const confirmed = confirm(
            "Are you sure you want to " +
            decision.toLowerCase() +
            " this defense request?"
        );

        if (!confirmed) return;

        statusCell.innerHTML =
            '<span class="status ' +
            (decision === "Approved" ? "approved" : "unavailable") +
            '">' + decision + "</span>";

        row.cells[4].textContent = "Reviewed";

        alert(
            "Request marked as " + decision +
            " in this prototype. It has not been saved to the database."
        );
    });
});

document.getElementById("logoutButton").addEventListener("click", function () {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = 'logout.php';
    }
});