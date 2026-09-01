document.addEventListener("DOMContentLoaded", function () {

    const menuButton = document.getElementById("mobileMenu");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (menuButton) {

        menuButton.addEventListener("click", function () {

            sidebar.classList.toggle("show");
            overlay.classList.toggle("show");

        });

    }

    if (overlay) {

        overlay.addEventListener("click", function () {

            sidebar.classList.remove("show");
            overlay.classList.remove("show");

        });

    }

});