<?php

declare(strict_types=1);

namespace App\Service\Core;

/**
 * KeyGeneratorService.
 *
 * To generate token, password ...
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class KeyGeneratorService
{
    private string $uppers = '';
    private string $lowers = '';
    private string $specialCharacters = '';
    private string $numbers = '';

    /**
     * Generate.
     */
    public function generate(int $uppers = 0, int $lowers = 0, int $specialCharacters = 0, int $numbers = 0): string
    {
        if ($uppers > 0) {
            $this->uppers($uppers);
        }

        if ($lowers > 0) {
            $this->lowers($lowers);
        }

        if ($specialCharacters > 0) {
            $this->specialCharacters($specialCharacters);
        }

        if ($numbers > 0) {
            $this->numbers($numbers);
        }

        return str_shuffle($this->uppers.$this->lowers.$this->specialCharacters.$this->numbers);
    }

    /**
     * Set uppers.
     */
    private function uppers(int $length): void
    {
        $uppers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->uppers = $this->randChars($length, $uppers);
    }

    /**
     * Set lowers.
     */
    private function lowers(int $length): void
    {
        $lowers = 'abcdefghijklmnopkrstuvwxyz';
        $this->lowers = $this->randChars($length, $lowers);
    }

    /**
     * Set special characters.
     */
    private function specialCharacters(int $length): void
    {
        $specialCharacters = '&~@!$?#*(){}_';
        $this->specialCharacters = $this->randChars($length, $specialCharacters);
    }

    /**
     * Set numbers.
     */
    private function numbers(int $length): void
    {
        $numbers = '1234567890';
        $this->numbers = $this->randChars($length, $numbers);
    }

    /**
     * Generate rand string for password.
     */
    private function randChars(int $length, string $string): bool|string
    {
        $string = str_shuffle($string);

        return substr($string, 0, $length);
    }
}
