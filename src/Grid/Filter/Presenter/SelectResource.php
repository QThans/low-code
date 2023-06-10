<?php

namespace Thans\Bpm\Grid\Filter\Presenter;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter\Presenter\SelectResource as PresenterSelectResource;
use Dcat\Admin\Support\Helper;

class SelectResource extends PresenterSelectResource
{
    public function view(): string
    {
        return 'bpm::filter.' . strtolower(class_basename(static::class));
    }
}
