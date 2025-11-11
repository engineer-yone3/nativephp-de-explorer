<?php

namespace App\Providers;

use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\Menu;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // ウィンドウの設定
        // route()ヘルパーを使用してルート名からURLを生成
        Window::open()
            ->width(1920)     // ウィンドウの幅
            ->height(1200)   // ウィンドウの高さ
            ->showDevTools(false)
            ->url(route('explorer.index'));

        // デフォルトメニュー（子メニュー付き）
        Menu::create(
            Menu::app(),
            Menu::file('ファイル'),
            Menu::edit('編集'),
            Menu::view('表示'),
            Menu::window('ウィンドウ'),
            Menu::make(
                Menu::redo('元に戻す'),
                Menu::undo('やり直す'),
            )->label('編集2'),
        );
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
