@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-gray-100">
        @include('components.static-nav')

        <div class="flex-grow w-full">
            <section class="max-w-3xl mx-auto px-6 py-12 space-y-8">
                @yield('page-content')
            </section>
        </div>

        @include('components.static-footer')
    </div>
@endsection
