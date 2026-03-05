<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="max-[500px]:flex max-[500px]:flex-col max-[500px]:items-center max-[500px]:text-center max-[500px]:gap-3">
        @csrf

        <img src="{{ Vite::asset('resources/images/logoBaudry.png') }}" alt="Logo Baudry" class="h-[90px] w-auto mx-auto max-[500px]:h-16" />

        <!-- Username -->
        <div class="w-full">
            <x-input-label for="name" :value="__('Nom')" class="max-[500px]:text-center" />
            <x-text-input id="name" class="block mt-1 w-full bg-white border border-gray-300 shadow-sm focus:shadow-md max-[500px]:text-center max-[500px]:placeholder:text-center" type="text" name="name" :value="old('name')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4 w-full max-[500px]:mt-0">
            <x-input-label for="password" :value="__('Password')" class="max-[500px]:text-center" />

            <x-text-input id="password" class="block mt-1 w-full bg-white border border-gray-300 shadow-sm focus:shadow-md max-[500px]:text-center max-[500px]:placeholder:text-center"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4 w-full max-[500px]:mt-0">
            <label for="remember_me" class="inline-flex items-center max-[500px]:justify-center max-[500px]:w-full">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-center mt-4 w-full max-[500px]:mt-0">
            <x-primary-button class="bg-black hover:bg-black focus:bg-black active:bg-black border-black max-[500px]:mx-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
