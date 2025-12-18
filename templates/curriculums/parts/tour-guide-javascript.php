<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tour Guide JavaScript Component
 * Contains the tour guide functionality
 */
function courscribe_render_tour_guide_javascript() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.textContent = `
                .tg-dialog {
                    border-radius: 8px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                    background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
                    color: #fff;
                }
                .tg-dialog .tg-dialog-title {
                    color: #E4B26F;
                    font-weight: 600;
                    border-bottom: 1px solid #444;
                    padding-bottom: 10px;
                    margin-bottom: 15px;
                }
                .tg-dialog .tg-dialog-body {
                    line-height: 1.6;
                    color: #e0e0e0;
                }
                .tg-dialog .tg-dialog-footer {
                    border-top: 1px solid #444;
                    padding-top: 15px;
                    margin-top: 15px;
                }
                .tg-dialog .tg-dialog-btn {
                    background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
                    border: none;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-weight: 500;
                    transition: opacity 0.3s;
                }
                .tg-dialog .tg-dialog-btn:hover {
                    opacity: 0.9;
                }
                .tg-dialog .tg-dialog-btn.tg-dialog-btn-secondary {
                    background: transparent;
                    border: 1px solid #666;
                    color: #ccc;
                }
                .tg-dialog .tg-dialog-close {
                    color: #999;
                    font-size: 18px;
                }
                .tg-dialog .tg-dialog-close:hover {
                    color: #fff;
                }
                .tg-overlay {
                    background: rgba(0, 0, 0, 0.7);
                }
                .tg-highlight {
                    border: 2px solid #E4B26F !important;
                    box-shadow: 0 0 0 4px rgba(228, 178, 111, 0.3) !important;
                }
                .tg-dialog .tg-dialog-dots>span.tg-dot {
                    background: #666;
                    border: none;
                }
                .tg-dialog .tg-dialog-dots>span.tg-dot svg {
                    fill: #fff;
                }
                .tg-dialog .tg-dialog-dots>span.tg-dot.tg-dot-active {
                    background: #E4B26F;
                }
                .tg-dot.active {
                    background: #E4B26F !important;
                }
                @media (max-width: 768px) {
                    .tg-dialog {
                        width: 90% !important;
                        margin: 0 auto !important;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
    <?php
}
?>