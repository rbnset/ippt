@props(['field'])
@if($errors->has($field))
    <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $errors->first($field) }}</p>
@endif
