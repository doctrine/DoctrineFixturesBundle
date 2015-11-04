DoctrineFixturesBundle
======================

Fixtures are used to load a controlled set of data into a database. This data
can be used for testing or could be the initial data required for the
application to run smoothly. Symfony has no built in way to manage fixtures
but Doctrine2 has a library to help you write fixtures for the Doctrine
:doc:`ORM</book/doctrine>` or :doc:`ODM</bundles/DoctrineMongoDBBundle/index>`.

Setup and Configuration
-----------------------

Doctrine fixtures for Symfony are maintained in the `DoctrineFixturesBundle`_,
which uses external `Doctrine Data Fixtures`_ library.

Follow these steps to install the bundle in your Symfony applications:

Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

.. code-block:: bash

    composer require --dev doctrine/doctrine-fixtures-bundle

This command requires you to have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

Step 2: Enable the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Then, add the following line in the ``app/AppKernel.php`` file to enable this
bundle only for the ``dev`` and ``test`` environments:

.. code-block:: php

    // app/AppKernel.php
    // ...

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            // ...
            if (in_array($this->getEnvironment(), array('dev', 'test'))) {
                $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
            }

            return $bundles
        }

        // ...
    }

Writing Simple Fixtures
-----------------------

Doctrine2 fixtures are PHP classes where you can create objects and persist
them to the database. Like all classes in Symfony, fixtures should live inside
one of your application bundles.

For a bundle located at ``src/AppBundle``, the fixture classes should live inside
``src/AppBundle/DataFixtures/ORM`` or ``src/AppBundle/DataFixtures/MongoDB``
respectively for the ORM and ODM. This tutorial assumes that you are using the ORM,
but fixtures can be added just as easily if you're using the ODM.

Imagine that you have a ``User`` class, and you'd like to load one ``User``
entry:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/LoadUserData.php

    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use AppBundle\Entity\User;

    class LoadUserData implements FixtureInterface
    {
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

Loading Fixtures
----------------

Once your fixtures have been written, you can load them via the command
line by using the ``doctrine:fixtures:load`` command:

.. caution::

    By default the ``load`` command purges the database, removing all data from every table.
    To append your fixtures' data specify the ``--append`` option.

.. code-block:: bash

    php app/console doctrine:fixtures:load

If you're using the ODM, use the ``doctrine:mongodb:fixtures:load`` command instead:

.. code-block:: bash

    php app/console doctrine:mongodb:fixtures:load

The task will look inside the ``DataFixtures/ORM/`` (or ``DataFixtures/MongoDB/``
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
For example, what if you load a ``User`` object in one fixture, and then want to
refer to it in a different fixture in order to assign that user to a particular
group?

The Doctrine fixtures library handles this easily by allowing you to specify
the order in which fixtures are loaded.

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/LoadUserData.php
    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use AppBundle\Entity\User;

    class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            $userAdmin = new User();
            $userAdmin->setUsername('admin');
            $userAdmin->setPassword('test');

            $manager->persist($userAdmin);
            $manager->flush();

            $this->addReference('admin-user', $userAdmin);
        }

        public function getOrder()
        {
            // the order in which fixtures will be loaded
            // the lower the number, the sooner that this fixture is loaded
            return 1;
        }
    }

The fixture class now implements ``OrderedFixtureInterface``, which tells
Doctrine that you want to control the order of your fixtures. Create another
fixture class and make it load after ``LoadUserData`` by returning an order
of 2:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/LoadGroupData.php
    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use ApBundle\Entity\Group;

    class LoadGroupData extends AbstractFixture implements OrderedFixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            $groupAdmin = new Group();
            $groupAdmin->setGroupName('admin');

            $manager->persist($groupAdmin);
            $manager->flush();

            $this->addReference('admin-group', $groupAdmin);
        }

        public function getOrder()
        {
            // the order in which fixtures will be loaded
            // the lower the number, the sooner that this fixture is loaded
            return 2;
        }
    }

Both of the fixture classes extend ``AbstractFixture``, which allows you
to create objects and then set them as references so that they can be used
later in other fixtures. For example, the ``$userAdmin`` and ``$groupAdmin``
objects can be referenced later via the ``admin-user`` and ``admin-group``
references:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/LoadUserGroupData.php
    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\AbstractFixture;
    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use AppBundle\Entity\UserGroup;

    class LoadUserGroupData extends AbstractFixture implements OrderedFixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            $userGroupAdmin = new UserGroup();
            $userGroupAdmin->setUser($this->getReference('admin-user'));
            $userGroupAdmin->setGroup($this->getReference('admin-group'));

            $manager->persist($userGroupAdmin);
            $manager->flush();
        }

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
Symfony makes it really easy: the container will be injected in all fixture
classes implementing :class:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`.

Let's rewrite the first fixture to encode the password before it's stored
in the database (a very good practice). This will use the encoder factory
to encode the password, ensuring it is encoded in the way used by the security
component when checking it:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/LoadUserData.php
    namespace AppBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use AppBundle\Entity\User;

    class LoadUserData implements FixtureInterface, ContainerAwareInterface
    {
        /**
         * @var ContainerInterface
         */
        private $container;

        public function setContainer(ContainerInterface $container = null)
        {
            $this->container = $container;
        }

        public function load(ObjectManager $manager)
        {

            $user = new User();
            $user->setUsername('admin');
            $user->setSalt(md5(uniqid()));

            // the 'security.password_encoder' service requires Symfony 2.6 or higher
            $encoder = $this->container->get('security.password_encoder');
            $password = $encoder->encodePassword($user, 'secret_password');
            $user->setPassword($password);

            $manager->persist($user);
            $manager->flush();
        }
    }

As you can see, all you need to do is add :class:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`
to the class and then create a new :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`
method that implements that interface. Before the fixture is executed, Symfony
will call the :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`
method automatically. As long as you store the container as a property in the
class (as shown above), you can access it in the ``load()`` method.

.. note::

    If you prefer not to implement the needed method :method:`Symfony\\Component\\DependencyInjection\\ContainerInterface::setContainer`,
    you can then extend your class with :class:`Symfony\\Component\\DependencyInjection\\ContainerAware`.

.. _DoctrineFixturesBundle: https://github.com/doctrine/DoctrineFixturesBundle
.. _`Doctrine Data Fixtures`: https://github.com/doctrine/data-fixtures
.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
