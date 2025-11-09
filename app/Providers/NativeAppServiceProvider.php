<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        // Language menu (English is always the default on app startup)
        // Menu items emit MenuItemClicked events that are handled by JavaScript
        $languageMenu = Menu::make(
            Menu::radio('English', true)->id('change_language_en'),
            Menu::radio('日本語')->id('change_language_ja')
        )->label('Language');

        if (PHP_OS_FAMILY === 'Darwin') {
            // For MacOS
            Menu::create(
                Menu::app(),
                $languageMenu
            );
        } else {
            Menu::create(
                $languageMenu
            );
        }

        Window::open()
            ->width(1920)
            ->height(1200)
            ->showDevTools(false)
            ->title('File Explorer')
            ->route('explorer.index');
    }

    public function phpIni(): array
    {
        return [
        ];
    }
}

