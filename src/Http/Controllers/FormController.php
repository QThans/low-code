<?php

namespace Thans\Bpm\Http\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\Role;
use Thans\Bpm\Models\User;

class FormController extends Controller
{
    protected $canUseType = ['resource'];

    protected $models = [
        'users'  => 'Thans\Bpm\Models\User',
        'roles'  => 'Thans\Bpm\Models\Role',
        'departments' => 'Thans\Bpm\Models\Department',
    ];

    public function index(Request $request)
    {
        $type = $request->type;
        return in_array($type, $this->canUseType) ? $this->$type($request) : [];
    }
    protected function resource(Request $request)
    {
        if (!$request->select) {
            return [];
        }
        $select = explode(',', $request->select);
        $select = collect($select)->map(function ($value, $key) {
            switch ($value) {
                case '_id':
                    return 'id as _id';
                    break;
                case 'title':
                    return 'name as title';
                    break;
            }
            return $value;
        });
        $forms = Form::select($select->toArray())->with(['components'])
            ->whereHas('components', function ($query) {
                $query->whereNotNull('values');
            })->get()->toArray();

        return array_merge($forms, $this->systemForm());
    }

    protected function systemForm()
    {
        return [
            [
                '_id' => 'users',
                'title' => admin_trans_field(Menu::where('uri', 'auth/users')->first()->title),
                'components' => null
            ],
            [
                '_id' => 'roles',
                'title' => admin_trans_field(Menu::where('uri', 'auth/roles')->first()->title),
                'components' => null
            ],
            [
                '_id' => 'departments',
                'title' => admin_trans_field(Menu::where('uri', 'bpm/department')->first()->title),
                'components' => null
            ]
        ];
    }

    public function users()
    {
        $components = User::components();
        return $components;
    }

    public function roles()
    {
        $components = Role::components();
        return $components;
    }

    public function departments()
    {
        $components = Department::components();
        return $components;
    }

    public function detail($id)
    {
        if (method_exists($this, $id)) {
            return $this->$id();
        }
        $form = Form::where('id', $id)->with(['components'])->with(['components'])
            ->whereHas('components', function ($query) {
                $query->whereNotNull('values');
            })->first();
        return $form ? $form : [];
    }

    public function submission(Request $request, $id)
    {
        $limit = $request->get('limit', '100');
        $skip = $request->get('skip', '0');
        if (isset($this->models[$id])) {
            $formSubmission = (new $this->models[$id]());
        }
        $content = json_decode($request->getContent());
        if ($content && $content->query) {
            if (!isset($this->models[$id])) {
                $form = Form::find($id);
                $formSubmission = (new FormSubmission())->setAlias($form->alias)->where($form->alias . '.form_id', $id);
            }
            $syntax = [];
            $query = explode(';', $content->query);
            foreach ($query as $key => $value) {
                if ($value) {
                    $isMatched = preg_match_all('/(.*?)\(\'(.*?)\',\'(.*?)\',\'(.*?)\'\)/', trim($value), $matches);
                    if ($isMatched & count($matches) == 5) {
                        $tmp = [
                            'method' => $matches[1][0],
                        ];
                        foreach (array_slice($matches, 2, count($matches) - 2) as $k => $v) {
                            $tmp['params'][] = $v[0];
                        }
                        $syntax[] = $tmp;
                    }
                }
            }
            if (isset($this->models[$id])) {
                $formSubmission = (new $this->models[$id]());
            }
            $formData = $content->formData;
            foreach ($syntax as $key => $value) {
                $method = $value['method'];
                foreach ($value['params'] as $k => $v) {
                    if (isset($formData->$v)) {
                        $value['params'][$k] = $formData->$v;
                    }
                }
                if (count($value['params']) == 2) {
                    $formSubmission->$method($value['params'][0], $value['params'][1]);
                } else {
                    $formSubmission->$method($value['params'][0], $value['params'][1], $value['params'][2]);
                }
            }
        } else {
            $formSubmission = $formSubmission ?? (new FormSubmission())->where('form_id', $id);
        }
        // if ($request->column && $request->value) {
        //     $formSubmission = $formSubmission->where('form_id', $id);
        //     $formSubmission->where($request->column, $request->value);
        //     return $formSubmission->first();
        // }
        return $formSubmission->skip($skip)->limit($limit)->get();
    }
}
