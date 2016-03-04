<?php

namespace Ansistrano;

class RandomStatsRepository implements StatsRepository
{
    public function increment($name)
    {

    }

    public function get($name)
    {
        return rand(0, 100);
    }
}