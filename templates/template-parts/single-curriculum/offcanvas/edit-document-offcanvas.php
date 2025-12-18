<div class="offcanvas offcanvas-end" tabindex="-1" id="courscribeEditDocumentOffcanvas"
    style="min-width:720px!important" aria-labelledby="courscribeEditDocumentOffcanvasLabel">
    <div class="offcanvas-header" style="padding-top:35px;">
        <h5 class="offcanvas-title" id="courscribeEditDocumentOffcanvasLabel">Edit Document</h5>
        <button class="courscribe-close-button btn-close" data-bs-dismiss="offcanvas"
            aria-label="Close">
            <span class="X"></span>
            <span class="Y"></span>
            <div class="courscribe-close-close">Close</div>
        </button>
    </div>
    <div class="offcanvas-body p-0" id="html-slides-container">
        <div id="courscribe-richtexteditor"
            style="width: 100%; height:100%; min-height: 500px; border: 1px solid #ddd;"></div>
        <div id="courscribe-richtexteditor-error"
            style="display: none; color: red; margin: 10px; font-weight: bold;"></div>
        <div id="courscribe-richtexteditor-loading" style="color: blue; margin: 10px;">Loading
            editor...</div>
    </div>
    <div class="offcanvas-footer p-3">
        <button id="courscribe-modal-close-btn" data-bs-dismiss="offcanvas"
            class="btn btn-secondary" style="margin-right: 10px;">Close</button>
        <button id="courscribe-reload-course-data" class="btn btn-outline-light">
            <i class="fas fa-sync-alt me-2"></i>Reload Course Data
        </button>
        <button id="courscribe-save-richtexteditor" class="Documents-btn save-changes">
            <span class="folderContainer">
                <svg class="fileBack" width="146" height="113" viewBox="0 0 146 113" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0 4C0 1.79086 1.79086 0 4 0H50.3802C51.8285 0 53.2056 0.627965 54.1553 1.72142L64.3303 13.4371C65.2799 14.5306 66.657 15.1585 68.1053 15.1585H141.509C143.718 15.1585 145.509 16.9494 145.509 19.1585V109C145.509 111.209 143.718 113 141.509 113H3.99999C1.79085 113 0 111.209 0 109V4Z"
                        fill="url(#paint0_linear_117_4)"></path>
                    <defs>
                        <linearGradient id="paint0_linear_117_4" x1="0" y1="0" x2="72.93"
                            y2="95.4804" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#8F88C2"></stop>
                            <stop offset="1" stop-color="#5C52A2"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                <svg class="filePage" width="88" height="99" viewBox="0 0 88 99" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <rect width="88" height="99" fill="url(#paint0_linear_117_6)"></rect>
                    <defs>
                        <linearGradient id="paint0_linear_117_6" x1="0" y1="0" x2="81"
                            y2="160.5" gradientUnits="userSpaceOnUse">
                            <stop stop-color="white"></stop>
                            <stop offset="1" stop-color="#686868"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                <svg class="fileFront" width="160" height="79" viewBox="0 0 160 79" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0.29306 12.2478C0.133905 9.38186 2.41499 6.97059 5.28537 6.97059H30.419H58.1902C59.5751 6.97059 60.9288 6.55982 62.0802 5.79025L68.977 1.18034C70.1283 0.410771 71.482 0 72.8669 0H77H155.462C157.87 0 159.733 2.1129 159.43 4.50232L150.443 75.5023C150.19 77.5013 148.489 79 146.474 79H7.78403C5.66106 79 3.9079 77.3415 3.79019 75.2218L0.29306 12.2478Z"
                        fill="url(#paint0_linear_117_5)"></path>
                    <defs>
                        <linearGradient id="paint0_linear_117_5" x1="38.7619" y1="8.71323"
                            x2="66.9106" y2="82.8317" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#C3BBFF"></stop>
                            <stop offset="1" stop-color="#51469A"></stop>
                        </linearGradient>
                    </defs>
                </svg>
            </span>
            <p class="text-for-save">Save Contents</p>
        </button>
    </div>
</div>