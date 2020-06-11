<?php

namespace Thans\Bpm\Models\Repositories;

use Dcat\Admin\Repositories\EloquentRepository;
use Thans\Bpm\Models\Department as ModelsDepartment;

class Department extends EloquentRepository
{
    public function __construct($relations = [])
    {
        $this->eloquentClass = ModelsDepartment::class;

        parent::__construct($relations);
    }
}
