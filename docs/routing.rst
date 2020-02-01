Advanced routing configuration
==============================

By default, the routing file ``@NucleosUserAdminBundle/Resources/config/routing/all.xml`` imports
all the routing files and enables all the routes.
In the case you want to enable or disable the different available routes, use the
single routing configuration files.

.. code-block:: yaml

    # config/routes/nucleos_user_admin.yaml
    nucleos_user_admin_admin_security:
        resource: "@NucleosUserAdminBundle/Resources/config/routing/admin_security.xml"
        prefix: /admin

    nucleos_user_admin_admin_resetting:
        resource: "@NucleosUserAdminBundle/Resources/config/routing/admin_resetting.xml"
        prefix: /admin/resetting

