<?php
declare(strict_types=1);

// Load the bundled Composer autoloader
require_once dirname(__DIR__, 3) . '/classes/vendor/autoload.php';

global $db, $messageStack;

$providerName = $_GET['provider'] ?? '';
// Pass 'false' as the 4th parameter to prevent zenid appending
$rawUrl = zen_href_link('social_oauth_callback', 'provider=' . $providerName, 'SSL', false);
// Decode the ampersand so the OAuth APIs receive the raw URL
$redirectUri = str_replace('&amp;', '&', $rawUrl);
$oauthProvider = null;
$dbColumn = '';

// Initialize the correct PHP League Provider
switch ($providerName) {
    case 'google':
        if (defined('SOCIAL_LOGIN_GOOGLE_STATUS') && SOCIAL_LOGIN_GOOGLE_STATUS === 'true') {
            $oauthProvider = new \League\OAuth2\Client\Provider\Google([
                'clientId'     => SOCIAL_LOGIN_GOOGLE_CLIENT_ID,
                'clientSecret' => SOCIAL_LOGIN_GOOGLE_CLIENT_SECRET,
                'redirectUri'  => $redirectUri,
            ]);
            $dbColumn = 'google_auth_id';
        }
        break;

    case 'facebook':
        if (defined('SOCIAL_LOGIN_FACEBOOK_STATUS') && SOCIAL_LOGIN_FACEBOOK_STATUS === 'true') {
            $oauthProvider = new \League\OAuth2\Client\Provider\Facebook([
                'clientId'          => SOCIAL_LOGIN_FACEBOOK_CLIENT_ID,
                'clientSecret'      => SOCIAL_LOGIN_FACEBOOK_CLIENT_SECRET,
                'redirectUri'       => $redirectUri,
                'graphApiVersion'   => 'v19.0',
            ]);
            $dbColumn = 'facebook_auth_id';
        }
        break;

    case 'apple':
        if (defined('SOCIAL_LOGIN_APPLE_STATUS') && SOCIAL_LOGIN_APPLE_STATUS === 'true') {
            $oauthProvider = new \League\OAuth2\Client\Provider\Apple([
                'clientId'          => SOCIAL_LOGIN_APPLE_CLIENT_ID,
                'teamId'            => SOCIAL_LOGIN_APPLE_TEAM_ID,
                'keyFileId'         => SOCIAL_LOGIN_APPLE_KEY_ID,
                'keyFilePath'       => SOCIAL_LOGIN_APPLE_KEY_FILE,
                'redirectUri'       => $redirectUri,
            ]);
            $dbColumn = 'apple_auth_id';
        }
        break;
}

