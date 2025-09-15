<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JobRoleImport;
use App\Models\JobRole;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Language\Entities\Language;

class JobRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(! userCan('job_role.view'), 403);

        $jobRoles = JobRole::withCount('jobs')->paginate(20);
        $app_language = Language::latest()->get(['code']);

        return view('backend.JobRole.index', compact('jobRoles', 'app_language'));
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(! userCan('job_role.create'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        $job_role = new JobRole();
        $job_role->save();

        foreach ($request->except('_token') as $key => $value) {
            $job_role->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $job_role->save();
        }

        flashSuccess(__('job_role_created_successfully'));

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(JobRole $jobRole)
    {
        abort_if(! userCan('job_role.update'), 403);

        $jobRoles = JobRole::withCount('jobs')->paginate(20);
        $app_language = Language::latest()->get(['code']);

        return view('backend.JobRole.index', compact('jobRole', 'jobRoles', 'app_language'));
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, JobRole $jobRole)
    {
        abort_if(! userCan('job_role.update'), 403);

        // validation
        $app_language = Language::latest()->get(['code']);
        $validate_array = [];
        foreach ($app_language as $language) {
            $validate_array['name_'.$language->code] = 'required|string|max:255';
        }
        $this->validate($request, $validate_array);

        // saving the data
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            $jobRole->translateOrNew(str_replace('name_', '', $key))->name = $value;
            $jobRole->save();
        }

        flashSuccess(__('job_role_updated_successfully'));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(JobRole $jobRole)
    {
        abort_if(! userCan('job_role.delete'), 403);

        // check if job role has jobs
        if ($jobRole && $jobRole->jobs->count()) {
            flashError(__('job_role_has_jobs'));

            return back();
        }

        // check if job role has candidates
        if ($jobRole && $jobRole->candidates->count()) {
            flashError(__('job_role_has_candidates'));

            return back();
        }

        $jobRole->delete();

        flashSuccess(__('job_role_deleted_successfully'));

        return redirect()->route('jobRole.index');
    }

    /**
     * Bulk data Import
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:csv,xlsx,xls',
        ]);

        try {
            Excel::import(new JobRoleImport(), $request->import_file);

            flashSuccess(__('job_role_imported_successfully'));

            return back();
        } catch (\Throwable $th) {
            flashError($th->getMessage());

            return back();
        }
    }
}
