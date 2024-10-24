<?php

namespace local_sigaaintegration;

class string_helper
{
    /**
     * https://github.com/curtinaz/php-capitalize-pt-br/
     *
     * @copyright Copyright 2021 Cristian Curtinaz
     *
     * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
     * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
     * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
     * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
     *
     * The above copyright notice and this permission notice shall be included in all copies
     * or substantial portions of the Software.
     *
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
     * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
     * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
     * OTHER DEALINGS IN THE SOFTWARE.
     */
    public static function capitalize($phrase, $encoding = 'UTF-8')
    {
        $lowercases = [
            'à',
            'a',
            'com',
            'da',
            'das',
            'de',
            'do',
            'dos',
            'e',
            'em',
            'na',
            'nas',
            'no',
            'nos',
            'o',
            'por',
            'sem',
            'para'
        ];

        $uppercases = [
            'cnpj',
            'cpf',
            'ltda',
            'qp',
            'tv',
            'mei',
            'me',
            'ei',
            'epp',
            'eireli',
            'sa',
            'ti',
            'i',
            'ii',
            'iii',
            'iv',
            'v',
            'vi',
            'vii',
            'viii',
            'ix',
            'x'
        ];

        $eachWord = explode(' ', mb_strtolower($phrase, $encoding));

        for ($i = 0; $i < count($eachWord); $i++) {
            $foundMatch[$i] = 0;
            for ($j = 0; $j < count($lowercases); $j++) { // verifica se a palavra deveria ser lowercase.
                if ($eachWord[$i] == $lowercases[$j]) {
                    $eachWord[$i] = mb_strtolower($eachWord[$i], $encoding);
                    $foundMatch[$i] = 1;
                }
            }
            if ($foundMatch[$i] == 0) {
                for ($j = 0; $j < count($uppercases); $j++) { // verifica se a palavra deveria ser uppercase.
                    if ($eachWord[$i] == $uppercases[$j]) {
                        $eachWord[$i] = mb_strtoupper($eachWord[$i], $encoding);
                        $foundMatch[$i] = 1;
                    }
                }
            }
            if ($foundMatch[$i] == 0) {
                $eachWord[$i] = mb_convert_case($eachWord[$i], MB_CASE_TITLE, $encoding);
            }
            if ($i == 0) {
                $phrase = $eachWord[$i];
            } else {
                $phrase = $phrase . ' ' . $eachWord[$i];
            }
        }
        return $phrase;
    }
}
