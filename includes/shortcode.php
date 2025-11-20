<?php
/**
 * Shortcode Functions
 * Displays the contact form on frontend
 */

if (!defined('ABSPATH')) exit;

/**
 * Contact form shortcode
 * Usage: [my_contact_form]
 */
function ccf_contact_form_shortcode() {
    ob_start();

    // Get current page info
    $page_title = get_the_title();
    $page_url = get_permalink();
    ?>
    <div class="ccf-form-container">
        <div id="ccf-message-container"></div>
        
        <form id="ccfContactForm" class="ccf-form">
            <div class="ccf-form-group">
                <label for="ccf_name">نام و نام خانوادگی <span class="ccf-required">*</span></label>
                <input type="text" id="ccf_name" name="name" placeholder="نام و نام خانوادگی" required>
            </div>
            
            <div class="ccf-form-group">
                <label for="ccf_phone">شماره همراه <span class="ccf-required">*</span></label>
                <input type="tel" id="ccf_phone" name="phone" placeholder="شماره همراه" required>
            </div>

            <div class="ccf-form-group">
                <label for="ccf_message">توضحیات (اختیاری)</label>
                <textarea id="ccf_message" name="message" placeholder="پیام خود را بنویسید..." rows="5"></textarea>
            </div>

            <!-- Hidden fields to capture page info -->
            <input type="hidden" name="page_title" value="<?php echo esc_attr($page_title); ?>">
            <input type="hidden" name="page_url" value="<?php echo esc_url($page_url); ?>">
            <input type="hidden" name="action" value="ccf_submit_form">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ccf_form_action'); ?>">
            
            <button type="submit" class="ccf-submit-btn">
                <span class="ccf-btn-text">ارسال اطلاعات</span>
                <span class="ccf-btn-loader" style="display:none;">در حال ارسال...</span>
            </button>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#ccfContactForm').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('.ccf-submit-btn');
            var $btnText = $btn.find('.ccf-btn-text');
            var $btnLoader = $btn.find('.ccf-btn-loader');
            var $messageContainer = $('#ccf-message-container');
            
            // Disable button and show loader
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoader.show();
            
            // Clear previous messages
            $messageContainer.html('');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $messageContainer.html('<div class="ccf-success-message">' + response.data.message + '</div>');
                        // Reset form
                        $form[0].reset();
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $messageContainer.offset().top - 100
                        }, 500);
                    } else {
                        // Show error message
                        $messageContainer.html('<div class="ccf-error-message">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $messageContainer.html('<div class="ccf-error-message">❌ خطا در ارسال. لطفا دوباره تلاش کنید.</div>');
                },
                complete: function() {
                    // Re-enable button and hide loader
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('my_contact_form', 'ccf_contact_form_shortcode');

/**
 * Enqueue form styles
 */
function ccf_enqueue_styles() {
    if (has_shortcode(get_post()->post_content ?? '', 'my_contact_form')) {
        wp_enqueue_style('ccf-form-style', CCF_PLUGIN_URL . 'assets/css/form-style.css', [], '1.4');
    }
}
add_action('wp_enqueue_scripts', 'ccf_enqueue_styles');
?>