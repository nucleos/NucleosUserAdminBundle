Installation
============

Prerequisites
-------------

You need to configure the NucleosUserBundle and SonataAdminBundle first,
check `NucleosUserBundle documentation`_ and the `SonataAdminBundle documentation`_.

Translations
~~~~~~~~~~~~

If you wish to use default texts provided in this bundle, you have to make
sure you have translator enabled in your config.

.. code-block:: yaml

    # config/packages/framework.yaml
    framework:
        translator: ~

For more information about translations, check `Symfony documentation`_.

Installation
------------

1. Download NucleosUserAdminBundle using composer
2. Enable the Bundle
3. Configure the admin
4. Configure your application's security.yaml
5. Import NucleosUserAdminBundle routing

Step 1: Download NucleosUserAdminBundle using composer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Require the bundle with composer:

.. code-block:: bash

    $ composer require nucleos/user-admin-bundle

Step 2: Enable the bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Enable the bundle in the kernel:

.. code-block:: php-annotations

    // config/bundles.php
    return [
        // ...
        Nucleos\UserBundle\NucleosUserAdminBundle::class => ['all' => true],
        // ...
    ]


Step 3: Configure the admin
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Add the following configuration to your ``config/packages/sonata_admin.yaml``.

.. code-block:: yaml

    # config/packages/sonata_admin.yaml
    sonata_admin:
        templates:
            user_block: '@NucleosUserAdmin/Admin/Core/user_block.html.twig'

Step 4: Configure your application's security.yaml
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In addition to the NucleosUserBundle configuration, you have to define some new access rules.

Below is a minimal example of the configuration necessary to use the NucleosUserBundle
in your application:

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        // ...

        access_control:
            # Admin login page needs to be accessed without credential
            - { path: ^/admin/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/logout$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/login_check$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/, role: [ROLE_ADMIN, ROLE_SONATA_ADMIN] }

You also need to define some new firewall rules, so the admin backend is protected for normal users.

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        firewalls:
            // ...

            # firewall for the admin area of the URL
            admin:
                pattern:            /admin(.*)
                context:            user
                form_login:
                    provider:       nucleos_userbundle
                    login_path:     /admin/login
                    use_forward:    false
                    check_path:     /admin/login_check
                    failure_path:   null
                logout:
                    path:           /admin/logout
                    target:         /admin/login
                anonymous:          true

        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_SONATA_ADMIN]
            ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

Step 5: Import NucleosUserAdminBundle routing files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now that you have activated and configured the bundle, all that is left to do is
import the NucleosUserAdminBundle routing files.

.. code-block:: yaml

    # config/routes/nucleos_user_admin.yaml
    nucleos_user_admin_admin_security:
        resource: "@NucleosUserAdminBundle/Resources/config/routing/all.xml"


.. _Symfony documentation: https://symfony.com/doc/current/book/translation.html
.. _SonataAdminBundle documentation: https://sonata-project.org/bundles/admin
.. _NucleosUserBundle documentation: https://docs.nucleos.rocks/projects/user-bundle/en/latest/
