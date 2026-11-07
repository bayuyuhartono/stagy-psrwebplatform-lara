<x-cms.guest-layout title="Sign in">
    <main class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-cms-navy p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -top-32 -left-32 size-96 rounded-full border border-white/8"></div>
            <div class="absolute right-12 bottom-16 size-52 rounded-full bg-cms-green/55 blur-3xl"></div>
            <div class="relative flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-cms-gold text-lg font-black text-cms-navy">P</span>
                <span class="font-bold tracking-wide">Pesoros CMS</span>
            </div>
            <div class="relative max-w-xl">
                <p class="mb-5 text-xs font-semibold tracking-[0.22em] text-cms-gold uppercase">Simple content, powerful results</p>
                <h1 class="text-5xl leading-[1.08] font-bold tracking-tight">Everything your website needs, in one calm workspace.</h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-white/60">Create pages, publish stories, manage enquiries, and keep your website moving.</p>
            </div>
            <p class="relative text-xs text-white/35">{{ now()->year }} · Secure administration</p>
        </section>

        <section class="flex items-center justify-center px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <span class="grid size-10 place-items-center rounded-xl bg-cms-gold font-black text-cms-navy">P</span>
                    <span class="font-bold text-cms-navy">Pesoros CMS</span>
                </div>
                <p class="text-sm font-semibold text-cms-green">Welcome back</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-cms-navy">Sign in to your workspace</h2>
                <p class="mt-3 text-sm leading-6 text-cms-navy/55">Use your administrator account to continue.</p>

                <form method="POST" action="{{ route('cms.login.store') }}" class="mt-9 grid gap-5">
                    @csrf
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-cms-navy">Email address</span>
                        <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="h-12 rounded-xl border border-cms-navy/15 bg-white/70 px-4 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                        @error('email') <span class="text-xs font-medium text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-cms-navy">Password</span>
                        <input name="password" type="password" autocomplete="current-password" required class="h-12 rounded-xl border border-cms-navy/15 bg-white/70 px-4 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                        @error('password') <span class="text-xs font-medium text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex items-center gap-2 text-sm text-cms-navy/65">
                        <input name="remember" type="checkbox" value="1" class="size-4 rounded border-cms-navy/20 text-cms-green focus:ring-cms-green/20">
                        Keep me signed in
                    </label>
                    <button type="submit" class="mt-2 h-12 rounded-xl bg-cms-green px-5 text-sm font-bold text-white shadow-lg shadow-cms-green/15 hover:bg-cms-navy focus:outline-none focus:ring-3 focus:ring-cms-gold/40">Sign in</button>
                </form>
            </div>
        </section>
    </main>
</x-cms.guest-layout>
