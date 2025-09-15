<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\SkillsImport;
use App\Models\Skill;
use App\Models\SkillTranslation;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Language\Entities\Language;

class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(!userCan('skills.view'), 403);

        $skills = Skill::with('translations')->paginate(10);

        $app_language = Language::latest()->get(['code', 'name']);

        return view('backend.skill.index', compact('skills', 'app_language'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(! userCan('skills.create'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        $skill = new Skill();
        $skill->save();

        foreach ($request->except('_token') as $key => $value) {
            $skill->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $skill->save();
        }

        flashSuccess(__('skill_created_successfully'));
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Skill $skill)
    {
        abort_if(! userCan('skills.update'), 403);

        $skilll = $skill;
        $skills = Skill::with('translations')->paginate(10);
        $app_language = Language::latest()->get(['code', 'name']);

        return view('backend.skill.index', compact('skilll', 'skills', 'app_language'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Skill $skill)
    {
        abort_if(! userCan('skills.update'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            $skill->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $skill->save();
        }

        flashSuccess(__('skill_updated_successfully'));
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Skill $skill)
    {
        abort_if(! userCan('skills.delete'), 403);

        // check if the skill has candidates
        if ($skill && $skill->candidates->count()) {
            flashError(__('skill_has_candidates'));

            return back();
        }

        $skill->delete();

        flashSuccess(__('skill_deleted_successfully'));
        return back();
    }

    /**
     * Bulk data Import.
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:csv,xlsx,xls',
        ]);

        try {
            Excel::import(new SkillsImport(), $request->import_file);

            flashSuccess(__('skill_imported_successfully'));

            return back();
        } catch (\Throwable $th) {
            flashError($th->getMessage());

            return back();
        }
    }
}
