DoctrineFixturesBundle
======================

Fixtures are used to load a controlled set of data into a database. This data
can be used for testing or could be the initial data required for the
application to run smoothly. Symfony2 has no built in way to manage fixtures
but Doctrine2 has a library to help you write fixtures for the Doctrine
:doc:`ORM</book/doctrine>` or :doc:`ODM</bundles/DoctrineMongoDBBundle/index>`.

Setup and Configuration
-----------------------

Doctrine fixtures for Symfony are maintained in the `DoctrineFixturesBundle`_.
The bundle uses external `Doctrine Data Fixtures`_ library.

Follow these steps to install the bundle and the library in the Symfony
Standard edition. Add the following to your ``composer.json`` file:

.. code-block:: json

    {
        "require": {
            "doctrine/doctrine-fixtures-bundle": "dev-master"
        }
    }

Update the vendor libraries:

.. code-block:: bash

    $ php composer.phar update

If everything worked, the ``DoctrineFixturesBundle`` can now be found
at ``vendor/doctrine/doctrine-fixtures-bundle``.

.. note::

    ``DoctrineFixturesBundle`` installs
    `Doctrine Data Fixtures`_ library. The library can be found
    at ``vendor/doctrine/data-fixtures``.

Finally, register the Bundle ``DoctrineFixturesBundle`` in ``app/AppKernel.php``.

.. code-block:: php

    // ...
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            // ...
        );
        // ...
    }

Writing Simple Fixtures
-----------------------

Doctrine2 fixtures are PHP classes where you can create objects and persist
them to the database. Like all classes in Symfony2, fixtures should live inside
one of your application bundles.

For a bundle located at ``src/Acme/HelloBundle``, the fixture classes
should live inside ``src/Acme/HelloBundle/DataFixtures/ORM`` or
``src/Acme/HelloBundle/DataFixtures/MongoDB`` respectively for the ORM and ODM,
This tutorial assumes that you are using the ORM - but fixtures can be added
just as easily if you're using the ODM.

Imagine that you have a ``User`` class, and you'd like to load one ``User``
entry:

.. code-block:: php

    // src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

    namespace Acme\HelloBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Acme\HelloBundle\Entity\User;

    class LoadUserData implements FixtureInterface
    {
        /**
         * {@inheritDoc}
         */
        public function load(ObjectManager $manager)
        {
            $userAdmin = new User();
            $userAdmin->setUsername('admin');
            $userAdmin->setPassword('test');

            $manager->persist($userAdmin);
            $manager->flush();
        }
    }

In Doctrine2, fixtures are just objects where you load data by interacting
with your entities as you normally do. This allows you to create the exact
fixtures you need for your application.

The most serious limitation is that you cannot share objects between fixtures.
Later, you'll see how to overcome this limitation.

Executing Fixtures
------------------

Once your fixtures have been written, you can load them via the command
line by using the ``doctrine:fixtures:load`` command:

.. code-block:: bash

    php app/console doctrine:fixtures:load

If you're using the ODM, use the ``doctrine:mongodb:fixtures:load`` command instead:

.. code-block:: bash

    php app/console doctrine:mongodb:fixtures:load

The task will look inside the ``DataFixtures/ORM`` (or ``DataFixtures/MongoDB``
for the ODM) directory of each bundle and execute each class that implements
the ``FixtureInterface``.

Both commands come with a few options:

* ``--fixtures=/path/to/fixture`` - Use this option to manually specify the
  directory where the fixtures classes should be loaded;

* ``--append`` - Use this flag to append data instead of deleting data before
  loading it (deleting first is the default behavior);

* ``--em=manager_name`` - Manually specify the entity manager to use for
  loading the data.

.. note::

   If using the ``doctrine:mongodb:fixtures:load`` task, replace the ``--em=``
   option with ``--dm=`` to manually specify the document manager.

A full example use might look like this:

.. code-block:: bash

   php app/console doctrine:fixtures:load --fixtures=/path/to/fixture1 --fixtures=/path/to/fixture2 --append --em=foo_manager

Sharing Objects between Fixtures
--------------------------------

Writing a basic fixture is simple. But what if you have multiple fixture classes
and want to be able to refer to the data loaded in other fixture classes?
For example, what if you load a ``User`` object in one fixture, and then
want to refer to reference it in a different fixture in order to assign that
user to a particular group?

The Doctrine fixtures library handles this easily by allowing you to specify
the order in which fixtures are loaded.

