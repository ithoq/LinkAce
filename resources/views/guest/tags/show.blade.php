@extends('layouts.guest')

@section('content')

    <div class="card">
        <header class="card-header">
            @lang('tag.tag')
        </header>
        <div class="card-body">

            <h2 class="mb-0">{{ $tag->name }}</h2>

        </div>
    </div>

    <div class="card mt-3 mb-3">
        <div class="card-header">
            @lang('link.links')
        </div>
        <div class="card-table">

            @include('guest.links.partials.table', ['links' => $tag_links])

        </div>
    </div>

    {!! $tag_links->onEachSide(1)->links() !!}

@endsection
