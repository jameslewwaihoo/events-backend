<!-- Top navbar -->
<nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
    <div class="container-fluid">
        <!-- Brand -->
        <h6 class="h2 text-white d-inline-block mb-0">
            {{ $pageTitle ?? 'Dashboard' }}
        </h6>
        {{-- <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="">{{ $pageTitle ?? 'Dashboard' }}</a> --}}
        <!-- Form -->
        @unless (View::hasSection('hide-navbar-search'))
            <form class="navbar-search navbar-search-dark form-inline mr-3 d-none d-md-flex ml-lg-auto">
                <div class="form-group mb-0">
                    <div class="input-group input-group-alternative">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input class="form-control" placeholder="Search" type="text">
                    </div>
                </div>
            </form>
        @endunless
        <!-- User -->
        <ul class="navbar-nav align-items-center d-none d-md-flex">
            <li class="nav-item">
                <a class="nav-link pr-0" href="{{ route('profile.edit') }}"
                    aria-label="{{ __('Profile for :name', ['name' => auth()->user()->name]) }}">
                    <div class="media align-items-center">
                        <span class="avatar avatar-sm rounded-circle">
                            <img alt="" src="{{ asset('argon') }}/img/theme/team-4-800x800.jpg">
                        </span>
                        <div class="media-body ml-2 d-none d-lg-block">
                            <span class="mb-0 text-sm  font-weight-bold">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </a>
            </li>
            <li class="nav-item ml-3">
                <button type="submit" form="logout-form" class="nav-link p-0 bg-transparent border-0">{{ __('Log out') }}</button>
            </li>
        </ul>
    </div>
</nav>
