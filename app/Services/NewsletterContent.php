<?php

namespace App\Services;

class NewsletterContent
{
    // Only image notation is supported. All other content remains escaped plain text.
    public const IMAGE_PATTERN = '!\[([^\]\r\n]*)\]\((https?://[^\s()<>"\x27]+)\)';

    public static function html(string $body): string
    {
        $html = '';
        $offset = 0;
        preg_match_all('~' . self::IMAGE_PATTERN . '~u', $body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $html .= nl2br(e(substr($body, $offset, $match[0][1] - $offset)));
            $html .= '<img src="' . e($match[2][0]) . '" alt="' . e($match[1][0])
                . '" width="544" style="display:block;width:100%;max-width:100%;height:auto;margin:12px 0;border:0;">';
            $offset = $match[0][1] + strlen($match[0][0]);
        }
        return $html . nl2br(e(substr($body, $offset)));
    }

    public static function text(string $body): string
    {
        return preg_replace_callback('~' . self::IMAGE_PATTERN . '~u', fn ($match) => $match[1] . ' (' . $match[2] . ')', $body);
    }
}
