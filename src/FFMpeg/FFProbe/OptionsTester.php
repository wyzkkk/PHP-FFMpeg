<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\FFProbe;

use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use FFMpeg\Driver\FFProbeDriver;
use FFMpeg\Exception\RuntimeException;
use Psr\SimpleCache\CacheInterface;

class OptionsTester implements OptionsTesterInterface
{
    /** @var FFProbeDriver */
    private $ffprobe;
    /** @var Cache */
    private $cache;

    public function __construct(FFProbeDriver $ffprobe, CacheInterface $cache)
    {
        $this->ffprobe = $ffprobe;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        $id = sprintf('option-%s', $name);

        if ($this->cache->has($id)) {
            return $this->cache->get($id);
        }

        $output = $this->retrieveHelpOutput();

        $ret = (bool) preg_match('/^'.$name.'/m', $output);

        $this->cache->set($id, $ret);

        return $ret;
    }

    private function retrieveHelpOutput(): string
    {
        $id = 'help';

        if ($this->cache->has($id)) {
            return $this->cache->get($id);
        }

        try {
            $output = $this->ffprobe->command(['-help', '-loglevel', 'quiet']);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Your FFProbe version is too old and does not support `-help` option, please upgrade.', $e->getCode(), $e);
        }

        $this->cache->set($id, $output);

        return $output;
    }
}
