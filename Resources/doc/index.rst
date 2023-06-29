DoctrineFixturesBundle
======================

Fixtures are used to load a "fake" set of data into a database that can then
be used for testing or to help give you some interesting data while you're
developing your application.

This bundle is compatible with any database supported by `Doctrine ORM`_
(MySQL, PostgreSQL, SQLite, etc.). If you are using MongoDB, you must use
`DoctrineMongoDBBundle`_ instead.

Installation
------------

In Symfony 4 or higher applications that use `Symfony Flex`_, open a command
console, enter your project directory and run the following command:

.. code-block:: terminal

    $ composer require --dev orm-fixtures

Starting from Symfony 4.0, Flex should be used by default and register the
bundle for you, and in that case you can skip to the next section and start
writing fixtures.

In Symfony 3 applications (or when not using Symfony Flex), run this other
command instead:

.. code-block:: terminal

    $ composer require --dev doctrine/doctrine-fixtures-bundle

You will also need to enable the bundle. In Symfony 3 and earlier applications,
update the ``AppKernel`` class::

    // app/AppKernel.php

    // ...
    // registerBundles()
    if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
        // ...
        $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
    }

Writing Fixtures
----------------

Data fixtures are PHP classes where you create objects and persist them to the
database.

Imagine that you want to add some ``Product`` objects to your database. No problem!
Create a fixtures class and start adding products::

    // src/DataFixtures/AppFixtures.php
    namespace App\DataFixtures;

    use App\Entity\Product;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;

    class AppFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            // create 20 products! Bam!
            for ($i = 0; $i < 20; $i++) {
                $product = new Product();
                $product->setName('product '.$i);
                $product->setPrice(mt_rand(10, 100));
                $manager->persist($product);
            }

            $manager->flush();
        }
    }

Loading Fixtures
----------------

Once your fixtures have been written, load them by executing this command:

.. code-block:: terminal

    # when using the ORM
    $ php bin/console doctrine:fixtures:load

.. caution::

    By default the ``load`` command **purges the database**, removing all data
    from every table. To append your fixtures' data add the ``--append`` option.

This command looks for all services tagged with ``doctrine.fixture.orm``. If you're
using the `default service configuration`_, any class that implements ``ORMFixtureInterface``
(for example, those extending from ``Fixture``) will automatically be registered
with this tag.

To see other options for the command, run:

.. code-block:: terminal

    $ php bin/console doctrine:fixtures:load --help

Accessing Services from the Fixtures
------------------------------------

In some cases you may need to access your application's services inside a fixtures
class. No problem! Your fixtures class is a service, so you can use normal dependency
injection::

    // src/DataFixtures/AppFixtures.php
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class AppFixtures extends Fixture
    {
        private UserPasswordHasherInterface $hasher;

        public function __construct(UserPasswordHasherInterface $hasher)
        {
            $this->hasher = $hasher;
        }

        // ...
        public function load(ObjectManager $manager)
        {
            $user = new User();
            $user->setUsername('admin');

            $password = $this->hasher->hashPassword($user, 'pass_1234');
            $user->setPassword($password);

            $manager->persist($user);
            $manager->flush();
        }
    }

.. _multiple-files:

Splitting Fixtures into Separate Files
--------------------------------------

In most applications, creating all your fixtures in just one class is fine.
This class may end up being a bit long, but it's worth it because having one
file helps keeping things simple.

If you do decide to split your fixtures into separate files, Symfony helps you
solve the two most common issues: sharing objects between fixtures and loading
the fixtures in order.

Sharing Objects between Fixtures
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When using multiple fixtures files, you can reuse PHP objects across different
files thanks to the **object references**. Use the ``addReference()`` method to
give a name to any object and then, use the ``getReference()`` method to get the
exact same object via its name.

.. note::

    Adding object references only works for ORM entities or ODM documents.

.. code-block:: php

    // src/DataFixtures/UserFixtures.php
    // ...
    class UserFixtures extends Fixture
    {
        public const ADMIN_USER_REFERENCE = 'admin-user';

        public function load(ObjectManager $manager)
        {
            $userAdmin = new User('admin', 'pass_1234');
            $manager->persist($userAdmin);
            $manager->flush();

            // other fixtures can get this object using the UserFixtures::ADMIN_USER_REFERENCE constant
            $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);
        }
    }

.. code-block:: php

    // src/DataFixtures/GroupFixtures.php
    // ...
    class GroupFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            $userGroup = new Group('administrators');
            // this reference returns the User object created in UserFixtures
            $userGroup->addUser($this->getReference(UserFixtures::ADMIN_USER_REFERENCE));

            $manager->persist($userGroup);
            $manager->flush();
        }
    }

The only caveat of using references is that fixtures need to be loaded in a
certain order (in this example, if the ``Group`` fixtures are load before the
``User`` fixtures, you'll see an error). By default Doctrine loads the fixture
files in alphabetical order, but you can control their order as explained in the
next section.

Loading the Fixture Files in Order
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of defining the exact order in which all fixture files must be loaded,
Doctrine uses a smarter approach to ensure that some fixtures are loaded before
others. Implement the ``DependentFixtureInterface`` and add a new
``getDependencies()`` method to your fixtures class. This will return
an array of the fixture classes that must be loaded before this one::

    // src/DataFixtures/UserFixtures.php
    namespace App\DataFixtures;

    // ...
    class UserFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            // ...
        }
    }

    // src/DataFixtures/GroupFixtures.php
    namespace App\DataFixtures;
    // ...
    use App\DataFixtures\UserFixtures;
    use Doctrine\Common\DataFixtures\DependentFixtureInterface;

    class GroupFixtures extends Fixture implements DependentFixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            // ...
        }

        public function getDependencies()
        {
            return [
                UserFixtures::class,
            ];
        }
    }