.. code-block:: php

    // src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php
    namespace Acme\HelloBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Acme\HelloBundle\Entity\User;

    class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
    {
        /**
         * {@inheritDoc}
         */
        public function load(ObjectManager $manager)
        {
            $userAdmin = new User();
            $userAdmin->setUsername('admin');
            $userAdmin->setPassword('test');

            $manager->persist($userAdmin);
            $manager->flush();

            $this->addReference('admin-user', $userAdmin);
        }

        /**
         * {@inheritDoc}
         */
        public function getOrder()
        {
            return 1; // the order in which fixtures will be loaded
        }
    }

The fixture class now implements ``OrderedFixtureInterface``, which tells
Doctrine that you want to control the order of your fixtures. Create another
fixture class and make it load after ``LoadUserData`` by returning an order
of 2:

.. code-block:: php

    // src/Acme/HelloBundle/DataFixtures/ORM/LoadGroupData.php

    namespace Acme\HelloBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Acme\HelloBundle\Entity\Group;

    class LoadGroupData extends AbstractFixture implements OrderedFixtureInterface
    {
        /**
         * {@inheritDoc}
         */
        public function load(ObjectManager $manager)
        {
            $groupAdmin = new Group();
            $groupAdmin->setGroupName('admin');

            $manager->persist($groupAdmin);
            $manager->flush();

            $this->addReference('admin-group', $groupAdmin);
        }

        /**
         * {@inheritDoc}
         */
        public function getOrder()
        {
            return 2; // the order in which fixtures will be loaded
        }
    }

Both of the fixture classes extend ``AbstractFixture``, which allows you
to create objects and then set them as references so that they can be used
later in other fixtures. For example, the ``$userAdmin`` and ``$groupAdmin``
objects can be referenced later via the ``admin-user`` and ``admin-group``
references:

.. code-block:: php

    // src/Acme/HelloBundle/DataFixtures/ORM/LoadUserGroupData.php

    namespace Acme\HelloBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Acme\HelloBundle\Entity\UserGroup;

    class LoadUserGroupData extends AbstractFixture implements OrderedFixtureInterface
    {
        /**
         * {@inheritDoc}
         */
        public function load(ObjectManager $manager)
        {
            $userGroupAdmin = new UserGroup();
            $userGroupAdmin->setUser($this->getReference('admin-user'));
            $userGroupAdmin->setGroup($this->getReference('admin-group'));

            $manager->persist($userGroupAdmin);
            $manager->flush();
        }

        /**
         * {@inheritDoc}
         */
        public function getOrder()
        {
            return 3;
        }
    }

The fixtures will now be executed in the ascending order of the value returned
by ``getOrder()``. Any object that is set with the ``setReference()`` method
can be accessed via ``getReference()`` in fixture classes that have a higher
order.

Fixtures allow you to create any type of data you need via the normal PHP
interface for creating and persisting objects. By controlling the order of
fixtures and setting references, almost anything can be handled by fixtures.

Using the Container in the Fixtures
-----------------------------------

In some cases you may need to access some services to load the fixtures.
Symfony2 makes it really easy: the container will be injected in all fixture
classes implementing :class:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`.

Let's rewrite the first fixture to encode the password before it's stored
in the database (a very good practice). This will use the encoder factory
to encode the password, ensuring it is encoded in the way used by the security
component when checking it:

.. code-block:: php

    // src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

    namespace Acme\HelloBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Acme\HelloBundle\Entity\User;

    class LoadUserData implements FixtureInterface, ContainerAwareInterface
    {
        /**
         * @var ContainerInterface
         */
        private $container;

        /**
         * {@inheritDoc}
         */
        public function setContainer(ContainerInterface $container = null)
        {
            $this->container = $container;
        }

        /**
         * {@inheritDoc}
         */
        public function load(ObjectManager $manager)
        {
            $user = new User();
            $user->setUsername('admin');
            $user->setSalt(md5(uniqid()));

            $encoder = $this->container
                ->get('security.encoder_factory')
                ->getEncoder($user)
            ;
            $user->setPassword($encoder->encodePassword('secret', $user->getSalt()));

            $manager->persist($user);
            $manager->flush();
        }
    }

As you can see, all you need to do is add :class:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`
to the class and then create a new :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`
method that implements that interface. Before the fixture is executed, Symfony
will call the :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`
method automatically. As long as you store the container as a property on the
class (as shown above), you can access it in the ``load()`` method.

.. note::

    If you are too lazy to implement the needed method :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`,
    you can then extend your class with :class:`Symfony\\Component\\DependencyInjection\\ContainerAware`.

.. _DoctrineFixturesBundle: https://github.com/doctrine/DoctrineFixturesBundle
.. _`Doctrine Data Fixtures`: https://github.com/doctrine/data-fixtures
