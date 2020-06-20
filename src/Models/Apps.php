<?php

namespace Thans\Bpm\Models;

use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\ModelTree;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;

class Apps extends Model implements Sortable
{
    use ModelTree,Versionable;
    protected $table = 'apps';
}
