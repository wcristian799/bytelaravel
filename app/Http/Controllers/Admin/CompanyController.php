<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyCreateFormRequest;
use App\Http\Requests\CompanyUpdateFormRequest;
use App\Models\Company;
use App\Models\IndustryType;
use App\Models\OrganizationType;
use App\Models\TeamSize;
use App\Models\User;
use App\Notifications\SendProfileVerifiedNotification;
use App\Services\Admin\Company\CompanyCreateService;
use App\Services\Admin\Company\CompanyListService;
use App\Services\Admin\Company\CompanyUpdateService;
use Illuminate\Http\Request;
use Modules\Location\Entities\City;
use Modules\Location\Entities\Country;
use Modules\Location\Entities\State;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! userCan('company.view'), 403);

        $companies = (new CompanyListService())->execute($request);
        $industry_types = IndustryType::all()->sortBy('name');
        $organization_types = OrganizationType::all()->sortBy('name');

        return view('backend.company.index', compact('companies', 'industry_types', 'organization_types'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(! userCan('company.create'), 403);

        $data['countries'] = Country::all();
        $data['industry_types'] = IndustryType::all()->sortBy('name');
        $data['organization_types'] = OrganizationType::all()->sortBy('name');
        $data['team_sizes'] = TeamSize::all();

        return view('backend.company.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyCreateFormRequest $request)
    {
        abort_if(! userCan('company.create'), 403);

        (new CompanyCreateService())->execute($request);

        flashSuccess(__('company_created_successfully'));

        return redirect()->route('company.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_if(! userCan('company.view'), 403);

        $company = Company::with(['jobs.appliedJobs', 'user.socialInfo', 'user.contactInfo', 'jobs' => function ($job) {
            return $job->latest()->with('category', 'role', 'job_type', 'salary_type');
        }])->findOrFail($id);

        return view('backend.company.show', compact('company'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort_if(! userCan('company.update'), 403);

        $data['company'] = Company::findOrFail($id);
        $data['user'] = $data['company']->user->load('socialInfo');
        $data['industry_types'] = IndustryType::all()->sortBy('name');
        $data['organization_types'] = OrganizationType::all()->sortBy('name');
        $data['team_sizes'] = TeamSize::all();
        $data['socials'] = $data['company']->user->socialInfo;

        return view('backend.company.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyUpdateFormRequest $request, Company $company)
    {
        abort_if(! userCan('company.update'), 403);

        (new CompanyUpdateService())->execute($request, $company);

        flashSuccess(__('company_updated_successfully'));

        return redirect()->route('company.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(! userCan('company.delete'), 403);

        $company = Company::findOrFail($id);

        // company image delete
        deleteFile($company->logo);
        deleteFile($company->banner);
        deleteFile($company->user->image);

        // company cv view items delete
        $company->cv_views()->delete();
        $company->user->delete();
        $company->delete();

        flashSuccess(__('company_deleted_successfully'));

        return back();
    }

    public function documents(Company $company)
    {
        $company = $company->load('media');

        return view('backend.company.document', [
            'company' => $company,
        ]);

    }

    public function downloadDocument(Request $request, Company $company)
    {
        $request->validate([
            'file_type' => 'required',
        ]);
        $media = $company->getFirstMedia($request->get('file_type'));

        return response()->download($media->getPath(), $media->file_name);

    }

    /**
     * Get state list by country id
     *
     * @return void
     */
    public function getStateList(Request $request)
    {
        $data['states'] = State::where('country_id', $request->country_id)
            ->get(['name', 'id']);

        return response()->json($data);
    }

    /**
     * Get city list by state id
     *
     * @return void
     */
    public function getCityList(Request $request)
    {
        $data['cities'] = City::where('state_id', $request->state_id)
            ->get(['name', 'id']);

        return response()->json($data);
    }

    /**
     * Change company status
     *
     * @return void
     */
    public function statusChange(Request $request)
    {

        $user = User::findOrFail($request->id);

        $user->update(['status' => $request->status]);

        if ($request->status == 1) {
            return responseSuccess(__('company_activated_successfully'));
        } else {
            return responseSuccess(__('company_deactivated_successfully'));
        }
    }

    /**
     * Change company verification status
     *
     * @return void
     */
    public function verificationChange(Request $request)
    {
        $user = User::findOrFail($request->id);

        if ($request->status) {
            $user->update(['email_verified_at' => now()]);
            $message = __('email_verified_successfully');
        } else {
            $user->update(['email_verified_at' => null]);
            $message = __('email_unverified_successfully');
        }

        return responseSuccess($message);
    }

    /**
     * Change company profile verification status
     *
     * @return void
     */
    public function profileVerificationChange(Request $request)
    {
        $company = Company::findOrFail($request->id);

        if ($request->status) {
            $company->update(['is_profile_verified' => true]);
            $company->user->notify(new SendProfileVerifiedNotification());
            $message = __('profile_verified_successfully');
        } else {
            $company->update(['is_profile_verified' => false]);
            $message = __('profile_unverified_successfully');
        }

        return responseSuccess($message);
    }

    /**
     * Change company document verification status
     *
     * @param  Request  $request
     * @return void
     */
    public function toggle(Company $company)
    {
        if ($company->document_verified_at) {
            $company->document_verified_at = null;
            $message = __('unverified').' '.__('successfully');

        } else {
            $company->document_verified_at = now();
            $message = __('verified').' '.__('successfully');

        }

        $company->save();

        return responseSuccess($message);
    }
}
