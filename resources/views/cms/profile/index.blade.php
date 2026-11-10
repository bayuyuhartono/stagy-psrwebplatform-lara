<x-cms.app-layout title="My Profile" section="profile">
    <section class="grid max-w-2xl gap-6">
        <div>
            <p class="text-sm text-cms-navy/55">Your own account. Changes here apply only to you.</p>
            <h2 class="mt-1 text-2xl font-bold">My Profile</h2>
        </div>

        <div class="flex items-center gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-cms-green text-sm font-bold text-white">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
            <div class="min-w-0">
                <p class="truncate font-bold">{{ auth()->user()->name }}</p>
                <p class="truncate text-sm text-cms-navy/55">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <form data-password-form class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
            <div>
                <h3 class="font-bold">Change password</h3>
                <p class="text-xs text-cms-navy/45">At least 12 characters, with upper- and lowercase letters, a number and a symbol. Signing in through the API will need the new password.</p>
            </div>
            <div data-password-notice class="hidden rounded-xl border p-4 text-sm" role="status"></div>
            <label class="grid gap-2">
                <span class="text-xs font-semibold">Current password</span>
                <input name="current_password" type="password" required autocomplete="current-password" class="h-11 rounded-xl border bg-white px-4">
            </label>
            <label class="grid gap-2">
                <span class="text-xs font-semibold">New password</span>
                <input name="password" type="password" required minlength="12" autocomplete="new-password" class="h-11 rounded-xl border bg-white px-4">
            </label>
            <label class="grid gap-2">
                <span class="text-xs font-semibold">Confirm new password</span>
                <input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="h-11 rounded-xl border bg-white px-4">
            </label>
            <button data-password-save class="h-11 justify-self-start rounded-xl bg-cms-green px-5 text-sm font-bold text-white disabled:opacity-50">Update password</button>
        </form>
    </section>
</x-cms.app-layout>
