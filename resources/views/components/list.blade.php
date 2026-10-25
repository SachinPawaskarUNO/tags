@props(['model'])

@if ($model->tags->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach ($model->tags as $tag)
            <li class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $tag->name }}</li>
        @endforeach
    </ul>
@endif
