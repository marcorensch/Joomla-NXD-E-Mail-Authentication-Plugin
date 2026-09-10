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

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Nxd\Plugin\Authentication\NxdEmailAuth\Extension\NxdEmailAuth;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new NxdEmailAuth(
                    (array) PluginHelper::getPlugin('authentication', 'nxdemailauth')
                );

                // The dispatcher is injected by PluginHelper::import() after the plugin is booted.
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));
                $plugin->setUserFactory($container->get(UserFactoryInterface::class));

                return $plugin;
            }
        );
    }
};
