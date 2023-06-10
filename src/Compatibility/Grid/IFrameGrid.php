<?php

namespace Thans\Bpm\Compatibility\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;

/**
 * 兼容2.0之前版本的IFrameGrid
 */
class IFrameGrid extends Grid
{
    const QUERY_NAME = '_grid_iframe_';

    public function __construct($repository, $builder = null)
    {
        parent::__construct($repository, $builder);

        $this->setName('simple');
        $this->disableCreateButton();
        $this->disableActions();
        $this->disablePerPages();
        $this->disableBatchActions();

        $this->rowSelector()->click();

        Content::composing(function (Content $content) {
            Admin::style('#app{padding: 1.4rem 1rem 1rem}');

            $content->full();
        }, true);
    }
}
