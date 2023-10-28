<?php
/**
 * @package    nxd_email_authentication
 *
 * @author     proximate <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Nxd_email_authentication script file.
 *
 * @package   nxd_email_authentication
 * @since     1.0.0
 */
class plgAuthenticationNxdemailauthInstallerScript
{
    private $minimumJoomlaVersion = '4.4';

    private $minimumPhpVersion = JOOMLA_MINIMUM_PHP;

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, $parent) {

    }

	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, $parent) {

        if($route === 'install'){
            $app = \Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage(Text::_('PLG_AUTHENTICATION_NXD_EMAIL_AUTH_INFO_ENABLE_PLUGIN'));
        }
    }

	/**
	 * Called on installation
	 *
	 *
	 * @return  boolean  True on success
	 */
	public function install($parent) {

        // Check for minimum Joomla version
        if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            throw new Exception(Text::sprintf('PLG_SYSTEM_NXD_EMAIL_AUTHENTICATION_INSTALLERSCRIPT_MINIMUM_JOOMLA', $this->minimumJoomlaVersion));
        }

        // Check for minimum PHP version
        if (!empty($this->minimumPhpVersion) && version_compare(PHP_VERSION, $this->minimumPhpVersion, '<')) {
            throw new Exception(Text::sprintf('PLG_SYSTEM_NXD_EMAIL_AUTHENTICATION_INSTALLERSCRIPT_MINIMUM_PHP', $this->minimumPhpVersion));
        }

    }

	/**
	 * Called on update
	 *
	 *
	 * @return  boolean  True on success
	 */
	public function update($parent) {

    }

	/**
	 * Called on uninstallation
	 *
	 */
	public function uninstall($parent) {

    }
}
