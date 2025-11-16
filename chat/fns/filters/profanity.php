<?php

namespace Snipe\BanBuilder;

class CensorWords
{
    public array $badwords = [];
    private string $replacer = '*';
    private array $censorChecks = [];
    private array $whiteList = [];
    private string $whiteListPlaceHolder = ' {whiteList[i]} ';
    private bool $whitelistLoaded = false;
    private ?array $leet_replace = null;

    public function __construct() {
        $this->badwords = [];
        $this->replacer = '*';
        $whitelist = [];
        try {
            $this->setDictionary('blacklist');
            include('fns/filters/whitelist.php');
            $this->addWhiteList($whitelist);
            $this->whitelistLoaded = true;
        } catch (\Exception $e) {}
    }

    public function setDictionary($dictionary): void {
        try {
            $this->badwords = $this->readBadWords($dictionary);
        } catch (\RuntimeException $e) {}
    }

    public function addDictionary($dictionary): void {
        try {
            $this->badwords = array_merge($this->badwords, $this->readBadWords($dictionary));
        } catch (\RuntimeException $e) {}
    }

    public function addFromArray(array $words): void {
        $this->badwords = array_unique(array_merge($this->badwords, $words));
    }

    private function readBadWords($dictionary): array {
        $badwords = [];
        $baseDictPath = __DIR__ . DIRECTORY_SEPARATOR;

        if (is_array($dictionary)) {
            foreach ($dictionary as $dictionary_file) {
                $badwords = array_merge($badwords, $this->readBadWords($dictionary_file));
            }
        }

        if (is_string($dictionary)) {
            if (file_exists($baseDictPath . $dictionary . '.php')) {
                include $baseDictPath . $dictionary . '.php';
            } elseif (file_exists($dictionary)) {
                include $dictionary;
            }
        }

        return array_unique($badwords);
    }

    public function addWhiteList(array $list): void {
        foreach ($list as $value) {
            if (is_string($value) && !empty($value)) {
                $this->whiteList[] = ['word' => $value];
            }
        }
    }

    private function convert_unicode_callback(array $matches): string {
        return $matches[0];
    }

    public function convert_unicode_text(string $text): string {
        return preg_replace_callback('/./us', [$this, 'convert_unicode_callback'], $text);
    }

    private function replaceWhiteListed(string $string, bool $reverse = false): string {
        foreach ($this->whiteList as $key => $list) {
            if ($reverse && !empty($this->whiteList[$key]['placeHolder'])) {
                $placeHolder = $this->whiteList[$key]['placeHolder'];
                $string = str_replace($placeHolder, $list['word'], $string);
            } else {
                $placeHolder = str_replace('[i]', $key, $this->whiteListPlaceHolder);
                $this->whiteList[$key]['placeHolder'] = $placeHolder;
                $string = str_replace($list['word'], $placeHolder, $string);
            }
        }
        return $string;
    }

    public function setReplaceChar(string $replacer): void {
        $this->replacer = $replacer;
    }

    public function randCensor(string $chars, int $len): string {
        $repeatCount = intdiv($len, strlen($chars));
        return str_shuffle(str_repeat($chars, $repeatCount) . substr($chars, 0, $len % strlen($chars)));
    }

    private function compileLeetDictionary(): void {
        if (is_null($this->leet_replace)) {
            $this->leet_replace = [
                'a' => '(a|a\.|a\-|@|Á|á|À|Â|à|Â|â|Ä|ä|Ã|ã|Å|å|α|Δ|Λ|λ)',
                'b' => '(b|b\.|b\-|8|\|3|ß|Β|β)',
                'c' => '(c|c\.|c\-|Ç|ç|¢|€|<|\(|{|©)',
                'd' => '(d|d\.|d\-|&part;|\|\)|Þ|þ|Ð|ð)',
                'e' => '(e|e\.|e\-|3|€|È|è|É|é|Ê|ê|∑)',
                'f' => '(f|f\.|f\-|ƒ)',
                'g' => '(g|g\.|g\-|6|9)',
                'h' => '(h|h\.|h\-|Η)',
                'i' => '(i|i\.|i\-|!|\||\]\[|]|1|∫|Ì|Í|Î|Ï|ì|í|î|ï)',
                'j' => '(j|j\.|j\-)',
                'k' => '(k|k\.|k\-|Κ|κ)',
                'l' => '(l|1\.|l\-|!|\||\]\[|]|£|∫|Ì|Í|Î|Ï)',
                'm' => '(m|m\.|m\-)',
                'n' => '(n|n\.|n\-|η|Ν|Π)',
                'o' => '(o|o\.|o\-|0|Ο|ο|Φ|¤|°|ø)',
                'p' => '(p|p\.|p\-|ρ|Ρ|¶|þ)',
                'q' => '(q|q\.|q\-)',
                'r' => '(r|r\.|r\-|®)',
                's' => '(s|s\.|s\-|5|\$|§)',
                't' => '(t|t\.|t\-|Τ|τ|7)',
                'u' => '(u|u\.|u\-|υ|µ)',
                'v' => '(v|v\.|v\-|υ|ν)',
                'w' => '(w|w\.|w\-|ω|ψ|Ψ)',
                'x' => '(x|x\.|x\-|Χ|χ)',
                'y' => '(y|y\.|y\-|¥|γ|ÿ|ý|Ÿ|Ý)',
                'z' => '(z|z\.|z\-|Ζ)',
            ];
        }
    }

    private function generateCensorChecks(bool $fullWords = false): void {
        $this->compileLeetDictionary();
        $badwords = $this->badwords;
        $censorChecks = [];

        $separatorPattern = '[\s\.\-_]*';

        foreach ($badwords as $badword) {

            if (!$fullWords) {

                $chars = preg_split('//u', $badword, -1, PREG_SPLIT_NO_EMPTY);
                $patternParts = [];

                foreach ($chars as $char) {
                    $lowerChar = mb_strtolower($char, 'UTF-8');
                    if (isset($this->leet_replace[$lowerChar])) {
                        $patternParts[] = $this->leet_replace[$lowerChar];
                    } else {
                        $patternParts[] = preg_quote($char, '/');
                    }
                }

                $pattern = implode($separatorPattern, $patternParts);

            } else {

                $pattern = preg_quote($badword, '/');

                $pattern = preg_replace('/(.)/u', '$1' . $separatorPattern, $pattern);

                foreach ($this->leet_replace as $char => $leet) {
                    $pattern = preg_replace('/' . preg_quote($char, '/') . '/i', $leet, $pattern);
                }
            }

            $censorChecks[] = $fullWords
            ? '/\b' . $pattern . '\b/ui'
            : '/' . $pattern . '/ui';
        }

        $this->censorChecks = $censorChecks;
    }

    public function censorString(string $string, bool $fullWords = false): array {
        try {
            if (empty($this->censorChecks)) {
                $this->generateCensorChecks($fullWords);
            }

            $match = [];
            $counter = 0;

            $original = $this->replaceWhiteListed($string);

            $cleanString = preg_replace_callback(
                $this->censorChecks,
                function ($matches) use (&$counter, &$match) {
                    $match[$counter++] = $matches[0];
                    return str_repeat($this->replacer, strlen($matches[0]));
                },
                $original
            );

            $cleanString = $this->replaceWhiteListed($cleanString, true);

            return ['orig' => $string, 'clean' => $cleanString, 'matched' => $match];

        } catch (\Exception $e) {
            return ['clean' => $string, 'matched' => []];
        }
    }

}