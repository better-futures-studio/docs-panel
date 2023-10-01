<x-filament-panels::page>
    <style>
        html {
            scroll-padding-top: 5rem;
        }

        .fi-page {
            padding-left: 16px;
        }

        .docs_main a h2:before,
        .docs_main a h3:before,
        .docs_main a h4:before {
            content: "#";
            margin-left: -1.5rem;
            font-weight: 400;
            position: absolute;
            opacity: .8;
            color: rgba(var(--primary-400), var(--tw-text-opacity));
        }
    </style>

    <template x-if="{{ !filament()->hasDarkModeForced() ? '$store.theme === \'dark\'' : 'true' }}">
        @if (filament()->hasDarkMode())
            <x-markdown
                class="prose dark:prose-invert docs_main"
                theme="github-dark"
            >
                {!! $content !!}
            </x-markdown>
        @endif
    </template>


    @if (!filament()->hasDarkModeForced())
        <template x-if="{{ filament()->hasDarkMode() ? '$store.theme === \'light\'' : 'true' }}">
            <x-markdown
                class="prose docs_main"
                theme="github-light"
            >
                {!! $content !!}
            </x-markdown>
        </template>
    @endif
</x-filament-panels::page>
