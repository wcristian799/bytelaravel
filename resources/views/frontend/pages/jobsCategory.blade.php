@extends('frontend.layouts.app')

@section('description')
    @php
        $data = metaData('jobs');
    @endphp
    {{ $data->description }}
@endsection
@section('og:image')
    {{ asset($data->image) }}
@endsection
@section('title')
    {{ $data->title }}
@endsection

@section('main')
    <form
action="{{ route('website.job') }}" method="GET" id="job_search_form">
        {{-- job filtering --}}
        <x-website.job.job-category-filtering :countries="$countries" :categories="$categories" :job-roles="$job_roles" :min-salary="$min_salary"
            :max-salary="$max_salary" :experiences="$experiences" :educations="$educations" :job-types="$job_types" :total-jobs="$jobs->total()" />

        <div class="job-filter-overlay"></div>

        <div class="joblist-content">
            <div class="container">
                @if ($popularTags && count($popularTags))
                    <x-website.job.job-sorting :popular-tags="$popularTags" />
                @endif

                @if ($featured_jobs && count($featured_jobs))
                    <div>
                        <h5>{{ __('featured_jobs') }}</h5>
                        <div class="testimonail_active feature-job !-tw-mx-3 ll-feature-job slick-bullet deafult_style_dot">
                            @foreach ($featured_jobs as $job)
                                <x-website.job.job-card :job="$job" />
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="row mt-5">
                    <h5>{{ __('latest_jobs') }}</h5>

                    @php
                        $mix_jobs = $all_jobs && count($all_jobs) ? $all_jobs : $jobs;
                    @endphp

                    @forelse ($mix_jobs as $job)
                        <div class="col-xl-4 col-md-6 fade-in-bottom rt-mb-24 cat-1 cat-3 ">
                            <x-website.job.job-card :job="$job" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card text-center">
                                <x-not-found message="{{ __('no_data_found') }}" />
                            </div>
                        </div>
                    @endforelse

                    @if ($jobs->total() > $jobs->count())
                        <div class="rt-pt-30">
                            <nav>
                                {{ $jobs->onEachSide(0)->links('vendor.pagination.frontend') }}
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
    <div class="rt-spacer-100 rt-spacer-md-50"></div>

    {{-- Subscribe Newsletter --}}
    <x-website.subscribe-newsletter />

    {{-- Apply job Modal --}}
    <div class="modal fade" id="cvModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-transparent">
                    <h5 class="modal-title" id="cvModalLabel">{{ __('apply_job') }}: <span
                            id="apply_job_title">{{ __('job_title') }}</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('website.job.apply') }}">
                    @csrf
                    <div class="modal-body mt-3">
                        <input type="hidden" id="apply_job_id" name="id">
                        <div class="from-group">
                            <x-forms.label name="choose_resume" :required="true" />
                            <select class="rt-selectactive form-control w-100-p" name="resume_id" required>
                                <option value="">{{ __('select_one') }}</option>
                                @foreach ($resumes as $resume)
                                    <option {{ old('resume_id') == $resume->id ? 'selected' : '' }}
                                        value="{{ $resume->id }}">{{ $resume->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <x-forms.label name="cover_letter" :required="true" />
                            <textarea id="default" class="form-control @error('cover_letter') is-invalid @enderror" name="cover_letter"
                                rows="7" required></textarea>
                            @error('cover_letter')
                                <span class="error invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer border-transparent">
                        <button type="button" class="bg-priamry-50 btn btn-outline-primary" data-bs-dismiss="modal"
                            aria-label="Close">{{ __('cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="button-content-wrapper ">
                                <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                <span class="button-text">
                                    {{ __('apply_now') }}
                                </span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .feature-job .slick-slide {
            margin-left: 0px !important;
            margin: 0px 12px !important;
        }

        .feature-job.testimonail_active .prev-arrow {
            left: -60px;
        }

        .feature-job.testimonail_active .next-arrow {
            right: -60px;
        }
    </style>
@endsection
