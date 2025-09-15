<?php

namespace Modules\Map\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Location\Entities\Country;

class MapController extends Controller
{
    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'update',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        return view('map::index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        return view('map::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('map::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('map::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function update(Request $request)
    {
        $setting = loadSetting();

        $request->validate([
            'map_type' => 'required',
            'default_latitude' => 'required|numeric',
            'default_longitude' => 'required|numeric',
        ]);

        if ($request->map_type == 'google-map') {
            $request->validate(['google_map_key' => 'nullable']);
        }

        if ($request->map_type == 'google-map') {
            $setting->update([
                'default_map' => $request->map_type,
                'google_map_key' => $request->google_map_key ?? '',
            ]);
        } else {
            $setting->update([
                'default_map' => $request->map_type,
            ]);
        }

        if ($request->has('app_country_type')) {
            $setting->update(['app_country_type' => $request->app_country_type]);

            if ($request->app_country_type == 'single_base') {

                $selected_country = session()->get('selected_country');
                if ($selected_country) {
                    session()->forget('selected_country');
                }

                $country = Country::FindOrFail($request->app_country);
                $setting->update([
                    'app_country' => $country->id,
                ]);
            } else {
                if ($request->has('multiple_country') && $request->multiple_country !== null) {

                    // first all old selected country unbind
                    $app_multiple_countries_old = Country::where('status', true)->get();
                    foreach ($app_multiple_countries_old as $country_old) {
                        $country_old->update([
                            'status' => 0,
                        ]);
                    }

                    // then new commer bind
                    $app_multiple_countries = Country::whereIn('id', $request->multiple_country)->get();
                    foreach ($app_multiple_countries as $country) {
                        $country->update([
                            'status' => true,
                        ]);
                    }
                }
            }
        }
        // lat & long update
        $setting->update([
            'default_long' => $request->default_longitude,
            'default_lat' => $request->default_latitude,
        ]);

        // ip based location
        setEnv('SET_IP_BASED_LOCATION', $request->ip_based_location ? 'true' : 'false');

        flashSuccess(__('location_configuration_updated'));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
