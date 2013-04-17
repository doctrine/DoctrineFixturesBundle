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

use Doctrine\Fixture\Executor;
use Doctrine\Fixture\Configuration;
use Doctrine\Fixture\Loader\Loader;
use Doctrine\Fixture\Filter\Filter;

/**
 * Executor Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class ExecutorService implements ExecutorServiceInterface
{
    /**
     * @var \Doctrine\Fixture\Configuration
     */
    protected $configuration;

    /**
     * @var \Doctrine\Fixture\Loader\Loader
     */
    protected $loader;

    /**
     * @var \Doctrine\Fixture\Filter\Filter
     */
    protected $filter;

    /**
     * Constructor.
     *
     * @param \Doctrine\Fixture\Configuration $configuration
     * @param \Doctrine\Fixture\Loader\Loader $loader
     * @param \Doctrine\Fixture\Filter\Filter $filter
     */
    public function __construct(Configuration $configuration, Loader $loader, Filter $filter)
    {
        $this->configuration = $configuration;
        $this->loader        = $loader;
        $this->filter        = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($flags)
    {
        $executor = new Executor($this->configuration);
        
        $executor->execute($this->loader, $this->filter, $flags);
    }
}