Fixture Groups: Only Executing Some Fixtures
--------------------------------------------

By default, *all* of your fixture classes are executed. If you only want
to execute *some* of your fixture classes, you can organize them into
groups.

The simplest way to organize a fixture class into a group is to
make your fixture implement ``FixtureGroupInterface``:

.. code-block:: diff

    // src/DataFixtures/UserFixtures.php

    + use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

    - class UserFixtures extends Fixture
    + class UserFixtures extends Fixture implements FixtureGroupInterface
    {
        // ...

    +     public static function getGroups(): array
    +     {
    +         return ['group1', 'group2'];
    +     }
    }

To execute all of your fixtures for a given group, pass the ``--group``
option:

.. code-block:: terminal

    $ php bin/console doctrine:fixtures:load --group=group1

    # or to execute multiple groups
    $ php bin/console doctrine:fixtures:load --group=group1 --group=group2

Alternatively, instead of implementing the ``FixtureGroupInterface``,
you can also tag your service with ``doctrine.fixture.orm`` and add
an extra ``group`` option set to a group your fixture should belong to.

Regardless of groups defined in the fixture or the service definition, the
fixture loader always adds the short name of the class as a separate group so
you can load a single fixture at a time. In the example above, you can load the
fixture using the ``UserFixtures`` group:

.. code-block:: terminal

    $ php bin/console doctrine:fixtures:load --group=UserFixtures

.. _`ORM`: https://symfony.com/doc/current/doctrine.html
.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
.. _`default service configuration`: https://symfony.com/doc/current/service_container.html#service-container-services-load-example


Specifying purging behavior
---------------------------

By default all previously existing data is purged using ``DELETE FROM table`` statements. If you prefer to use
``TRUNCATE table`` statements for purging, use ``--purge-with-truncate``.

If you want to exclude a set of tables from being purged, e.g. because your schema comes with pre-populated,
semi-static data, pass the option ``--purge-exclusions``. Specify ``--purge-exclusions`` multiple times to exclude
multiple tables:

.. code-block:: terminal

    $ php bin/console doctrine:fixtures:load --purge-exclusions=post_category --purge-exclusions=comment_type

You can also customize purging behavior significantly more and implement a custom purger plus a custom purger factory::

    // src/Purger/CustomPurger.php
    namespace App\Purger;

    use Doctrine\Common\DataFixtures\Purger\PurgerInterface;

    // ...
    class CustomPurger implements PurgerInterface
    {
        public function purge() : void
        {
            // ...
        }
    }

    // src/Purger/CustomPurgerFactory.php
    namespace App\Purger;
    // ...
    use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;

    class CustomPurgerFactory implements PurgerFactory
    {
        public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false) : PurgerInterface
        {
            return new CustomPurger($em);
        }
    }

The next step is to register our custom purger factory and specify its alias.

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml
        services:
            App\Purger\CustomPurgerFactory:
                tags:
                    - { name: 'doctrine.fixtures.purger_factory', alias: 'my_purger' }

    .. code-block:: xml

        <!-- config/services.xml -->
        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services
                https://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <service id="App\Purger\CustomPurgerFactory">
                    <tag name="doctrine.fixtures.purger_factory" alias="my_purger"/>
                </service>
            </services>
        </container>

    .. code-block:: php

        // config/services.php
        namespace Symfony\Component\DependencyInjection\Loader\Configurator;

        use App\Purger\CustomerPurgerFactory;

        return function(ContainerConfigurator $configurator) : void {
            $services = $configurator->services();

            $services->set(CustomerPurgerFactory::class)
                ->tag('doctrine.fixtures.purger_factory', ['alias' => 'my_purger'])
            ;
        };

With the ``--purger`` option we can now specify to use ``my_purger`` instead of the ``default`` purger.

.. code-block:: terminal

    $ php bin/console doctrine:fixtures:load --purger=my_purger

.. _`Doctrine ORM`: https://symfony.com/doc/current/doctrine.html
.. _`DoctrineMongoDBBundle`: https://github.com/doctrine/DoctrineMongoDBBundle


How to load Fixtures from a different Directory
-----------------------------------------------
By default, fixtures are loaded from the ``src/DataFixtures`` directory.
In this example, we are going to load our DataFixtures from a new ``fixtures`` directory.

First, add a new ``PSR-4`` autoload-entry in the ``composer.json`` with the new ``fixtures`` directory:

.. code-block:: json

    "autoload-dev": {
        "psr-4": {
            "...": "...",
            "DataFixtures\\": "fixtures/"
        }
    },

.. note::

    You need to dump the autoloader with ``composer dump-autoloader``

Then, enable Dependency Injection for the ``fixtures`` directory:

.. configuration-block::

    .. code-block:: yaml
    
        # config/services.yaml
        services:
            DataFixtures\:
                resource: '../fixtures'
    
    .. code-block:: php
    
        // config/services.php
        namespace Symfony\Component\DependencyInjection\Loader\Configurator;
    
        return function(ContainerConfigurator $container) : void {
            $services = $container->services()
                ->defaults()
                    ->autowire()
                    ->autoconfigure();
    
            $services->load('DataFixtures\\', '../fixtures');
        };

.. caution::

    This will not override the default ``src/DataFixtures`` directory when creating fixtures with the
    `Symfony MakerBundle`_ (``make:fixtures``).

.. _`Symfony MakerBundle`: https://symfony.com/bundles/SymfonyMakerBundle/current/index.html