if (!$oauthProvider) {
    $messageStack->add_session('header', TEXT_INVALID_PROVIDER, 'error');
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

// Handle OAuth Flow
// Catch cancellations and provider errors
$oauthError = $_GET['error'] ?? $_POST['error'] ?? null;
$oauthErrorDesc = $_GET['error_description'] ?? $_POST['error_description'] ?? null;

if ($oauthError) {
    $errorText = TEXT_LOGIN_CANCELLED;

    if (!empty($oauthErrorDesc)) {
        // Decode and escape the provider's error string to prevent XSS injection in the banner
        $cleanDesc = zen_output_string_protected(urldecode($oauthErrorDesc));
        $errorText .= TEXT_LOGIN_CANCELLED_REASON . $cleanDesc;
    }

    $messageStack->add_session('header', $errorText, 'warning');
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

// Session Recovery Bounce: Catch cross-origin POST requests and bounce them to a local GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['code'])) {

    // CRITICAL: Strip the new empty session cookie Zen Cart just tried to create.
    // This forces the browser to use its existing session cookie on the next request,
    // preserving both the shopping cart and the OAuth validation state
    header_remove('Set-Cookie');

    $bounceUrl = zen_href_link(
        'social_oauth_callback',
        'provider=' . $providerName . '&code=' . urlencode($_POST['code']) . '&state=' . urlencode($_POST['state'] ?? ''),
        'SSL',
        false
    );
    zen_redirect(str_replace('&amp;', '&', $bounceUrl));
}

$authCode = $_GET['code'] ?? null;
$authState = $_GET['state'] ?? null;

if (!$authCode) {
    // Send to Provider
    $options = [];
    if ($providerName === 'apple') {
        $options = ['scope' => 'name email', 'response_mode' => 'form_post'];
    } elseif ($providerName === 'google') {
        $options = ['response_mode' => 'form_post'];
    }

    $authUrl = $oauthProvider->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $oauthProvider->getState();
    zen_redirect($authUrl);

} else {
    // Handle Callback Validation
    if (empty($authState) || empty($_SESSION['oauth2state']) || $authState !== $_SESSION['oauth2state']) {
        unset($_SESSION['oauth2state']);
        $messageStack->add_session('header', TEXT_ERROR_INVALID_SESSION_STATE, 'error');
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }

    try {
        // Exchange code for token and get user details
        $token = $oauthProvider->getAccessToken('authorization_code', [
            'code' => $authCode
        ]);

        $user = $oauthProvider->getResourceOwner($token);

        $socialId = zen_db_prepare_input($user->getId());
        $emailRaw = $user->getEmail();

        if (empty($emailRaw)) {
            throw new \Exception(TEXT_ERROR_NO_EMAIL);
        }
        $email = zen_db_prepare_input($emailRaw);

        // Safe Name Extraction Fallbacks
        $firstNameRaw = TEXT_FALLBACK_FIRST_NAME;
        $lastNameRaw = TEXT_FALLBACK_LAST_NAME;

        if (method_exists($user, 'getFirstName') && !empty($user->getFirstName())) {
            $firstNameRaw = $user->getFirstName();
            $lastNameRaw = method_exists($user, 'getLastName') && !empty($user->getLastName()) ? $user->getLastName() : TEXT_FALLBACK_LAST_NAME;
        } elseif (method_exists($user, 'getName') && !empty($user->getName())) {
            $nameParts = explode(' ', trim($user->getName()), 2);
            $firstNameRaw = $nameParts[0];
            $lastNameRaw = $nameParts[1] ?? TEXT_FALLBACK_LAST_NAME;
        }

        $firstName = zen_db_prepare_input($firstNameRaw);
        $lastName = zen_db_prepare_input($lastNameRaw);

        // Database sync
        $sql = "SELECT customers_id, customers_firstname, customers_lastname, customers_default_address_id, customers_authorization
                FROM " . TABLE_CUSTOMERS . "
                WHERE {$dbColumn} = :socialId LIMIT 1";
        $sql = $db->bindVars($sql, ':socialId', $socialId, 'string');
        $lookup = $db->Execute($sql);

        if (!$lookup->EOF) {
            $ses_customer_id = $lookup->fields['customers_id'];
            $ses_first_name = $lookup->fields['customers_firstname'];
            $ses_last_name = $lookup->fields['customers_lastname'];
            $ses_authorization = $lookup->fields['customers_authorization'];
            $ses_address_id = $lookup->fields['customers_default_address_id'];
        } else {
            $sqlEmail = "SELECT customers_id, customers_firstname, customers_lastname, customers_default_address_id, customers_authorization
                         FROM " . TABLE_CUSTOMERS . "
                         WHERE customers_email_address = :email LIMIT 1";
            $sqlEmail = $db->bindVars($sqlEmail, ':email', $email, 'string');
            $lookupEmail = $db->Execute($sqlEmail);

            if (!$lookupEmail->EOF) {
                $updateSql = "UPDATE " . TABLE_CUSTOMERS . "
                              SET {$dbColumn} = :socialId
                              WHERE customers_id = " . (int)$lookupEmail->fields['customers_id'];
                $updateSql = $db->bindVars($updateSql, ':socialId', $socialId, 'string');
                $db->Execute($updateSql);

                $ses_customer_id = $lookupEmail->fields['customers_id'];
                $ses_first_name = $lookupEmail->fields['customers_firstname'];
                $ses_last_name = $lookupEmail->fields['customers_lastname'];
                $ses_authorization = $lookupEmail->fields['customers_authorization'];
                $ses_address_id = $lookupEmail->fields['customers_default_address_id'];
            } else {
                $password = zen_encrypt_password(zen_create_random_value(20));

                $insertSql = "INSERT INTO " . TABLE_CUSTOMERS . "
                              (customers_firstname, customers_lastname, customers_email_address,
                               customers_telephone, customers_password, customers_newsletter,
                               customers_default_address_id, {$dbColumn}, customers_authorization)
                              VALUES
                              (:firstName, :lastName, :email, '', :password, 0, 0, :socialId, 0)";

                $insertSql = $db->bindVars($insertSql, ':firstName', $firstName, 'string');
                $insertSql = $db->bindVars($insertSql, ':lastName', $lastName, 'string');
                $insertSql = $db->bindVars($insertSql, ':email', $email, 'string');
                $insertSql = $db->bindVars($insertSql, ':password', $password, 'string');
                $insertSql = $db->bindVars($insertSql, ':socialId', $socialId, 'string');

                $db->Execute($insertSql);
                $newCustomerId = $db->Insert_ID();

                $db->Execute("INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                              (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created)
                              VALUES (" . (int)$newCustomerId . ", 0, now())");

                $ses_customer_id = $newCustomerId;
                $ses_first_name = $firstName;
                $ses_last_name = $lastName;
                $ses_authorization = 0;
                $ses_address_id = 0;
            }
        }

        if ($ses_authorization == 4) {
            $messageStack->add_session('header', TEXT_ERROR_ACCOUNT_BANNED, 'error');
            zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        $_SESSION['customer_id'] = $ses_customer_id;
        $_SESSION['customer_first_name'] = $ses_first_name;
        $_SESSION['customer_last_name'] = $ses_last_name;
        $_SESSION['customers_authorization'] = $ses_authorization;
        $_SESSION['customer_default_address_id'] = $ses_address_id;

        $_SESSION['customer_country_id'] = 0;
        $_SESSION['customer_zone_id'] = 0;

        if ($_SESSION['customer_default_address_id'] > 0) {
            $addressSql = "SELECT entry_country_id, entry_zone_id
                           FROM " . TABLE_ADDRESS_BOOK . "
                           WHERE address_book_id = " . (int)$_SESSION['customer_default_address_id'] . "
                           AND customers_id = " . (int)$_SESSION['customer_id'];
            $address = $db->Execute($addressSql);

            if (!$address->EOF) {
                $_SESSION['customer_country_id'] = $address->fields['entry_country_id'];
                $_SESSION['customer_zone_id'] = $address->fields['entry_zone_id'];
            }
        }

        $db->Execute("UPDATE " . TABLE_CUSTOMERS_INFO . "
                      SET customers_info_date_of_last_logon = now(),
                          customers_info_number_of_logons = customers_info_number_of_logons + 1
                      WHERE customers_info_id = " . (int)$_SESSION['customer_id']);

        if (isset($_SESSION['cart']) && is_object($_SESSION['cart'])) {
            $_SESSION['cart']->restore_contents();
        }

        // Restore navigation and flow
        if (isset($_SESSION['navigation']) && is_object($_SESSION['navigation']) && sizeof($_SESSION['navigation']->snapshot) > 0) {
            // Restore snapshot path (e.g. they clicked checkout while logged out)
            $origin_href = zen_href_link(
                $_SESSION['navigation']->snapshot['page'],
                zen_array_to_string($_SESSION['navigation']->snapshot['get'], [zen_session_name()]),
                $_SESSION['navigation']->snapshot['mode']
            );
            $_SESSION['navigation']->clear_snapshot();
            zen_redirect($origin_href);
        } elseif (isset($_SESSION['cart']) && is_object($_SESSION['cart']) && $_SESSION['cart']->count_contents() > 0) {
            // Uninterrupted login flow, but they have items
            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
        } else {
            // Baseline fallback
            zen_redirect(zen_href_link(FILENAME_DEFAULT));
        }

    } catch (\Exception $e) {
        $messageStack->add_session('header', TEXT_LOGIN_FAILED . $e->getMessage(), 'error');
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
}
