<?php

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 */
class DoctrineFixturesExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $container;
    protected $extension;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new DoctrineFixturesExtension();
    }

    public function testPathsConfiguration()
    {
        $config = [
            'doctrine_fixtures' => [
                'paths' => [
                    '/path1/',
                    '/path2/'
                ]
            ]
        ];

        $this->extension->load($config, $this->container);
        $this->assertTrue($this->container->hasParameter('doctrine_fixtures.paths'));
        $this->assertEquals(['/path1/', '/path2/'], $this->container->getParameter('doctrine_fixtures.paths'));
    }
}
