<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ProfessionImport;
use App\Models\Profession;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Language\Entities\Language;

class ProfessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(! userCan('professions.view'), 403);

        $professions = Profession::all();
        $app_language = Language::latest()->get(['code']);

        return view('backend.profession.index', compact('professions', 'app_language'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(! userCan('professions.create'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        $profession = new Profession();
        $profession->save();
        foreach ($request->except('_token') as $key => $value) {
            $profession->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $profession->save();
        }

        flashSuccess(__('profession_created_successfully'));

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Profession $profession)
    {
        abort_if(! userCan('professions.update'), 403);

        $prof = $profession;
        $professions = Profession::all();
        $app_language = Language::latest()->get(['code']);

        return view('backend.profession.index', compact('prof', 'professions', 'app_language'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Profession $profession)
    {
        abort_if(! userCan('professions.update'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            $profession->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $profession->save();
        }

        flashSuccess(__('profession_updated_successfully'));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Profession $profession)
    {
        abort_if(! userCan('professions.delete'), 403);

        // check if profession has candidates
        if ($profession && $profession->candidates->count()) {
            flashError(__('profession_has_candidates'));

            return back();
        }

        $profession->delete();

        flashSuccess(__('profession_deleted_successfully'));

        return redirect()->route('profession.index');
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
            Excel::import(new ProfessionImport(), $request->import_file);

            flashSuccess(__('profession_imported_successfully'));

            return back();
        } catch (\Throwable $th) {
            flashError($th->getMessage());

            return back();
        }
    }
}
