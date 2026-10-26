document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".sidebar-toggle");
    const close = document.querySelector(".sidebar-close");
    const backdrop = document.querySelector(".sidebar-backdrop");
    const sidebarNav = document.querySelector(".sidebar-nav");
    const sidebarScrollKey = "paper-management-sidebar-scroll";

    if (sidebarNav) {
        function saveSidebarScroll() {
            sessionStorage.setItem(sidebarScrollKey, String(sidebarNav.scrollTop));
        }

        const savedScroll = Number(sessionStorage.getItem(sidebarScrollKey));
        if (Number.isFinite(savedScroll) && savedScroll > 0) {
            sidebarNav.scrollTop = savedScroll;
            requestAnimationFrame(function () {
                sidebarNav.scrollTop = savedScroll;
            });
        }

        let saveScrollTimeout;
        sidebarNav.addEventListener("scroll", function () {
            window.clearTimeout(saveScrollTimeout);
            saveScrollTimeout = window.setTimeout(saveSidebarScroll, 100);
        }, {passive: true});

        sidebarNav.addEventListener("click", function (event) {
            if (event.target.closest("a")) {
                saveSidebarScroll();
            }
        });
    }

    function setSidebarOpen(open) {
        document.body.classList.toggle("sidebar-open", open);
        if (toggle) {
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        }
    }

    if (toggle) {
        toggle.addEventListener("click", function () {
            setSidebarOpen(!document.body.classList.contains("sidebar-open"));
        });
    }
    if (close) {
        close.addEventListener("click", function () {
            setSidebarOpen(false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener("click", function () {
            setSidebarOpen(false);
        });
    }
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            setSidebarOpen(false);
        }
    });
});
