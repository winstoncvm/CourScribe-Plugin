jQuery(document).ready(function($) {
    // Initialize all tooltips on the page
    $('.courscribe-tooltip').each(function() {
        const $tooltip = $(this);
        const $button = $tooltip.find('button');
        
        // Get data attributes
        const requiredPackage = $tooltip.data('required-package');
        const title = $tooltip.data('title');
        const description = $tooltip.data('description');
        const userPackage = courscribeTooltipData.userPackage;
        
        // Compare packages
        const userLevel = courscribeTooltipData.packageLevels[userPackage] || 0;
        const requiredLevel = courscribeTooltipData.packageLevels[requiredPackage] || 0;
        const hasAccess = userLevel >= requiredLevel;
        
        // Create tooltip content
        const tooltipContent = `
            <div class="tooltip-content">
                <div class="tooltip-inner">
                    <div class="tooltip-decoration"></div>
                    
                    <div class="tooltip-header">
                        <div class="tooltip-icon">
                            <i class="fas fa-info-circle text-indigo-400"></i>
                        </div>
                        <h3 class="tooltip-title">${title}</h3>
                    </div>
                    
                    <div class="tooltip-body">
                        <p class="tooltip-description">${description}</p>
                        
                        <div class="package-requirement ${hasAccess ? 'has-access' : 'no-access'}">
                            <i class="fas ${hasAccess ? 'fa-check-circle icon-check' : 'fa-times-circle icon-x'}"></i>
                            <span>${requiredPackage} ${hasAccess ? '(Available)' : '(Required)'}</span>
                        </div>
                        
                        <div class="user-package">
                            <i class="fas fa-user-circle text-gray-400"></i>
                            <span>Your Package: ${userPackage}</span>
                        </div>
                    </div>
                    
                    <div class="tooltip-arrow"></div>
                </div>
            </div>
        `;
        
        // Append tooltip content
        $tooltip.append(tooltipContent);
        
        // Ensure button maintains its original classes and attributes
        //$button.addClass('original-button');
    });
});