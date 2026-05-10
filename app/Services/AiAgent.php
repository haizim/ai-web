<?php

namespace App\Services;

class AiAgent
{
    protected static function model(string $task): string
    {
        $model = config("ai.openrouter.models.$task");

        return is_string($model) && $model !== '' ? $model : '';
    }

    public static function generatePage($konten, $style, $images = null)
    {
        $system = SystemPrompt::generatePage();

        $json = json_encode([
            'konten' => $konten,
            'tampilan' => $style,
            'images' => $images,
        ]);

        return OpenRouter::generate($json, $system, self::model('generate_page'))['response'];
    }

    public static function editPage($html, $command)
    {
        $system = SystemPrompt::editPage();

        $json = json_encode([
            'html' => $html,
            'command' => $command,
        ]);

        return OpenRouter::generate($json, $system, self::model('edit_page'))['response'];
    }

    public static function generateStyle($konten)
    {
        $system = SystemPrompt::generateStyle();

        return OpenRouter::generate($konten, $system, self::model('generate_style'))['response'];
    }

    public static function generateStyleFromDesc($konten, $style)
    {
        $system = SystemPrompt::generateStyleFromDesc();

        $json = json_encode([
            'konten' => $konten,
            'style' => $style,
        ]);

        return OpenRouter::generate($json, $system, self::model('generate_style_from_desc'))['response'];
    }

    // MINI APP
    public static function generateMiniApp($konten, $functionality, $style, $images = null)
    {
        $system = SystemPrompt::generateMiniApp();

        $json = json_encode([
            'konten' => $konten,
            'functionality' => $functionality,
            'tampilan' => $style,
            'images' => $images,
        ]);

        return OpenRouter::generate($json, $system, self::model('generate_miniapp'))['response'];
    }

    public static function editMiniApp($html, $command)
    {
        $system = SystemPrompt::editMiniApp();

        $json = json_encode([
            'html' => $html,
            'command' => $command,
        ]);

        return OpenRouter::generate($json, $system, self::model('edit_miniapp'))['response'];
    }

    public static function generateFunctionality($konten)
    {
        $system = SystemPrompt::generateFunctionality();

        return OpenRouter::generate($konten, $system, self::model('generate_functionality'))['response'];
    }

    public static function generateMiniAppStyle($functionality, $style = null)
    {
        if (empty($style)) {
            $system = SystemPrompt::generateMiniAppStyleNoStyle();

            return OpenRouter::generate($functionality, $system, self::model('generate_miniapp_style'))['response'];
        } else {
            $system = SystemPrompt::generateMiniAppStyleWithStyle();

            $json = json_encode([
                'functionality' => $functionality,
                'style' => $style,
            ]);

            return OpenRouter::generate($json, $system, self::model('generate_miniapp_style'))['response'];
        }
    }
}
