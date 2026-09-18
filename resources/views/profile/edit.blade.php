<x-app-layout>
    <div class="mb-6 animate-in" style="animation-delay: 0.05s;">
        <h1 class="text-xl font-bold" style="color: var(--text-primary);">{{ __('Profile') }}</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">Ubah informasi profil dan kata sandi Anda</p>
    </div>

    <div class="py-2">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="card p-6 sm:p-8 animate-in" style="animation-delay: 0.1s;">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card p-6 sm:p-8 animate-in" style="animation-delay: 0.15s;">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card p-6 sm:p-8 animate-in" style="animation-delay: 0.2s;">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
