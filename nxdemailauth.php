<?php
/**
 * @package    nxd_email_authentication
 *
 * @author     NXD nx-designs Marco Rensch <support@nx-designs.ch>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseDriver;

/**
 * Nxd_email_authentication plugin.
 *
 * Based on the default Joomla authentication plugin
 *
 * @package   nxd_email_authentication
 * @since     1.0.0
 */
class PlgAuthenticationNxdEmailAuth extends CMSPlugin implements SubscriberInterface
{
    /**
     * Database object
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    protected $db;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onUserAuthenticate' => 'authenticate',
        ];
    }

    public function authenticate(Joomla\CMS\Event\User\AuthenticationEvent $event)
    {

        $pluginParams = $this->params;
        $backendEnabled = $pluginParams->get('backend_enabled', 0);
        if(Factory::getApplication()->isClient('administrator') && !$backendEnabled) {
            return;
        }

        $credentials = $event->getCredentials();
        $response = $event->getAuthenticationResponse();
        $response->type = 'NXDEmailAuth';

        // Joomla does not like blank passwords
        if (empty($credentials['password'])) {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = Factory::getApplication()->getLanguage()->_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

            return;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'password']))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = :username')
            ->bind(':username', $credentials['username']);

        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            $match = UserHelper::verifyPassword($credentials['password'], $result->password, $result->id);

            if ($match === true) {
                // Bring this in line with the rest of the system
                $user = Factory::getUser($result->id);
                $response->username = $user->username;
                $response->fullname = $user->name;

                // Set default status response to success
                $_status = Authentication::STATUS_SUCCESS;
                $_errorMessage = '';

                if (Factory::getApplication()->isClient('administrator')) {
                    $response->language = $user->getParam('admin_language');
                } else {
                    $response->language = $user->getParam('language');

                    if (Factory::getApplication()->get('offline') && !$user->authorise('core.login.offline')) {
                        // User do not have access in offline mode
                        $_status = Authentication::STATUS_FAILURE;
                        $_errorMessage = Factory::getApplication()->getLanguage()->_('JLIB_LOGIN_DENIED');
                    }
                }

                $response->status = $_status;
                $response->error_message = $_errorMessage;

                // Stop event propagation when status is STATUS_SUCCESS
                if ($response->status === Authentication::STATUS_SUCCESS) {
                    $event->stopPropagation();
                }
            } else {
                // Invalid password
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Factory::getApplication()->getLanguage()->_('JGLOBAL_AUTH_INVALID_PASS');
            }
        } else {
            // Let's hash the entered password even if we don't have a matching user for some extra response time
            // By doing so, we mitigate side channel user enumeration attacks
            UserHelper::hashPassword($credentials['password']);

            // Invalid user
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = Factory::getApplication()->getLanguage()->_('JGLOBAL_AUTH_NO_USER');
        }

    }


}
