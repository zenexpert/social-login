<?php
/**
 * Social Login for Zen Cart 2.x
 * @package social-login
 * @copyright ZenExpert 2026
 * @version $Id: auto.social_login.php 2026 Sept 05 ZenExpert $
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

use Zencart\Traits\InteractsWithPlugins;
use Zencart\Traits\NotifierManager;
use Zencart\Traits\ObserverManager;

class zcObserverSocialLogin extends base
{
    use InteractsWithPlugins;
    use NotifierManager;
    use ObserverManager;

    private const string DUMMY_STREET_FLAG = TEXT_DUMMY_STREET_FLAG;

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_HTML_HEAD_END',
            'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
            'NOTIFY_HEADER_START_CHECKOUT_ONE',
            'NOTIFY_HEADER_START_ADDRESS_BOOK',
            'NOTIFY_FOOTER_END'
        ]);

        $this->detectZcPluginDetails(__DIR__);
    }

    public function update(&$class, $eventID, $paramsArray = []): void
    {
        // Get the page name whether Zen Cart passes it as a string or an array
        $currentPageBase = is_string($paramsArray) ? $paramsArray : (is_array($paramsArray) && isset($paramsArray[0]) ? (string)$paramsArray[0] : ($_GET['main_page'] ?? ''));

        // CSS Injection Hook
        if ($eventID === 'NOTIFY_HTML_HEAD_END') {
            if ((defined('SOCIAL_LOGIN_GOOGLE_STATUS') && SOCIAL_LOGIN_GOOGLE_STATUS === 'true') || (defined('SOCIAL_LOGIN_FACEBOOK_STATUS') && SOCIAL_LOGIN_FACEBOOK_STATUS === 'true') || (defined('SOCIAL_LOGIN_APPLE_STATUS') && SOCIAL_LOGIN_APPLE_STATUS === 'true')) {
                // Restrict injection strictly to the login page
                if ($currentPageBase === FILENAME_LOGIN) {
                    $this->linkCatalogStylesheet('social_login.css', $currentPageBase);
                }
            }
            return;
        }

        if ($eventID === 'NOTIFY_FOOTER_END' && $currentPageBase === FILENAME_LOGIN) {
            global $template;

            if ((defined('SOCIAL_LOGIN_GOOGLE_STATUS') && SOCIAL_LOGIN_GOOGLE_STATUS === 'true') ||
                (defined('SOCIAL_LOGIN_FACEBOOK_STATUS') && SOCIAL_LOGIN_FACEBOOK_STATUS === 'true') ||
                (defined('SOCIAL_LOGIN_APPLE_STATUS') && SOCIAL_LOGIN_APPLE_STATUS === 'true')) {

                // Capture the raw HTML from the Social Login template file
                ob_start();
                require($template->get_template_dir('tpl_social_login.php', DIR_WS_TEMPLATE, $currentPageBase, 'templates') . '/tpl_social_login.php');
                $socialHtml = ob_get_clean();

                // JSON encode the HTML string to safely handle multiline breaks and quotes in JS
                $jsHtml = json_encode($socialHtml);

                // Inject it into the DOM immediately before the native login form
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        // Scope to the login wrapper to prevent targeting site logos in the global header
                        var wrapper = document.getElementById("loginDefault") || document.querySelector(".centerColumn") || document.body;
                        var firstHeading = wrapper.querySelector("h1, h2, h3");

                        if (firstHeading) {
                            firstHeading.insertAdjacentHTML("afterend", ' . $jsHtml . ');
                        } else {
                            // Failsafe fallback just in case a highly custom template has no headings
                            var loginForm = document.querySelector("form[name=\'login\'], form[name=\'loginForm\']");
                            if (loginForm) {
                                loginForm.insertAdjacentHTML("beforebegin", ' . $jsHtml . ');
                            }
                        }
                    });
                </script>';
            }
        }

        // From here everything requires a logged-in session
        if (empty($_SESSION['customer_id'])) {
            return;
        }

        // Catch the user immediately as they return from creating a new address
        if ($eventID === 'NOTIFY_HEADER_START_ADDRESS_BOOK') {
            if (!empty($_SESSION['google_login_checkout_intercept'])) {
                $targetPage = $_SESSION['google_login_checkout_intercept'];
                unset($_SESSION['google_login_checkout_intercept']);
                zen_redirect(zen_href_link($targetPage, '', 'SSL'));
            }
            return;
        }

        global $db, $messageStack;

        $skipEnabled = (defined('SOCIAL_LOGIN_SKIP_FREE_CHECKOUT') && SOCIAL_LOGIN_SKIP_FREE_CHECKOUT === 'true');
        $isFreeCart = (isset($_SESSION['cart']) && is_object($_SESSION['cart']) && (float)$_SESSION['cart']->show_total() <= 0.0);

        $hasDummyAddress = false;
        if (!empty($_SESSION['customer_default_address_id'])) {
            $checkSql = "SELECT entry_street_address
                         FROM " . TABLE_ADDRESS_BOOK . "
                         WHERE address_book_id = " . (int)$_SESSION['customer_default_address_id'];
            $check = $db->Execute($checkSql);

            if (!$check->EOF && $check->fields['entry_street_address'] === self::DUMMY_STREET_FLAG) {
                $hasDummyAddress = true;
            }
        }

        // Self-Check Session Sync
        if (empty($_SESSION['customer_default_address_id']) || $hasDummyAddress) {

            $findRealSql = "SELECT address_book_id, entry_country_id, entry_zone_id
                            FROM " . TABLE_ADDRESS_BOOK . "
                            WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
                            AND entry_street_address != :dummyFlag
                            ORDER BY address_book_id DESC LIMIT 1";
            $findRealSql = $db->bindVars($findRealSql, ':dummyFlag', self::DUMMY_STREET_FLAG, 'string');
            $realAddress = $db->Execute($findRealSql);

            if (!$realAddress->EOF) {
                $newDefaultId = (int)$realAddress->fields['address_book_id'];

                $db->Execute("UPDATE " . TABLE_CUSTOMERS . "
                              SET customers_default_address_id = " . $newDefaultId . "
                              WHERE customers_id = " . (int)$_SESSION['customer_id']);

                if ($hasDummyAddress) {
                    $db->Execute("DELETE FROM " . TABLE_ADDRESS_BOOK . "
                                  WHERE address_book_id = " . (int)$_SESSION['customer_default_address_id']);
                }

                $_SESSION['customer_default_address_id'] = $newDefaultId;
                $_SESSION['customer_country_id'] = $realAddress->fields['entry_country_id'];
                $_SESSION['customer_zone_id'] = $realAddress->fields['entry_zone_id'];

                $_SESSION['sendto'] = $newDefaultId;
                $_SESSION['billto'] = $newDefaultId;

                $hasDummyAddress = false;
            }
        }

        // Address Routing
        if (empty($_SESSION['customer_default_address_id']) || $hasDummyAddress) {

            if ($skipEnabled && $isFreeCart) {
                if (empty($_SESSION['customer_default_address_id'])) {
                    $this->createPlaceholderAddress();
                }
            } else {
                $currentPage = $_GET['main_page'] ?? FILENAME_CHECKOUT_SHIPPING;
                $_SESSION['google_login_checkout_intercept'] = $currentPage;

                if (!isset($_SESSION['navigation'])) {
                    $_SESSION['navigation'] = new \navigationHistory();
                }
                $_SESSION['navigation']->set_snapshot(['page' => $currentPage, 'mode' => 'SSL']);

                if ($_GET['main_page'] !== FILENAME_ADDRESS_BOOK_PROCESS) {
                    $messageStack->add_session('addressbook', TEXT_PLEASE_ENTER_ADDRESS, 'warning');
                    zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'));
                }
            }
        }

        // Free Order Checkout Bypass
        if ($skipEnabled && $isFreeCart && $eventID !== 'NOTIFY_HEADER_START_CHECKOUT_ONE') {
            if (empty($_SESSION['sendto'])) {
                $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
            }
            if (empty($_SESSION['billto'])) {
                $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
            }

            if ($eventID === 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING') {
                if (empty($_POST['action'])) {
                    $_POST['action'] = 'process';
                    $_POST['shipping'] = 'freeshipper_freeshipper';
                    if (isset($_SESSION['securityToken'])) {
                        $_POST['securityToken'] = $_SESSION['securityToken'];
                    }
                }
            }

            if ($eventID === 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT') {
                if (empty($_POST['action'])) {
                    $_POST['action'] = 'submit';
                    $_POST['payment'] = 'freecharger';
                    $_POST['conditions'] = '1';
                    if (isset($_SESSION['securityToken'])) {
                        $_POST['securityToken'] = $_SESSION['securityToken'];
                    }
                }
            }
        }
    }

    private function createPlaceholderAddress(): void
    {
        global $db;

        $countryId = (int)STORE_COUNTRY;
        $zoneId = (int)STORE_ZONE;

        $sql = "INSERT INTO " . TABLE_ADDRESS_BOOK . "
                (customers_id, entry_firstname, entry_lastname, entry_street_address,
                 entry_postcode, entry_city, entry_country_id, entry_zone_id)
                VALUES (
                    :custId,
                    :firstName,
                    :lastName,
                    :street,
                    '00000',
                    'N/A',
                    :country,
                    :zone
                )";

        $sql = $db->bindVars($sql, ':custId', $_SESSION['customer_id'], 'integer');
        $sql = $db->bindVars($sql, ':firstName', $_SESSION['customer_first_name'], 'string');
        $sql = $db->bindVars($sql, ':lastName', $_SESSION['customer_last_name'], 'string');
        $sql = $db->bindVars($sql, ':street', self::DUMMY_STREET_FLAG, 'string');
        $sql = $db->bindVars($sql, ':country', $countryId, 'integer');
        $sql = $db->bindVars($sql, ':zone', $zoneId, 'integer');

        $db->Execute($sql);
        $newAddressId = $db->Insert_ID();

        $db->Execute("UPDATE " . TABLE_CUSTOMERS . "
                      SET customers_default_address_id = " . (int)$newAddressId . "
                      WHERE customers_id = " . (int)$_SESSION['customer_id']);

        $_SESSION['customer_default_address_id'] = $newAddressId;
        $_SESSION['customer_country_id'] = $countryId;
        $_SESSION['customer_zone_id'] = $zoneId;
    }
}
