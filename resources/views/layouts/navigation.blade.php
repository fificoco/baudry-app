@php
    $isAdminPage = request()->routeIs('admin.*');
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-28">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-[6.5rem] w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 sm:space-x-8 sm:ms-10 self-end mb-[2.33rem] {{ $isAdminPage ? 'lg:flex' : 'sm:flex' }}">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Carte') }}
                    </x-nav-link>
                    <x-nav-link href="https://www.baudry-sa.com" :active="false" target="_blank" rel="noopener noreferrer">
                        {{ __('Siteweb') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="sm:items-center sm:ms-6 gap-3 sm:gap-4 self-end mb-[2.28rem] {{ $isAdminPage ? 'flex' : 'hidden sm:flex' }}">
                <span class="text-2xl font-bold leading-6 text-black">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="{{ $isAdminPage ? 'hidden sm:block' : '' }}">
                    @csrf
                    <button
                        type="submit"
                        title="Déconnexion"
                        aria-label="Déconnexion"
                        class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] text-[#111]"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10 17L15 12L10 7" stroke="#111111" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 12H4" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                            <path d="M20 4V20" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 items-center sm:hidden {{ $isAdminPage ? 'hidden' : 'flex' }}">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @if($isAdminPage)
    <div class="hidden sm:block lg:hidden border-t border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="inline-flex h-[38px] min-w-[92px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-base font-semibold whitespace-nowrap text-[#111]">
                    {{ __('Carte') }}
                </a>
                <a href="https://www.baudry-sa.com" target="_blank" rel="noopener noreferrer" class="inline-flex h-[38px] min-w-[92px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-base font-semibold whitespace-nowrap text-[#111]">
                    {{ __('Siteweb') }}
                </a>
            </div>
        </div>
    </div>

    <div class="sm:hidden border-t border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="inline-flex h-[38px] min-w-[92px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-base font-semibold whitespace-nowrap text-[#111]">
                    {{ __('Carte') }}
                </a>
                <a href="https://www.baudry-sa.com" target="_blank" rel="noopener noreferrer" class="inline-flex h-[38px] min-w-[92px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-base font-semibold whitespace-nowrap text-[#111]">
                    {{ __('Siteweb') }}
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    title="Déconnexion"
                    aria-label="Déconnexion"
                    class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] text-[#111]"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10 17L15 12L10 7" stroke="#111111" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12H4" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                        <path d="M20 4V20" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Responsive Navigation Menu -->
    @unless($isAdminPage)
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="px-4 pt-3 pb-2">
            <div class="text-right font-bold text-base text-black truncate">{{ Auth::user()->name }}</div>
        </div>

        <div class="px-4 pb-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="inline-flex h-[38px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-sm font-semibold text-[#111]">
                        {{ __('Carte') }}
                    </a>
                    <a href="https://www.baudry-sa.com" target="_blank" rel="noopener noreferrer" class="inline-flex h-[38px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] px-3 text-sm font-semibold text-[#111]">
                        {{ __('Siteweb') }}
                    </a>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        title="Déconnexion"
                        aria-label="Déconnexion"
                        class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[8px] border border-black/45 bg-[#f7c600] text-[#111]"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10 17L15 12L10 7" stroke="#111111" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 12H4" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                            <path d="M20 4V20" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endunless
</nav>
