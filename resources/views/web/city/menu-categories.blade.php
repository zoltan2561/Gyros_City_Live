<nav class="city-menu-categories {{ $categoryNavClass ?? '' }}" aria-label="Étlap kategóriái">
    @foreach ($menuCategories as $category)
        <a href="{{ url('/menu?'.http_build_query(['category' => $category->slug])) }}" @class(['is-current' => $category->id === $categorydata->id]) @if ($category->id === $categorydata->id) aria-current="page" @endif>
            <img src="{{ helper::image_path($category->image) }}" width="38" height="38" alt="" loading="lazy">
            <span>{{ $category->category_name }}</span>
            <span class="city-menu-count">{{ $category->item_info->count() }}</span>
        </a>
    @endforeach
</nav>
