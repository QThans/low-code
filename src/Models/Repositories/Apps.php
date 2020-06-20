<?php

namespace Thans\Bpm\Models\Repositories;

use Dcat\Admin\Repositories\EloquentRepository;
use Thans\Bpm\Models\Apps as ModelsApps;

class Apps extends EloquentRepository
{
    public function __construct($relations = [])
    {
        $this->eloquentClass = ModelsApps::class;

        parent::__construct($relations);
    }
}
