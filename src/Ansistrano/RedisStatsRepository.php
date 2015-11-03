<?php

namespace Ansistrano;

use Predis\Client;

class RedisStatsRepository implements StatsRepository
{
    private $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    public function increment($name)
    {
        $this->redis->incr($name);
    }

    public function get($name)
    {
        return $this->redis->get($name);
    }
}