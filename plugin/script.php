<?php

/**
 * @package    nxd_email_authentication
 *
 * @author     NXD nx-designs Marco Rensch <support@nx-designs.ch>
 * @copyright  Copyright (C) 2026 NXD. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://www.nx-designs.ch
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;

/**
 * Installer script for the nxd_email_authentication plugin.
 *
 * Returned as an object implementing InstallerScriptInterface; the old convention of declaring a
 * plgAuthenticationNxdemailauthInstallerScript class is deprecated and gone in Joomla 6.
 *
 * @since  2.0.0
 */
return new class () implements InstallerScriptInterface {
    /**
     * Lowest supported Joomla version.
     *
     * @var    string
     * @since  2.0.0
     */
    private $minimumJoomlaVersion = '5.0';

    /**
     * Lowest supported PHP version.
     *
     * @var    string
     * @since  2.0.0
     */
    private $minimumPhpVersion = '8.1';

    /**
     * Runs before install, update and uninstall, and before any file is copied.
     *
     * @param   string            $type     The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            $this->enqueueMessage(
                Text::sprintf(
                    'PLG_AUTHENTICATION_NXDEMAILAUTH_INSTALLERSCRIPT_MINIMUM_JOOMLA',
                    $this->minimumJoomlaVersion
                ),
                'error'
            );

            return false;
        }

        if (version_compare(PHP_VERSION, $this->minimumPhpVersion, '<')) {
            $this->enqueueMessage(
                Text::sprintf(
                    'PLG_AUTHENTICATION_NXDEMAILAUTH_INSTALLERSCRIPT_MINIMUM_PHP',
                    $this->minimumPhpVersion
                ),
                'error'
            );

            return false;
        }

        return true;
    }

    /**
     * Runs on a fresh install.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on update.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on uninstall.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs after install, update and uninstall, once the files are in place.
     *
     * @param   string            $type     The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $this->removeLegacyFiles();

        if ($type === 'install' || $type === 'discover_install') {
            $this->enqueueMessage(Text::_('PLG_AUTHENTICATION_NXDEMAILAUTH_INFO_ENABLE_PLUGIN'));
        }

        return true;
    }

    /**
     * Deletes files left over from the 1.x layout.
     *
     * An update keeps files that are no longer part of the manifest. The 1.x entry point has to go:
     * Joomla only falls back to it when services/provider.php is missing, but leaving a stale copy
     * of the old plugin class behind is asking for trouble on the next refactor.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function removeLegacyFiles(): void
    {
        $legacyFiles = [
            JPATH_PLUGINS . '/authentication/nxdemailauth/nxdemailauth.php',
        ];

        foreach ($legacyFiles as $file) {
            if (is_file($file)) {
                File::delete($file);
            }
        }
    }

    /**
     * Pushes a message onto the application message queue, if there is an application to talk to.
     *
     * @param   string  $message  The message to show
     * @param   string  $type     The Joomla message type
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function enqueueMessage(string $message, string $type = 'info'): void
    {
        $app = Factory::getApplication();

        if ($app !== null) {
            $app->enqueueMessage($message, $type);
        }
    }
};
