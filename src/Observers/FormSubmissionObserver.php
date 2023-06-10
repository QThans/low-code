<?php

namespace Thans\Bpm\Observers;

use Thans\Bpm\Models\FormSubmission;
use Illuminate\Support\Facades\Request;

class FormSubmissionObserver
{
    /**
     * 处理 FormSubmission「creating」事件
     *
     * @param  \Thans\Bpm\Models\FormSubmission  $formSubmission
     * @return void
     */
    public function creating(FormSubmission $formSubmission)
    {
    }
    /**
     * 处理 FormSubmission「created」事件
     *
     * @param  \Thans\Bpm\Models\FormSubmission  $formSubmission
     * @return void
     */
    public function created(FormSubmission $formSubmission)
    {
    }

    /**
     * 处理 FormSubmission「updated」事件
     *
     * @param  \Thans\Bpm\Models\FormSubmission  $formSubmission
     * @return void
     */
    public function updated(FormSubmission $formSubmission)
    {
    }

    /**
     * 处理 FormSubmission「deleted」事件
     *
     * @param  \Thans\Bpm\Models\FormSubmission  $formSubmission
     * @return void
     */
    public function deleted(FormSubmission $formSubmission)
    {
        //
    }

    /**
     * 处理 FormSubmission「forceDeleted」事件
     *
     * @param  \Thans\Bpm\Models\FormSubmission  $formSubmission
     * @return void
     */
    public function forceDeleted(FormSubmission $formSubmission)
    {
        //
    }
}
