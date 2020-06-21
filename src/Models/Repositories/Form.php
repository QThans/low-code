<?php

namespace Thans\Bpm\Models\Repositories;

use Dcat\Admin\Repositories\EloquentRepository;
use Thans\Bpm\Models\Form as ModelsForm;

class Form extends EloquentRepository
{
    public function __construct($relations = [])
    {
        $this->eloquentClass = ModelsForm::class;

        parent::__construct($relations);
    }
}
