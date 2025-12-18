// assets/js/courscribe/tabs.js
document.addEventListener('DOMContentLoaded', function () {
    // Generic tab-switching function
    function setupTabs(tabLinkSelector, tabContentSelector) {
        const tabLinks = document.querySelectorAll(tabLinkSelector);
        const tabContents = document.querySelectorAll(tabContentSelector);

        tabLinks.forEach(link => {
            link.addEventListener('click', function () {
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                link.classList.add('active');
                document.getElementById(link.getAttribute('data-tab')).classList.add('active');
            });
        });
    }

    // Setup for different tab types
    setupTabs('.tab-link', '.tab-content'); // Modules
    setupTabs('.teachingPoint-tab-link', '.teachingPoint-tab-content'); // Teaching Points
    setupTabs('.tab-link-teachingPoint-module', '.tab-content-teachingPoint-module'); // Teaching Point Modules
});