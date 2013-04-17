<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\FixturesBundle\Service;

use Doctrine\Fixture\Loader\Loader;
use Doctrine\Fixture\Loader\ChainLoader;
use Doctrine\Fixture\Loader\RecursiveDirectoryLoader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Loader Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class LoaderService implements Loader
{
    /**
     * @var string
     */
    protected $fixturesRelativePath = 'DataFixtures';

    /**
     * @var \Doctrine\Fixture\Loader\ChainLoader
     */
    protected $internalLoader;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Define the relative fixtures path to a bundle.
     *
     * @param string $fixturesRelativePath
     */
    public function setFixturesRelativePath($fixturesRelativePath)
    {
        $this->fixturesRelativePath = $fixturesRelativePath;
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if ( ! $this->internalLoader) {
            $this->createInternalLoader();
        }

        return $this->internalLoader->load();
    }

    /**
     * Create internal loader.
     *
     * @return \Doctrine\Fixture\Loader\ChainLoader
     */
    protected function createInternalLoader()
    {
        $this->internalLoader = new ChainLoader();

        // Loading fixtures across all bundles
        foreach ($this->getBundleList() as $bundle) {
            $this->addBundleLoader($bundle);
        }
    }

    /**
     * Retrieve current bundle list.
     *
     * @return array
     */
    protected function getBundleList()
    {
        return $this->kernel->getBundles();
    }

    /**
     * Add a loader to the chain for a given bundle.
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     */
    private function addBundleLoader(Bundle $bundle)
    {
        $fixturesDirectory = realpath(sprintf('%s/%s', $bundle->getPath(), $this->fixturesRelativePath));

        if ($fixturesDirectory === false) {
            return;
        }

        $this->internalLoader->addLoader(new RecursiveDirectoryLoader($fixturesDirectory));
    }
}
