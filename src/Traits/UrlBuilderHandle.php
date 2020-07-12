<?php

namespace Thans\Bpm\Traits;

use Thans\Bpm\Http\Controllers\BpmController;

trait UrlBuilderHandle
{
    public function indexUrl()
    {
        return action([BpmController::class, 'index'], ['alias' => $this->formAlias]);
    }
    public function destroyUrl($id)
    {
        return action([BpmController::class, 'destroy'], ['alias' => $this->formAlias, 'form' => $id]);
    }
    public function editUrl($id)
    {
        return action([BpmController::class, 'edit'], ['alias' => $this->formAlias, 'form' => $id]);
    }
}
