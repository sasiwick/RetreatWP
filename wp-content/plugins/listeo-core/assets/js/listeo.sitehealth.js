/* ----------------- Start Document ----------------- */
(function ($) {
  "use strict";

  $(document).ready(function () {
    $(document).on("click", ".listeo-health-check-table-pages .button", function (e) {
      e.preventDefault();
      if (window.confirm("Are you sure?")) {
        var $this = $(this);

        // preparing data for ajax
        var ajax_data = {
          action: "listeo_recreate_page",
          page: $this.data("page"),
          //'nonce': nonce
        };
        $.ajax({
          type: "POST",
          dataType: "json",
          url: ajaxurl,
          data: ajax_data,

          success: function (data) {
            // display loader class
            location.reload();
          },
        });
      }
    });

    // Handle memory limit update button
    $(document).on("click", ".listeo-memory-limit-fix", function (e) {
      e.preventDefault();
      
      var $this = $(this);
      var memoryLimit = $this.data("memory-limit") || "256M";
      
      if (window.confirm("Are you sure you want to update the WordPress memory limit to " + memoryLimit + "? A backup of wp-config.php will be created.")) {
        // Show loading state
        $this.prop('disabled', true).text('Updating...');
        
        // Preparing data for AJAX
        var ajax_data = {
          action: "listeo_update_memory_limit",
          memory_limit: memoryLimit,
          nonce: listeo_site_health_vars.memory_limit_nonce
        };
        
        $.ajax({
          type: "POST",
          dataType: "json",
          url: ajaxurl,
          data: ajax_data,
          
          success: function (response) {
            if (response.success) {
              alert("Success: " + response.data.message + "\nBackup created: " + response.data.backup_created);
              location.reload();
            } else {
              alert("Error: " + (response.data.message || "Unknown error occurred"));
              $this.prop('disabled', false).text('Fix Memory Limit');
            }
          },
          
          error: function () {
            alert("Error: Failed to communicate with server");
            $this.prop('disabled', false).text('Fix Memory Limit');
          }
        });
      }
    });

    // Handle granular debug control buttons
    $(document).on("click", ".listeo-debug-control", function (e) {
      e.preventDefault();
      
      var $this = $(this);
      var debugAction = $this.data("debug-action");
      var originalText = $this.text();
      
      // Create user-friendly confirmation messages
      var confirmMessages = {
        'enable_full': 'enable full debug mode (includes frontend error display)',
        'disable_all': 'turn off all debug features',
        'enable_logging': 'enable error logging only (recommended for production)',
        'disable_display': 'hide errors from frontend visitors'
      };
      
      var confirmText = confirmMessages[debugAction] || 'update debug settings';
      
      if (window.confirm("Are you sure you want to " + confirmText + "? A backup of wp-config.php will be created.")) {
        // Show loading state
        $this.prop('disabled', true).text('Updating...');
        
        // Preparing data for AJAX
        var ajax_data = {
          action: "listeo_toggle_debug_mode",
          debug_action: debugAction,
          nonce: listeo_site_health_vars.debug_toggle_nonce
        };
        
        $.ajax({
          type: "POST",
          dataType: "json",
          url: ajaxurl,
          data: ajax_data,
          
          success: function (response) {
            if (response.success) {
              alert("Success: " + response.data.message + "\nBackup created: " + response.data.backup_created);
              location.reload();
            } else {
              alert("Error: " + (response.data.message || "Unknown error occurred"));
              $this.prop('disabled', false).text(originalText);
            }
          },
          
          error: function () {
            alert("Error: Failed to communicate with server");
            $this.prop('disabled', false).text(originalText);
          }
        });
      }
    });

    // Handle test email button
    $(document).on("click", ".listeo-test-email", function (e) {
      e.preventDefault();
      
      var $this = $(this);
      var originalText = $this.text();
      
      // Get email from input field
      var testEmail = $('#test_email_input').val().trim();
      
      // Basic email validation
      if (!testEmail) {
        alert('Please enter an email address');
        return;
      }
      
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(testEmail)) {
        alert('Please enter a valid email address');
        return;
      }
      
      if (window.confirm('Send test email to ' + testEmail + '?')) {
        // Show loading state
        $this.prop('disabled', true).text('Sending...');
        
        // Preparing data for AJAX
        var ajax_data = {
          action: "listeo_test_email",
          test_email: testEmail,
          nonce: listeo_site_health_vars.test_email_nonce
        };
        
        $.ajax({
          type: "POST",
          dataType: "json",
          url: ajaxurl,
          data: ajax_data,
          
          success: function (response) {
            if (response.success) {
              alert("Success: " + response.data.message);
            } else {
              alert("Error: " + (response.data.message || "Unknown error occurred"));
            }
            $this.prop('disabled', false).text(originalText);
          },
          
          error: function () {
            alert("Error: Failed to communicate with server");
            $this.prop('disabled', false).text(originalText);
          }
        });
      }
    });
  });
})(this.jQuery);
