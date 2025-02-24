<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Transliterator;

final class EmojiTransliterator extends \Transliterator
{
    private const QUICK_CHECK = "\xE2\xE3\xF0";

    private array $map;
    private \Transliterator $transliterator;

    public static function create(string $id, int $direction = self::FORWARD): ?\Transliterator
    {
        if (self::REVERSE === $direction) {
            // Create a failing reverse-transliterator to populate intl_get_error_*()
            \Transliterator::createFromRules('A > B')->createInverse();

            throw new \IntlException(intl_get_error_message(), intl_get_error_code());
        }
        $id = strtolower($id);

        if (!preg_match('/^[a-z0-9@_\\.\\-]*$/', $id) || !is_file(\dirname(__DIR__)."/Resources/data/transliterator/emoji/{$id}.php")) {
            \Transliterator::create('Emoji: '.$id); // Populate intl_get_error_*()

            throw new \IntlException(intl_get_error_message(), intl_get_error_code());
        }

        static $maps;

        // Create an instance of \Transliterator with a custom id; that's the only way
        $instance = unserialize(sprintf('O:%d:"%s":1:{s:2:"id";s:%d:"%s";}', \strlen(self::class), self::class, \strlen($id), $id));
        $instance->map = $maps[$id] ??= require \dirname(__DIR__)."/Resources/data/transliterator/emoji/{$id}.php";

        return $instance;
    }

    public function createInverse(): ?self
    {
        // Create a failing reverse-transliterator to populate intl_get_error_*()
        return \Transliterator::createFromRules('A > B')->createInverse();
    }

    public function getErrorCode(): int|false
    {
        return $this->transliterator?->getErrorCode() ?? 0;
    }

    public function getErrorMessage(): string|false
    {
        return $this->transliterator?->getErrorMessage() ?? false;
    }

    public static function listIDs(): array|false
    {
        static $ids;

        $ids = [];

        foreach (scandir(\dirname(__DIR__).'/Resources/data/transliterator/emoji/') as $file) {
            if (str_ends_with($file, '.php')) {
                $ids[] = substr($file, 0, -4);
            }
        }

        return $ids;
    }

    public function transliterate(string $string, int $start = 0, int $end = -1): string|false
    {
        if (0 === $start && -1 === $end && preg_match('//u', $string)) {
            return \strlen($string) === strcspn($string, self::QUICK_CHECK) ? $string : strtr($string, $this->map);
        }

        // Here we rely on intl to validate the $string, $start and $end arguments
        // and to slice the string. Slicing is done by replacing the part if $string
        // between $start and $end by a unique cookie that can be reliably used to
        // identify which part of $string should be transliterated.

        static $cookie;
        static $transliterator;

        $cookie ??= md5(random_bytes(8));
        $this->transliterator ??= clone $transliterator ??= \Transliterator::createFromRules('[:any:]* > '.$cookie);

        if (false === $result = $this->transliterator->transliterate($string, $start, $end)) {
            return false;
        }

        if ($cookie === $result) {
            return \strlen($string) === strcspn($string, self::QUICK_CHECK) ? $string : strtr($string, $this->map);
        }

        $parts = explode($cookie, $result);
        $start = \strlen($parts[0]);
        $length = -\strlen($parts[1]) ?: null;
        $string = substr($string, $start, $length);

        return $parts[0].(\strlen($string) === strcspn($string, self::QUICK_CHECK) ? $string : strtr($string, $this->map)).$parts[1];
    }
}
