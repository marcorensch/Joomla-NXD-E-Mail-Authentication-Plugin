<?php

/**
 * @package    nxd_email_authentication
 *
 * @author     NXD nx-designs Marco Rensch <support@nx-designs.ch>
 * @copyright  Copyright (C) 2026 NXD. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://www.nx-designs.ch
 */

namespace Nxd\Plugin\Authentication\NxdEmailAuth\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Authenticates a user by their e-mail address instead of their username.
 *
 * Based on the default Joomla authentication plugin. The core plugin keeps handling
 * usernames; this one only ever acts on input that looks like an e-mail address.
 *
 * @package   nxd_email_authentication
 * @since     2.0.0
 */
final class NxdEmailAuth extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use UserFactoryAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onUserAuthenticate' => 'onUserAuthenticate'];
    }

    /**
     * Handles the authentication and reports back to the subject.
     *
     * @param   AuthenticationEvent  $event  Authentication event
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onUserAuthenticate(AuthenticationEvent $event): void
    {
        $app = $this->getApplication();

        // Logging into the administrator with an e-mail address is opt-in.
        if ($app->isClient('administrator') && !$this->params->get('backend_enabled', 0)) {
            return;
        }

        $credentials = $event->getCredentials();
        $response    = $event->getAuthenticationResponse();

        $response->type = 'NXDEmailAuth';

        // Joomla does not like blank passwords
        if (empty($credentials['password'])) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = $app->getLanguage()->_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

            return;
        }

        $email = trim((string) ($credentials['username'] ?? ''));

        /**
         * Anything that is not an e-mail address is none of our business - the core plugin
         * handles those. No timing mitigation is needed here: the attacker already knows
         * whether the value they submitted was an e-mail address.
         */
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = $app->getLanguage()->_('JGLOBAL_AUTH_NO_USER');

            return;
        }

        $matches = $this->findUsersByEmail($email);

        /**
         * Joomla enforces unique e-mail addresses in the user model, but not with a database
         * constraint. Imports, LDAP synchronisation and third party registration extensions can
         * still produce duplicates. Which row comes back first is undefined, so refuse to guess.
         */
        if (\count($matches) > 1) {
            UserHelper::hashPassword($credentials['password']);

            Log::add(
                \sprintf(
                    'Refused a login by e-mail address: %d accounts share the address %s.',
                    \count($matches),
                    $email
                ),
                Log::WARNING,
                'plg_authentication_nxdemailauth'
            );

            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = $app->getLanguage()->_('JGLOBAL_AUTH_NO_USER');

            return;
        }

        if (!$matches) {
            // Let's hash the entered password even if we don't have a matching user for some extra response time
            // By doing so, we mitigate side channel user enumeration attacks
            UserHelper::hashPassword($credentials['password']);

            // Invalid user
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = $app->getLanguage()->_('JGLOBAL_AUTH_NO_USER');

            return;
        }

        $result = $matches[0];

        // Passing the user id lets Joomla transparently upgrade an outdated password hash.
        if (UserHelper::verifyPassword($credentials['password'], $result->password, $result->id) !== true) {
            // Invalid password
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = $app->getLanguage()->_('JGLOBAL_AUTH_INVALID_PASS');

            return;
        }

        $user = $this->getUserFactory()->loadUserById($result->id);

        /**
         * Bring this in line with the rest of the system. Setting the username is what makes this
         * plugin work at all: plg_user_joomla resolves the account from $response->username in
         * onUserLogin, so it has to be the real username and not the address that was typed in.
         */
        $response->username = $user->username;
        $response->email    = $user->email;
        $response->fullname = $user->name;

        // Set default status response to success
        $_status       = Authentication::STATUS_SUCCESS;
        $_errorMessage = '';

        if ($app->isClient('administrator')) {
            $response->language = $user->getParam('admin_language');
        } else {
            $response->language = $user->getParam('language');

            if ($app->get('offline') && !$user->authorise('core.login.offline')) {
                // User do not have access in offline mode
                $_status       = Authentication::STATUS_FAILURE;
                $_errorMessage = $app->getLanguage()->_('JLIB_LOGIN_DENIED');
            }
        }

        $response->status        = $_status;
        $response->error_message = $_errorMessage;

        // Stop event propagation when status is STATUS_SUCCESS
        if ($response->status === Authentication::STATUS_SUCCESS) {
            $event->stopPropagation();
        }
    }

    /**
     * Looks up the accounts registered under an e-mail address.
     *
     * The exact comparison runs first because it can use the index on the column and, with the
     * case insensitive collations MySQL uses by default, already answers the question. Only when
     * that finds nothing do we fall back to a case insensitive lookup, which is what makes the
     * plugin behave the same way on PostgreSQL. The fallback therefore only ever costs a scan on
     * a failed login, where the dummy password hash dominates the response time anyway.
     *
     * At most two rows are fetched - enough to notice duplicates without loading them all.
     *
     * @param   string  $email  The e-mail address to look up
     *
     * @return  \stdClass[]  Rows holding the id and password hash
     *
     * @since   2.0.0
     */
    private function findUsersByEmail(string $email): array
    {
        return $this->queryUsersByEmail($email, false) ?: $this->queryUsersByEmail($email, true);
    }

    /**
     * Runs a single e-mail lookup against the user table.
     *
     * @param   string   $email            The e-mail address to look up
     * @param   boolean  $caseInsensitive  Whether to compare the column case insensitively
     *
     * @return  \stdClass[]  Rows holding the id and password hash
     *
     * @since   2.0.0
     */
    private function queryUsersByEmail(string $email, bool $caseInsensitive): array
    {
        $db     = $this->getDatabase();
        $column = $db->quoteName('email');

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'password']))
            ->from($db->quoteName('#__users'))
            ->where($caseInsensitive ? 'LOWER(' . $column . ') = LOWER(:email)' : $column . ' = :email')
            ->bind(':email', $email);

        $db->setQuery($query, 0, 2);

        return $db->loadObjectList() ?: [];
    }
}
