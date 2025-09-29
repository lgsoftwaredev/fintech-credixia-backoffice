<?php

namespace App\Services\Api;

use App\Models\RuleSet;

class RuleSetService
{
    public function getActive(): RuleSet
    {
        return RuleSet::query()->where('is_active', true)->latest('id')->firstOrFail();
    }
}
