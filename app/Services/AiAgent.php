<?php
namespace App\Services;

class AiAgent
{
    public static function generatePage($konten, $style, $images = null)
    {
        $system = SystemPrompt::generatePage();

        $json = json_encode([
            'konten' => $konten,
            'tampilan' => $style,
            'images' => $images
        ]);
        
        return OpenRouter::generate($json, $system)['response'];
    }

    public static function editPage($html, $command)
    {
        $system = SystemPrompt::editPage();

        $json = json_encode([
            'html' => $html,
            'command' => $command
        ]);
        
        return OpenRouter::generate($json, $system)['response'];
    }

    public static function generateStyle($konten)
    {
        $system = SystemPrompt::generateStyle();
        
        return OpenRouter::generate($konten, $system, 'google/gemini-3-flash-preview')['response'];
    }

    public static function generateStyleFromDesc($konten, $style)
    {
        $system = SystemPrompt::generateStyleFromDesc();

        $json = json_encode([
            'konten' => $konten,
            'style' => $style
        ]);
        
        return OpenRouter::generate($json, $system, 'google/gemini-3-flash-preview')['response'];
    }

    // MINI APP
    public static function generateMiniApp($konten, $functionality, $style, $images = null)
    {
        $system = SystemPrompt::generateMiniApp();

        $json = json_encode([
            'konten' => $konten,
            'functionality' => $functionality,
            'tampilan' => $style,
            'images' => $images
        ]);
        
        return OpenRouter::generate($json, $system)['response'];
    }

    public static function editMiniApp($html, $command)
    {
        $system = SystemPrompt::editMiniApp();

        $json = json_encode([
            'html' => $html,
            'command' => $command
        ]);
        
        return OpenRouter::generate($json, $system)['response'];
    }

    public static function generateFunctionality($konten)
    {
        $system = SystemPrompt::generateFunctionality();

        return OpenRouter::generate($konten, $system)['response'];
    }

    public static function generateMiniAppStyle($functionality, $style = null)
    {
        if (empty($style)) {
            $system = SystemPrompt::generateMiniAppStyleNoStyle();

            return OpenRouter::generate($functionality, $system)['response'];
        } else {
            $system = SystemPrompt::generateMiniAppStyleWithStyle();

            $json = json_encode([
                'functionality' => $functionality,
                'style' => $style
            ]);

            return OpenRouter::generate($json, $system)['response'];
        }
    }
}