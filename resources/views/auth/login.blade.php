<x-guest-layout title="Login - Civil Service Review 2027">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-16 h-16 rounded-2xl bg-[#C92535] flex items-center justify-center font-extrabold text-white text-2xl shadow-lg ring-4 ring-white/30">
                CSC
            </div>
        </div>
        <h2 class="mt-6 text-center text-2xl font-extrabold tracking-tight text-slate-900">
            Civil Service Review 2027
        </h2>
        <p class="mt-1 text-center text-sm font-medium text-slate-500">
            Personal Examination Review & Practice Platform
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-md rounded-2xl sm:px-10 border border-slate-200">
            <!-- Account hint -->
            <div class="mb-6 p-3.5 rounded-xl bg-blue-50 border border-blue-100 text-xs text-blue-900 leading-relaxed">
                <span class="font-semibold block mb-0.5 text-[#174A8B]">Civil Service Reviewer Access:</span>
                Default reviewer account: <span class="font-mono font-semibold">reviewer@csc2027.ph</span><br>
                Password: <span class="font-mono font-semibold">Reviewer2027!</span>
            </div>

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Email Address</label>
                    <div class="mt-1.5">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email', 'reviewer@csc2027.ph') }}"
                               class="appearance-none block w-full px-3.5 py-2.5 border border-slate-300 rounded-lg shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent text-sm">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Password</label>
                    <div class="mt-1.5">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               value="Reviewer2027!"
                               class="appearance-none block w-full px-3.5 py-2.5 border border-slate-300 rounded-lg shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent text-sm">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" checked
                               class="h-4 w-4 text-[#174A8B] focus:ring-[#2563EB] border-slate-300 rounded">
                        <label for="remember" class="ml-2 block text-xs text-slate-700">
                            Remember this device
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-[#C92535] hover:bg-[#A81B29] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C92535] transition">
                        Sign In to Reviewer
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Civil Service Commission (CSC) Professional Examination Preparation System
        </div>
    </div>
</x-guest-layout>
