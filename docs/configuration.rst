Configuration reference
=======================

All available configuration options are listed below with their default values.

.. code-block:: yaml

    nucleos_user_admin:
        security_acl:               false
        impersonating:
            route: 'my_custom_route'
            parameters:
                foo: bar
        admin:
            group:
                class:              'Nucleos\UserAdminBundle\Admin\Entity\GroupAdmin'
                controller:         'Sonata\AdminBundle\Controller\CRUDController'
                translation:        'NucleosUserAdminBundle'
            user:
                class:              'Nucleos\UserAdminBundle\Admin\Entity\UserAdmin'
                controller:         'Sonata\AdminBundle\Controller\CRUDController'
                translation:        'NucleosUserAdminBundle'
        avatar:
            resolver:               'Nucleos\UserAdminBundle\Avatar\StaticAvatarResolver'
            default_avatar:         '/bundles/nucleosuseradmin/default_avatar.png'
