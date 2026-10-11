<div class="social-login-container">

    <?php if (defined('SOCIAL_LOGIN_GOOGLE_STATUS') && SOCIAL_LOGIN_GOOGLE_STATUS === 'true') { ?>
            <a href="<?php echo zen_href_link('social_oauth_callback', 'provider=google', 'SSL'); ?>" class="btn-google-standard">
                <div class="google-icon-wrapper">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="google-icon">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                        <path fill="none" d="M0 0h48v48H0z"></path>
                    </svg>
                </div>
                <span class="google-text"><?= TEXT_SOCIAL_LOGIN_GOOGLE ?></span>
            </a>
    <?php } ?>

    <?php if (defined('SOCIAL_LOGIN_FACEBOOK_STATUS') && SOCIAL_LOGIN_FACEBOOK_STATUS === 'true') { ?>
        <a href="<?php echo zen_href_link('social_oauth_callback', 'provider=facebook', 'SSL'); ?>" class="btn-facebook-standard">
            <div class="facebook-icon-wrapper">
                <svg viewBox="0 0 36 36" class="facebook-icon" fill="#1877F2">
                    <path d="M20.18 35.61v-13.9h4.66l.7-5.41h-5.36v-3.46c0-1.57.44-2.63 2.68-2.63h2.86V5.37c-.49-.07-2.19-.21-4.16-.21-4.12 0-6.93 2.51-6.93 7.13v4.18H9.98v5.41h4.66v13.9c1.78.28 3.61.28 5.54 0z"></path>
                </svg>
            </div>
            <span class="social-text"><?= TEXT_SOCIAL_LOGIN_FACEBOOK ?></span>
        </a>
    <?php } ?>

    <?php if (defined('SOCIAL_LOGIN_APPLE_STATUS') && SOCIAL_LOGIN_APPLE_STATUS === 'true') { ?>
        <a href="<?php echo zen_href_link('social_oauth_callback', 'provider=apple', 'SSL'); ?>" class="btn-apple-standard">
            <div class="apple-icon-wrapper">
                <svg viewBox="0 0 384 512" class="apple-icon" fill="#ffffff">
                    <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"></path>
                </svg>
            </div>
            <span class="social-text"><?= TEXT_SOCIAL_LOGIN_APPLE ?></span>
        </a>
    <?php } ?>

</div>
