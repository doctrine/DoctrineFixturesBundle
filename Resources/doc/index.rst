DoctrineFixturesBundle
======================

Fixtures are used to load a "fake" set of data into a database that can then
be used for testing or to help give you some interesting data while you're
developing your application. This bundle makes creating fixtures *easy*, and
supports the `ORM`_ (MySQL, PostgreSQL, SQLite, etc.) and `ODM`_ (MongoDB, etc.).

Setup and Configuration
-----------------------

Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open a command console, enter your project directory and run the
following command to download the latest stable version of this bundle:

.. code-block:: bash

    composer require --dev doctrine/doctrine-fixtures-bundle

This command assumes you have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

Step 2: Enable the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Next, add the following line to ``app/AppKernel.php`` to enable the
bundle for the ``dev`` and ``test`` environments only:

.. code-block:: php

    // app/AppKernel.php
    // ...

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            // ...
            if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
                // ...
                $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
            }

            return $bundles;
        }

        // ...
    }

Writing Fixtures
----------------

Data fixtures are PHP classes where you create objects and persist them to the
database. By default, these classes live in ``src/AppBundle/DataFixtures/ORM/``
(``src/AppBundle/DataFixtures/MongoDB/`` when using ODM).

Imagine that you want to add some ``Product`` objects to you database. No problem!
Just create a fixtures class and start adding products!

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/Fixtures.php
    namespace AppBundle\DataFixtures\ORM;

    use AppBundle\Entity\Product;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Common\Persistence\ObjectManager;

    class Fixtures extends Fixture
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

That's it! Inside ``load()``, create and persist as many objects as you want.

.. tip::

    You can also create multiple fixtures classes. See :ref:`multiple-files`.

Loading Fixtures
----------------

Once your fixtures have been written, load them by executing this command:

.. code-block:: bash

    # when using the ORM
    $ php bin/console doctrine:fixtures:load

    # when using the ODM
    $ php bin/console doctrine:mongodb:fixtures:load

.. caution::

    By default the ``load`` command **purges the database**, removing all data
    from every table. To append your fixtures' data add the ``--append`` option.

This command looks inside the ``DataFixtures/ORM/`` (or ``DataFixtures/MongoDB/``)
directory of each bundle and executes all the classes that implement the
``FixtureInterface`` (for example, those extending from ``Fixture``).

These are the options that you can add to the command:

* ``--fixtures=/path/to/fixture`` to make the command load only the fixtures
  defined in that directory (which can be any directory, not only the standard
  ``DataFixtures/ORM/`` directory). This option can be set repeatedly to load
  fixtures from several directories;
* ``--append`` to make the command append data instead of deleting it before
  loading the fixtures;
* ``--em=manager_name`` (``--dm=manager_name``) to define explicitly the entity
  manager or document manager to use when loading the data.

Using the Container in the Fixtures
-----------------------------------

In some cases you may need to access your application's services inside a fixtures
class. No problem! The container is available via the ``$this->container`` property
on your fixture class:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/Fixtures.php

    // ...
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('admin');

        $encoder = $this->container->get('security.password_encoder');
        $password = $encoder->encodePassword($user, 'pass_1234');
        $user->setPassword($password);

        $manager->persist($user);
        $manager->flush();
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
exact same object via its name:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/UserFixtures.php
    // ...
    class UserFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            $userAdmin = new User('admin', 'pass_1234');
            $manager->persist($userAdmin);
            $manager->flush();

            // other fixtures can get this object using the 'admin-user' name
            $this->addReference('admin-user', $userAdmin);
        }
    }

    // src/AppBundle/DataFixtures/ORM/GroupFixtures.php
    // ...
    class GroupFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            $userGroup = new Group('administrators');
            // this reference returns the User object created in UserFixtures
            $userGroup->addUser($this->getReference('admin-user'));

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
others. Just add the ``getDependencies()`` method to your fixtures class
and return an array of the fixture classes that must be loaded before
this one:

.. code-block:: php

    // src/AppBundle/DataFixtures/ORM/UserFixtures.php
    namespace AppBundle\DataFixtures\ORM;
    // ...
    class UserFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            // ...
        }

        // No need to define getDependencies() here because this fixture
        // doesn't need any other fixture loaded before
    }

    // src/AppBundle/DataFixtures/ORM/GroupFixtures.php
    namespace AppBundle\DataFixtures\ORM;
    // ...
    use AppBundle\DataFixtures\ORM\UserFixtures;

    class GroupFixtures extends Fixture
    {
        public function load(ObjectManager $manager)
        {
            // ...
        }

        public function getDependencies()
        {
            return array(
                UserFixtures::class,
            );
        }
    }

.. _`ORM`: http://symfony.com/doc/current/doctrine.html
.. _`ODM`: http://symfony.com/doc/current/bundles/DoctrineMongoDBBundle/index.html
.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
