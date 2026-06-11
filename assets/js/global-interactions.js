/*
 * HMS Global Interactive Enhancements
 * JavaScript for enhanced user interactions across the entire system
 */

$(document).ready(function() {
    
    // ===========================================
    // ENHANCED BUTTON INTERACTIONS
    // ===========================================
    
    // Add ripple effect to all buttons
    $('.btn').on('click', function(e) {
        var $this = $(this);
        var ripple = $('<span class="ripple"></span>');
        var rect = this.getBoundingClientRect();
        var size = Math.max(rect.width, rect.height);
        var x = e.clientX - rect.left - size / 2;
        var y = e.clientY - rect.top - size / 2;
        
        ripple.css({
            width: size,
            height: size,
            left: x,
            top: y
        });
        
        $this.append(ripple);
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });
    
    // ===========================================
    // ENHANCED CARD INTERACTIONS (tilt disabled for stability)
    // ===========================================
    // Card tilt effect removed so cards remain stable when hovering/moving mouse
    
    // ===========================================
    // ENHANCED TABLE INTERACTIONS
    // ===========================================
    
    // Add smooth row highlighting
    $('.table tbody tr').on('mouseenter', function() {
        $(this).addClass('table-row-highlight');
    }).on('mouseleave', function() {
        $(this).removeClass('table-row-highlight');
    });
    
    // ===========================================
    // ENHANCED FORM INTERACTIONS
    // ===========================================
    
    // Add floating label effect
    $('.form-control').on('focus', function() {
        $(this).parent().addClass('form-focused');
    }).on('blur', function() {
        if (!$(this).val()) {
            $(this).parent().removeClass('form-focused');
        }
    });
    
    // ===========================================
    // ENHANCED NAVIGATION INTERACTIONS
    // ===========================================
    
    // Add smooth navigation transitions
    $('.sidebar-menu a, .nav-link').on('click', function(e) {
        var $this = $(this);
        
        // Add click animation
        $this.addClass('nav-clicked');
        setTimeout(function() {
            $this.removeClass('nav-clicked');
        }, 200);
    });
    
    // ===========================================
    // ENHANCED MODAL INTERACTIONS
    // ===========================================
    
    // Add smooth modal animations
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-dialog').css({
            'transform': 'scale(0.8)',
            'opacity': '0'
        });
    });
    
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('.modal-dialog').css({
            'transform': 'scale(1)',
            'opacity': '1',
            'transition': 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
        });
    });
    
    // ===========================================
    // ENHANCED DROPDOWN INTERACTIONS
    // ===========================================
    
    // Add smooth dropdown animations
    $('.dropdown-toggle').on('click', function() {
        var $dropdown = $(this).next('.dropdown-menu');
        $dropdown.addClass('dropdown-animating');
        
        setTimeout(function() {
            $dropdown.removeClass('dropdown-animating');
        }, 300);
    });
    
    // ===========================================
    // ENHANCED BADGE INTERACTIONS
    // ===========================================
    
    // Add pulse effect to important badges
    $('.badge-danger, .badge-warning').on('mouseenter', function() {
        $(this).addClass('badge-pulse');
    }).on('mouseleave', function() {
        $(this).removeClass('badge-pulse');
    });
    
    // ===========================================
    // ENHANCED ALERT INTERACTIONS
    // ===========================================
    
    // Add smooth alert dismissals
    $('.alert .close').on('click', function() {
        var $alert = $(this).closest('.alert');
        $alert.addClass('alert-dismissing');
        
        setTimeout(function() {
            $alert.remove();
        }, 300);
    });
    
    // ===========================================
    // ENHANCED PROGRESS BAR INTERACTIONS
    // ===========================================
    
    // Add animated progress bars
    $('.progress-bar').each(function() {
        var $this = $(this);
        var width = $this.attr('aria-valuenow');
        
        $this.css('width', '0%');
        
        setTimeout(function() {
            $this.css('width', width + '%');
        }, 100);
    });
    
    // ===========================================
    // ENHANCED TOOLTIP INTERACTIONS
    // ===========================================
    
    // Initialize tooltips with enhanced animations
    $('[data-toggle="tooltip"]').tooltip({
        animation: true,
        delay: { "show": 200, "hide": 100 }
    });
    
    // ===========================================
    // ENHANCED POPOVER INTERACTIONS
    // ===========================================
    
    // Initialize popovers with enhanced animations
    $('[data-toggle="popover"]').popover({
        animation: true,
        delay: { "show": 200, "hide": 100 }
    });
    
    // ===========================================
    // ENHANCED DATATABLE INTERACTIONS
    // ===========================================
    
    // Add smooth pagination transitions
    $('.dataTables_wrapper').on('click', '.paginate_button', function() {
        $('.dataTables_wrapper').addClass('table-transitioning');
        
        setTimeout(function() {
            $('.dataTables_wrapper').removeClass('table-transitioning');
        }, 300);
    });
    
    // ===========================================
    // ENHANCED SEARCH INTERACTIONS
    // ===========================================
    
    // Add search animation
    $('.form-control[type="search"]').on('input', function() {
        $(this).addClass('search-active');
        
        if ($(this).val().length === 0) {
            $(this).removeClass('search-active');
        }
    });
    
    // ===========================================
    // ENHANCED LOADING STATES
    // ===========================================
    
    // Add loading states to forms
    $('form').on('submit', function() {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.addClass('btn-loading');
        $submitBtn.prop('disabled', true);
        
        // Remove loading state after 3 seconds (adjust as needed)
        setTimeout(function() {
            $submitBtn.removeClass('btn-loading');
            $submitBtn.prop('disabled', false);
        }, 3000);
    });
    
    // ===========================================
    // ENHANCED SCROLL INTERACTIONS
    // ===========================================
    
    // Add smooth scrolling to anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800, 'easeInOutCubic');
        }
    });
    
    // ===========================================
    // ENHANCED KEYBOARD INTERACTIONS
    // ===========================================
    
    // Add keyboard navigation enhancements
    $(document).on('keydown', function(e) {
        // Escape key to close modals
        if (e.keyCode === 27) {
            $('.modal').modal('hide');
        }
        
        // Enter key to submit forms
        if (e.keyCode === 13) {
            var $focused = $(':focus');
            if ($focused.is('input, textarea, select')) {
                var $form = $focused.closest('form');
                if ($form.length) {
                    $form.submit();
                }
            }
        }
    });
    
    // ===========================================
    // ENHANCED TOUCH INTERACTIONS (MOBILE)
    // ===========================================
    
    // Add touch feedback for mobile devices
    if ('ontouchstart' in window) {
        $('.btn, .card, .nav-link').on('touchstart', function() {
            $(this).addClass('touch-active');
        }).on('touchend', function() {
            var $this = $(this);
            setTimeout(function() {
                $this.removeClass('touch-active');
            }, 150);
        });
    }
    
    // ===========================================
    // ENHANCED ACCESSIBILITY
    // ===========================================
    
    // Add focus indicators for keyboard navigation
    $('button, a, input, select, textarea').on('focus', function() {
        $(this).addClass('keyboard-focus');
    }).on('blur', function() {
        $(this).removeClass('keyboard-focus');
    });
    
    // ===========================================
    // ENHANCED PERFORMANCE
    // ===========================================
    
    // Debounce scroll events
    var scrollTimer;
    $(window).on('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            // Add scroll-based animations here
        }, 10);
    });
    
    // Debounce resize events
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Add resize-based adjustments here
        }, 250);
    });
});

// ===========================================
// ADDITIONAL CSS CLASSES FOR JAVASCRIPT EFFECTS
// ===========================================

// Add these styles to your CSS file or in a <style> tag
var additionalStyles = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .table-row-highlight {
        /* Row highlight without scaling */
        background-color: rgba(0, 123, 255, 0.1) !important;
        transition: all 0.3s ease;
    }
    
    .form-focused .form-control {
        /* Keep focus glow without scaling */
        transform: none;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .nav-clicked {
        transform: scale(0.95);
        transition: transform 0.1s ease;
    }
    
    .dropdown-animating {
        animation: dropdown-slide 0.3s ease;
    }
    
    @keyframes dropdown-slide {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .badge-pulse {
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .alert-dismissing {
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    }
    
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .table-transitioning {
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }
    
    .search-active {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        border-color: #007bff;
    }
    
    .touch-active {
        transform: none;
        transition: transform 0.1s ease;
    }
    
    .keyboard-focus {
        outline: 2px solid #007bff;
        outline-offset: 2px;
    }
`;

// Inject additional styles
$('<style>').text(additionalStyles).appendTo('head');
