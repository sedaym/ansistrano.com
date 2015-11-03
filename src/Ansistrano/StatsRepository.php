<?php

namespace Ansistrano;

interface StatsRepository
{
    /**
     * @param $name
     */
    public function increment($name);

    /**
     * @param $name
     * @return mixed
     */
    public function get($name);
}