<?php

namespace Doctrine\Bundle\FixturesBundle\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Fixture
{
    public $env;
    public $order = 0;

    public function __construct(array $data)
    {
        if (array_key_exists('value', $data)) {
            $value = $data['value'];

            if (!is_array($value)) {
                $value = array($value);
            }

            $this->env = $value;
        }

        if (array_key_exists('env', $data)) {
            $env = $data['env'];
            if (!is_array($env)) {
                $env = array($env);
            }

            $this->env = $env;
        }

        if (array_key_exists('order', $data)) {
            $this->order = (int) $data['order'];
        }
    }
}