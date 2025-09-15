<?php

namespace App;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;
    
    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Configure allowed HTML elements and attributes
        $config->set('HTML.Allowed',
            'p,br,strong,b,em,i,u,s,h1,h2,h3,h4,h5,h6,ul,ol,li,blockquote,a[href|title],table,tr,td,th,thead,tbody,pre,code,hr'
        );

        // Allow safe protocols for links
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        // Enable auto-paragraphing for better formatting
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);

        // Disable linkification
        $config->set('AutoFormat.Linkify', false);

        // Cache configuration for better performance
        $config->set('Cache.SerializerPath', sys_get_temp_dir());

        $this->purifier = new HTMLPurifier($config);
    }
    
    public function sanitize(string $html): string
    {
        // Pre-process to clean up common Word artifacts
        $html = $this->preProcessWordContent($html);
        
        // Purify the HTML
        $cleaned = $this->purifier->purify($html);
        
        // Post-process for final cleanup
        return $this->postProcessContent($cleaned);
    }
    
    private function preProcessWordContent(string $html): string
    {
        // Remove Word XML namespaces and declarations
        $html = preg_replace('/<\\?xml[^>]*>/', '', $html);
        $html = preg_replace('/<\\/?[a-z]+:[^>]*>/i', '', $html);
        
        // Remove Word-specific style attributes
        $html = preg_replace('/\\s*mso-[^:]*:[^;]*;?/i', '', $html);
        $html = preg_replace('/\\s*style="[^"]*"/i', '', $html);
        
        // Remove Word's conditional comments
        $html = preg_replace('/<!--\\[if[\\s\\S]*?<!\\[endif\\]-->/i', '', $html);
        
        // Remove Word's o:p tags
        $html = preg_replace('/<\\/?o:p[^>]*>/i', '', $html);
        
        // Convert Word's special characters using hex codes
        $html = str_replace(
            [chr(0xE2).chr(0x80).chr(0x93), chr(0xE2).chr(0x80).chr(0x94), chr(0xE2).chr(0x80).chr(0x9C), chr(0xE2).chr(0x80).chr(0x9D), chr(0xE2).chr(0x80).chr(0x98), chr(0xE2).chr(0x80).chr(0x99)],
            ['-', '-', '"', '"', "'", "'"],
            $html
        );
        
        // Clean up excessive whitespace
        $html = preg_replace('/\\s+/', ' ', $html);
        $html = preg_replace('/\\s*\\n\\s*/', '\n', $html);
        
        return $html;
    }
    
    private function postProcessContent(string $html): string
    {
        // Remove empty paragraphs
        $html = preg_replace('/<p[^>]*>\\s*<\\/p>/i', '', $html);

        // Clean up nested paragraphs (shouldn't happen but just in case)
        $html = preg_replace('/<p[^>]*>\\s*<p[^>]*>/i', '<p>', $html);
        $html = preg_replace('/<\\/p>\\s*<\\/p>/i', '</p>', $html);

        // Final whitespace cleanup - remove any remaining newlines and excessive whitespace
        $html = preg_replace('/\\s+/', ' ', $html);
        $html = trim($html);

        return $html;
    }
    
    public function stripAllTags(string $html): string
    {
        return strip_tags($html);
    }
    
    public function getTextPreview(string $html, int $length = 200): string
    {
        $text = $this->stripAllTags($html);
        $text = preg_replace('/\\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
}